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

    // First query: Pickup request details
    $pickupRequests = [];
    $query1 = "
        SELECT 
            pr.datetime_submit_form AS pickup_date, 
            u.username,
            d.driver_name,
            i.item_name,
            p.quantity
        FROM pickup_request pr
        JOIN user u ON pr.user_id = u.user_id
        LEFT JOIN driver d ON pr.driver_id = d.driver_id
        JOIN item_pickup p ON pr.pickup_request_id = p.pickup_request_id
        JOIN item i ON i.item_id = p.item_id
        WHERE pr.status = 'Completed'
        ORDER BY pr.datetime_submit_form ASC
    ";

    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $pickupRequests[] = $row;
    }

    // Second query: Items picked for the past 7 days
    $itemsPicked = [];
    $query2 = "
        SELECT 
            DATE(pr.datetime_submit_form) AS pickup_date, 
            SUM(ip.quantity) AS items_picked
        FROM pickup_request pr
        LEFT JOIN item_pickup ip ON pr.pickup_request_id = ip.pickup_request_id
        WHERE pr.status ='Completed'
        GROUP BY pickup_date
        ORDER BY pickup_date ASC
    ";
    $result2 = $conn->query($query2);
    while ($row = $result2->fetch_assoc()) {
        $itemsPicked[] = $row;
    }
    $query3 = "
        SELECT 
            YEAR(datetime_submit_form) AS year, 
            MONTH(datetime_submit_form) AS month, 
            COUNT(DISTINCT pickup_request_id) AS total_pickups 
        FROM pickup_request
        WHERE status = 'Completed'
        GROUP BY year, month
        ORDER BY year DESC, month ASC
    ";


    $result = $conn->query($query3); 

    $data = [];

    while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    }
    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pickup Request Report - Green Coin</title>
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

    /* Media query for smaller screens */
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
    #pickupChart {
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
            Pickup Request Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                <select id="yearFilter">
                    <?php
                    $query = "SELECT DISTINCT YEAR(datetime_submit_form) AS year FROM pickup_request ORDER BY year DESC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['year'] . "'>" . $row['year'] . "</option>";
                        }
                    } else {
                        echo "<option disabled>No data</option>";
                    }
                    ?>
                </select>
                <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </div>
            <canvas id="pickupChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
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
                <i class="fa-solid fa-caret-down dropdown-icon" style="margin-bottom:10px;"></i>
            </div>

            <table style="margin-top:20px;">
                <thead>
                    <tr>
                        <th style="width:15%">Pickup Date</th>
                        <th style="width:25%">Username</th>
                        <th style="width:25%">Driver Name</th>
                        <th style="width:30%">Item Name</th>
                        <th style="width:10%">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pickupRequests as $row) :
                        $month = date('m', strtotime($row['pickup_date']));
                        $year = date('Y', strtotime($row['pickup_date']));
                    ?>
                        <tr class="month-<?php echo $month; ?> year-<?php echo $year; ?>">
                            <td style="width:15%"><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['pickup_date']))); ?></td>
                            <td style="width:25%"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td style="width:22.5%"><?php echo htmlspecialchars($row['driver_name']); ?></td>
                            <td style="width:30%"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td style="width:10%"><?php echo htmlspecialchars($row['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="no-results-row" style="display: none;">
                        <td colspan="5" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>
            <form action="Admin-Report-Pickup-PickupRequest-PDF.php" method="post" target="_blank">
                <input type="hidden" id="monthFilter" name="monthFilter" value="">
                <input type="hidden" id="yearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let pickupData = <?php echo json_encode($data); ?>;

            const monthList = [
                { num: "01", name: "Jan" },
                { num: "02", name: "Feb" },
                { num: "03", name: "Mar" },
                { num: "04", name: "Apr" },
                { num: "05", name: "May" },
                { num: "06", name: "Jun" },
                { num: "07", name: "Jul" },
                { num: "08", name: "Aug" },
                { num: "09", name: "Sep" },
                { num: "10", name: "Oct" },
                { num: "11", name: "Nov" },
                { num: "12", name: "Dec" }
            ];

            const monthNames = Object.fromEntries(monthList.map(m => [m.num, m.name]));
            const allMonthKeys = monthList.map(m => m.num);

            function getCurrentYear() {
                return new Date().getFullYear().toString(); 
            }

            function filterDataByYear(year) {
                if (year === "") year = getCurrentYear();
                return pickupData.filter(item => item.year.toString() === year.toString());
            }

            let highlightedMonth = null;
            let lastHoverIndex = null;

            function getBarColors(labels) {
                if (!highlightedMonth) {
                    return labels.map(() => "rgba(14, 97, 43, 0.6)");
                }

                const highlightLabel = monthNames[highlightedMonth];
                return labels.map(label =>
                    label === highlightLabel
                        ? "rgba(14, 97, 43, 0.6)"
                        : "rgba(181, 222, 173, 0.46)"
                );
            }

            let ctx = document.getElementById("pickupChart").getContext("2d");

            let pickupChart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: monthList.map(m => m.name),
                    datasets: [{
                        label: "Total Pickups per Month",
                        data: [],
                        backgroundColor: Array(12).fill("rgba(14, 97, 43, 0.6)")
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function (value) {
                                    return Number.isInteger(value) ? value : null;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            onClick: () => {},
                            labels: {
                                generateLabels: function () {
                                    return [{
                                        text: "Total Pickups per Month",
                                        fillStyle: "rgba(14, 97, 43, 0.6)",
                                        strokeStyle: "rgba(14, 97, 43, 0.6)",
                                        lineWidth: 1,
                                        hidden: false,
                                        index: 0
                                    }];
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                },
                plugins: [{
                    id: 'customHoverColors',
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

                                dataset.backgroundColor = dataset.data.map((_, i) => {
                                    if (activeIndex === i) return 'rgba(14, 97, 43, 0.6)';
                                    if (selectedIndex === i) return 'rgba(14, 97, 43, 0.6)';
                                    return 'rgba(181, 222, 173, 0.46)';
                                });

                                chart.update('none');
                            }
                        }

                        if (event.type === 'mouseout') {
                            chart._lastHoveredIndex = null;

                            dataset.backgroundColor = dataset.data.map((_, i) =>
                                selectedIndex === i
                                    ? 'rgba(14, 97, 43, 0.6)'
                                    : selectedMonth
                                        ? 'rgba(181, 222, 173, 0.46)'
                                        : 'rgba(14, 97, 43, 0.6)'
                            );

                            chart.update('none');
                        }
                    }
                }]

            });

            function updateChart(year) {
                let filteredData = filterDataByYear(year);
                let dataMap = {};
                filteredData.forEach(item => {
                    let month = item.month.toString().padStart(2, "0");
                    dataMap[month] = item.total_pickups;
                });

                const labels = monthList.map(m => m.name);
                const chartData = monthList.map(m => dataMap[m.num] ?? 0);

                pickupChart.data.labels = labels;
                pickupChart.data.datasets[0].data = chartData;
                pickupChart.data.datasets[0].backgroundColor = getBarColors(labels);
                pickupChart.update('none');
            }

            document.getElementById("yearFilter").addEventListener("change", function () {
                let selectedYear = this.value;
                document.getElementById("monthFilter").value = "";
                highlightedMonth = null;

                updateChart(selectedYear); 
                applyFilters(); 
            });

            document.getElementById("monthFilter").addEventListener("change", function () {
                let selectedMonth = this.value.padStart(2, "0");
                highlightedMonth = selectedMonth;

                pickupChart.data.datasets[0].backgroundColor = getBarColors(pickupChart.data.labels);
                pickupChart.update('none');
                applyFilters();
            });

            function applyFilters() {
                let selectedMonth = document.getElementById("monthFilter").value;
                let selectedYear = document.getElementById("yearFilter").value || getCurrentYear();
                let rows = document.querySelectorAll("tbody tr:not(#no-results-row)");
                let noResultsRow = document.getElementById("no-results-row");
                let visibleCount = 0;

                rows.forEach(row => {
                    let monthClass = [...row.classList].find(cls => cls.startsWith("month-"));
                    let yearClass = [...row.classList].find(cls => cls.startsWith("year-"));

                    let rowMonth = monthClass ? monthClass.replace("month-", "").padStart(2, "0") : "";
                    let rowYear = yearClass ? yearClass.replace("year-", "") : "";

                    let monthMatches = (selectedMonth === "" || rowMonth === selectedMonth);
                    let yearMatches = (rowYear === selectedYear);

                    if (monthMatches && yearMatches) {
                        row.style.display = "";
                        visibleCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                if (noResultsRow) {
                    noResultsRow.style.display = visibleCount === 0 ? "" : "none";
                }

                document.querySelector("input[name='monthFilter']").value = selectedMonth;
                document.querySelector("input[name='yearFilter']").value = selectedYear;
            }

            // Initial load
            updateChart(getCurrentYear());
            applyFilters();
        });

    </script>
</body>

</html>