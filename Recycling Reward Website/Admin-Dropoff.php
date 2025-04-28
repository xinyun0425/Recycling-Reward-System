<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin-Login.php");
    exit();
}
?>

<?php
$con = mysqli_connect("localhost","root","","cp_assignment");

if(mysqli_connect_errno()) {
    echo "Failed to connect to MySQL:".mysqli_connect_error();
}

$currentDate = date("Y-m-d");

$getExpiredDropoffRequestQuery = mysqli_query($con, "SELECT d.dropoff_date, d.dropoff_id, d.user_id 
                                                    FROM dropoff d
                                                    WHERE d.status = 'unread'");
while($getExpiredDropoffRequestResult = mysqli_fetch_assoc($getExpiredDropoffRequestQuery)) {
    $dateDropoffCheck = $getExpiredDropoffRequestResult['dropoff_date'];
    $dropoffIdCheck = $getExpiredDropoffRequestResult['dropoff_id'];
    $userId = $getExpiredDropoffRequestResult['user_id'];

    if (strtotime($dateDropoffCheck) < strtotime($currentDate)) {
        $updateDropoffStatus = mysqli_query($con, "UPDATE dropoff SET status = 'Expired' WHERE dropoff_id = '$dropoffIdCheck' AND status = 'unread'");
        
        if ($updateDropoffStatus) {
            $userAnnouncement = "Your drop-off request for ".$dateDropoffCheck." has expired.  
                               Please submit a new request when you are ready to drop-off your items.  
                               Thank you for recycling with us! ♻️";
            
            $userNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) 
                             VALUES ('$userId', NOW(), 'Drop-off Expired', '$userAnnouncement', 'unread')";
            mysqli_query($con, $userNotiQuery);

            $adminAnnouncement = "Drop-off request #$dropoffIdCheck for ".date('d M Y', strtotime($dateDropoffCheck))." 
                                has expired without being processed.";
            
            $adminNotiQuery = "INSERT INTO admin_notification(datetime, title, announcement, status) 
                              VALUES (NOW(), 'Expired Drop-off', '$adminAnnouncement', 'unread')";
            mysqli_query($con, $adminNotiQuery);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drop-off Requests Management - Green Coin</title>
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

    .today, .all{
        border: none;
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

    .today {
        background-color: white; 
        color: black;
    }

    .today:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .today.active {
        background-color: #4E9F3D;
        color:white;
    }

    .all {
        background-color: white;
        color:black;
        border:1px solid black;
    }

    .all:hover {
        transform: translateY(-2px);
        background-color:rgba(236,252,235,0.7);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

    .filter-group select {
        width: 145%;
        padding: 11.5px 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: all 0.3s ease;
        appearance:none;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M7 10l5 5 5-5z"/></svg>'); /* Custom dropdown arrow */
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 24px 24px;
        padding-right: 40px;
    }

    .filter-group input:focus {
        outline: none;
    }

    .filter-group select:focus {
        outline: none;
    }

    .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        margin-left:50px;
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
        display: inline-block;
        padding: 4px 15px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        margin:0;
        text-align:left;
    }

    .status-unread {
        background-color: #FFF3CD;
        color: #856404;
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
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li class="active"><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
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
            <h2>Drop-off Requests Management</h2>
        </div>

        <hr style="width: 92%; margin-left:45px;">
        <div class="upperbutton">
            <button class="today active">Drop-off Requests</button>
            <button class="all" onclick="window.location.href='Admin-Dropoff-Complete.php'">Completed Requests</button>
        </div>
        <div class="detail-card">
            <h3>Drop-off Requests</h3>
            <?php
                $con = mysqli_connect("localhost","root","","cp_assignment");

                if(mysqli_connect_errno()){
                    echo "Failed to connect to MySQL:".mysqli_connect_error();
                }

                $sql = "SELECT 
                        dropoff.dropoff_id,
                        dropoff.dropoff_date,
                        dropoff.status,
                        user.username,
                        location.location_name
                        
                        FROM dropoff 
                        LEFT JOIN user ON dropoff.user_id = user.user_id
                        LEFT JOIN location ON dropoff.location_id = location.location_id
                        WHERE dropoff.status = 'unread'
                        GROUP BY dropoff.dropoff_id
                        ORDER BY dropoff_date, username";
                $result = mysqli_query($con,$sql);
            ?>
            <div class="filter-container">
                <div class="filter-group">
                    <label for="filter_dropoff_date">Filter by Drop-off Date</label>
                    <input type="date" id="filter_dropoff_date">
                </div>
                <div class="filter-group">
                    <label for="filter_location">Filter by Location</label>
                    <select id="filter_location">
                        <option value="">All Locations</option>
                        <?php
                            $location_query = "SELECT DISTINCT location_name FROM location WHERE status = 'Available' ORDER BY location_name";
                            $location_result = mysqli_query($con, $location_query);
                            while($loc = mysqli_fetch_assoc($location_result)) {
                                echo '<option value="'.htmlspecialchars($loc['location_name']).'">'.htmlspecialchars($loc['location_name']).'</option>';
                            }
                            ?>
                    </select>
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
                    <th style="width:15%;">Drop-off Date</th>
                    <th style="width:20%;">Drop-off Location</th>
                    <th style="width:12%; text-align:left;">Status</th>
                </tr>
                <?php
                    $result = mysqli_query($con, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        $counter=1;
                        while($row=mysqli_fetch_array($result)){
                ?>
                <tr class="row_hover" onclick="window.location.href='Admin-Dropoff-Detail.php?dropoff_id=<?php echo $row['dropoff_id'];?>'">
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $row['username'];?></td>
                    <td><?php echo date('d M Y', strtotime($row['dropoff_date'])); ?></td>
                    <td><?php echo $row['location_name']; ?></td>                    
                    <td style="text-align:left;padding-left:5px;">
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo ($row['status'] == 'unread') ? 'Pending' : $row['status']; ?>
                        </span>
                    </td>
                </tr>         
                <?php
                    }
                }else{
                ?>
                    <tr id="no-records-row">
                        <td colspan="6" style="text-align:center; padding: 20px; background-color:white;">
                            <span style="font-size: 16px;"><i class="fas fa-inbox" style=" color: black; margin-right: 8px;"></i>
                            No Drop-off Request Found.</span>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </table>
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

        function filterByDate(){
            const dropoffDate = document.getElementById('filter_dropoff_date').value;
            const location = document.getElementById('filter_location').value;
            const table = document.getElementById("assigned_table");
            const rows = table.getElementsByTagName('tr');

            const noRecordsRow = document.getElementById("no-records-row");
            if (noRecordsRow) noRecordsRow.style.display = 'none';

            const existingNoResultsRow = document.getElementById("no-results-row");
            if (existingNoResultsRow) existingNoResultsRow.remove();

            let matchDate = false;
            let matchLocation = false;
            let hasResults = false;

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                if (cells.length < 4) continue;

                const rowDropoffDate = cells[2].textContent.trim();
                const rowLocation = cells[3].textContent.trim();
                const formattedRowDropoffDate = formatDateForComparison(rowDropoffDate);

                const dateMatches = !dropoffDate || formattedRowDropoffDate === dropoffDate;
                const locationMatches = !location || rowLocation === location;

                const showRow = dateMatches && locationMatches;
                rows[i].style.display = showRow ? '' : 'none';

                if (showRow) {
                    hasResults = true;
                }
                if (dateMatches) matchDate = true;
                if (locationMatches) matchLocation = true;
            }

            if (!hasResults) {
                let message = "";

                if (!matchDate && !matchLocation && dropoffDate && location) {
                    message = "No drop-off record found for the selected date and location.";
                } else if (!matchDate && dropoffDate) {
                    message = "No drop-off record found for the selected date.";
                } else if (!matchLocation && location) {
                    message = "No drop-off record found for the selected location.";
                } else {
                    message = "No drop-off record found.";
                }

                const newRow = table.insertRow(-1);
                newRow.id = "no-results-row";

                const newCell = newRow.insertCell(0);
                newCell.colSpan = rows[0].cells.length;
                newCell.style.textAlign = "center";
                newCell.style.padding = "20px";
                newCell.style.backgroundColor = "white";
                newCell.innerHTML = `
                    <i class="fas fa-calendar-times" style="margin-right: 8px;"></i>
                    ${message}
                `;
            }
        }

        function resetDateFilter() {
            document.getElementById('filter_dropoff_date').value = '';
            document.getElementById('filter_location').value = '';
            const table = document.getElementById('assigned_table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }

            const existingNoResultsRow = document.getElementById("no-results-row");
            if (existingNoResultsRow) {
                existingNoResultsRow.remove();
            }

            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }

        function formatDateForComparison(displayDate) {
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
        
    </script>

</body>
</html>