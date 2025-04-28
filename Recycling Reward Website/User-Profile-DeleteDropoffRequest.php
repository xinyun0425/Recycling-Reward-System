<?php
    session_start();

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    $user_id = $_SESSION['user_id'];

    $dropoff_id = mysqli_real_escape_string($conn, $_POST['dropoff-id']);
    $deleteDropoffRequest = mysqli_query($conn, "DELETE FROM dropoff WHERE dropoff_id = '$dropoff_id'");

    $system_announcement = "Your drop-off request has been successfully canceled. ❌📦
                            If this was a mistake or you wish to reschedule, feel free to submit a new request anytime.
                            Thank you for your commitment to responsible recycling! ♻️🌍";
    $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
    ('$user_id', NOW(), 'Drop-Off Request Canceled ❌', '$system_announcement', 'unread')";
    mysqli_query($conn, $requestSubmittedNotiQuery);

    $admin_announcement = "A user has canceled their drop-off request. No further action is required for this request. ✅";
    $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
    ('$user_id', NOW(), '🗑️ Drop-Off Request Canceled', '$admin_announcement', 'unread')";
    mysqli_query($conn, $newRequestNotiQuery);
    $_SESSION['delete_dropoff'] = true;
    echo '<script>window.location.href="User-Profile.php";</script>';
    
?>