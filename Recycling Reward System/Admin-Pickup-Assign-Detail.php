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
    <title>Pickup Requests Detail - Green Coin</title>
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
        animation:floatIn 0.8s ease-out;
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

    .assign-action{
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 60px;
    }

    .action-buttons {
        width:200px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 15px 26px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        margin-bottom:20px;
    }

    .action-buttons i {
        font-size: 16px;
    }

    .action-buttons:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .assign-button{
        background-color: #3BB143;
        color: white;
    }

    .assign-button:hover {
        background-color: #4E9F3D;
    }

    .absent-button{
        background-color:#EE6666;
        color: white;
    }

    .absent-button:hover {
        background-color: #DD5555;
    }

    .reject-confirm {
        width: 100%;
        padding: 14px;
        background-color: #EE6666;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow:auto;
        justify-content: center;
        align-items: center;
        background-color: rgba(0, 0, 0, 0.57);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: #fefefe;
        /* margin: 5% auto;  */
        padding: 20px; 
        border: 1px solid #888;
        width: 600px;
        max-height: 80vh; 
        overflow: hidden; 
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .modal-content p {
        color: rgb(89,89,89);
        line-height: 1.5;
        font-size:16px;
    }

    .imgcontainer {
        text-align: right;
        margin: 10px 20px 0 40px;
        position: relative;
    }

    .close {
        position: absolute;
        right: 5px;
        top: 5px;
        color:rgb(133, 133, 133);
        font-size: 35px;
        cursor:pointer;
    }

    .close:hover {
        color: black;
        text-decoration: none;
    }

    .container{
        padding-left: 40px;
        padding-right: 40px;
        padding-top: 35px;
    }

    .container h2{
        font-size: 30px;
        line-height: 0.5;
    }

    .container label{
        color: rgb(89,89,89);
        width:27%;
    }

    .modal-details {
        display: grid;
        /* grid-template-columns: 1fr 1fr; */
        gap: 15px;
        margin-bottom: 35px;
        margin-top:15px;
    }

    .modal-details div {
        flex-direction: vertical;
    }

    .modal-details span {
        display: block;
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }

    .modal-details strong {
        font-size: 16px;
        color: #333;
    }

    .modal-body{
        padding-bottom: 15px;
        max-height: 36vh;
        overflow-y: auto;
        overflow-x:hidden;
    }

    .total-points-container {
        margin-top: 15px;
        display: flex;
        flex-direction: row; 
        gap:5px;
        align-items: center;
    }

    .total-points-value {
        color: black;
        padding: 10px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;
        display: block;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        text-align: center;
        width: 75.5%;
        border: 1px solid #d0c4a5;
        outline:none;
    }

    .confirm-button{
        width: 100%;
        padding: 14px;
        background-color:rgb(78,120,49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
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
        transform-origin: center center;
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
        min-width: 0; 
        word-wrap: break-word; 
        overflow-wrap: break-word; 
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
        display: block;
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    .detail-box.highlight {
        background: #e8f5e9;
        border-left: 4px solid #78A24C;
    }

    .status-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .status-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

    .status-value.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .points-value {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        color: #5D9C59;
    }

    .points-value span {
        font-size: 14px;
        font-weight: normal;
        color: #666;
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

    <?php
        $con = mysqli_connect("localhost","root","","cp_assignment");

        if(mysqli_connect_errno()){
            echo "Failed to connect to MySQL:".mysqli_connect_error();
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'assign_points') {
                $pickup_request_id = mysqli_real_escape_string($con, $_POST['pickup_request_id']);
                $total_points = mysqli_real_escape_string($con, $_POST['total_points']);

                if (empty($total_points) || $total_points <0) {
                    echo "<script>
                            alert('Please insert the total points.');
                            window.history.back();
                          </script>";
                    exit();
                }
                
                mysqli_begin_transaction($con);
                
                try {
                    $update_request = "UPDATE pickup_request 
                                        SET status = 'Completed', 
                                        total_point_earned = $total_points 
                                        WHERE pickup_request_id = '$pickup_request_id'";
                    
                    if (!mysqli_query($con, $update_request)) {
                        throw new Exception("Failed to update pickup request");
                    }
                    
                    $user_query = "SELECT user_id FROM pickup_request WHERE pickup_request_id = '$pickup_request_id'";
                    $user_result = mysqli_query($con, $user_query);
                    $user_data = mysqli_fetch_assoc($user_result);
                    $user_id = $user_data['user_id'];
                    
                    $update_points = "UPDATE user SET points = points + $total_points WHERE user_id = '$user_id'";
                    
                    if (!mysqli_query($con, $update_points)) {
                        throw new Exception("Failed to update user points.");
                    }
        
                    $announcement = "Your pickup request has been successfully completed and verified. You've been awarded $total_points points for your contribution! 🌱🎉 Thank you for recycling with us and helping to create a cleaner, greener future. ♻💚";     
                    $announcement = mysqli_real_escape_string($con, $announcement);                
                    $notificationQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) 
                                        VALUES ('$user_id', NOW(), 'Pickup Completed & Points Awarded ✅ ', '$announcement', 'unread')";
                    
                    if (!mysqli_query($con, $notificationQuery)) {
                        throw new Exception("Failed to create notification.");
                    }
                    
                    mysqli_commit($con);
                    echo "<script>
                            alert('Points assigned successfully.');
                            window.location.href='Admin-Pickup-Complete.php'; 
                          </script>";
                    exit();
                    
                } catch (Exception $e) {
                    mysqli_rollback($con);
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
                }
            }

            if ($_POST['action'] === 'absent') {
                $pickup_request_id = mysqli_real_escape_string($con, $_POST['pickup_request_id']);
            
                $update_query = "UPDATE pickup_request 
                                 SET status = 'Absent',
                                 total_point_earned=0
                                 WHERE pickup_request_id = '$pickup_request_id'";
            
                if (mysqli_query($con, $update_query)) {
                     echo "<script>
                             alert('Request marked as absent successfully.');
                             window.location.href='Admin-Pickup-Complete.php'; 
                           </script>";
                     exit();
                } else {
                     echo "<script>alert('Error updating record: " . mysqli_error($con) . "');</script>";
                     exit();
                }
            }            
        }

        if(isset($_GET['pickup_request_id'])){
            $pickup_id = mysqli_real_escape_string($con, $_GET['pickup_request_id']);
            $sql = "SELECT 
                pickup_request.pickup_request_id,
                pickup_request.time_slot_id,
                pickup_request.address,
                pickup_request.contact_no,
                pickup_request.status,
                pickup_request.datetime_submit_form,
                pickup_request.item_image,
                pickup_request.remark,
                user.username,
                user.email,
                time_slot.date AS pickup_date, 
                time_slot.time AS pickup_time,
                driver.driver_name
            FROM pickup_request 
            LEFT JOIN user ON pickup_request.user_id = user.user_id
            LEFT JOIN time_slot ON pickup_request.time_slot_id = time_slot.time_slot_id
            LEFT JOIN driver ON pickup_request.driver_id = driver.driver_id
            WHERE pickup_request.pickup_request_id = '$pickup_id'";

            $items_sql = "SELECT 
                item_pickup.quantity,
                item.item_name,
                item.item_id,
                item.point_given  
            FROM item_pickup
            LEFT JOIN item ON item_pickup.item_id = item.item_id
            WHERE item_pickup.pickup_request_id = '$pickup_id'";

            $items_result = mysqli_query($con, $items_sql);
            $items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
            
            $result = mysqli_query($con, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $request = mysqli_fetch_assoc($result); 
                $time_slot_id = $request['time_slot_id'];
            } else {
                echo "<script>alert('No pickup request details found.'); window.location.href='Admin-Pickup-Assign.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('No pickup request selected.'); window.location.href='Admin-Pickup-Assign.php';</script>";
            exit();
        }
    ?>

    <div class="content">
    <h2 class='title'>
            <a href='Admin-Pickup-Assign.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Pickup Request Detail
        </h2>

        <hr style="width: 92%; margin-left:45px;">
        <div class="user-detail">
            <h3><i class="fas fa-user"></i> User Information</h3>
            <div class="items-container">
                <div class="item-details-card" style="width: 100%;">
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Name</span>
                            <strong><?php echo $request['username']; ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Contact</span>
                            <strong><?php echo $request['contact_no']; ?></strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Email</span>
                            <strong><?php echo $request['email']; ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Request Date</span>
                            <strong><?php echo date('d M Y H:i', strtotime($request['datetime_submit_form'])); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            
        <div class="user-detail">
            <h3><i class="fas fa-map-marker-alt"></i> Pickup Information</h3>
            <div class="items-container">
                <div class="item-details-card" style="width: 100%;">
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Pickup Date</span>
                            <strong><?php echo date('d M Y', strtotime($request['pickup_date'])); ?></strong>
                        </div>
                        <div class="detail-box" style="width:50%">
                            <span>Pickup Time</span>
                            <strong><?php echo date('H:i', strtotime($request['pickup_time'])); ?></strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Address</span>
                            <strong><?php echo $request['address']; ?></strong>
                        </div>
                        <div class="detail-box" style="width:50%">
                            <span>Driver Incharge </span>
                            <strong><?php echo $request['driver_name']; ?></strong>                   
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-detail">
            <h3><i class="fas fa-box-open"></i> Items for Pickup</h3>
            <div class="items-container">
                <div class="item-image-card">
                    <?php if(!empty($request['item_image'])):?>
                        <iframe src="https://drive.google.com/file/d/<?php echo $request['item_image']; ?>/preview" width="250" height="150" style="border:none;" margin="10px"></iframe>
                    <?php else:?>
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
                            <span>Quantity </span>
                            <strong><?php echo $item['quantity']; ?></strong>                
                        </div>
                    </div>
                    <?php endforeach;?>

                    <div class="detail-row">
                        <div class="detail-box">
                            <span>Remark </span>
                            <strong><?php echo !empty($request['remark']) ? $request['remark'] : '-'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>    
        </div>

    <div class="assign-action">
        <button type="submit" class="action-buttons assign-button" onclick="openAssignPointModal()">
            <i class="fa-solid fa-coins"></i>Assign Point
        </button>
        <button type="button" class="action-buttons absent-button" onclick="openAbsentModal()">
            <i class="fas fa-times"></i> User Absent
        </button>
    </div>
        
    <div id="modal-details" class="modal">
        <form class="modal-content" action="#" method="post">
            <div class="imgcontainer">
                <span class="close" onclick="closeModal('modal-details')">&times;</span>
            </div>
            <div class="container">
                <h2>Assign Points</h2>
                <p>Review and adjust points for this pickup request.</p>

                <div class="modal-body"> 
                    <?php 
                    $total_points = 0;
                    $itemCounter = 1;
                    foreach($items as $item): 
                        $item_points = $item['quantity'] * $item['point_given'];
                        $total_points += $item_points;
                    ?>
                    <div class="modal-details">
                        <div class="detail-row-full">
                            <div class="detail-box">
                                <span>Item <?php echo $itemCounter++; ?></span>
                                <strong><?php echo $item['item_name']; ?></strong>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-box">
                                <span>Quantity</span>
                                <strong><?php echo $item['quantity']; ?></strong>
                            </div>
                            <div class="detail-box">
                                <span>Points Per Item</span>
                                <strong><?php echo $item['point_given'];?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="total-points-container">
                        <label><b>Total Points: </b></label>
                        <input type="number" name="total_points" id="totalPointsInput" 
                            value="<?php echo $total_points; ?>" 
                            min="0" class="total-points-value"
                            style=" padding: 10px; border: 1px solid lightgrey; border-radius: 8px; text-align: center; font-weight: bold; width:73%;">
                    </div>
                </div> 

                <input type="hidden" name="pickup_request_id" value="<?php echo $pickup_id; ?>">
                <input type="hidden" name="action" value="assign_points">

                <button type="submit" class="confirm-button">Confirm</button>
            </div>  
        </form>
    </div>

    <div id="absentRequest" class="modal">
        <form class="modal-content animate" action="#" method="post">
            <div class="imgcontainer">
                <span class="close" onclick="closeModal('absentRequest')">&times;</span>
            </div>
            <div class="container">
                <h2>Mark as Absent</h2>
                <p>Are you sure the user was absent during the pickup?</p>
                <br>
                <input type="hidden" name="pickup_request_id" value="<?php echo $pickup_id; ?>">
                <input type="hidden" name="action" value="absent">
                <button type="submit" class="reject-confirm">Confirm</button>
            </div>
        </form>
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

        const body = document.body;
        function openAssignPointModal() {
            document.getElementById('modal-details').style.display = 'flex';
            body.style.overflow = "hidden";
        }

        function closeModal() {
            document.getElementById("modal-details").style.display = "none";
            body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                body.style.overflow = "auto";
            }
        }

        function calculateTotal() {
            let total = 0;
            const pointInputs = document.querySelectorAll('.point-input');
            const quantityElements = document.querySelectorAll('.detail-row .detail-box:nth-child(1) strong');
            
            pointInputs.forEach((input, index) => {
                const quantity = parseInt(quantityElements[index].textContent) || 0;
                const points = parseInt(input.value) || 0;
                total += quantity * points;
            });
            
            document.getElementById('totalPointsInput').value = total;
        }

        function openAbsentModal() {
            document.getElementById("absentRequest").style.display = "flex";
            body.style.overflow = "hidden";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            body.style.overflow = "auto";
        }

    </script>

</body>
</html>