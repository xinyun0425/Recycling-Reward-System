
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
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

    $userRedemptions = [];
    $query1 = "
        SELECT 
            rr.collect_datetime AS date,
            u.username,
            r.reward_name
        FROM redeem_reward rr
        JOIN user u ON rr.user_id = u.user_id
        JOIN reward r ON rr.reward_id = r.reward_id        
        WHERE rr.status = 'Redeemed'
        ORDER BY rr.collect_datetime ASC
    ";
    $result1 = $conn->query($query1);
    
    while ($row = $result1->fetch_assoc()) {
        $userRedemptions[] = $row;
    }
    
    
    $monthlyRedemptions = [];

    $queryMonthly = "
        SELECT
            YEAR(rr.collect_datetime) AS year,
            MONTH(rr.collect_datetime) AS month,
            COUNT(*) AS total
        FROM redeem_reward rr
        WHERE rr.status = 'Redeemed'
        GROUP BY year, month
        ORDER BY year, month
    ";
    
    $resultMonthly = $conn->query($queryMonthly);
    while ($row = $resultMonthly->fetch_assoc()) {
        $monthlyRedemptions[] = [
            'year' => (int)$row['year'],
            'month' => str_pad($row['month'], 2, '0', STR_PAD_LEFT), // e.g., "01"
            'total' => (int)$row['total']
        ];
    }
    
    
    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Redemption Report - Green Coin</title>
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
    #userRedemptionsChart{
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
            User Redemption History Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
        <div class="yearFilterDropdown">
                <select id="yearFilter">
                    <?php
                    $yearQuery = "SELECT DISTINCT YEAR(collect_datetime) AS year FROM redeem_reward WHERE status='Redeemed' ORDER BY year DESC";
                    $result = $conn->query($yearQuery);
                    $currentYear = date('Y');

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $isSelected = ($row['year'] == $year) ? "selected" : "";
                            echo "<option value='" . $row['year'] . "' $isSelected>" . $row['year'] . "</option>";
                        }
                    } else {
                        echo "<option disabled>No data</option>";
                    }
                    ?>
                </select>
                <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </div>
            <canvas id="userRedemptionsChart" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
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
                        <th style="width:10%">Collect Date</th>
                        <th style="width:25%">Username</th>
                        <th style="width:25%">Reward Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($userRedemptions) > 0): ?>
                        <?php foreach ($userRedemptions as $row): ?>
                            <tr 
                            data-month="<?= date('m', strtotime($row['date'])) ?>" 
                            data-year="<?= date('Y', strtotime($row['date'])) ?>">
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['date']))) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['reward_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="3" style="text-align: center; font-style: italic;">No results found.</td>
                    </tr>
                </tbody>
            </table>
            <form action="Admin-Report-Reward-UserRedemptionHistory-PDF.php" method="post" target="_blank">
                <input type="hidden" id="monthFilter" name="monthFilter" value="">
                <input type="hidden" id="yearFilter" name="yearFilter" value="">
                <button type="submit" class="generate-btn">Generate PDF Report</button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const yearFilter = document.getElementById('yearFilter');
        const monthFilter = document.getElementById('monthFilter');
        const noResultsRow = document.getElementById('noResultsRow');

        const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const allRedemptionData = <?php echo json_encode($monthlyRedemptions); ?>;

        function getMonthlyDataForYear(year) {
            const monthTotals = {};

            allRedemptionData.forEach(item => {
                if (item.year == year) {
                    monthTotals[item.month] = item.total;
                }
            });

            return allMonths.map((_, index) => {
                const monthKey = String(index + 1).padStart(2, '0');
                return monthTotals[monthKey] || 0;
            });
        }

        const ctx = document.getElementById('userRedemptionsChart').getContext('2d');
        const barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: allMonths,
                datasets: [{
                    label: 'Total Redemptions',
                    data: [], // Will be populated dynamically
                    backgroundColor: Array(12).fill('rgba(14, 97, 43, 0.6)')
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        onClick: () => {},
                        labels: {
                            generateLabels(chart) {
                                return [{
                                    text: 'Total Redemptions',
                                    fillStyle: 'rgba(14, 97, 43, 0.6)',
                                    strokeStyle: 'rgba(14, 97, 43, 0.6)',
                                    lineWidth: 1,
                                    hidden: false,
                                    index: 0
                                }];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `Redemptions: ${context.parsed.y}`;
                            }
                        }
                    }
                },
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
                }
            },
            plugins: [{
                id: 'hoverHighlight',
                afterEvent(chart, args) {
                    const event = args.event;
                    const dataset = chart.data.datasets[0];
                    const selectedMonth = monthFilter.value;
                    const hasFilter = selectedMonth !== '';
                    const selectedIndex = hasFilter ? parseInt(selectedMonth, 10) - 1 : -1;

                    if (event.type === 'mousemove') {
                        const points = chart.getElementsAtEventForMode(event, 'index', { intersect: false }, false);
                        const activeIndex = points.length ? points[0].index : null;

                        if (activeIndex !== chart._lastHoveredIndex) {
                            chart._lastHoveredIndex = activeIndex;

                            dataset.backgroundColor = dataset.data.map((_, i) => {
                                if (!hasFilter) {
                                    return i === activeIndex
                                        ? 'rgba(14, 97, 43, 0.6)'
                                        : 'rgba(153, 201, 143, 0.46)';
                                }
                                return (i === activeIndex || i === selectedIndex)
                                    ? 'rgba(14, 97, 43, 0.6)'
                                    : 'rgba(153, 201, 143, 0.46)';
                            });

                            chart.update('none');
                        }
                    }

                    if (event.type === 'mouseout') {
                        chart._lastHoveredIndex = null;

                        dataset.backgroundColor = dataset.data.map((_, i) => {
                            return hasFilter && i === selectedIndex
                                ? 'rgba(14, 97, 43, 0.6)'
                                : hasFilter
                                    ? 'rgba(153, 201, 143, 0.46)'
                                    : 'rgba(14, 97, 43, 0.6)';
                        });

                        chart.update('none');
                    }
                }
            }]
        });

        function updateChartForYear(year, selectedMonthIndex = -1) {
            const chartData = getMonthlyDataForYear(year);
            barChart.data.datasets[0].data = chartData;

            barChart.data.datasets[0].backgroundColor = chartData.map((_, i) =>
                selectedMonthIndex === -1
                    ? 'rgba(14, 97, 43, 0.6)'
                    : (i === selectedMonthIndex
                        ? 'rgba(14, 97, 43, 0.6)'
                        : 'rgba(153, 201, 143, 0.46)')
            );

            barChart.update();
        }

        function applyFilters() {
            const selectedMonth = monthFilter.value;
            const selectedYear = yearFilter.value;
            const selectedMonthIndex = selectedMonth ? parseInt(selectedMonth, 10) - 1 : -1;

            const rows = document.querySelectorAll("tbody tr[data-month][data-year]");
            let visibleCount = 0;

            rows.forEach(row => {
                const rowMonth = row.getAttribute('data-month');
                const rowYear = row.getAttribute('data-year');

                const matchesMonth = !selectedMonth || rowMonth === selectedMonth;
                const matchesYear = !selectedYear || rowYear === selectedYear;

                if (matchesMonth && matchesYear) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';

            document.querySelector("input[name='monthFilter']").value = selectedMonth;
            document.querySelector("input[name='yearFilter']").value = selectedYear;

            if (selectedYear) {
                updateChartForYear(selectedYear, selectedMonthIndex);
            }
        }

        // Initial setup
        monthFilter.addEventListener('change', applyFilters);
        yearFilter.addEventListener('change', function () {
            monthFilter.value = '';
            applyFilters();
        });


        // Optionally auto-select current year
        const currentYear = new Date().getFullYear();
        if (!yearFilter.value) {
            yearFilter.value = currentYear;
        }

        applyFilters();
    });


    </script>
</body>

</html>