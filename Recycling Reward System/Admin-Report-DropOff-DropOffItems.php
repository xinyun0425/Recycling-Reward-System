
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

    // First query: Table details
    $dropOffItems = [];
    $query1 = "
        SELECT 
            YEAR(d.dropoff_date) AS year,
            MONTH(d.dropoff_date) AS month_num,
            MONTHNAME(d.dropoff_date) AS month,
            i.item_name, 
            id.quantity
        FROM dropoff d
        JOIN item_dropoff id ON d.dropoff_id = id.dropoff_id
        JOIN item i ON id.item_id = i.item_id
        WHERE d.status = 'Complete'
        ORDER BY d.dropoff_date;
    ";
    
    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $dropOffItems[] = $row;
    }
    

    #Chart Details
    $query3 = "
        SELECT 
            i.item_name, 
            SUM(id.quantity) AS total_quantity,
            YEAR(d.dropoff_date) AS year,
            MONTH(d.dropoff_date) AS month_num
        FROM item_dropoff id
        JOIN dropoff d ON id.dropoff_id = d.dropoff_id
        JOIN item i ON id.item_id = i.item_id
        WHERE d.status = 'Complete'
        GROUP BY i.item_name, YEAR(d.dropoff_date), MONTH(d.dropoff_date)
        ORDER BY total_quantity DESC;
    ";

    $result = $conn->query($query3); 

    $data = [];

    while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    }
    $queryitem = "SELECT DISTINCT item_name FROM item ORDER BY item_name ASC"; 
    $itemresult = $conn->query($queryitem);

    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drop-off Items Report - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

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
        padding:10px 27px 10px 16px;
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
        <div class = "header">
            <h2>
            <a href='Admin-Report.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Drop-off Items Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                <select id="yearFilter">
                    <?php    
                        $query= "SELECT DISTINCT YEAR(dropoff_date) AS year FROM dropoff ORDER BY year DESC";
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
            <table id="dropOffTable">
                <thead>
                    <tr>
                        <th style="width:10%">Month</th>
                        <th style="width:25%">Item Name</th>
                        <th style="width:10%">Quantity</th>
                    </tr>
                </thead>
                <tbody id="dropOffTableBody">
                    <?php foreach ($dropOffItems as $row): ?>
                        <tr>
                            <td style="width:10%"><?php echo htmlspecialchars($row['month']); ?></td>
                            <td style="width:25%"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td style="width:10%"><?php echo htmlspecialchars($row['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="3" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>
            <form action="Admin-Report-DropOff-DropOffItems-PDF.php" method="post" target="_blank">
                <input type="hidden" id="hiddenMonthFilter" name="monthFilter" value="">
                <input type="hidden" id="hiddenYearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>

        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const itemData = <?php echo json_encode($data); ?>;
        const dropOffTableData = <?php echo json_encode($dropOffItems); ?>;
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

        function createPieChart(labels, data) {
            if (itemPieChart) itemPieChart.destroy();

            Chart.register(ChartDataLabels);

            const isNoData = labels.length === 1 && labels[0] === "No data";

            const backgroundColors = isNoData
                ? ["#cccccc"]
                : labels.map((label, index) => pastelColors[index % pastelColors.length]);

            itemPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
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
                            display: !isNoData,
                            anchor: 'end',
                            align: 'end',
                            offset: 5,
                            color: '#000',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: (value, ctx) => {
                                return ctx.chart.data.labels[ctx.dataIndex];
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function updateChart(year, month = "") {
            const filtered = itemData.filter(row =>
                row.year == year && (!month || row.month_num == parseInt(month))
            );

            let labels, quantities;

            if (filtered.length === 0) {
                labels = ["No data"];
                quantities = [1];
            } else {
                labels = filtered.map(row => row.item_name);
                quantities = filtered.map(row => parseInt(row.total_quantity));
            }

            createPieChart(labels, quantities);
        }


        function updateTable(year, month = "") {
            const tableBody = document.getElementById("dropOffTableBody");
            tableBody.innerHTML = "";

            const filtered = dropOffTableData.filter(row =>
                row.year == year && (!month || row.month_num == parseInt(month))
            );

            if (filtered.length === 0) {
                const noResultRow = document.createElement("tr");
                noResultRow.innerHTML = `
                    <td colspan="3" style="width:100%; text-align:center; font-style:italic;">No results found.</td>
                `;
                tableBody.appendChild(noResultRow);
                updateHiddenInputs(year, month);
                return;
            }

            filtered.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td style="width:15%">${row.month}</td>
                    <td style="width:20%">${row.item_name}</td>
                    <td style="width:10%">${row.quantity}</td>
                `;
                tableBody.appendChild(tr);
            });

            updateHiddenInputs(year, month);
        }

        function updateHiddenInputs(selectedYear, selectedMonth) {
            document.getElementById("hiddenYearFilter").value = selectedYear;
            document.getElementById("hiddenMonthFilter").value = selectedMonth;
        }

        const yearFilter = document.getElementById("yearFilter");
        const monthFilter = document.getElementById("monthFilter");

        yearFilter.addEventListener("change", function () {
            const selectedYear = this.value;
            const selectedMonth = monthFilter.value;

            updateChart(selectedYear, selectedMonth);
            updateTable(selectedYear, selectedMonth);
            updateHiddenInputs(selectedYear, selectedMonth);
        });

        monthFilter.addEventListener("change", function () {
            const selectedMonth = this.value;
            const selectedYear = yearFilter.value;

            updateChart(selectedYear, selectedMonth);
            updateTable(selectedYear, selectedMonth);
            updateHiddenInputs(selectedYear, selectedMonth);
        });

        const currentYear = new Date().getFullYear().toString();
        yearFilter.value = currentYear;
        monthFilter.value = ""; // default to All

        updateChart(currentYear);
        updateTable(currentYear);
        updateHiddenInputs(currentYear, "");
    });

    </script>
</body>

</html>