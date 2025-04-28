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
    $selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

    // First query: Returning Users details
    $query1 = "
        SELECT 
            @rownum := @rownum + 1 AS bil,
            u.username,
            u.email,
            COUNT(*) AS total_services,
            MAX(all_services.service_date) AS latest_service_date
        FROM (
            SELECT 
                p.user_id,
                p.datetime_submit_form AS service_date
            FROM pickup_request p
            WHERE p.status = 'Completed'

            UNION ALL

            SELECT 
                d.user_id,
                d.dropoff_date AS service_date
            FROM dropoff d
            WHERE d.status = 'Complete'
        ) AS all_services
        JOIN user u ON u.user_id = all_services.user_id
        JOIN (SELECT @rownum := 0) r
        GROUP BY u.user_id
        HAVING COUNT(*) > 1
        ORDER BY total_services DESC
    ";

    $ReturningClientsTrend = [];
    $result = $conn->query($query1);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ReturningClientsTrend[] = $row;
        }
    }
    // CHART PHP

    $queryMonthly = "
        SELECT 
            CASE 
                WHEN total_services BETWEEN 2 AND 5 THEN '2-5'
                WHEN total_services BETWEEN 6 AND 10 THEN '6-10'
                WHEN total_services BETWEEN 11 AND 15 THEN '11-15'
                WHEN total_services BETWEEN 16 AND 20 THEN '16-20'
                ELSE '21+'
            END AS service_range,
            COUNT(*) AS total_users
        FROM (
            SELECT 
                user_id,
                COUNT(*) AS total_services
            FROM (
                SELECT user_id FROM pickup_request WHERE status = 'Completed'
                UNION ALL
                SELECT user_id FROM dropoff WHERE status = 'Complete'
            ) AS all_services
            GROUP BY user_id
            HAVING COUNT(*) > 1
        ) AS user_totals
        GROUP BY service_range
        ORDER BY 
            CASE service_range
                WHEN '2-5' THEN 1
                WHEN '6-10' THEN 2
                WHEN '11-15' THEN 3
                WHEN '16-20' THEN 4
                WHEN '21+' THEN 5
                ELSE 6
            END
    ";

    $result = $conn->query($queryMonthly);
    
    $monthlyLabels = [];
    $monthlyData = [];
    
    while ($row = $result->fetch_assoc()) {
        $monthlyLabels[] = $row['service_range'];
        $monthlyData[] = (int)$row['total_users'];
    }
    
    ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returning Clients Report - Green Coin</title>
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
    #ServiceChart{
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
        padding: 10px 16px;
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
            Returning Clients Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                    <select id="yearFilter">
                    <?php  
                        $query = "
                            SELECT DISTINCT YEAR(u.created_at) AS year
                            FROM user u
                            WHERE u.user_id IN (
                                SELECT user_id FROM pickup_request WHERE status = 'Completed'
                                UNION
                                SELECT user_id FROM dropoff WHERE status = 'Complete'
                            )
                            ORDER BY year DESC
                        ";
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
            <canvas id="ServiceChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
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
                        <th style="width:30%">Username</th>
                        <th style="width:30%">Email</th>
                        <th style="width:15%">Total Services</th>
                        <th style="width:20%">Latest Service Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $bil =1; 
                    foreach ($ReturningClientsTrend as $client): ?>
                        <tr>
                            <td><?php echo $bil++; ?></td>
                            <td><?php echo htmlspecialchars($client['username']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo $client['total_services']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($client['latest_service_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="no-results-row" style="display: none;">
                        <td colspan="5" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
                <script>
                    const allUsers = <?php echo json_encode($ReturningClientsTrend); ?>;
                    const monthlyLabels = <?php echo json_encode(array_values($monthlyLabels)); ?>;
                    const monthlyData = <?php echo json_encode(array_values($monthlyData)); ?>;
                </script>
            </table>
            <form id="pdfReportForm" action="Admin-Report-Users-ReturningClients-PDF.php" method="post" target="_blank">
                <input type="hidden" id="hiddenMonthFilter" name="monthFilter" value="">
                <input type="hidden" id="hiddenYearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('ServiceChart').getContext('2d');
            const yearFilter = document.getElementById('yearFilter');
            const monthFilter = document.getElementById('monthFilter');
            const tbody = document.querySelector('table tbody');

            const baseColor = 'rgba(14, 97, 43, 0.6)';
            const highlightColor = 'rgba(14, 97, 43, 0.6)';

            const serviceRanges = [
                { label: '2–5', min: 2, max: 5 },
                { label: '6–10', min: 6, max: 10 },
                { label: '11–15', min: 11, max: 15 },
                { label: '16–20', min: 16, max: 20 },
                { label: '21-30', min: 21, max: 30 },
                { label: '31-40', min: 31, max: 40 },
                { label: '41-50', min: 41, max: 50 },
                { label: '51+', min: 51, max: Infinity }
            ];

            let chartData = Array(serviceRanges.length).fill(0);

            function groupByServiceRange(data) {
                return data.reduce((acc, user) => {
                    const total = parseInt(user.total_services, 10);
                    const index = serviceRanges.findIndex(range => total >= range.min && total <= range.max);
                    if (index !== -1) acc[index]++;
                    return acc;
                }, Array(serviceRanges.length).fill(0));
            }

            function filterUsers() {
                const selectedYear = yearFilter.value;
                const selectedMonth = monthFilter.value;

                const filtered = allUsers.filter(user => {
                    const date = new Date(user.latest_service_date);
                    const userYear = date.getFullYear().toString();
                    const userMonth = ('0' + (date.getMonth() + 1)).slice(-2);

                    const yearMatch = userYear === selectedYear;
                    const monthMatch = selectedMonth === "" || userMonth === selectedMonth;

                    return yearMatch && monthMatch;
                });

                chartData = groupByServiceRange(filtered);
                serviceChart.data.datasets[0].data = chartData;
                serviceChart.update();

                renderTable(filtered);
            }
            function renderTable(users) {
                tbody.innerHTML = "";

                if (users.length === 0) {
                    const noResultsRow = `
                        <tr>
                            <td colspan="5" style="text-align: center; font-style: italic;">No results found.</td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', noResultsRow);
                    return;
                }

                users.forEach((user, index) => {
                    const row = `
                        <tr>
                            <td style="width:10%">${index + 1}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${user.total_services}</td>
                            <td>${new Date(user.latest_service_date).toISOString().split('T')[0]}</td>
                        </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
            }
            const serviceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: serviceRanges.map(r => r.label),
                    datasets: [{
                        label: 'Users per Service Range',
                        data: chartData,
                        backgroundColor: serviceRanges.map(() => baseColor)
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            onClick: () => {},
                            labels: {
                                generateLabels: function () {
                                    return [{
                                        text: 'Service Ranges',
                                        fillStyle: baseColor,
                                        strokeStyle: baseColor,
                                        lineWidth: 1,
                                        hidden: false,
                                        index: 0
                                    }];
                                }
                            }
                        },
                        customHoverColors: {
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                },
                plugins: [{
                    id: 'customHoverColors',
                    afterEvent(chart, args) {
                        const event = args.event;
                        const dataset = chart.data.datasets[0];

                        if (!chart._lastHoveredIndex && chart._lastHoveredIndex !== 0) {
                            chart._lastHoveredIndex = null;
                        }

                        if (event.type === 'mousemove') {
                            const points = chart.getElementsAtEventForMode(event, 'index', { intersect: false }, false);
                            const activeIndex = points.length ? points[0].index : null;

                            if (activeIndex !== chart._lastHoveredIndex) {
                                chart._lastHoveredIndex = activeIndex;

                                dataset.backgroundColor = dataset.data.map((_, i) =>
                                    i === activeIndex ? highlightColor : 'rgba(181, 222, 173, 0.46)'
                                );

                                chart.update('none');
                            }
                        }

                        if (event.type === 'mouseout') {
                            chart._lastHoveredIndex = null;

                            dataset.backgroundColor = dataset.data.map(() => baseColor);
                            chart.update('none');
                        }
                    }
                }]
            });


            yearFilter.addEventListener('change', filterUsers);
            monthFilter.addEventListener('change', filterUsers);

            filterUsers();
        });
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('pdfReportForm');
            const yearFilter = document.getElementById('yearFilter');
            const monthFilter = document.getElementById('monthFilter');
            const hiddenYear = document.getElementById('hiddenYearFilter');
            const hiddenMonth = document.getElementById('hiddenMonthFilter');

            form.addEventListener('submit', function () {
                hiddenYear.value = yearFilter.value;
                hiddenMonth.value = monthFilter.value;
            });
        });
</script>

</body>

</html>
