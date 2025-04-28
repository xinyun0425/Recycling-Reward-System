
<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
    $servername = "localhost"; 
    $username = "root";  
    $password = "";  
    $dbname = "cp_assignment";  

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
    }
    $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $selectedMonth = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : null;

    // First Query: First Time Users (filtered)
    $firsttimeUsers = [];

    $query1 = "
        SELECT 
            user_id, 
            email,
            username, 
            created_at
        FROM user 
        WHERE status = 'Verified'
        AND YEAR(created_at) = $selectedYear
    ";

    if ($selectedMonth) {
        $query1 .= " AND MONTH(created_at) = $selectedMonth";
    }

    $query1 .= " ORDER BY created_at DESC";

    $result1 = $conn->query($query1);
    if ($result1 && $result1->num_rows > 0) {
        while ($row = $result1->fetch_assoc()) {
            $firsttimeUsers[] = $row;
        }
    }

    // Second Query: Monthly registrations chart data (filtered by year)
    $monthlyUserRegistrations = [];

    $query2 = "
        SELECT 
            MONTH(created_at) AS month, 
            COUNT(*) AS user_count 
        FROM user 
        WHERE status = 'Verified'
        AND YEAR(created_at) = $selectedYear
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ";

    $result2 = $conn->query($query2);
    if ($result2 && $result2->num_rows > 0) {
        while ($row = $result2->fetch_assoc()) {
            $monthlyUserRegistrations[] = $row;
        }
    }

    ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>First Time Users Report - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @keyframes floatIn {
        0% {
            transform: translateY(-50px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
    }
    
    .main-content {
        overflow-x: hidden;
        padding:20px;
        margin-left:300px;
        width:calc(100%-270px);
        overflow-y:auto;
    }
    .header {
        display: flex;
        flex-direction: column;
        align-items: left;
        justify-content: center;
        margin-left: 73px;
        animation: floatIn 0.8s ease-out;
    }
    .header i {
        font-size:1.0em;
        margin-right:20px;
        color:rgb(134, 134, 134);
        /* color: green; */
        cursor: pointer;
    }

    .sidebar {
        width: 250px;
        height: 100vh; 
        background: #f8f9fa;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        position: fixed;
        overflow-y: auto;
        z-index: 100;
        display: flex;
        flex-direction: column;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin-left: 13px;
        width: 220px;
    }


    .menu li {
        border-radius: 5px;
    }

    .menu li a {
        text-decoration: none;
        color: black;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 10px;
        border-radius: 10px;
    }

    .menu li i{
        color:rgb(134, 134, 134);
        width: 5px;
        padding-right:18px;
    }

    .menu li.active{
        background-color: #E4EBE6;
        border-radius: 10px;
        color:rgb(11, 91, 19);
    }

    .menu a:hover,
    .menu a.active{
        background:#E4EBE6;
        color:rgb(11, 91, 19);
    }

    .menu li.active i,
    .menu li:hover i{
        color:green;
        background-color: #E4EBE6;
    }

    .menu li.active a,
    .menu li:hover a{
        color:rgb(11, 91, 19);
        background-color: #E4EBE6;
    }
    .logout {
        background-color: #fff5f5;
        margin-top: 30px;
        color: #c6433a;
        font-size: 15px;
        border: 2px solid #e2847e;
        box-shadow: none;
        border-radius: 25px;
        padding: 10px 50px;
        width: 100%;
    }
    .logout:hover {
        background-color: rgba(249,226,226,0.91);
        transition:all 0.5s ease;
    }
    .logout i{
        padding-right:10px;
    }
    table {
        border-collapse: collapse;
        table-layout: fixed;
        border: 1px solid #cbcbcb;
        width: 90%;
        font-size: 16px;
        margin-left: 60px;
        background-color: rgba(255, 255, 255, 0.5);
        table-layout: fixed;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        word-wrap: break-word;
    }

    th {
        background-color: #E0E1E1;
    }

    tr:hover {
        background-color: rgba(184, 194, 172, 0.05);
        cursor: pointer;
    }


    @media screen and (max-width: 768px) {
        th, td {
            font-size: 14px;
            padding: 10px;
        }
    }

    canvas {
        max-width: 100%;
        height: 300px;
        display: block;
        margin: 0 auto; 
    }
    #registrationChart{
        width: 100%; 
        max-width: 1000px; 
        height: auto; 
        max-height: 400px; 
    }
    .yearFilterDropdown {
        position: relative;
        display: flex;
        justify-content: flex-end;
        width: 100%;
        padding-right: 3.8vw;
        margin:2vw 0vw 2vw 0vw;
        box-sizing: border-box;
    }

    .yearFilterDropdown select {
        padding: 10px 27px 10px 16px;
        width: 180px;
        border: 2px solid #2d6a4f;
        border-radius: 8px;
        background: #ffffff;
        color: #2d6a4f;
        font-size: 16px;
        font-weight: bold;
        appearance: none;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 0;
    }

    .dropdown-icon {
        position: absolute;
        right: calc(3.8vw + 16px);
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        color: #2d6a4f;
        pointer-events: none;
    }


    .yearFilterDropdown select:hover {
        border-color: #1b4332;
    }

    .yearFilterDropdown select:focus {
        outline: none;
        border-color: #1b4332;
        box-shadow: 0px 0px 6px rgba(27, 67, 50, 0.4);
    }

    .generate-btn {
        padding: 16px 32px;
        border-radius: 10px;
        font-size: 1.05rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: #3BB143;
        color: white;
        border: none;
        margin: 2vw 30vw 2vw 31vw;
    }

    hr{
        border: none;
        height: 1.5px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
        }
</style>
</head>
<body>
<div class="sidebar">
        <div>
        <a href="Admin-Dashboard.php">
            <img src="User-Logo.png" 
                style="width: 200px; margin-bottom: 25px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;">
        </a>
        </div>
        <ul class="menu">
            <li><a href="Admin-Dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="Admin-Notification.php"><i class="fa-solid fa-bell"></i>Notifications</a></li>
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li ><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
            <li><a href="Admin-Review.php"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li class="active"><a href="Admin-Report.php"><i class="fa-solid fa-chart-column"></i>Report</a></li>
            <li><a href="Admin-FAQ.php"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
            <form action="Admin-Logout.php" method="post" style="display:inline;">
                <button type="submit" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </ul>
    </div>
    <div class ="main-content">
    <div class="header">
        <h2>
            <a href='Admin-Report.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            First Time Users Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                    <select id="yearFilter">
                    <?php    
                        $query= "SELECT DISTINCT YEAR(created_at) AS year FROM user ORDER BY year DESC";
                        $result = $conn->query($query);
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $isSelected = ($row['year'] == $selectedYear) ? "selected" : "";
                                echo "<option value='" . $row['year'] . "' $isSelected>" . $row['year'] . "</option>";
                            }
                        } else {
                            echo "<option disabled>No data</option>";
                        }
                        ?>
                    </select>
                    <i class="fa-solid fa-caret-down dropdown-icon"></i>
                </div>
            <canvas id="registrationChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
            <div class="yearFilterDropdown">
                <select id="monthFilter">
                    <option value="">All</option>
                    <option value="01">January</option>
                    <option value="02">February</option>
                    <option value="03">March</option>
                    <option value="04">April</option>
                    <option value="05">May</option>
                    <option value="06">June</option>
                    <option value="07">July</option>
                    <option value="08">August</option>
                    <option value="09">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
                <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%"></th>
                        <th style="width:34.5%">Username</th>
                        <th style="width:34.5%">Email</th>
                        <th style="width:25%">Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $bil = 1;
                    foreach ($firsttimeUsers as $row) : 
                ?>
                    <tr>
                        <td style="width:10%"><?php echo $bil++; ?></td>
                        <td style="width:32.5%"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td style="width:32.5%"><?php echo htmlspecialchars($row['email']); ?></td>
                        <td style="width:25%"><?php echo htmlspecialchars($row['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <script>
                    const registrationLabels = <?php echo json_encode(array_map(function($row) {
                        return date("F", mktime(0, 0, 0, $row['month'], 10));
                    }, $monthlyUserRegistrations)); ?>;
                    const registrationData = <?php echo json_encode(array_column($monthlyUserRegistrations, 'user_count')); ?>;
                    const allUsers = <?php echo json_encode($firsttimeUsers); ?>;
                </script>
            </table>
            <form id="pdfForm" action="Admin-Report-Users-FirstTimeUsers-PDF.php" method="post" target="_blank">
                <input type="hidden" id="hiddenMonthFilter" name="monthFilter" value="">
                <input type="hidden" id="hiddenYearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctxReg = document.getElementById('registrationChart').getContext('2d');
            const baseColor = 'rgba(14, 97, 43, 0.6)';
            const highlightColor = 'rgba(14, 97, 43, 0.6)';

            const monthLabels = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'
            ];

            const completeRegistrationData = new Array(12).fill(0);
            const monthMap = {
                "January": "Jan", "February": "Feb", "March": "Mar", "April": "Apr",
                "May": "May", "June": "Jun", "July": "Jul", "August": "Aug",
                "September": "Sept", "October": "Oct", "November": "Nov", "December": "Dec"
            };

            registrationLabels.forEach((label, i) => {
                const shortLabel = monthMap[label];
                const index = monthLabels.findIndex(m => m === shortLabel);
                if (index !== -1) {
                    completeRegistrationData[index] = registrationData[i];
                }
            });

            const registrationChart = new Chart(ctxReg, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Users Registered',
                        data: completeRegistrationData,
                        backgroundColor: monthLabels.map(() => baseColor)
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            onClick: () => {},
                            labels: {
                                generateLabels: function () {
                                    return [{
                                        text: 'Users Registered',
                                        fillStyle: baseColor,
                                        strokeStyle: baseColor,
                                        lineWidth: 1,
                                        hidden: false,
                                        index: 0
                                    }];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'hoverHighlight',
                    afterEvent(chart, args) {
                        const event = args.event;
                        const dataset = chart.data.datasets[0];
                        const labels = chart.data.labels;
                        const selectedMonth = document.getElementById('monthFilter').value;
                        const selectedIndex = selectedMonth ? parseInt(selectedMonth, 10) - 1 : -1;

                        if (!chart._lastHoveredIndex && chart._lastHoveredIndex !== 0) {
                            chart._lastHoveredIndex = null;
                        }

                        if (event.type === 'mousemove') {
                            const points = chart.getElementsAtEventForMode(event, 'index', { intersect: false }, false);
                            const activeIndex = points.length ? points[0].index : null;

                            if (activeIndex !== chart._lastHoveredIndex) {
                                chart._lastHoveredIndex = activeIndex;

                                dataset.backgroundColor = labels.map((_, i) => {
                                    if (activeIndex === i) return 'rgba(14, 97, 43, 0.6)';
                                    if (selectedIndex === i) return 'rgba(14, 97, 43, 0.6)';
                                    return 'rgba(181, 222, 173, 0.46)';
                                });

                                chart.update('none');
                            }
                        }

                        if (event.type === 'mouseout') {
                            chart._lastHoveredIndex = null;
                            dataset.backgroundColor = labels.map((_, i) =>
                                selectedIndex === i
                                    ? 'rgba(14, 97, 43, 0.6)'
                                    : selectedMonth
                                        ? 'rgba(181, 222, 173, 0.46)'
                                        : baseColor
                            );
                            chart.update('none');
                        }
                    }
                }]
            });

            const yearFilter = document.getElementById('yearFilter');
            const monthFilter = document.getElementById('monthFilter');
            const tbody = document.querySelector('table tbody');

            function filterUsers() {
                const selectedYear = yearFilter.value;
                const selectedMonth = monthFilter.value;

                const filtered = allUsers.filter(user => {
                    const date = new Date(user.created_at);
                    const userYear = date.getFullYear().toString();
                    const userMonth = ('0' + (date.getMonth() + 1)).slice(-2);

                    const yearMatch = userYear === selectedYear;
                    const monthMatch = selectedMonth === "" || userMonth === selectedMonth;

                    return yearMatch && monthMatch;
                });

                if (selectedMonth !== "") {
                    const monthIndex = parseInt(selectedMonth, 10) - 1;
                    registrationChart.data.datasets[0].backgroundColor = monthLabels.map((_, idx) =>
                        idx === monthIndex ? highlightColor : 'rgba(181, 222, 173, 0.46)'
                    );
                } else {
                    registrationChart.data.datasets[0].backgroundColor = monthLabels.map(() => baseColor);
                }

                registrationChart.update();
                renderTable(filtered);
            }

            function renderTable(users) {
                tbody.innerHTML = "";
                if (users.length === 0) {
                    tbody.innerHTML = "<tr><td colspan='4' style='text-align: center; font-style: italic;'>No results found.</td></tr>";
                    return;
                }

                users.forEach((user, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${user.created_at}</td>
                        </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
            }

            yearFilter.addEventListener('change', filterUsers);
            monthFilter.addEventListener('change', filterUsers);

            filterUsers();
            const pdfForm = document.getElementById('pdfForm');
            if (pdfForm) {
                pdfForm.addEventListener('submit', function () {
                    document.getElementById('hiddenMonthFilter').value = monthFilter.value;
                    document.getElementById('hiddenYearFilter').value = yearFilter.value;
                });
            }
        });

    </script>
</body>

</html>