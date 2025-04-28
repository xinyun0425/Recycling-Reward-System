<?php
    session_start();

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    $user_id = $_SESSION['user_id'];

    $pickup_id = mysqli_real_escape_string($conn, $_POST['pickup-request-id']);
    $getDateTimeQuery = mysqli_query($conn, "SELECT ts.date AS date, ts.time AS time FROM pickup_request pr INNER JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id WHERE pr.pickup_request_id = '$pickup_id'"); 
    $getDateTimeResult = mysqli_fetch_assoc($getDateTimeQuery);
    $date = $getDateTimeResult['date'];
    $time = $getDateTimeResult['time'];
    $deleteItemPickup = mysqli_query($conn, "DELETE FROM item_pickup WHERE pickup_request_id = '$pickup_id'");
    $deleteDropoffRequest = mysqli_query($conn, "DELETE FROM pickup_request WHERE pickup_request_id = '$pickup_id'");

    $system_announcement = "Your pickup request scheduled for ".$date." at ".$time." has been canceled as per your request. ❌🗓️
                            If this was a mistake or you\'d like to schedule a new pickup, you can do so anytime through our request form.
                            We appreciate your commitment to sustainability and hope to assist you again soon! 🌍✨";
    $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
    ('$user_id', NOW(), 'Pickup Request Canceled ❌', '$system_announcement', 'unread')";
    mysqli_query($conn, $requestSubmittedNotiQuery);

    $admin_announcement = "A user has canceled their pickup request that was originally scheduled for ".$date." at ".$time.".
                            No further action is required on this request.";
    $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
    ('$user_id', NOW(), '🗑️ Pickup Request Canceled', '$admin_announcement', 'unread')";
    mysqli_query($conn, $newRequestNotiQuery);
    
    $_SESSION['delete_pickup'] = true;
    echo '<script>window.location.href="User-Profile.php";</script>';
    
?>