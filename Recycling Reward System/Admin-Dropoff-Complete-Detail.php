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
    <title>Drop-off Detail - Green Coin</title>
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
        overflow-x:hidden;
    }

    .title{
        text-align: left;  
        width: 100%;
        margin-left:50px;
        margin-bottom: 20px; 
        animation: floatIn 0.8s ease-out;
    }

    .title-container{
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left:30px; 
    }

    .title i {
        font-size:1.0em;
        margin-right:20px;
        color:rgb(134, 134, 134);
        cursor: pointer;
    }

    .user-detail{
        background-color: white;
        border-radius: 12px; 
        padding: 25px; 
        margin: 15px 8px 15px 75px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        border: 1px solid #f0f0f0; 
        width:83%;
    }

    .user-detail-information{
        display:grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap:20px;
    }

    .user-detail h3 {
        color: #5D9C59;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .current-status{
        background-color: white;
        border-radius: 12px; 
        padding: 25px; 
        margin: 0px 0px 15px 0px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        border: 1px solid #f0f0f0; 
        width:42%;
    }

    .point-earned{
        background-color: white;
        border-radius: 12px; 
        padding: 25px; 
        margin: 0px 30px 15px 0px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        border: 1px solid #f0f0f0; 
        width:42%;
    }

    .bottom-part{
        margin: 15px 8px 15px 75px;
        display:flex;
        flex-direction:horizontal;
        gap:7px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-item span {
        background-color: #f8f9fa;
        padding: 12px 15px;
        margin-top: 6px;
        border-radius: 8px;
        border-left: 4px solid #78A24C;
    }

    .items-container {
        display: flex;
        gap: 50px;
        flex-wrap: wrap;
    }

    .item-image-card {
        flex: 1;
        width:200px;
        height: 235px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        position:relative;
    }

    .item-image-card iframe {
        width: 100%;
        height: 100%;
        border: none;
        position: absolute;
        pointer-events: none;
        transform-origin: center center;
        transform:scale(1.2);
    }

    .image-placeholder {
        width: 100%;
        height: 100%;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #777;
    }

    .image-placeholder i {
        font-size: 50px;
        margin-bottom: 15px;
    }

    .item-cards {
        flex: 2;
        min-width: 300px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .item-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .item-name {
        font-weight: bold;
        margin-bottom: 12px;
        color: #333;
    }

    .item-details {
        display: flex;
        gap: 10px;
    }

    .detail-box {
        flex: 1;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .detail-box span {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }

    .detail-box.highlight {
        background: #e8f5e9;
        font-weight: bold;
    }

    .item-image-card iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .image-placeholder {
        width: 100%;
        height: 100%;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #777;
    }

    .image-placeholder i {
        font-size: 50px;
        margin-bottom: 15px;
    }

    .item-details-card {
        flex: 2;
        min-width: 300px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .detail-row {
        display: flex;
        gap: 15px;
    }

    .detail-row-full {
        width: 100%;
    }

    .detail-box {
        flex: 1;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .detail-box span {
        display: block;
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }

    .detail-box strong {
        font-size: 16px;
        color: #333;
    }

    .detail-box.highlight {
        background: #e8f5e9;
        border-left: 4px solid #78A24C;
    }    

    .status-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        color: #5D9C59;
    }

    .status-header i {
        font-size: 20px;
    }

    .status-header h4 {
        margin: 0;
        font-size: 16px;
    }

    .status-value {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .status-value.complete {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-value.expired {
        background-color: rgba(255,210,210,0.45);
        color: #e85059;
    }

    .points-value {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        background: #e8f5e9;
    }

    .points-value.complete {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .points-value span {
        font-size: 14px;
        font-weight: normal;
        color: #666;
    }

    .points-value.expired {
        background-color: rgba(255,210,210,0.45);
        color: #e85059;
    }

    .detail-row-full {
        width: 100%;
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

    <?php
        $con = mysqli_connect("localhost","root","","cp_assignment");

        if(mysqli_connect_errno()){
            echo "Failed to connect to MySQL:".mysqli_connect_error();
            exit();
        }

        if(isset($_GET['dropoff_id'])){
            $dropoff_id = mysqli_real_escape_string($con, $_GET['dropoff_id']);
            $sql = "SELECT 
                dropoff.dropoff_id,
                dropoff.dropoff_date,
                dropoff.status,
                dropoff.total_point_earned,
                dropoff.item_image,
                user.username,
                user.phone_number,
                user.email,
                location.location_name,
                item.item_name,
                item.item_id,
                item.point_given,  
                item_dropoff.quantity,
                item_dropoff.item_dropoff_id as item_dropoff_id
                FROM dropoff 
                LEFT JOIN user ON dropoff.user_id = user.user_id
                LEFT JOIN location ON dropoff.location_id = location.location_id
                LEFT JOIN item_dropoff ON item_dropoff.dropoff_id = dropoff.dropoff_id
                LEFT JOIN item ON item_dropoff.item_id = item.item_id
                WHERE dropoff.dropoff_id = '$dropoff_id'";
            
            $result = mysqli_query($con, $sql);

            if (!$result) {
                die("Query failed: " . mysqli_error($con));
            }
        
            $items = [];
            while($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        
            if (empty($items)) {
                echo "<script>alert('No dropoff request details found.'); window.location.href='Admin-Dropoff-Complete.php';</script>";
                exit();
            }
            
            $request = $items[0];
        } else {
            echo "<script>alert('No dropoff request selected.'); window.location.href='Admin-Dropoff-Complete.php';</script>";
            exit();
        }
    ?>

    <div class="content">
        <h2 class='title'>
            <a href='Admin-Dropoff-Complete.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Drop-off Request Detail
        </h2>

        <hr style="width: 92%; margin-left:45px;">
        <div class="user-detail">
            <h3><i class="fas fa-user"></i> User Information</h3>
            <div class="items-container">
                <div class="item-details-card" style="width:100%;">
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Name </span>
                            <strong><?php echo $request['username']; ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Contact</span>
                            <strong>
                                <?php echo !empty($request['phone_number']) ? '0' . $request['phone_number'] : '-'; ?>
                            </strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Email </span>
                            <strong><?php echo $request['email']; ?></strong>                 
                        </div>
                        <div class="detail-box">
                            <span>Drop-off Date </span>
                            <strong><?php echo date('d M Y', strtotime($request['dropoff_date'])); ?></strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Drop-off Address </span>
                            <strong><?php echo $request['location_name']; ?></strong>                 
                        </div>
                        <div class="detail-box" style="visibility: hidden;">  <!-- Empty box for alignment -->
                            <span>&nbsp;</span>
                            <strong>&nbsp;</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-detail">
            <h3><i class="fas fa-map-marker-alt"></i> Drop-off Items</h3>
            <div class="items-container">
                <div class="item-image-card">
                    <?php if(!empty($items[0]['item_image'])): ?>
                        <iframe src="https://drive.google.com/file/d/<?= $items[0]['item_image']; ?>/preview" width="250" height="150" style="border:none;" margin="10px"></iframe>
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-image"></i>
                            <span>No image available</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                
                <div class="item-details-card">
                    <?php 
                        $itemCounter=1;
                        foreach($items as $item): 
                    ?>
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Item <?php echo $itemCounter++; ?></span>
                            <strong><?php echo $item['item_name']; ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Quantity</span>
                            <strong><?php echo $item['quantity']; ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
            
                    
                </div>
            </div>
        </div>

        <div class="bottom-part">
            <div class="current-status">
                <div>
                    <div class="status-header">
                    <i class="fa-solid fa-arrows-rotate"></i>
                        <h4>Current Status</h4>
                    </div>
                    <div class="status-value <?php echo strtolower($request['status']); ?>">
                        <?php echo $request['status']; ?>
                    </div>
                </div>
            </div>
            <div class="point-earned">
                <div>  
                    <div class="status-header">
                    <i class="fas fa-coins"></i>
                        <h4>Points Earned</h4>
                    </div>
                    <?php
                        $statusClass = '';
                        if (strtolower($request['status']) === 'complete') {
                            $statusClass = 'complete';
                        } elseif (strtolower($request['status']) === 'expired' ) {
                            $statusClass = 'expired';
                        }
                    ?>
                    <div class="points-value <?php echo $statusClass; ?>">
                        <?php echo !empty($request['total_point_earned']) ? $request['total_point_earned'] : '0'; ?>
                        <span>points</span>
                    </div>
                </div>
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
    </script>

</body>
</html>