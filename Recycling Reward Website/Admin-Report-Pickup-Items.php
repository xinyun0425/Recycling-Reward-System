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
    die("Connection failed: " . $conn->connect_error);
}

// First query: Pickup Items details
$pickupItems = [];

$queryTable = "
    SELECT 
        MONTH(pr.datetime_submit_form) AS month_num,
        DATE_FORMAT(pr.datetime_submit_form, '%M') AS month_name,
        YEAR(pr.datetime_submit_form) AS year,
        i.item_name, 
        SUM(p.quantity) AS total_quantity
    FROM item_pickup p
    JOIN item i ON p.item_id = i.item_id
    JOIN pickup_request pr ON p.pickup_request_id = pr.pickup_request_id
    WHERE pr.status = 'Completed'
    GROUP BY month_num, year, i.item_name
    ORDER BY year, month_num, total_quantity DESC
";

$resultTable = $conn->query($queryTable);
while ($row = $resultTable->fetch_assoc()) {
    $pickupItems[] = $row;
}


// Pie Chart Details
$itemData = [];

$query = "
    SELECT 
        i.item_name,
        MONTH(pr.datetime_submit_form) AS month_num,
        YEAR(pr.datetime_submit_form) AS year,
        SUM(p.quantity) AS total_quantity
    FROM item_pickup p
    JOIN item i ON p.item_id = i.item_id
    JOIN pickup_request pr ON p.pickup_request_id = pr.pickup_request_id
    WHERE pr.status = 'Completed'
    GROUP BY i.item_name, MONTH(pr.datetime_submit_form), YEAR(pr.datetime_submit_form)
    ORDER BY total_quantity DESC
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $itemData[] = [
        'name' => $row['item_name'],
        'quantity' => (int)$row['total_quantity'],
        'month_num' => str_pad($row['month_num'], 2, '0', STR_PAD_LEFT),
        'year' => $row['year']
    ];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pickup Items Report - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>



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
    #itemPieChart {
        margin-top: 40px;
        width: 100%; 
        max-width: 1000px; 
        height: auto; 
        max-height: 300px; 
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
    <div class="main-content">
        <div class="header">
            <h2>
            <a href='Admin-Report.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Pickup Items Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                <select id="yearFilter">
                <?php
                    $query = "SELECT DISTINCT YEAR(datetime_submit_form) AS year FROM pickup_request ORDER BY year DESC";
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
            <!-- Chart -->
            <canvas id="itemPieChart" width="600" height="400"></canvas>
            <div class="yearFilterDropdown">
                    <select id="monthFilter">
                        <option value="">All</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                    <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </div>
            <script>
                const selectedYear = <?php echo json_encode($selectedYear); ?>;
                const itemData = <?php echo json_encode($itemData); ?>;
            </script>
            <table style="margin-top:20px;">
                <thead>
                    <tr>
                        <th style="width:10%">Month</th>
                        <th style="width:25%">Item Name</th>
                        <th style="width:10%">Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                <script>
                    const itemData = <?php echo json_encode($itemData); ?>;
                </script>
                    <?php foreach ($pickupItems as $row) : ?>
                        <tr data-month="<?php echo str_pad($row['month_num'], 2, '0', STR_PAD_LEFT); ?>" data-year="<?php echo $row['year']; ?>">
                            <td style="width:15%"><?php echo $row['month_name']; ?></td>
                            <td style="width:20%"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td style="width:10%"><?php echo htmlspecialchars($row['total_quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="3" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>
            <form action="Admin-Report-Pickup-Items-PDF.php" method="post" target="_blank">
                <input type="hidden" id="monthFilter" name="monthFilter" value="">
                <input type="hidden" id="yearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>

    <script>
        let itemPieChart;
        const ctx = document.getElementById('itemPieChart').getContext('2d');
        const pastelColors = [
            '#E68A90',
            '#E6C39A',
            '#8EE6A8',
            '#8DC6E6',
            '#B290E6',
            '#E6ADD6',
            '#8ED6E6'
        ];

        function createChart(labels, data) {
            if (itemPieChart) itemPieChart.destroy();
            Chart.register(ChartDataLabels);

            const isEmptyChart = labels.length === 1 && labels[0] === "No data";
            const assignedColors = isEmptyChart
                ? ['rgba(128, 128, 128, 0.3)']
                : labels.map((_, i) => pastelColors[i % pastelColors.length]);

            itemPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: assignedColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    layout: {
                        padding: {
                            top: 40
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 30,
                                font: {
                                    size: 10
                                }
                            },
                            onClick: () => {}
                        },
                        datalabels: {
                            display: !isEmptyChart,
                            anchor: 'end',
                            align: 'end',
                            offset: 5,
                            color: '#000',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: (value, ctx) => {
                                return `${ctx.chart.data.labels[ctx.dataIndex]}`;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function filterData() {
            const rawMonth = document.getElementById('monthFilter').value;
            const selectedMonth = rawMonth ? rawMonth.padStart(2, "0") : "";

            const selectedYear = document.getElementById('yearFilter').value || new Date().getFullYear().toString();
            const tableRows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;
            tableRows.forEach(row => {
                const rowMonth = row.getAttribute('data-month');
                const rowYear = row.getAttribute('data-year');

                const matchesMonth = !selectedMonth || rowMonth === selectedMonth;
                const matchesYear = rowYear === selectedYear;

                const shouldShow = matchesMonth && matchesYear;
                row.style.display = shouldShow ? '' : 'none';

                if (shouldShow) visibleCount++;
            });

            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
            }

            document.querySelector("input[name='monthFilter']").value = selectedMonth;
            document.querySelector("input[name='yearFilter']").value = selectedYear;

            const filteredItemNames = [];
            const filteredQuantities = [];

            itemData.forEach(item => {
                const matchesMonth = !selectedMonth || item.month_num === selectedMonth;
                const matchesYear = item.year.toString() === selectedYear;

                if (matchesMonth && matchesYear) {
                    filteredItemNames.push(item.name);
                    filteredQuantities.push(item.quantity);
                }
            });

            if (filteredItemNames.length === 0) {
                createChart(["No data"], [1]);
            } else {
                createChart(filteredItemNames, filteredQuantities);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const yearSelect = document.getElementById('yearFilter');
            const monthFilter = document.getElementById('monthFilter');

            monthFilter.addEventListener('change', filterData);
            yearSelect.addEventListener('change', filterData);

            filterData();
        });

    </script>
</body>

</html>