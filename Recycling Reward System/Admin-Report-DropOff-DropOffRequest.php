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

    // First query: Drop Off request details
    $dropOffRequests = [];
    $query1 = "
    SELECT 
        do.dropoff_date, 
        u.username,
        i.item_name,
        d.quantity,
        l.location_name
    FROM dropoff do
    LEFT JOIN user u ON do.user_id = u.user_id
    LEFT JOIN item_dropoff d ON do.dropoff_id = d.dropoff_id
    LEFT JOIN item i ON i.item_id = d.item_id
    LEFT JOIN location l ON do.location_id = l.location_id
    WHERE do.status ='Complete'
    ORDER BY do.dropoff_date ASC
";

    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $dropOffRequests[] = $row;
    }

    $itemsDropOff = [];
    $query2 = "
        SELECT 
            DATE(do.dropoff_date) AS dropoff_date, 
            SUM(id.quantity) AS items_dropoff
        FROM dropoff do
        LEFT JOIN item_dropoff id ON do.dropoff_id = id.dropoff_id
        WHERE do.status ='Complete'
        GROUP BY DATE(do.dropoff_date)
        ORDER BY dropoff_date ASC
    ";
    $result2 = $conn->query($query2);
    while ($row = $result2->fetch_assoc()) {
        $itemsDropOff[] = $row;
    }
    

    $query3 = "
        SELECT 
            YEAR(dropoff_date) AS year, 
            MONTH(dropoff_date) AS month, 
            COUNT(*) AS total_dropoff 
        FROM dropoff
        WHERE status ='Complete'
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
    <title>Drop-off Request Report - Green Coin</title>
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
    #dropOffChart{
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
        <div class ="header">
            <h2>
            <a href='Admin-Report.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Drop-off Requests Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                <select id="yearFilter">
                <?php    
                    $query= "SELECT DISTINCT YEAR(dropoff_date) AS year FROM dropoff WHERE status ='Complete' ORDER BY year DESC";
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
            <canvas id="dropOffChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
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
                        <th style="width:15%">Drop-off Date</th>
                        <th style="width:20%">Username</th>
                        <th style="width:25%">Item Name</th>
                        <th style="width:10%">Quantity</th>
                        <th style="width:20%">Location Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dropOffRequests as $row) :
                        $month = date('m', strtotime($row['dropoff_date']));
                        $year = date('Y', strtotime($row['dropoff_date']));
                    ?>
                        <tr class="month-<?php echo $month; ?> year-<?php echo $year; ?>">
                            <td style="width:15%"><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['dropoff_date']))); ?></td>
                            <td style="width:20%"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td style="width:25%"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td style="width:10%"><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td style="width:20%"><?php echo htmlspecialchars($row['location_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="5" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>
            <form action="Admin-Report-DropOff-DropOffRequest-PDF.php" method="post" target="_blank">
                <input type="hidden" id="monthFilter" name="monthFilter" value="">
                <input type="hidden" id="yearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let dropOffData = <?php echo json_encode($data); ?>;

        const monthNames = {
            "01": "Jan", "02": "Feb", "03": "Mar", "04": "Apr",
            "05": "May", "06": "Jun", "07": "Jul", "08": "Aug",
            "09": "Sep", "10": "Oct", "11": "Nov", "12": "Dec"
        };

        const allMonthKeys = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
        let highlightedMonth = null;

        function getCurrentYear() {
            return new Date().getFullYear().toString();
        }

        function filterDataByYear(year) {
            if (year === "") year = getCurrentYear();
            return dropOffData.filter(item => item.year === year);
        }

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

        function applyFilters() {
            let selectedMonth = document.getElementById("monthFilter").value;
            let selectedYear = document.getElementById("yearFilter").value || getCurrentYear();
            highlightedMonth = selectedMonth.padStart(2, "0");

            let rows = document.querySelectorAll("tbody tr");
            let visibleCount = 0;

            rows.forEach(row => {
                let monthClass = [...row.classList].find(cls => cls.startsWith("month-"));
                let yearClass = [...row.classList].find(cls => cls.startsWith("year-"));

                let rowMonth = monthClass ? monthClass.replace("month-", "").padStart(2, "0") : "";
                let rowYear = yearClass ? yearClass.replace("year-", "") : "";

                let show = (selectedMonth === "" || rowMonth === selectedMonth) && (rowYear === selectedYear);
                row.style.display = show ? "" : "none";

                if (show) visibleCount++;
            });

            const noResultsRow = document.getElementById("noResultsRow");
            if (noResultsRow) {
                noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
            }
            document.querySelector("input[name='monthFilter']").value = selectedMonth;
            document.querySelector("input[name='yearFilter']").value = selectedYear;
        }

        function updateChart(year) {
            let filteredData = filterDataByYear(year);
            let dataMap = {};
            filteredData.forEach(item => {
                let monthNum = item.month.toString().padStart(2, "0");
                dataMap[monthNum] = item.total_dropoff;
            });

            const labels = allMonthKeys.map(key => monthNames[key]);
            const data = allMonthKeys.map(key => dataMap[key] || 0);

            dropOffChart.data.labels = labels;
            dropOffChart.data.datasets[0].data = data;
            dropOffChart.data.datasets[0].backgroundColor = getBarColors(labels);
            dropOffChart.update('none');
        }

        let initialYear = getCurrentYear();
        let initialData = filterDataByYear(initialYear);
        let ctx = document.getElementById("dropOffChart").getContext("2d");

        let dataMap = {};
        initialData.forEach(item => {
            let monthNum = item.month.toString().padStart(2, "0");
            dataMap[monthNum] = item.total_dropoff;
        });

        let initialChartData = allMonthKeys.map(key => dataMap[key] || 0);

        let lastHoverIndex = null;

        let dropOffChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: allMonthKeys.map(key => monthNames[key]),
                datasets: [{
                    label: "Total Drop Off per Month",
                    data: initialChartData,
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
                                    text: "Total Drop Off per Month",
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

                    // Keep a reference to the last hover index
                    if (!chart._lastHoveredIndex && chart._lastHoveredIndex !== 0) {
                        chart._lastHoveredIndex = null;
                    }

                    const selectedMonth = document.getElementById('monthFilter').value;
                    const selectedIndex = selectedMonth ? parseInt(selectedMonth.padStart(2, '0'), 10) - 1 : -1;

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

                        dataset.backgroundColor = dataset.data.map((_, i) => {
                            if (selectedIndex === i) return 'rgba(14, 97, 43, 0.6)';
                            return selectedMonth ? 'rgba(181, 222, 173, 0.46)' : 'rgba(14, 97, 43, 0.6)';
                        });

                        chart.update('none');
                    }
                }
            }]
        });

        document.getElementById("yearFilter").addEventListener("change", function () {
            let selectedYear = this.value;
            document.getElementById("monthFilter").value = "";
            highlightedMonth = null;

            updateChart(selectedYear);
            applyFilters();
        });

        document.getElementById('monthFilter').addEventListener('change', function () {
            const selected = this.value;

            if (!selected) {
                dropOffChart.data.datasets[0].backgroundColor = Array(12).fill('rgba(14, 97, 43, 0.6)');
                highlightedMonth = null;
            } else {
                highlightedMonth = selected.padStart(2, '0');
                dropOffChart.data.datasets[0].backgroundColor = getBarColors(dropOffChart.data.labels);
            }

            dropOffChart.update('none');
            applyFilters();
        });

        applyFilters();
    });

    </script>
</body>

</html>