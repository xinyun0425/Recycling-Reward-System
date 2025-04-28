<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin-Login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pickup Requests Management - Green Coin </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
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

    .profile-container{
        width:100%;
        margin-top:130px;
        bottom:12px;
        margin-top:0px;
    }

    .profile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f8f9fa ;
        border-radius: 10px;
        border:2px solid rgba(116, 116, 116, 0.76);
        padding: 15px; 
        padding-left:20px;
        width: 93%;
        position: relative;
        margin: 15px;
        box-sizing: border-box;
    }
    .profileicon {
        font-size: 30px;
        color: #333;
    } 

    .profile-info {
        font-size: 14px;
        flex-grow: 1;
        padding-left: 15px;
    }

    .profile-info p {
        margin: 0;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin-left:13px;
        width:220px;
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

    .menu li.active
    {
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

    .notificationProfile {
        border: none; 
        background-color: transparent;
        cursor: pointer;        
        position: relative;
        display: flex; 
        align-items: center; 
        justify-content: center;
        width: 40px; 
        height: 40px;  
        border-radius: 50%; 
        font-size: 25px;
        transition: background-color 0.2s ease-in-out;
    }

    .notificationProfile:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%; 
        background: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        width: 150px; 
        text-align: left;
        z-index: 10;
        padding: 5px 0;
    }

    .dropdown-btn {
        border: none;
        background-color: transparent;
        cursor: pointer;
        font-size: 16px;
    }

    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
        text-align: center;
    }

    .dropdown a:hover {
        background: #E4EBE6;
        color: rgb(11, 91, 19);
    }

    .content{
        padding:20px;
        margin-left:300px;
        width:calc(100%-270px);
        overflow-y:auto;
    }

    .title{
        display:flex;
        flex-direction: column;
        align-items:left;
        justify-content: center;  
        margin-left:73px;
        animation:floatIn 0.8s ease-out;
    }

    .upperbutton{
        margin:20px 0 0 75px  ;
        
    }

    .pending, .assign, .complete {
        padding: 12px 24px;
        text-align: center;
        font-size: 16px;
        margin: 0 10px 10px 0;
        cursor: pointer;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 50px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border: 1px solid transparent;
        width:210px;
    }

    .pending {
        background-color: white; 
        color: black;
        border:1px solid black;
    }

    .pending:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        background-color:rgba(236,252,235,0.7);
    }

    .assign {
        background-color: white;
        color:black;
        border:1px solid black;
    }

    .assign:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        background-color:rgba(236,252,235,0.7);
    }

    .complete {
        background-color: white;
        color: black;
    }

    .complete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .complete.active {
        background-color: #4E9F3D;
        color:white;    
    }

    .detail-card{
        display: flex;
        flex-direction: column;
        width: 79.5%;
        margin: 15px 60px 10px 75px;
        background-color: #ffffff;
        padding: 20px 40px 40px 40px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .filter-container {
        display: flex;
        gap: 50px;
        margin: 25px 0;
        flex-wrap: wrap;
        align-items:flex-end;
    }

    .filter-group {
        flex: 0 0 180px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        color: #555;
    }

    .filter-group input {
        width: 100%;
        padding: 10px 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: all 0.3s ease;
        font-family: Arial, sans-serif;
    }

    .filter-group input:focus {
        outline: none;
    }

    .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .filter-button{
        background-color:#cddff4;
        color: black;
        padding: 11px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size:14px;
        border:1.5px solid #737373;
    }

    .reset-button{
        background-color: #f8f9fa;
        padding: 11px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size:14px;
        border:1.5px solid rgb(193, 193, 193);
    }

    table{
        border-collapse:collapse;
        width:100%;
        font-size:16px;
        border:1px solid rgb(200, 200, 200);
    }

    table.center{
        margin-left:auto;
        margin-right:auto;
    }

    th {
        padding: 15px;
        text-align: left;
        background-color:#E0E1E1;
    }
    
    td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover {
        background-color: rgba(120, 162, 76, 0.05);
        cursor:pointer;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
        width:80px;
        text-align:center;
    }

    .status-completed {
        background-color: #D4EDDA;
        color: #27AE60;
    }

    .status-rejected,.status-absent {
        background-color: #FFD2D2;
        color: #D8000C;
    }

    hr{
        border: none;
        height: 1.5px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
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
            <li class="active"><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable items</a></li>
            <li ><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
            <li><a href="Admin-Review.php"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li><a href="Admin-Report.php"><i class="fa-solid fa-chart-column"></i>Report</a></li>
            <li><a href="Admin-FAQ.php"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
            <form action="Admin-Logout.php" method="post" style="display:inline;">
                <button type="submit" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>            
        </ul>
    </div>

    <div class="content">
        <div class="title">
            <h2>Pickup Requests Management</h2>
        </div>

        <hr style="width: 92%; margin-left:45px;">
        <div class="upperbutton">
            <button class="pending" onclick="window.location.href='Admin-Pickup-Pending.php'">Pending Requests</button>
            <button class="assign" onclick="window.location.href='Admin-Pickup-Assign.php'">Assigned Requests</button>
            <button class="complete active">Completed Requests</button>
        </div>
        <div class="detail-card">
            <h3>Completed Requests</h3>
                
            <?php
                $con = mysqli_connect("localhost","root","","cp_assignment");

                if(mysqli_connect_errno()){
                    echo "Failed to connect to MySQL:".mysqli_connect_error();
                }

                $sql = "SELECT 
                        pickup_request.pickup_request_id,
                        user.username,
                        driver.driver_name,
                        time_slot.date AS pickup_date, 
                        time_slot.time AS pickup_time,
                        item_pickup.quantity,
                        item.point_given,
                        pickup_request.status,
                        pickup_request.total_point_earned
                    FROM pickup_request 
                    LEFT JOIN driver ON pickup_request.driver_id = driver.driver_id
                    LEFT JOIN item_pickup ON item_pickup.pickup_request_id = pickup_request.pickup_request_id
                    LEFT JOIN item ON item_pickup.item_id = item.item_id
                    LEFT JOIN user ON pickup_request.user_id = user.user_id
                    LEFT JOIN time_slot ON pickup_request.time_slot_id = time_slot.time_slot_id
                    WHERE pickup_request.status = 'completed' OR pickup_request.status = 'rejected' OR pickup_request.status = 'absent'
                    GROUP BY pickup_request.pickup_request_id
                    ORDER BY pickup_date, username";
                $result = mysqli_query($con,$sql);
            ?>
            <div class="filter-container">
                <div class="filter-group">
                    <label for="filter_pickup_date">Filter by Pickup Date</label>
                    <input type="date" id="filter_pickup_date">
                </div>
                <div class="filter-actions">
                    <button class="filter-button" onclick="filterByDate()">Apply Filter</button>
                    <button class="reset-button" onclick="resetDateFilter()">Reset</button>
                </div>
            </div>

            <table id="assigned_table">
                <tr>
                    <th style="width:5%;"></th>
                    <th style="width:30%;">Username</th>
                    <th style="width:15%;">Pickup Date</th>
                    <th style="width:15%;">Pickup Time</th>
                    <th style="width:10%;">Point</th>
                    <th style="width:12%; text-align:left;">Status</th>
                </tr>
                <?php
                    $result=mysqli_query($con,$sql);
                    if(mysqli_num_rows($result)>0){
                        $counter=1;
                        while($row=mysqli_fetch_array($result)){
                ?>
                <tr class="row_hover" onclick="window.location.href='Admin-Pickup-Complete-Detail.php?pickup_request_id=<?php echo $row['pickup_request_id'];?>'">
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $row['username'];?></td>
                    <td><?php echo date('d M Y', strtotime($row['pickup_date'])); ?></td>
                    <td><?php echo date('H:i', strtotime($row['pickup_time'])); ?></td>
                    <td><?php echo $row['total_point_earned']; ?></td>                    
                    <td style="text-align:left; padding-left:0">
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span>
                    </td> 
                </tr>        
                <?php
                    }
                }else{
                ?>
                    <tr id="no-records-row">
                        <td colspan="6" style="text-align:center; padding: 20px; background-color:white;">
                            <span style="font-size: 16px;"><i class="fas fa-inbox" style=" color: #black; margin-right: 8px;"></i>
                            No Completed Pickup Request Found.</span>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </div>
    </div>
    <script>
        function toggleDropdown(event) {
            event.stopPropagation(); 
            let dropdown = document.getElementById("profileDropdown");
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        }
        document.addEventListener("click", function(event) {
            let dropdown = document.getElementById("profileDropdown");
            let button = document.querySelector(".dropdown-btn");
            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });

        function filterByDate() {
            const filterDate = document.getElementById('filter_pickup_date').value;
            const table = document.getElementById("assigned_table");
            const rows = table.getElementsByTagName('tr');
            
            const noRecordsRow = document.getElementById("no-records-row");
            if (noRecordsRow) {
                noRecordsRow.style.display = 'none';
            }

            const existingNoResultsRow = document.getElementById("no-results-row");
            if (existingNoResultsRow) {
                existingNoResultsRow.remove();
            }

            let hasResults = false;
            let hasAnyRows = false;

            for (let i = 1; i < rows.length; i++) {
                if (rows[i].id === "no-records-row") continue;
                
                hasAnyRows = true;
                const cells = rows[i].getElementsByTagName('td');
                
                if (cells.length < 3) continue;

                const displayDate = cells[2].textContent.trim();
                const formattedDisplayDate = convertDisplayDateToISO(displayDate);

                if (filterDate && formattedDisplayDate !== filterDate) {
                    rows[i].style.display = 'none';
                } else {
                    rows[i].style.display = '';
                    hasResults = true;
                }
            }

            if (!hasResults) {
                const tbody = table.getElementsByTagName('tbody')[0] || table;
                const newRow = tbody.insertRow(-1);
                newRow.id = "no-results-row";

                const newCell = newRow.insertCell(0);
                newCell.colSpan = rows[0].cells.length;
                newCell.style.textAlign = "center";
                newCell.style.padding = "20px";
                newCell.innerHTML = `
                    <i class="fas fa-calendar-times" style="margin-right: 8px; color: #black;"></i>
                    No pickup record found for the selected date.
                `;
            }
        }

        function convertDisplayDateToISO(displayDate) {
            const months = {
                'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
                'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
                'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
            };
            
            const parts = displayDate.split(' ');
            if (parts.length === 3) {
                const day = parts[0].padStart(2, '0');
                const month = months[parts[1]];
                const year = parts[2];
                return `${year}-${month}-${day}`;
            }
            return displayDate;
        }

        function resetDateFilter() {
            document.getElementById('filter_pickup_date').value = '';
            const rows = document.getElementById('assigned_table').getElementsByTagName('tr');

            const existingNoResultsRow = document.getElementById("no-results-row");
            if (existingNoResultsRow) {
                existingNoResultsRow.remove();
            }

            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }

    </script>

</body>
</html>