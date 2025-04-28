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

    $selectedYear = isset($_POST['year']) ? $_POST['year'] : date("Y");
    $selectedYear = (int) $selectedYear;
    
    $query3 = "
        SELECT 
            DATE(ts.date) AS pickup_date,
            d.driver_name, 
            COUNT(pr.pickup_request_id) AS total_pickups
        FROM pickup_request pr
        JOIN driver d ON pr.driver_id = d.driver_id
        JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id
        WHERE YEAR(ts.date) = $selectedYear 
        AND pr.status = 'Completed' 
        AND pr.driver_id IS NOT NULL
        GROUP BY pickup_date, d.driver_name
        ORDER BY pickup_date ASC, total_pickups ASC

    ";
    

    $result3 = $conn->query($query3);

    $driverPickupCounts = [];

    while ($row = $result3->fetch_assoc()) {
        $driverPickupCounts[] = $row;
    }
    $queryChart = "
        SELECT d.driver_name, COUNT(pr.pickup_request_id) AS total_pickups 
        FROM pickup_request pr
        JOIN driver d ON pr.driver_id = d.driver_id
        JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id
        WHERE YEAR(ts.date) = $selectedYear 
        AND pr.status = 'Completed' 
        AND pr.driver_id IS NOT NULL
        GROUP BY d.driver_name
        ORDER BY total_pickups ASC

    ";

$resultChart = $conn->query($queryChart);

$drivers = [];
$pickups = [];

while ($row = $resultChart->fetch_assoc()) {
    $drivers[] = $row['driver_name'];
    $pickups[] = $row['total_pickups'];
}

    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Activity Report - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @media screen and (max-width: 768px) {
        .main-content {
            margin-left: 0;
            width: 100%;
        }
        th, td {
            font-size: 14px;
            padding: 10px;
        }
    }
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
        overflow-y: auto;
        padding: 20px;
        margin-left: 300px;
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

    canvas {
        max-width: 100%;
        height: 300px;
        display: block;
        margin: 0 auto; 
    }
    #driverPickupChart {
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
            Driver Activity Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
            <form method="post" id="yearFilterForm">
                <select name="year" id="yearFilter" onchange="document.getElementById('yearFilterForm').submit();">
                    <?php
                    $query = "SELECT DISTINCT YEAR(ts.date) AS year 
                            FROM pickup_request pr 
                            JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id 
                            ORDER BY year DESC";

                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $year = $row['year'];
                            $isSelected = ($year == $selectedYear) ? "selected" : "";
                            echo "<option value='$year' $isSelected>$year</option>";
                        }
                    } else {
                        echo "<option disabled>No data</option>";
                    }
                    ?>
                </select>
                <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </form>
            </div>
            <canvas id="driverPickupChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
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
                        <th>Pickup Date</th>
                        <th>Driver Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($driverPickupCounts as $row) : 
                        $month = date('m', strtotime($row['pickup_date']));
                        $year = date('Y', strtotime($row['pickup_date']));
                    ?>
                        <tr class="month-<?php echo $month; ?> year-<?php echo $year; ?>">
                            <td style="width:20%"><?php echo htmlspecialchars($row['pickup_date']); ?></td>
                            <td style="width:40%"><?php echo htmlspecialchars($row['driver_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="2" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>

            <form action="Admin-Report-Pickup-DriverActivity-PDF.php" method="post" target="_blank">
                <input type="hidden" id="monthFilter" name="monthFilter" value="">
                <input type="hidden" id="yearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('driverPickupChart').getContext('2d');

        // Ensure allDrivers is always a proper array of strings
        const allDrivers = <?php 
            echo json_encode(
                array_values(
                    array_filter(
                        array_unique(
                            array_map('strval', array_column($driverPickupCounts ?? [], 'driver_name'))
                        )
                    )
                )
            ); 
        ?>;

        let lastHoveredIndex = null;

        const pickupChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Pickup Requests Accepted',
                    data: [],
                    backgroundColor: [],
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: value => Number.isInteger(value) ? value : null
                        }
                    }
                },
                plugins: {
                    legend: {
                        onClick: null,
                        labels: {
                            generateLabels(chart) {
                                return [{
                                    text: 'Pickup Requests Accepted',
                                    fillStyle: 'rgba(14, 97, 43, 0.6)',
                                    strokeStyle: 'rgba(14, 97, 43, 0.6)',
                                    lineWidth: 1,
                                    hidden: false,
                                    index: 0
                                }];
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'hoverHighlight',
                beforeEvent(chart, args) {
                    const event = args.event;
                    const dataset = chart.data.datasets[0];

                    if (event.type === 'mousemove') {
                        const points = chart.getElementsAtEventForMode(event.native, 'index', { intersect: false }, false);
                        const activeIndex = points.length ? points[0].index : null;

                        if (activeIndex !== lastHoveredIndex) {
                            lastHoveredIndex = activeIndex;
                            dataset.backgroundColor = dataset.data.map((_, i) =>
                                i === activeIndex ? 'rgba(14, 97, 43, 0.6)' : 'rgba(181, 222, 173, 0.46)'
                            );
                            chart.update('none');
                        }
                    }

                    if (event.type === 'mouseout') {
                        lastHoveredIndex = null;
                        dataset.backgroundColor = dataset.data.map(() => 'rgba(14, 97, 43, 0.6)');
                        chart.update('none');
                    }
                }
            }]
        });

        function updateChartFromTable() {
            const selectedMonth = document.getElementById("monthFilter").value;
            const selectedYear = document.getElementById("yearFilter")?.value;
            const rows = document.querySelectorAll("table tbody tr");
            const noResultsRow = document.getElementById("noResultsRow");

            const driverCounts = {};
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.id === "noResultsRow") return;

                const matchesMonth = selectedMonth === "" || row.classList.contains("month-" + selectedMonth);
                const matchesYear = !selectedYear || row.classList.contains("year-" + selectedYear);

                const show = matchesMonth && matchesYear;
                row.style.display = show ? "" : "none";

                if (show) {
                    visibleCount++;
                    const driverName = row.cells[1].textContent.trim();
                    driverCounts[driverName] = (driverCounts[driverName] || 0) + 1;
                }
            });

            noResultsRow.style.display = visibleCount === 0 ? "" : "none";

            document.querySelector("input[name='monthFilter']").value = selectedMonth;
            document.querySelector("input[name='yearFilter']").value = selectedYear;

            pickupChart.data.labels = allDrivers.map(name => name.trim());
            pickupChart.data.datasets[0].data = allDrivers.map(driver => driverCounts[driver] || 0);
            pickupChart.data.datasets[0].backgroundColor = allDrivers.map(driver =>
                driverCounts[driver] ? 'rgba(14, 97, 43, 0.6)' : 'rgba(0, 0, 0, 0)'
            );

            pickupChart.update();
        }

        document.getElementById("monthFilter").addEventListener("change", updateChartFromTable);
        document.getElementById("yearFilter")?.addEventListener("change", updateChartFromTable);

        updateChartFromTable();
    });
</script>


</body>

</html>