
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

    // First query: User Points details
    $userPoints = [];
    $query1 = "
        SELECT 
            username,
            points
        FROM user
        ORDER BY points DESC
    ";


    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $userPoints[] = $row;
    }
    $pointBuckets = [
        '0-99' => 0,
        '100-199' => 0,
        '200-299' => 0,
        '300-399' => 0,
        '400-499' => 0,
        '500+' => 0
    ];
    
    foreach ($userPoints as $user) {
        $points = (int) $user['points'];
    
        if ($points >= 0 && $points < 100) {
            $pointBuckets['0-99']++;
        } elseif ($points < 200) {
            $pointBuckets['100-199']++;
        } elseif ($points < 300) {
            $pointBuckets['200-299']++;
        } elseif ($points < 400) {
            $pointBuckets['300-399']++;
        } elseif ($points < 500) {
            $pointBuckets['400-499']++;
        } else {
            $pointBuckets['500+']++;
        }
    }
    
    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Points Report - Green Coin</title>
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
        margin-bottom: 40px;
    }
    #userPointsGraph{
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
            User Points Report</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <div class="yearFilterDropdown">
                <select id="rangeFilter">
                    <option value="all">All</option>
                    <option value="0-99">0-99</option>
                    <option value="100-199">100-199</option>
                    <option value="200-299">200-299</option>
                    <option value="300-399">300-399</option>
                    <option value="400-499">400-499</option>
                    <option value="500+">500+</option>
                </select>
                <i class="fa-solid fa-caret-down dropdown-icon"></i>
            </div>
            <canvas id="userPointsGraph" style="display: block; box-sizing: border-box;padding-bottom:10px;"></canvas>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%"></th>
                        <th style="width:50%">Username</th>
                        <th style="width:45%">Points</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $bil = 1;
                    foreach ($userPoints as $row) : 
                    ?>
                        <tr>
                            <td><?php echo $bil++; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
                <form action="Admin-Report-Reward-UserPoints-PDF.php" method="post" target="_blank">
                    <input type="hidden" name="rangeFilter" value="">
                    <button type="submit" class="generate-btn">Generate PDF Report</button>
                </form>
            </div>
        </div>
    <script>
            const pointBuckets = <?php echo json_encode($pointBuckets); ?>;
            const userPoints = <?php echo json_encode($userPoints); ?>;

            let barChart;
            const ctx = document.getElementById('userPointsGraph').getContext('2d');

            const pointRanges = Object.keys(pointBuckets);
            function renderChart(highlightRange = 'all') {
                if (barChart) barChart.destroy();

                const backgroundColors = pointRanges.map(range => {
                    if (highlightRange === 'all') {
                        return 'rgba(14, 97, 43, 0.6)';
                    }
                    return range === highlightRange
                        ? 'rgba(14, 97, 43, 0.6)'
                        : 'rgba(181, 222, 173, 0.46)';
                });

                barChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: pointRanges,
                        datasets: [{
                            label: 'Number of Users',
                            data: pointRanges.map(range => pointBuckets[range]),
                            backgroundColor: backgroundColors
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    generateLabels: function (chart) {
                                        const dataset = chart.data.datasets[0];
                                        return [{
                                            text: dataset.label,
                                            fillStyle: 'rgba(14, 97, 43, 0.6)'
                                        }];
                                    }
                                },
                                onClick: () => {}
                            },
                            tooltip: {
                                callbacks: {
                                    label: context => `Users: ${context.parsed.y}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    callback: value => Number.isInteger(value) ? value : null
                                }
                            }
                        }
                    },
                    plugins: [{
                        id: 'hoverHighlight',
                        afterEvent(chart, args) {
                            const event = args.event;
                            const dataset = chart.data.datasets[0];
                            const highlightRange = document.querySelector("input[name='rangeFilter']").value;
                            const labels = chart.data.labels;

                            const selectedIndex = highlightRange !== 'all'
                                ? labels.indexOf(highlightRange)
                                : -1;

                            if (!chart._lastHoveredIndex && chart._lastHoveredIndex !== 0) {
                                chart._lastHoveredIndex = null;
                            }

                            if (event.type === 'mousemove') {
                                const points = chart.getElementsAtEventForMode(event, 'index', { intersect: false }, false);
                                const activeIndex = points.length ? points[0].index : null;

                                if (activeIndex !== chart._lastHoveredIndex) {
                                    chart._lastHoveredIndex = activeIndex;

                                    dataset.backgroundColor = labels.map((_, i) => {
                                        if (activeIndex === i) {
                                            return 'rgba(14, 97, 43, 0.6)';
                                        }

                                        // Keep selected highlighted if it's not hovered
                                        if (highlightRange !== 'all' && i === selectedIndex) {
                                            return 'rgba(14, 97, 43, 0.6)';
                                        }

                                        return 'rgba(181, 222, 173, 0.46)';
                                    });

                                    chart.update('none');
                                }
                            }

                            if (event.type === 'mouseout') {
                                chart._lastHoveredIndex = null;

                                dataset.backgroundColor = labels.map((_, i) => {
                                    if (highlightRange === 'all') {
                                        return 'rgba(14, 97, 43, 0.6)';
                                    }

                                    return i === selectedIndex
                                        ? 'rgba(14, 97, 43, 0.6)'
                                        : 'rgba(181, 222, 173, 0.46)';
                                });

                                chart.update('none');
                            }
                        }
                    }]
                });
            }


            function filterUsersByRange(range) {
                return userPoints.filter(user => {
                    const points = parseInt(user.points);

                    if (range === 'all') return true;
                    if (range === '500+') return points >= 500;

                    const [min, max] = range.split('-').map(Number);
                    return points >= min && points <= max;
                });
            }

            function updateTable(filteredUsers) {
                const tbody = document.querySelector("table tbody");
                tbody.innerHTML = "";

                if (filteredUsers.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; font-style: italic;">No results found.</td></tr>`;
                    return;
                }

                filteredUsers.forEach((user, index) => {
                    const row = `
                        <tr>
                            <td style="width:10%">${index + 1}</td>
                            <td style="width:20%">${user.username}</td>
                            <td style="width:20%">${user.points}</td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML("beforeend", row);
                });
            }

            function handleFilterChange(range) {
                renderChart(range); 
                const filteredUsers = filterUsersByRange(range);
                updateTable(filteredUsers);
                document.querySelector("input[name='rangeFilter']").value = range;
            }

            document.addEventListener('DOMContentLoaded', () => {
                handleFilterChange('all');

                document.getElementById('rangeFilter').addEventListener('change', function () {
                    handleFilterChange(this.value);
                });
            });

    </script>
</body>

</html>