<?php 
    session_start();
    
    require 'google-api-php-client/vendor/autoload.php'; 

    function uploadToGoogleDrive($fileTmpName, $fileName) {
        $client = new Google\Client();
        $client->setHttpClient(new GuzzleHttp\Client(['verify' => false])); 
        $client->setAuthConfig('keen-diode-454703-r9-847455d54fc8.json');
        $client->addScope(Google\Service\Drive::DRIVE_FILE);
        
        $service = new Google\Service\Drive($client);
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => ['1mMMgBT0R6VDtmF_i8se9o8FRZ3nh_3Bv']
        ]);

        $content = file_get_contents($fileTmpName);
        if ($content === false) {
            die("Error: Unable to read file from temporary storage.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);

        try {
            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);
            $fileID = $file->id;
            $permission = new Google\Service\Drive\Permission();
            $permission->setType('anyone');
            $permission->setRole('reader');
            $service->permissions->create($fileID, $permission);
            return $fileID;
        } catch (Exception $e) {
            echo "Error uploading file: " . $e->getMessage();
            return false;
        }
    }


    if (isset($_SESSION['user_id'])){

    }else{
        echo "<script>window.location.href='User-Login.php';</script>";
    }

    $user_id = $_SESSION["user_id"] ?? null;

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    if (isset($_GET['date'])) {
        $selectedDate = mysqli_real_escape_string($conn, $_GET['date']);
        $query = "SELECT time FROM time_slot WHERE date = '$selectedDate' AND no_driver_per_slot > '0' ORDER BY time";
        $result = mysqli_query($conn, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $time = $row['time'];
            $timestamp = strtotime($time);
            $formattedTime = date("H:i ", $timestamp);
            $text = $formattedTime . " - " . date("H:i ", $timestamp + 60 * 60);
            echo '<option value="'.$time.'">'.$text.'</option>';
        }
        exit(); 
    }

    $maxItemCountQuery = mysqli_query($conn, "SELECT count(*) AS count FROM item");
    $maxItemCount = mysqli_fetch_assoc($maxItemCountQuery)['count'];

    if (isset($_POST['userId'])){
        

        $newUsername = $_POST['username'];
        $newPhoneNumber = $_POST['user-phoneNo'];
        $newAddress = $_POST['user-address'];
        $newDOB = $_POST['user-dob'];
        $dateInput = $newDOB;
        $date = DateTime::createFromFormat('Y-m-d', $dateInput);

        if ($date && $date->format('Y-m-d') === $dateInput) {
        } else {
            $date = DateTime::createFromFormat('d-m-Y', $dateInput);
            if ($date) {
                $newDOB = $date->format('Y-m-d'); 
            }
        }

        

        // Validate date format
        $dateRegex = "/^\d{4}-\d{2}-\d{2}$/";
        if (!preg_match($dateRegex, $newDOB)) {
            $newDOB = NULL;  // Prevent incorrect date storage
        }
        $newPW = $_POST['user-password'];
        $newProfileImg = $_POST['currentProfileImage'];
        if (trim($newPhoneNumber) == ""){
            if(trim($newAddress) == ""){
                $updateUserDetailsQuery = mysqli_query($conn, "UPDATE user SET username='$newUsername', 
                                            phone_number = NULL, address = NULL,
                                            dob = '$newDOB', password = '$newPW', profile_image = '$newProfileImg' 
                                            WHERE user_id = '$user_id'");
            }else{
                $updateUserDetailsQuery = mysqli_query($conn, "UPDATE user SET username='$newUsername', 
                                                phone_number = NULL, address = '$newAddress',
                                                dob = '$newDOB', password = '$newPW', profile_image = '$newProfileImg' 
                                                WHERE user_id = '$user_id'");
            }
        }else{
            if(trim($newAddress) == ""){
                $updateUserDetailsQuery = mysqli_query($conn, "UPDATE user SET username='$newUsername', 
                                                phone_number = '$newPhoneNumber', address = NULL,
                                                dob = '$newDOB', password = '$newPW', profile_image = '$newProfileImg' 
                                                WHERE user_id = '$user_id'");
            }else{
                $updateUserDetailsQuery = mysqli_query($conn, "UPDATE user SET username='$newUsername', 
                                                phone_number = '$newPhoneNumber', address = '$newAddress',
                                                dob = '$newDOB', password = '$newPW', profile_image = '$newProfileImg' 
                                                WHERE user_id = '$user_id'");
            }
        }

        echo "<script>window.location.href = 'User-Profile.php';</script>";
    }else if (isset($_POST['submitBtn'])){
        $star_given = $_POST['star-given'];
        $review_text = $_POST['review-text'];
        $pr_dr = $_POST['pr_dr_type'];
        $pr_dr_id = $_POST['pr_dr_id'];


        if ($pr_dr == "Dropoff"){
            $insertNewReviewQuery = "INSERT INTO review(dropoff_id, pickup_request_id, review, date, star) VALUES
                                    ('$pr_dr_id', NULL, '$review_text', NOW(), '$star_given')";    
        }else{
            $insertNewReviewQuery = "INSERT INTO review(dropoff_id, pickup_request_id, review, date, star) VALUES
                                    (NULL, '$pr_dr_id', '$review_text', NOW(), '$star_given')";    
        }
        $insertNewReview = mysqli_query($conn, $insertNewReviewQuery);
         
        $system_announcement = "Thank you for your feedback! 💬
        Your review has been successfully submitted.
        We truly appreciate you taking the time to share your experience — it helps us grow and serve you better. 🌟🌿";
        $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), 'Review Submitted! 📝', '$system_announcement', 'unread')";
        mysqli_query($conn, $requestSubmittedNotiQuery);

        $admin_announcement = "A user has share their recycling experience. Check the review section to view and respond.";
        $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), '📝 New User Review Received!', '$admin_announcement', 'unread')";
        mysqli_query($conn, $newRequestNotiQuery);

        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const popup = document.getElementById('ReviewSuccessPopup');
                    popup.style.display = 'block';
                    setTimeout(function() {
                        popup.style.display = 'none';
                        window.location.href = 'User-Review.php';
                    }, 4000); 
                });
            </script>";
    }else if (isset($_POST['prSubmitBtn'])) {
        $pickup_request_id = $_POST['pickup-request-id'];
        $date = $_POST['date-pickup'];
        $time = $_POST['time-pickup'];
        $phone = $_POST['phone-no-pickup'];
        $address = $_POST['address-pickup'];
        $fileTmpName = $_FILES["image-pickup"]["tmp_name"];
        $fileName = $_FILES["image-pickup"]["name"];
        $remark = $_POST['remark-pickup'];
        $previewImage = $_POST['pickup-image-preview'];
        if (trim($previewImage) == ""){
            $fileID = uploadToGoogleDrive($fileTmpName, $fileName);
        }else{
            $fileID = $previewImage;
        }

        $items = [];
        $quantities = [];
        
        $itemCount = 1;
        while (isset($_POST["item_$itemCount"])) {
            $items[] = $_POST["item_$itemCount"]; 
            $quantities[] = $_POST["quantity_$itemCount"]; 
            $itemCount++;
        }

        $getTimeSlotIDQuery = mysqli_query($conn, "SELECT time_slot_id FROM time_slot WHERE date = '$date' AND time = '$time' AND no_driver_per_slot > 0");
        $getTimeSlotID = mysqli_fetch_assoc($getTimeSlotIDQuery)['time_slot_id'];
    
        $addNewPickupRequestQuery = "UPDATE pickup_request SET time_slot_id = '$getTimeSlotID', datetime_submit_form = NOW(), address = '$address', contact_no = '$phone',
                                    remark = '$remark', item_image = '$fileID', status = 'Unread' WHERE pickup_request_id = '$pickup_request_id' ";


        $system_announcement = "Your pickup request has been successfully updated! 🚚🔄
                                The revised schedule is now set for ".$date." at ".$time.".
                                Please note that your updated request is currently under review. You will receive a confirmation once it has been approved by our team.
                                Thank you for continuing to support a cleaner, greener future! 🌱💚";
        $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), 'Pickup Request Updated ✏️', '$system_announcement', 'unread')";
        mysqli_query($conn, $requestSubmittedNotiQuery);

        $admin_announcement = "A user has updated their pickup request.
                                The new scheduled date and time is ".$date." at ".$time.".
                                Please review the updated request and approve or reject it as soon as possible.";
        $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), '🔄 Pickup Request Updated', '$admin_announcement', 'unread')";
        mysqli_query($conn, $newRequestNotiQuery);
        
        $removeExistingItem = mysqli_query($conn, "DELETE FROM item_pickup WHERE pickup_request_id = '$pickup_request_id'");
        // Execute the query
        if (mysqli_query($conn, $addNewPickupRequestQuery)) {    
            for ($i = 0; $i < count($items); $i++) {
                $item = $items[$i];
                $quantity = $quantities[$i];

                $getItemIDQuery = mysqli_query($conn, "SELECT item_id FROM item WHERE item_name = '$item'");
                $item_id = mysqli_fetch_assoc($getItemIDQuery)['item_id'];
    
                $addNewItemPickupQuery = "INSERT INTO item_pickup (item_id, quantity, pickup_request_id) 
                              VALUES ('$item_id', '$quantity', '$pickup_request_id')";
                mysqli_query($conn, $addNewItemPickupQuery);
            }

        } else {
            echo "Error: " . mysqli_error($conn);
        }
        echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const popup = document.getElementById('PickupEditSuccessPopup');
                        popup.style.display = 'block';
                        setTimeout(function() {
                            popup.style.display = 'none';
                            window.location.href = 'User-Profile.php';
                        }, 4000); 
                    });
                </script>";

    }else if (isset($_POST['drSubmitBtn'])){
        $date = mysqli_real_escape_string($conn,$_POST['date']);
        $location_id = mysqli_real_escape_string($conn,$_POST['location']);
        $user_id = $_SESSION['user_id'];
        $dropoff_id = $_POST['dropoff-id'];

        $sql_insert= mysqli_query($conn, "UPDATE dropoff SET dropoff_date = '$date', status = 'unread', location_id = '$location_id'
        WHERE dropoff_id = '$dropoff_id'");

        
        $system_announcement = "Your drop-off request has been successfully updated! 🔄📦
                                Please proceed to your selected drop-off location on the updated date.
                                Points will be assigned upon verification of your drop-off.
                                Thank you for continuing to support a cleaner, greener planet! 🌿💚";
        $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), 'Drop-Off Request Updated ✏️', '$system_announcement', 'unread')";
        mysqli_query($conn, $requestSubmittedNotiQuery);

        $admin_announcement = "A user has modified their drop-off request. Please review the updated details and process it accordingly.";
        $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), '🔄 Drop-Off Request Updated', '$admin_announcement', 'unread')";
        mysqli_query($conn, $newRequestNotiQuery);

        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const popup = document.getElementById('DropoffEditSuccessPopup');
                    popup.style.display = 'block';
                    setTimeout(function() {
                        popup.style.display = 'none';
                        window.location.href = 'User-Profile.php';
                    }, 4000); 
                });
            </script>";
        
    }

    if (isset($_SESSION['delete_dropoff'])){
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('DropoffCancelSuccessPopup');
                popup.style.display = 'block';
                setTimeout(function() {
                    popup.style.display = 'none';
                    window.location.href = 'User-Profile.php';
                }, 4000); 
            });
        </script>";
        unset($_SESSION['delete_dropoff']); 
    }

    if (isset($_SESSION['delete_pickup'])){
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('PickupCancelSuccessPopup');
                popup.style.display = 'block';
                setTimeout(function() {
                    popup.style.display = 'none';
                    window.location.href = 'User-Profile.php';
                }, 4000); 
            });
        </script>";
        unset($_SESSION['delete_pickup']); 
    }

    $unreadCount = 0; 

    if ($user_id) { 
        $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
        $unreadResult = mysqli_query($conn, $unreadQuery);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $unreadCount = $unreadData['unread_count'];

        $userDetailQuery = mysqli_query($conn, "SELECT username, email, password, phone_number, dob, address, points, profile_image, created_at FROM user WHERE user_id = '$user_id'");
        $userDetailResult = mysqli_fetch_assoc($userDetailQuery);
        $username = $userDetailResult['username'];
        $userEmail = $userDetailResult['email'];
        $userPW = $userDetailResult['password'];
        $userPhoneNo = $userDetailResult['phone_number'];
        if ($userPhoneNo == "" || $userPhoneNo ==  "0"){
            $userPhoneNo = "-";
        }else{
            $userPhoneNo = "0".$userPhoneNo;
        }
        
        $userDOB = $userDetailResult['dob'];
        $userDOB = date_format(date_create($userDOB),'d-m-Y');

        $userAddress = $userDetailResult['address'];
        if ($userAddress == ""){
            $userAddress = "-";
        }
        $userPoints = $userDetailResult['points'];
        $userDateCreated = $userDetailResult['created_at'];
        $userDateCreated = date_format(date_create($userDateCreated),'d-m-Y');
        $userProfileImage = $userDetailResult['profile_image'];

    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
    <title>Profile - Green Coin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playpen+Sans:wght@100..800&display=swap');
        *{
            margin:0px;
            padding:0px;
            font-family:"Open Sans", sans-serif;
        }
        .material-icons{
            font-size: 30px;
        }

        header {
            position: sticky;
            z-index: 1000;
            top: 0;
            height: 73px;
            background-color:#78A24C;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .logo-container img {
            height: 40px;
            cursor: pointer;
        }

        .nav-links {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
        }

        .nav-links li {
            display: inline;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            transition: all 0.3 ease;
        }

        .nav-links a.active, .nav-links a:hover {
            color: white !important;
            cursor: pointer;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .noti-button, .profile-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            color: #ffffff;
            background: #78A24C;
            border: none;
            outline: none;
            border-radius: 50%;
            position: relative;
        }

        .noti-button:hover, .profile-button:hover {
            cursor: pointer;
        }

        .noti-button__badge {
            position: absolute;
            top: 5px;
            right: 0px;
            width: 20px;
            height: 20px;
            background: red;
            color: #ffffff;
            font-family: "Playpen Sans", cursive;
            display: <?php echo ($unreadCount > 0) ? 'flex' : 'none'; ?>;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
        }
        
        .page-div{
            display:flex;
            flex-direction:row;
        }

        .profile-container{
            width:25%;
            border:2px solid  #ebebeb;
            display:flex;
            flex-direction:column;
            padding:3.35vh 0px;
            margin:20px 0px 20px 60px;
            border-radius:25px;
            background-color: #ebebeb;
            position:fixed;
            height: 79vh;
            justify-content:center;
        }
        
        .profile-div img{
            border-radius:50%;
            border:5px solid lightgrey;
            padding:10px;
            /* border: 5px solid rgb(210, 200, 186); */

        }

        .username{
            font-size:25px;
            font-weight:bold;
            text-align:center;
            margin:10px 0px 5px 0px;
            padding:10px 0px;
            width:100%;
            border:1px solid lightgrey;
            border-radius:20px;
            outline:none;
        }

        .profile-div label{
            width:auto;
            font-size:16px;
        }

        .editProfile{
            background-color:rgb(116, 115, 114);
            border-radius:25px;
            font-size:16px;
            padding:10px 20px;
            margin:20px 0px;
            border:2px solid rgb(116, 115, 114);
            cursor:pointer;
            color:white;
            transition: all 0.3s ease;
        }

        .editProfile:hover{
            background-color: transparent;
            border:2px solid rgb(116, 115, 114);
            color:rgb(116, 115, 114);
        }

        .logout-btn{
            float:right;
            font-size:20px;
            margin-right:-20px;
        }

        .editProfile i {
            padding-right:8px;
        }

        .profile-details-div {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%; 
            max-width: 600px;
        }

        .profile-details-div label{
            color: grey;
        }

        .user-phoneNo, .user-dob, user-password{
            padding:10px 10px;
            margin:5px 0px 5px 5px;
            font-size:16px;
            border:1px solid lightgrey;
            border-radius:20px;
            flex: 1; 
            outline:none;
            width: inherit;
        }

        .user-password{
            padding:10px 10px;
            margin:5px 0px 5px 5px;
            font-size:16px;
            border-radius:20px;
            border:1px solid lightgrey;
            flex: 1; 
            outline:none;
            width: inherit;
        }

        .address-container {
            display: flex;
            gap: 10px; 
            margin-top:10px;
        }

        .user-address{
            padding:10px 10px;
            margin:0px 0px 0px 5px;
            font-size:16px;
            border-radius:20px;
            border:1px solid lightgrey;
            resize:none;
            width:100%;
            flex: 1; 
            outline:none;
        }

        .profile-details-div span{
            display:inline-block;
            margin:20px 0px 20px 0px;
            font-size:16px;
        }

        .username:disabled{
            background-color:transparent;
            border-radius:0px;
            border:none;
            outline:none;
            color:black;
            padding:0px;
        }

        .user-phoneNo:disabled, .user-dob:disabled, .user-password:disabled{
            background-color:transparent;
            border-radius:0px;
            border:none;
            outline:none;
            padding:10px 0px;
            margin-left:0px;
            color:black;
            font-size:16px;
            width: 40%;
        }

        .user-email{
            background-color:transparent;
            border-radius:0px;
            border:none;
            outline:none;
            padding:10px 0px;
            color:black;
            font-size:16px;
            margin:10px 0px;
            width: 90%;
        }

        .user-joinedAt{
            background-color:transparent;
            border-radius:0px;
            border:none;
            outline:none;
            padding:10px 0px;
            color:black;
            font-size:16px;
            margin:10px 0px;
        }

        .user-password:disabled{
            background-color:transparent;
            border-radius:0px;
            border:none;
            outline:none;
            padding:10px 0px;
            margin-left:0px;
            color:black;
        }
        
        .disabledInput{
            cursor: not-allowed;
            border: 1px solid rgb(215, 215, 215);
            background-color:rgb(215, 215, 215);
            padding:10px 10px;
            margin:10px 0px 10px 5px;
            font-size:16px;
            border-radius:20px;
            flex: 1; 
            width: inherit;
        }

        .user-address:disabled{
            background-color:transparent;
            border:none;
            border-radius:0px;
            margin-left:0px;
            outline:none;
            padding:0px;
            color:black;
        }

        .change-profile{
            background-color:grey;
            border-radius:50%;
            font-size:14px;
            padding:8px;
            margin-top: -35px;
            margin-left:25px;
            position:absolute;
            z-index:20;
            display:none;
            cursor:pointer;
            color:white;
        }

        .chooseImageOverlay{
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.2); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000; 
            display: none;
        }

        .chooseImageContainer{
            width:600px;
            height: auto;
            border-radius:20px;
            position:relative;
            padding:20px;
            background-color:white;
            
        }

        .chooseImage{
            display:flex;
            flex-wrap:wrap;
            align-items: center;
            justify-content: center;
        }

        .chooseImage img{
            border-radius:50%;
            border:none;
            padding:10px;
        }

        .ImageContainer{
            cursor:pointer;
            margin:10px;
            border-radius:50%;
            border: 2px solid lightgrey;
        }

        .ImageSelected{
            border-radius:50%;
            border:2px solid #78a42c;
        }

        .chooseImgBtn{
            border-radius:20px;
            padding:15px;
            width:150px;
            color:white;
            cursor: pointer;
            margin-top:10px;
            background-color:#78a42c;
            border:none;
        }
        
        .logoutBtn{
            padding:10px 20px;
            border-radius:20px;
            border:2px solid grey;
            background-color:white;
            cursor: pointer;
        }

        .logoutBtn:hover{
            background-color:rgba(232, 218, 213, 0.99);
            border: 2px solid rgb(176, 121, 102);
            /* border: 2px solid rgba(232, 218, 213, 0.99); */

        }

        .logoutIcon{
            padding:0px;
            color:rgb(55, 55, 55);
            font-size:18px;
            text-align:center;
        }

        .logoutBtn:hover .logoutIcon{
            /* color:rgb(254, 253, 252); */
        }

        .showHidePw{
            display:none;
            cursor: pointer;
        }

        .phone-error-message, .pw-error-message, .date-error-message, .name-error-message{
            display: none;
            font-size: 14px;
            color:#f7656d;
            padding:5px 0px 10px;
            text-align: left;
            font-family: "Open Sans", sans-serif;

        }

        .right-container{
            width:65%;
            margin:20px 60px 20px 0px;
            flex: 1;
            display:flex;
            flex-direction:column;
            margin-left: 32%;
            overflow-y: scroll;
            overflow-x:hidden;
            position: relative;
            background-color:#FAFAF6;
            border: 1px solid lightgrey;
            height: 86vh;
            border-radius: 25px;
        }
        
        .tab{
            position: fixed;
            width:61.05%;
            background-color: #FAFAF6;
            z-index:100;
            padding-top:20px;
            margin: 0px 20px;
        }

        .all-record{
            margin:80px 20px 0px 20px;
            width: 96%;
            overflow-y: auto;
            overflow-x: hidden;
            flex:1;
        }

        .tablink{
            background-color:transparent;
            border:none;
            font-size:18px;
            float:left;
            cursor: pointer;
            width: 50%;
            padding:10px 20px;
            font-weight:bold;
            font-family: "Playpen Sans", cursive;
            border-bottom: 3px solid lightgrey;
            color: lightgrey;
        }

        .selected-tab{
            color:rgb(158, 102, 19);
            border-bottom: 3px solid rgb(158, 102, 19);
        }

        hr{
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
        }

        .record-row{
            width: 100%;
            gap:30px;
            display:flex;
            flex-direction:row;
            padding:10px 15px;
            margin:0px 10px;
            
        }

        .record-icon{
            width:5%;
            margin:auto 0px;
        }

        .record-icon img{
            vertical-align:middle;
        }

        .record-details{
            width: 70%;
        }

        .record-details h3{
            padding:5px 0px;
        }

        .date{
            font-size:12px;
            color:grey;
        }

        .item{
            font-size:14px;
            color:grey;
        }
        .record-points{
            width: 15%;
            display:flex;
            flex-direction:row;
        }

        .record-points-img{
            margin:auto 5px;
        }

        .record-points-img img{
            vertical-align:middle;
        }

        .record-points-p{
            font-size:18px;
            font-weight:bold;
            margin:auto 5px;
        }

        .reward-History{
            display:none;
            z-index:5;
        }

        .recycle-History{
            z-index:5;
        }

        .record-status{
            width: 10%;
            display:flex;
            flex-direction:column;
            margin:auto 0px;
        }

        .record-status-text{
            font-size: 18px;
            font-weight:bold;
            text-align:center;
        }

        .record-review-btn button{
            /*background-color: rgb(209, 137, 42);*/
            background-color:transparent;
            border-radius: 20px;
            border:2px solid #427c5d;
            text-align:center;
            padding:5px;
            width: 100%;
            color:#427c5d;
            margin: auto 0px;
            font-size:16px;
        }

        .record-review-btn button:hover{
            background-color:rgba(188, 207, 196, 0.3);
        }

        
        .review-form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-out;
        }

        .review-form-overlay.show {
            opacity: 1;
            display: block;
        }

        .review-form-overlay.hide {
            opacity: 0;
        }

        .review-form-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .review-form-popup.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .review-form-popup.hide {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1); 
            transition: opacity 0.3s ease-out;
        }
        
        .close-container {
            text-align: center;
            margin: 20px 0 40px 40px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 25px;
            top: 0;
            color:rgb(40, 79, 12);
            font-size: 35px;
        }

        .close:hover, .close:focus {
            color:rgb(13, 84, 17);
            cursor: pointer;
        }

        .review-form-container {
            padding-left: 50px;
            padding-right: 50px;
            padding-bottom: 50px;
            padding-top: 25px;
        }

        .review-form-header h3 {
            font-size: 30px;
            line-height: 1.8;
        }

        .review-form-p p {
            line-height: 1.5;
            color:rgb(89, 89, 89);
        }

        .starSelected{
            color: #f8c455 !important;
        }

        .review-form-input-div label{
            color :rgb(89,89,89);
        }

        .review-form-input-div textarea{
            width: 94%;
            padding: 12px 16px;
            margin: 8px 0px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size:16px;
            outline:none;
            transition: all 0.3s ease-in-out;
            height: 180px;
            resize:none;
        }

        .review-form-star{
            margin:10px 0px;
        }

        .review-form-star i {
            color: lightgrey;
            font-size:30px;
        }
        
        .submitBtn{
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            color: white;
            padding: 14px 20px;
            margin: 8px 0;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 8px; 
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
        }

        .submitBtn:hover{
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }

        .submitBtn:active{
            transform: scale(0.98);
        }
    
        .star-error-message, .review-text-error-message{
            color: #f7656d;
            font-size:14px;
            display:none;
        }

        .pickup-form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-out;
        }

        .pickup-form-overlay.show {
            opacity: 1;
            display: block;
        }

        .pickup-form-overlay.hide {
            opacity: 0;
        }

        .pickup-form-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            max-height:90vh;
            box-sizing:border-box;
        }

        .pickup-form-popup.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .pickup-form-popup.hide {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1); 
            transition: opacity 0.3s ease-out;
        }
        
        .close-container {
            text-align: center;
            margin: 20px 0 40px 40px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 25px;
            top: 0;
            color:rgb(40, 79, 12);
            font-size: 35px;
        }

        .close:hover, .close:focus {
            color:rgb(13, 84, 17);
            cursor: pointer;
        }

        .pickup-form-container {
            padding-left: 50px;
            padding-right: 50px;
            padding-bottom: 50px;
            padding-top: 25px;
        }

        .pickup-form-header h3 {
            font-size: 30px;
            line-height: 1.8;
        }

        .pickup-form-p p {
            line-height: 1.5;
            color:rgb(89, 89, 89);
        }

        .pickup-form-input-div{
            max-height: 55vh;
            overflow-y: auto;
            overflow-x:hidden;
            padding-right: 20px;
        }

        .pickup-form-input-div label{
            color :rgb(89,89,89);
        }

        .pickup-form-input-div textarea{
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size:16px;
            outline:none;
            transition: all 0.3s ease-in-out;
            box-sizing:border-box;
            height: 60px;
            resize:none;
        }
 
        .submitBtn{
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            color: white;
            padding: 14px 20px;
            margin: 0px 0 15px;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 8px; 
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
        }

        .submitBtn:hover{
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }

        .submitBtn:active{
            transform: scale(0.98);
        }

        .pickup-date-time-div{
            display:flex;
            flex-direction:row;
            gap:50px;
        }

        .pickup-date-time-div div{
            width: 50%;
        }

        .pickup-date-time-div select{
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px; 
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;  
            box-sizing:border-box;
            transition: all 0.3s ease-in-out;
        }

        select {
            background: white;
            cursor: pointer;
            appearance: none; 
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black"><path d="M7 10l5 5 5-5z"/></svg>'); /* Custom dropdown arrow */
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            box-sizing:border-box;
            padding-right: 40px;
        }

        .pickup-form-input-div input{
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size:16px;
            outline:none;
            box-sizing:border-box;
            transition: all 0.3s ease-in-out;
            display: block;
        }

        .all-pickup-item{
            margin-top: 15px;
        }

        .pickup-item-quantity-div{
            display:flex;
            flex-direction:row;
        }

        .pickup-item-quantity-div .item-div{
            width: 50%; 
            /* margin-right:48px; */
            margin-right:40px;
        }

        .pickup-item-quantity-div .quantity-div{
            /* width: 40%;  */
            width: 45%;
            /* margin-left:8px; */
            margin-left:13px;
        }


        /* .pickup-item-quantity-div .add-delete-btn{
            width: 10%;
            margin:33px 0px;
            padding-left:10px;
            display: flex;
            flex-direction:row;
            gap:8px;
        } */

        .pickup-item-quantity-div .add-delete-btn{
            margin:33px 0px;
            padding-left:10px;
        }


        .delete-item-btn{
            border-radius:50%;
            font-size:20px;
            text-align:center;
            vertical-align:middle;
            background: white;
            color:#f7656d;
            cursor:pointer;
            margin-top:5px;
        }

        .pickup-item-quantity-div select{
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px; 
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;  
            transition: all 0.3s ease-in-out;
        }

        .add-item-btn{
            font-size:15px;
            padding:1px 6px 0px 0px;
            text-align:center;
            vertical-align:middle;
            background-color:transparent;
            border:none;
            text-decoration:underline;
            cursor:pointer;
            color: rgb(40, 86, 15);
            margin-top: -30px;
        }

        .add-item-btn:hover{
            color: rgb(20, 43, 8);
        }

        .disabled-delete-btn {
            pointer-events:none;
            opacity: 0.6; 
        }

        .disabled-add-btn {
            cursor: not-allowed;
            opacity: 0.6; 
        }

        .disabled-add-btn:hover{
            color: rgb(40, 86, 15);
        }

        .p-date-error-message,
        .time-error-message,
        .p-phone-error-message,
        .address-error-message,
        .image-error-message{
            display:none;
            font-size: 14px;
            color:#f7656d;
            padding:0px 5px 10px;
            text-align: left;
            font-family: "Open Sans", sans-serif;
        }

        .item-error-message,
        .quantity-error-message{
            display:none;
            font-size: 14px;
            color:#f7656d;
            padding:0px 5px 30px;
            text-align: left;
            font-family: "Open Sans", sans-serif;
        }

        iframe{
            pointer-events: none;
            width: 50%;
            aspect-ratio: 1;
            display: block;
            margin-bottom:20px;
        }

        .edit-pr-request button{
            background-color:transparent;
            border-radius: 20px;
            border:2px solid rgb(199, 199, 199);
            text-align:center;
            padding:5px;
            width: 100%;
            color:rgb(137, 137, 137);
            margin: auto 0px;
            font-size:16px;
        }

        .dropoff-form-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-out;
        }

        .dropoff-form-overlay.show {
            opacity: 1;
            display: block;
        }

        .dropoff-form-overlay.hide {
            opacity: 0;
        }

        .dropoff-form-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .dropoff-form-popup.show {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
        }

        .dropoff-form-popup.hide {
            opacity: 0;
            transform: translate(-50%, -50%) scale(1); 
            transition: opacity 0.3s ease-out;
        }

        .close:hover, .close:focus {
            color:rgb(13, 84, 17);
            cursor: pointer;
        }

        .form-container {
            padding-left: 50px;
            padding-right: 50px;
            padding-bottom: 50px;
            padding-top: 25px;
        }

        .form-container h1 {
            font-size: 30px;
            line-height: 1.8;
        }

        .form-container p {
            line-height: 1.5;
            color:rgb(89, 89, 89);
        }

        .form-container label {
            color:rgb(89, 89, 89);
        }

        .dropoff-form-popup input[type="text"], 
        .dropoff-form-popup input[type="date"], 
        select {
            width: 100%;
            padding: 12px 16px;
            margin: 8px 0;
            border-radius: 8px; 
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;  
            transition: all 0.3s ease-in-out;
        }

        .dropoff-form-popup input[type="date"] {
            appearance: none; 
            background: white;
            /* background-size: 20px; */
            cursor: pointer;
            height: 44px;
        }

        select {
            background: white;
            cursor: pointer;
            appearance: none; 
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="black"><path d="M7 10l5 5 5-5z"/></svg>'); /* Custom dropdown arrow */
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }

        .dropoff-form-popup input[type="date"]:hover, 
        .dropoff-form-popup input[type="date"]:focus, 
        .dropoff-form-popup select:hover, 
        .dropoff-form-popup select:focus {
            border-color:rgb(123, 206, 159); 
            box-shadow: 0 0 8px rgba(216, 27, 96, 0.2);
        }

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #333;
            margin-top: 15px;
        }

        .checkbox-container input[type="checkbox"] {
            width: 15px;
            height: 15px;
            cursor: pointer;
            accent-color: rgb(78, 120, 49); 
            margin-top: 1px;
        }

        .error {
            border-color:rgb(207, 62, 59) !important;
            box-shadow: 0 0 8px rgba(216, 27, 96, 0.2);
        }

        .error-checkbox {
            outline: 1px solid rgb(207, 62, 59) !important;
            border-radius: 2px; 
        }

        .addformbtn {
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            color: white;
            padding: 14px 20px;
            margin: 8px 0;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 8px; 
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
        }
        
        .addformbtn:hover {
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }

        .addformbtn:active {
            transform: scale(0.98); 
        }

        .cancelbtn {
            background: transparent; 
            color: rgb(56, 90, 35);
            padding: 12px 20px;
            margin: 15px 0 8px 0;
            border: 2px solid rgb(78, 120, 49);
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 8px; 
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
        }

        .cancelbtn:hover {
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }

        .cancelbtn:active {
            transform: scale(0.98); 
        }

        .cancelprbtn {
            background: transparent; 
            color: rgb(56, 90, 35);
            padding: 12px 20px;
            margin: 8px 0;
            border: 2px solid rgb(78, 120, 49);
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 8px; 
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px; 
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
        }

        .cancelprbtn:hover {
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }

        .cancelprbtn:active {
            transform: scale(0.98); 
        }

        .success-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color:rgb(233, 241, 231);
            border: 1px solid rgb(143, 143, 143);
            color: black;
            padding: 30px 50px;
            border-radius: 10px;
            z-index: 9999;
            box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeInOut 4s ease-in-out;
        }

        .success-popup p {
            color: black;
            font-size: 16px;
            line-height: 1.8;
            font-family: "Playpen Sans", cursive;
        }

        .success-popup i {
            color: green;
            font-size: 48px;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }


        #userDetailForm{
            place-content:center;
        }
        

        .scrollbar {
            overflow: overlay;
            place-content:center;
        }

        .scrollbar::-webkit-scrollbar {
            background-color: rgba(0,0,0,0);
            width: 16px;
            height: 16px;
            z-index: 999999;
        }

        .scrollbar::-webkit-scrollbar-track {
            background-color: rgba(0,0,0,0);
        }

        .scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0);
            border-radius:16px;
            border:0px solid #fff;
        }

        .scrollbar::-webkit-scrollbar-button {
            display:none;
        }


        .scrollbar:hover::-webkit-scrollbar-thumb {
            background-color: #a0a0a5;
            border:4px solid #ebebeb;
        }

        .scrollbar::-webkit-scrollbar-thumb:hover {
            background-color:#a0a0a5;
            border:4px solid #ebebeb;
        }

        .scrollbar-mac{
            overflow: auto;

        }

    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
            <li><a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
            <li><a onclick="window.location.href='User-Rewards.php'">Rewards</a></li>
            <li><a onclick="window.location.href='User-Review.php'">Review</a></li>
            <li><a onclick="window.location.href='User-FAQ.php'">FAQ</a></li>
        </ul>
        <div class="header-icons">
            <button class="noti-button" type="button" onclick="redirectToNotifications()">
                <span class="material-icons">mail</span>
                <?php if ($user_id && $unreadCount > 0) { ?>
                    <span class="noti-button__badge" id="notiBadge"><?php echo $unreadCount; ?></span>
                <?php } ?>
            </button>
            <button class="profile-button" type="button" onclick="window.location.href = 'User-Profile.php'">
                <span class="material-icons">account_circle</span>
            </button>
        </div>
    </header>
    <div class="page-div">
        <div class="profile-container">
            <div id="scroll-div" class="">
                <div style="margin:auto; width:80%;" class="testing">
                    <form method="post" id="userDetailForm">
                        <div class="profile-div">
                            <center>
                                <div style="position:relative; display:inline-block;">
                                    <input name="userId" type="text" hidden>
                                    <center><img style="background-color:white; border-radius:50px;" id="currentProfileImg" src="<?php echo $userProfileImage;?>" width="90"></center>
                                    <input type="hidden" id="currentProfileImageInput" name="currentProfileImage" value="<?php echo $userProfileImage;?>">
                                    <center><span><i class="fa-solid fa-pen change-profile"></i></span></center>
                                </div>
                            </center>
                            <div class="chooseImageOverlay">
                                <div class="chooseImageContainer">
                                    <div class="chooseImage">
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-1.png">
                                            <img src="User-Profile-Avatar-1.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-2.png">
                                            <img src="User-Profile-Avatar-2.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-3.png">
                                            <img src="User-Profile-Avatar-3.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-4.png">
                                            <img src="User-Profile-Avatar-4.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-5.png">
                                            <img src="User-Profile-Avatar-5.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-6.png">
                                            <img src="User-Profile-Avatar-6.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-7.png">
                                            <img src="User-Profile-Avatar-7.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-8.png">
                                            <img src="User-Profile-Avatar-8.png" width="75">
                                        </div>
                                        <div class="ImageContainer" data-value="User-Profile-Avatar-9.png">
                                            <img src="User-Profile-Avatar-9.png" width="75">
                                        </div>
                                    </div>
                                    <center><button type="button" class="chooseImgBtn">OK</button></center>
                                </div>
                                
                            </div>

                            <input type="text" class="username" name="username" value="<?php echo $username; ?>" ><br>
                            <p class="name-error-message">Please enter your name.</p>
                            <label class="points"><center><strong>Points: </strong><?php echo $userPoints; ?></center></label>
                            <center>
                                <button type="button" id="editProfile" class="editProfile" name="editProfile" value="0"><i id="edit-btn-icon"class="fa-solid fa-pen"></i>Edit Profile</button> 
                                <button type="button" class="logoutBtn" onclick="window.location.href = 'User-Logout.php'"><i class="fa-solid fa-right-from-bracket logoutIcon"></i></button> 
                            </center>
                        </div>
                        <br>
                        <div class="profile-details-div">
                            <div class="input-container">
                                <label>Email:</label>
                                <input type="text" class="user-email" value="<?php echo $userEmail;?>">
                            </div>

                            <div class="input-container">
                                <label style="white-space:nowrap;">Phone Number:</label>
                                <input type="text" class="user-phoneNo" name="user-phoneNo" value="<?php echo $userPhoneNo;?>">
                            </div>
                            <p class="phone-error-message">Please enter a valid phone number.</p>

                            <div class="input-container">
                                <label>Date of Birth:</label>
                                <input type="text" class="user-dob" onblur="(this.type='text')" name="user-dob" value="<?php echo htmlspecialchars($userDOB);?>">
                            </div>
                            <p class="date-error-message">Please choose your date of birth.</p>

                            <div class="input-container">
                                <label>Password:</label>
                                <input type="password" class="user-password" name="user-password" value="<?php echo $userPW;?>" >
                                <img src="User-HidePasswordIcon.png" width="20px" class="showHidePw">
                            </div>
                            <p class="pw-error-message">Please enter your password.</p>
                            

                            <div class="input-container">
                                <label>Joined At:</label>
                                <input type="text" class="user-joinedAt" value="<?php echo $userDateCreated;?>">
                            </div>

                            <div class="address-container">
                                <label>Address:</label>
                                <textarea class="user-address" name="user-address" ><?php echo $userAddress;?></textarea>
                            </div>
                        </div>
                    

                    </form>
                </div>
            </div>
        </div>
        <div class="right-container">
            <div class="tab">
                <button class="tablink selected-tab" id="points-tab" data-tab="points">Points History</button>
                <button class="tablink" id="recycle-tab" data-tab="recycle">Recycle History</button>
            </div>
            <div class="reward-History">
                <div class="all-record">
                    <?php 
                        $getPointHistoryQuery = mysqli_query($conn, "SELECT dr.dropoff_id AS transaction_id, dr.dropoff_date AS transaction_date, 
                                                                        dr.total_point_earned AS points, '-' AS reward_id ,'Dropoff' AS transaction_type FROM dropoff dr 
                                                                        WHERE dr.user_id = '$user_id' AND dr.status = 'Complete'
                                                                        UNION ALL
                                                                        SELECT pr.pickup_request_id AS transaction_id, pr.datetime_submit_form AS transaction_date, 
                                                                        pr.total_point_earned AS points, '-' AS reward_id, 'Pickup' AS transaction_type FROM pickup_request pr 
                                                                        WHERE pr.user_id = '$user_id' AND pr.status = 'Completed'
                                                                        UNION ALL
                                                                        SELECT rr.redeem_reward_id AS transaction_id, rr.redeem_datetime AS transaction_date, 
                                                                        reward.point_needed AS points , rr.reward_id AS reward_id, 'Reward' AS transaction_type FROM redeem_reward rr 
                                                                        INNER JOIN reward ON rr.reward_id = reward.reward_id WHERE rr.user_id = '$user_id'
                                                                        ORDER BY transaction_date DESC;");

                        while ($getPointHistoryResult = mysqli_fetch_assoc($getPointHistoryQuery)){
                            $transaction_id = $getPointHistoryResult['transaction_id'];
                            echo '<div class="record-row">';
                                echo '<div class="record-icon">';
                                    if ($getPointHistoryResult['transaction_type'] == "Dropoff"){
                                        echo '<img src="User-Profile-History-DropoffIcon.png" width="50">';
                                    }else if ($getPointHistoryResult['transaction_type'] == "Pickup"){
                                        echo '<img src="User-Profile-History-PickupIcon.png" width="50">';
                                    }else if ($getPointHistoryResult['transaction_type'] == "Reward"){
                                        echo '<img src="User-Profile-History-RewardIcon.png" width="50">';
                                    }
                                    
                                echo '</div>';
                                echo '<div class="record-details">';
                                    echo '<p class="date">'.$getPointHistoryResult['transaction_date'].'</p>';
                                    if ($getPointHistoryResult['transaction_type'] == "Dropoff"){
                                        echo '<h3>Drop-off</h3>';
                                    }else{
                                        echo '<h3>'.$getPointHistoryResult['transaction_type'].'</h3>';
                                    }
                                    echo '<p class="item">';
                                    if ($getPointHistoryResult['transaction_type'] == "Dropoff"){
                                        $getItemQuery = mysqli_query($conn, "SELECT i.item_name AS item, idr.quantity AS quantity FROM item_dropoff idr 
                                                                                    INNER JOIN ITEM i ON idr.item_id = i.item_id WHERE 
                                                                                    idr.dropoff_id = '$transaction_id'");
                                    }else if ($getPointHistoryResult['transaction_type'] == "Pickup"){
                                        $getItemQuery = mysqli_query($conn, "SELECT i.item_name AS item, ipr.quantity AS quantity FROM item_pickup ipr 
                                                                                    INNER JOIN ITEM i ON ipr.item_id = i.item_id WHERE 
                                                                                    ipr.pickup_request_id = '$transaction_id'");
                                    }else if ($getPointHistoryResult['transaction_type'] == "Reward"){
                                        $getItemQuery = mysqli_query($conn, "SELECT reward_name as item, '1' AS quantity FROM reward WHERE reward_id = '$getPointHistoryResult[reward_id]'");
                                    }
                                    $count = 1;
                                    while ($getItemResult = mysqli_fetch_assoc($getItemQuery)){
                                        if ($count == 1){
                                            echo '<span>'.$getItemResult['item'].' (x'.$getItemResult['quantity'].')<span>';
                                        }else{
                                            echo '<span>, '.$getItemResult['item'].' (x'.$getItemResult['quantity'].')<span>';
                                        }
                                        $count += 1;
                                    }
                                    echo '</p>';
                                echo '</div>';
                                echo '<div class="record-points">';
                                    echo '<div class="record-points-img">';
                                        echo '<img src="User-Profile-History-CoinIcon.png" width="30">';
                                    echo '</div>';
                                    echo '<div class="record-points-p">';
                                        if ($getPointHistoryResult['transaction_type'] == "Dropoff" || $getPointHistoryResult['transaction_type'] == "Pickup"){
                                            echo '<p style="color:#427c5d;">+ '.$getPointHistoryResult['points'].'</p>';
                                        }else if ($getPointHistoryResult['transaction_type'] == "Reward"){
                                            echo '<p style="color:#fa5613;">- '.$getPointHistoryResult['points'].'</p>';
                                        }
                                    echo '</div>';
                                echo '</div>';
                            echo '</div>';
                            echo '<hr>';
                        }
                    ?>
                </div>
            </div>
            <?php 
                $getRecyleHistoryQuery = mysqli_query($conn, "SELECT dr.dropoff_id AS transaction_id, 
                                                            dr.dropoff_date AS transaction_date, dr.status AS status, 
                                                            dr.total_point_earned, 'Dropoff' AS transaction_type 
                                                            FROM dropoff dr WHERE dr.user_id = '$user_id' 
                                                            UNION ALL
                                                            SELECT pr.pickup_request_id AS transaction_id, 
                                                            pr.datetime_submit_form AS transaction_date, 
                                                            pr.status AS status, pr.total_point_earned, 'Pickup' AS transaction_type 
                                                            FROM pickup_request pr WHERE pr.user_id = '$user_id'
                                                            ORDER BY transaction_date DESC;");
            ?>
            <div class="recycle-History">
                <div class="all-record">
                    <?php 
                        while ($getRecyleHistoryResult = mysqli_fetch_assoc($getRecyleHistoryQuery)){ 
                            $transaction_id = $getRecyleHistoryResult['transaction_id'];        
                            $transaction_type = $getRecyleHistoryResult['transaction_type'];              
                            echo '<div class="record-row">';
                                echo '<div class="record-icon">';
                                    if ($getRecyleHistoryResult['transaction_type'] == "Dropoff"){
                                        echo '<img src="User-Profile-History-DropoffIcon.png" width="50">';
                                    }else if ($getRecyleHistoryResult['transaction_type'] == "Pickup"){
                                        echo '<img src="User-Profile-History-PickupIcon.png" width="50">';
                                    }
                                echo '</div>';
                                echo '<div class="record-details">';
                                    echo '<p class="date">'.$getRecyleHistoryResult['transaction_date'].'</p>';
                                    if ($transaction_type == "Dropoff"){
                                        echo '<h3>Drop-off</h3>';
                                    }else{
                                        echo '<h3>'.$transaction_type.'</h3>';
                                    }
                                    echo '<p class="item">';
                                    if ($getRecyleHistoryResult['transaction_type'] == "Dropoff"){
                                        $getRecyleItemQuery = mysqli_query($conn, "SELECT i.item_name AS item, idr.quantity AS quantity FROM item_dropoff idr 
                                                                                    INNER JOIN ITEM i ON idr.item_id = i.item_id WHERE 
                                                                                    idr.dropoff_id = $transaction_id");
                                    }else if ($getRecyleHistoryResult['transaction_type'] == "Pickup"){
                                        $getRecyleItemQuery = mysqli_query($conn, "SELECT i.item_name AS item, ipr.quantity AS quantity FROM item_pickup ipr 
                                                                                    INNER JOIN ITEM i ON ipr.item_id = i.item_id WHERE 
                                                                                    ipr.pickup_request_id = $transaction_id");
                                    }
                                    $count = 1;
                                    while ($getRecyleItemResult = mysqli_fetch_assoc($getRecyleItemQuery)){
                                        if ($count == 1){
                                            echo '<span>'.$getRecyleItemResult['item'].' (x'.$getRecyleItemResult['quantity'].')<span>';
                                        }else{
                                            echo '<span>, '.$getRecyleItemResult['item'].' (x'.$getRecyleItemResult['quantity'].')<span>';
                                        }
                                        $count += 1;
                                    }
                                    echo '</p>';
                                echo '</div>';

                                if($getRecyleHistoryResult['transaction_type'] == "Pickup"){
                                    $alrReviewQuery = mysqli_query($conn, "SELECT count(*) AS count FROM review where pickup_request_id = '$transaction_id'");
                                    $alrReviewResult = mysqli_fetch_assoc($alrReviewQuery)['count'];
                                    if ($alrReviewResult > 0){
                                        echo '<div class="record-status">';
                                            echo '<div class="record-status-text">';
                                                echo '<p style="color:#427c5d;">Rated</p>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else if($getRecyleHistoryResult['status'] == "Completed"){
                                        echo '<div class="record-status">';
                                            echo '<div class="record-review-btn">';
                                                echo '<center><button onclick="showPopup(' . $transaction_id . ', \'' . $transaction_type . '\')">Rate</button></center>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else if ($getRecyleHistoryResult['status'] == "Unread"){
                                        echo '<div class="record-status">';
                                            echo '<div class="edit-pr-request">';
                                                echo '<center><button onclick="showPickupPopup(' . $transaction_id . ', \'' . $transaction_type . '\')">Edit</button></center>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else if ($getRecyleHistoryResult['status'] == "Assigned"){
                                        echo '<div class="record-status">';
                                            echo '<div class="record-status-text">';
                                                echo '<p style="color:rgb(255, 152, 75);">Approved</p>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else{
                                        echo '<div class="record-status">';
                                            echo '<div class="record-status-text">';
                                                echo '<p style="color:rgb(209, 23, 5);">'.$getRecyleHistoryResult['status'].'</p>';
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                }else if ($getRecyleHistoryResult['transaction_type'] == "Dropoff"){
                                    $alrReviewQuery = mysqli_query($conn, "SELECT count(*) AS count FROM review where dropoff_id = '$transaction_id'");
                                    $alrReviewResult = mysqli_fetch_assoc($alrReviewQuery)['count'];
                                    if ($alrReviewResult > 0){
                                        echo '<div class="record-status">';
                                            echo '<div class="record-status-text">';
                                                echo '<p style="color:#427c5d;">Rated</p>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else if ($getRecyleHistoryResult['status'] == "Complete"){
                                        echo '<div class="record-status">';
                                            echo '<div class="record-review-btn">';
                                                echo '<center><button onclick="showPopup(' . $transaction_id . ', \'' . $transaction_type . '\')">Rate</button></center>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else if ($getRecyleHistoryResult['status'] == "unread" || $getRecyleHistoryResult['status'] == "Unread"){
                                        echo '<div class="record-status">';
                                            echo '<div class="edit-pr-request">';
                                                echo '<center><button onclick="showDropOffPopup(' . $transaction_id . ', \'' . $transaction_type . '\')">Edit</button></center>';
                                            echo '</div>';
                                        echo '</div>';
                                    }else{
                                        echo '<div class="record-status">';
                                            echo '<div class="record-status-text">';
                                                echo '<p style="color:rgb(209, 23, 5);">'.$getRecyleHistoryResult['status'].'</p>';
                                            echo '</div>';
                                        echo '</div>';
                                    }
                                }
                            echo '</div>';
                            echo '<hr>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>  
    
    <div class="review-form-overlay"></div>
    <div id="reviewFormDiv" class="modal">
        <form class="review-form-popup" method="post">
            <div class="close-container">
                <span class="close" id="closePopup">&times;</span>
            </div>
            <div class="review-form-container">
                <div class="review-form-header">
                    <h3>Review</h3>
                </div>
                <div class="review-form-p">
                    <p>Share your experience with us! Your feedback helps us improve and serve you better.</p>
                </div>
                <br><br>
                <div class="review-form-input-div">
                    <label>Rate</label>
                    <div class="review-form-star">
                        <i class="fa-solid fa-star star1" data-star="1" onclick="givenStar(1)"></i>
                        <i class="fa-solid fa-star star2" data-star="2" onclick="givenStar(2)"></i>
                        <i class="fa-solid fa-star star3" data-star="3" onclick="givenStar(3)"></i>
                        <i class="fa-solid fa-star star4" data-star="4" onclick="givenStar(4)"></i>
                        <i class="fa-solid fa-star star5" data-star="5" onclick="givenStar(5)"></i>
                    </div>
                    <p class="star-error-message">Please choose a rating from 1 to 5 stars before submitting.</p>
                    <br>
                    <label>Review</label><br>
                    <textarea name="review-text" id="review-text" placeholder="Share details of your own experience at this place"></textarea>
                    <p class="review-text-error-message">Please write a review before submitting.</p>
                </div>
                <input type="hidden" name="star-given" id="star-given">
                <input type="hidden" name="pr_dr_id" id="pr_dr_id">
                <input type="hidden" name="pr_dr_type" id="pr_dr_type">
                <br>
                <button class="submitBtn" type="submit" name="submitBtn">Submit</button>
            </div>
        </form>
    </div>

    <div id="ReviewSuccessPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Review submitted successfully!</p>
    </div>

    <div class="pickup-form-overlay">
    </div>
    <div id="pickupFormDiv" class="modal">
        <form class="pickup-form-popup" method="POST" enctype="multipart/form-data">
            <div class="close-container">
                <span class="close" id="puClosePopup">&times;</span>
            </div>
            <div class="pickup-form-container">
                <div class="pickup-form-header">
                    <h3>Pickup Scheduling</h3>
                </div>
                <div class="pickup-form-p">
                    <p>Fill out this form before scheduling your e-waste pickup to ensure a smooth collection process and earn your reward points.</p>
                </div>
                <br><br>
                <div class="pickup-form-input-div">
                    <div class="pickup-date-time-div">
                        <input type="hidden" name="pickup-request-id">
                        <div>
                            <label>Date</label>
                            <br>
                            <select id="date-select" name="date-pickup">
                                <option value="">Select a date</option>
                                <?php 
                                    $getAllAvailableDateQuery = mysqli_query($conn, "SELECT DISTINCT date FROM time_slot WHERE date > NOW() + INTERVAL 1 WEEK AND no_driver_per_slot > 0 ORDER BY date "); 
                                    while ($getAllAvailableDateResult = mysqli_fetch_assoc($getAllAvailableDateQuery)){
                                        echo '<option value="'.$getAllAvailableDateResult['date'].'">';
                                            echo $getAllAvailableDateResult['date'];
                                        echo '</option>'; 
                                    }
                                ?>
                            </select>
                            <p class="p-date-error-message">Please select a date.</p>
                        </div>
                        <div>
                            <label>Time</label>
                            <br>
                            <select id="time-select" name="time-pickup">
                                <option value="">Select a time</option>
                            </select>
                            <p class="time-error-message">Please select a time.</p>
                        </div>
                    </div>
                    <br>
                    <label>Contact Number</label>
                    <br>
                    <?php 
                        $getPhoneNoQuery = mysqli_query($conn, "SELECT phone_number FROM user WHERE user_id = '$user_id'");
                        $getPhoneNo = mysqli_fetch_assoc($getPhoneNoQuery)['phone_number'];
                        if ($getPhoneNo == NULL){
                            $getPhoneNo = "";
                        }else if (trim($getPhoneNo) == "" || trim($getPhoneNo) == "-" ){
                            $getPhoneNo = "";
                        }else{
                            $getPhoneNo = "0".$getPhoneNo;
                        }
                    ?>
                    <input type="text" name="phone-no-pickup" autocomplete="off" value="<?php echo $getPhoneNo; ?>">
                    <p class="p-phone-error-message">Please enter your phone number.</p>
                    <br>
                    <label>Address</label>
                    <br>
                    <?php 
                        $getAddressQuery = mysqli_query($conn, "SELECT address FROM user WHERE user_id = '$user_id'");
                        $getAddress = mysqli_fetch_assoc($getAddressQuery)['address'];
                        if ($getAddress == NULL){
                            $getAddress = "";
                        }else if(trim($getAddress) == "" || trim($getAddress) == "-" ){
                            $getAddress = "";
                        }
                    ?>
                    <textarea autocomplete="off" name="address-pickup"><?php echo $getAddress;?></textarea>
                    <p class="address-error-message">Please enter your address.</p>
                    <br>

                    <div class="all-pickup-item">
                        <div class="pickup-item-quantity-div">
                            <div class="item-div">
                                <label>Item 1</label>
                                <br>
                                <select name="item_1">
                                    <option value="">Select a item</option>
                                    <?php 
                                        $getAllItemQuery = mysqli_query($conn, "SELECT item_name FROM item WHERE status = 'Available' ORDER BY item_name");
                                        while($getAllItemResult = mysqli_fetch_assoc($getAllItemQuery)){
                                            echo '<option value="'.$getAllItemResult['item_name'].'">';
                                            echo $getAllItemResult['item_name'];
                                            echo '</option>';
                                        }
                                    ?>
                                </select>
                                <p class="item-error-message">Please select an item.</p>
                            </div>
                            <div class="quantity-div">
                                <label>Quantity</label>
                                <br>
                                <input type="number" value="" name="quantity_1">
                                <p class="quantity-error-message">Quantity cannot be zero.</p>
                            </div>
                            <div class="add-delete-btn">
                                <!-- <button class="delete-item-btn">&times;</button> -->
                                <i class="delete-item-btn fa-solid fa-circle-minus"></i>
                            </div>
                            
                        </div>

                        
                        <button class="add-item-btn">Add more item</button>
                        <br><br>
                    </div>
                    <label for="image-pickup">Image</label>
                    <br>
                    <input type="file" name="image-pickup">
                    <p class="image-error-message">Please upload an image of your e-waste.</p>
                    <input type="hidden" name="pickup-image-preview" id="pickup-image-id-hidden" value="">
                    <br>
                    <label>Remark</label>
                    <br>
                    <textarea name="remark-pickup"></textarea>
                    <br><br><br>
                    <button class="submitBtn" type="submit" name="prSubmitBtn">Submit</button>
                    <button class="cancelprbtn" type="submit" name="drDeleteBtn" formaction="User-Profile-DeletePickupRequest.php">Cancel Dropoff Request</button>
                </div>
            </div>
        </form>
    </div>

    <div id="PickupEditSuccessPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Pickup request <br> edited successfully!</p>
    </div>

    <div id="PickupCancelSuccessPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Pickup request <br> cancelled successfully!</p>
    </div>

    <div class="dropoff-form-overlay"></div>

    <div class="modal">
        <form class="dropoff-form-popup animate" action="#" method="post">
            <div class="close-container">
                <span class="close" id="drClosePopup">&times;</span>
            </div>

            <div class="form-container">
                <h1>Drop-Off Request</h1>
                <p>
                    Fill out this form before heading to the drop-off points
                    to ensure a smooth drop-off process and earn your reward points.
                </p>
                <br><br>
                <input type="hidden" name="dropoff-id">

                <label for="date">Drop-off Date</label>
                <br>
                <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>" max="2100-12-31">
                <br><br>

                <label for="location">Drop-off Location</label>
                <?php
                    $conn=mysqli_connect("localhost","root","","cp_assignment");
                                    
                    if(mysqli_connect_errno()){
                        echo "Failed to connect to MySQL:".mysqli_connect_error();
                    }

                    $sql = "SELECT * FROM location WHERE status = 'Available' ORDER BY location_name";
    
                    $location_result = mysqli_query($conn, $sql);
                ?>
                <select name="location">
                    <option value="" disabled selected>Select a drop-off location</option> 
                    <?php while ($row = mysqli_fetch_assoc($location_result)) : ?>
                        <option value="<?php echo $row['location_id']; ?>">
                            <?php echo htmlspecialchars($row['location_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <br><br>

                <div class="checkbox-container">
                    <input type="checkbox" id="acknowledge" name="acknowledge">
                    <label for="acknowledge">
                        I hereby acknowledge and agree to accept the points allocated by the administrator upon the successful completion of the drop-off process.
                    </label>
                </div>
                <br><br>

                <button class="addformbtn" type="submit" name="drSubmitBtn">Submit</button>
                <button class="cancelbtn" type="submit" name="drDeleteBtn" formaction="User-Profile-DeleteDropoffRequest.php">Cancel Dropoff Request</button>
            </div>
        </form>
    </div>

    <div id="DropoffEditSuccessPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Dropoff request <br> edited successfully!</p>
    </div>

    <div id="DropoffCancelSuccessPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Dropoff request <br> cancelled successfully!</p>
    </div>

    <script>

        function getOS() {
            var userAgent = window.navigator.userAgent,
            platform = window.navigator.platform
            macosPlatforms = ["Macintosh", "MacIntel", "MacPPC", "Mac68K"],
            windowsPlatforms = ["Win32", "Win64", "Windows", "WinCE"],
            iosPlatforms = ["iPhone", "iPad", "iPod"],
            os = null;
        
            if (macosPlatforms.indexOf(platform) !== -1) {
            os = "Mac OS";
            } else if (iosPlatforms.indexOf(platform) !== -1) {
            os = "iOS";
            } else if (windowsPlatforms.indexOf(platform) !== -1) {
            os = "Windows";
            } else if (/Android/.test(userAgent)) {
            os = "Android";
            } else if (!os && /Linux/.test(platform)) {
            os = "Linux";
            }
        
            return os;
        }

        const scrollDiv = document.getElementById("scroll-div");
        if (getOS() == "Windows") {
            scrollDiv.classList.add("scrollbar");
        }else{
            scrollDiv.classList.add("scrollbar-mac");
        }

        function redirectToNotifications() {
            fetch("User-Notification.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "check_login=true"
            })
            .then(response => response.text())
            .then(isLoggedIn => {
                console.log("Login Check Response:", isLoggedIn); // Debugging

                if (isLoggedIn.trim() === "true") {
                    window.location.href = 'User-Notification.php';
                } else {
                    window.location.href = 'User-Login.php'; 
                }
            })
            .catch(error => console.error("Error checking login:", error));
        }

        document.getElementById('editProfile').addEventListener('click', function () {
            const editBtn = document.getElementById('editProfile');
            const changeImage = document.getElementsByClassName('change-profile')[0];
            const form = document.getElementById('userDetailForm');
            const emailInput = document.getElementsByClassName('user-email')[0];
            const dateJoinedInput = document.getElementsByClassName('user-joinedAt')[0];

            if (editBtn.value == "0"){
                changeImage.style.display = "inline";
                const allInputs = document.querySelectorAll('.profile-container input');
                allInputs.forEach(input => input.disabled = false);

                emailInput.disabled = true;
                dateJoinedInput.disabled = true;
                emailInput.classList.add("disabledInput");
                dateJoinedInput.classList.add("disabledInput");

                const textarea = document.querySelector(".user-address");
                textarea.disabled = false;

                const addressLabel = document.querySelector(".address-container label");
                addressLabel.style.paddingTop = "10px";

                adjustHeight(textarea);
                editBtn.innerHTML = '<i class="fa fa-save"></i> Save';
                editBtn.value = "1";
                
            }else{
                let hasError = false;

                let dobInput = document.querySelector('.user-dob');

                if (dobInput.value.includes("-")) {  // Convert back to YYYY-MM-DD
                    let dateParts = dobInput.value.split("-");
                    if (dateParts.length === 3) {
                        dobInput.value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                    }
                }

                const phoneInput = document.querySelector('.user-phoneNo');
                const phonePattern = /^01[0-46-9]\d{7,8}$/;
                if (phoneInput.value.trim() === "-" || phoneInput.value.trim() === "" || phoneInput.value.trim() == "0"){
                    phoneInput.value = "";
                    document.querySelector('.phone-error-message').style.display = "none";
                }else if(!phonePattern.test(phoneInput.value.trim())) {
                    document.querySelector('.phone-error-message').style.display = "block";
                    hasError = true;
                } else {
                    document.querySelector('.phone-error-message').style.display = "none";
                }

                const passwordInput = document.querySelector('.user-password');
                const passwordPattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
                if(passwordInput.value.trim() === ""){
                    document.querySelector('.pw-error-message').textContent = "Please enter your password.";
                    document.querySelector('.pw-error-message').style.display = "block";
                    hasError = true;
                } else if(!passwordPattern.test(passwordInput.value.trim())) {
                    document.querySelector('.pw-error-message').textContent = "Password must have at least 8 characters, including an uppercase letter, a lowercase letter, and a number.";
                    document.querySelector('.pw-error-message').style.display = "block";
                    hasError = true;
                } else {
                    document.querySelector('.pw-error-message').style.display = "none";
                }

                const enteredDOB = new Date(dobInput.value);
                const today = new Date();
                const minDOB = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
                if (dobInput.value.trim() === "") {
                    document.querySelector('.date-error-message').textContent = "Please choose your date of birth.";
                    document.querySelector('.date-error-message').style.display = "block";
                    hasError = true;
                }else if (enteredDOB > today){
                    document.querySelector('.date-error-message').textContent = "You cannot choose a future date.";
                    document.querySelector('.date-error-message').style.display = "block";
                    hasError = true;
                }else if (enteredDOB > minDOB){
                    document.querySelector('.date-error-message').textContent = "You must be at least 18 years old.";
                    document.querySelector('.date-error-message').style.display = "block";
                    hasError = true;
                }else {
                    document.querySelector('.date-error-message').style.display = "none";
                }

                const usernameInput = document.querySelector('.username');
                if (usernameInput.value.trim() === ""){
                    document.querySelector('.name-error-message').style.display = "block"; 
                    hasError = true;
                }else{
                    document.querySelector('.name-error-message').style.display = "none"; 
                }

                const address = document.querySelector(".user-address");
                if (address.value.trim() == "-" || address.value.trim() == ""){
                    address.value = "";
                }
                
                if (hasError) {
                    event.preventDefault();
                    return;
                }
                form.submit();

                allInputs.forEach(input => input.disabled = true);
                emailInput.disabled = true;
                dateJoinedInput.disabled = true;
                editBtn.innerHTML = '<i class="fa-solid fa-pen"></i> Edit Profile';
                editBtn.value = "0";
                changeImage.style.display = "none";

                const addressLabel = document.querySelector(".address-container label");
                addressLabel.style.paddingTop = "0px";

                const textarea = document.querySelector(".user-address");
                textarea.disabled = true;
                changeImage.style.display = "none";

                adjustHeight(textarea);
            }
        });

        function adjustHeight(el) {
            el.style.height = "auto"; 
            el.style.height = el.scrollHeight + "px"; 
        }

        document.addEventListener("DOMContentLoaded", function () {
            const textarea = document.querySelector(".user-address");

            if (textarea) {
                adjustHeight(textarea);

                textarea.addEventListener("input", function () {
                    adjustHeight(textarea);
                });
            }

            const allInputs = document.querySelectorAll('.profile-container input');
            allInputs.forEach(input => input.disabled = true);

            textarea.disabled = true;

            const dobInput = document.querySelector('.user-dob');
            const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];


            dobInput.addEventListener("focus", function () {
                showHidePWIcon.style.display = "none";
                if (this.value.includes("-")) {  // If the value is already in DD-MM-YYYY format
                    let dateParts = this.value.split("-");
                    if (dateParts.length === 3) {
                        this.value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`; // Convert to YYYY-MM-DD
                    }
                }
                this.type = "date";  // Ensure the input uses date picker
                this.max = new Date().toISOString().split("T")[0]; // Set max date to today
            });

            dobInput.addEventListener("blur", function () {
                if (this.value.includes("-")) {  // If the value is in YYYY-MM-DD format
                    let dateParts = this.value.split("-");
                    if (dateParts.length === 3) {
                        this.value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`; // Convert back to DD-MM-YYYY
                    }
                }
                this.type = "text";  // Switch back to text display format
            });


            function convertToYYYYMMDD(dateString) {
                const parts = dateString.split("-");
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`; 
                }
                return ""; 
            }

            function convertToDDMMYYYY(dateString) {
                const parts = dateString.split("-");
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`; 
                }
                return "";
            }

            const password = document.getElementsByName('user-password')[0];
            // const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];
            // password. = function(){
            //     showHidePWIcon.style.display = "inline";
            //     document.getElementsByClassName("showHidePw")[0].addEventListener("click",function(event){
            //         if (password.type === "password"){
            //             showHidePWIcon.src = "User-ViewPasswordIcon.png";
            //             password.type = "text";
            //         }else{
            //             showHidePWIcon.src = "User-HidePasswordIcon.png";
            //             password.type = "password";
            //         }
            //     });
            // }

            password.onclick = function(){
                showHidePWIcon.style.display = "inline";
            }

            document.getElementsByClassName("showHidePw")[0].addEventListener("click",function(event){
                if (password.type === "password"){
                    showHidePWIcon.src = "User-ViewPasswordIcon.png";
                    password.type = "text";
                }else{
                    showHidePWIcon.src = "User-HidePasswordIcon.png";
                    password.type = "password";
                }
            });

            password.onblur = function(){
                window.onclick = function(event) {
                    if (!password.contains(event.target) && !showHidePWIcon.contains(event.target)) {
                        showHidePWIcon.style.display = "none";
                    }
                }

            }
            

            // window.onclick = function(event) {
            //     if (!password.contains(event.target) && !showHidePWIcon.contains(event.target)) {
            //         showHidePWIcon.style.display = "none";
            //     }
            // }

            const recycleHistoryContent = document.querySelector('.recycle-History');
            const rewardHistoryContent = document.querySelector('.reward-History');
            const tabs = document.querySelectorAll('.tablink');

            if (!recycleHistoryContent || !rewardHistoryContent || tabs.length === 0) {
                console.error("One or more elements are missing.");
            } else {
                const savedTab = localStorage.getItem('activeTabIndex');
                const activeTabIndex = savedTab !== null ? parseInt(savedTab) : 0;

                tabs.forEach((tab, index) => {
                    tab.classList.remove('selected-tab');
                    if (index === activeTabIndex) {
                        tab.classList.add('selected-tab');
                    }
                });

                if (activeTabIndex === 1) {
                    recycleHistoryContent.style.display = "block";
                    rewardHistoryContent.style.display = "none";
                    localStorage.setItem('activeTabIndex', 1);
                } else {
                    recycleHistoryContent.style.display = "none";
                    rewardHistoryContent.style.display = "block";
                    localStorage.setItem('activeTabIndex', 0);
                }

                tabs.forEach((tab, index) => {
                    tab.addEventListener('click', () => {
                        localStorage.setItem('activeTabIndex', index);

                        tabs.forEach(tab => tab.classList.remove('selected-tab'));
                        tab.classList.add('selected-tab');

                        if (index === 1) {
                            recycleHistoryContent.style.display = "block";
                            rewardHistoryContent.style.display = "none";
                        } else {
                            recycleHistoryContent.style.display = "none";
                            rewardHistoryContent.style.display = "block";
                        }
                    });
                });
            }

            const openPopupBtn = document.getElementById("openPopup");
            const closePopupBtn = document.getElementById("closePopup");
            const modal = document.querySelector(".review-form-popup");
            const overlay = document.querySelector(".review-form-overlay");
            const body = document.body;

            function closePopup() {
                modal.classList.remove("show");
                overlay.classList.remove("show");
                body.style.overflow = "auto";

                setTimeout(() => {
                    modal.classList.add("hide");
                    overlay.classList.add("hide");
                    body.style.overflow = "auto";
                }, 300);

                setTimeout(() => {
                    modal.style.visibility = "hidden"; 
                    overlay.style.display = "none"; 
                    modal.classList.remove("hide"); 
                    overlay.classList.remove("hide");
                    body.style.overflow = "auto";
                }, 350);
            }

            if (openPopupBtn) {
                openPopupBtn.addEventListener("click", redirectToForm);
            }

            if (closePopupBtn) {
                closePopupBtn.addEventListener("click", closePopup);
            }

            overlay.addEventListener("click", function (event) {
                if (event.target === overlay) {
                    closePopup();
                }
            });

            const pOpenPopupBtn = document.getElementById("puOpenPopup");
            const pClosePopupBtn = document.getElementById("puClosePopup");
            const pumodal = document.querySelector(".pickup-form-popup");
            const puoverlay = document.querySelector(".pickup-form-overlay");

            function pclosePopup() {
                pumodal.classList.remove("show");
                puoverlay.classList.remove("show");
                body.style.overflow = "auto";

                setTimeout(() => {
                    pumodal.classList.add("hide");
                    puoverlay.classList.add("hide");
                    body.style.overflow = "auto";
                }, 300);

                setTimeout(() => {
                    pumodal.style.visibility = "hidden"; 
                    puoverlay.style.display = "none"; 
                    pumodal.classList.remove("hide"); 
                    puoverlay.classList.remove("hide");
                    body.style.overflow = "auto";
                }, 350);
            }

            if (pOpenPopupBtn) {
                pOpenPopupBtn.addEventListener("click", redirectToForm);
            }

            if (pClosePopupBtn) {
                pClosePopupBtn.addEventListener("click", pclosePopup);
            }

            puoverlay.addEventListener("click", function (event) {
                if (event.target === puoverlay) {
                    pclosePopup();
                }
            });

            const drOpenPopupBtn = document.getElementById("drOpenPopup");
            const drClosePopupBtn = document.getElementById("drClosePopup");
            const drmodal = document.querySelector(".dropoff-form-popup");
            const droverlay = document.querySelector(".dropoff-form-overlay");

            function drclosePopup() {
                drmodal.classList.remove("show");
                droverlay.classList.remove("show");
                body.style.overflow = "auto";

                setTimeout(() => {
                    drmodal.classList.add("hide");
                    droverlay.classList.add("hide");
                    body.style.overflow = "auto";
                }, 300);

                setTimeout(() => {
                    drmodal.style.visibility = "hidden"; 
                    droverlay.style.display = "none"; 
                    drmodal.classList.remove("hide"); 
                    droverlay.classList.remove("hide");
                    body.style.overflow = "auto";
                }, 350);
            }

            if (drOpenPopupBtn) {
                drOpenPopupBtn.addEventListener("click", redirectToForm);
            }

            if (drClosePopupBtn) {
                drClosePopupBtn.addEventListener("click", drclosePopup);
            }

            droverlay.addEventListener("click", function (event) {
                if (event.target === droverlay) {
                    drclosePopup();
                }
            });

            const itemSelects = document.querySelectorAll(".item-div select");
            function updateOptions() {
                let selectedItems = new Set();
                
                itemSelects.forEach(select => {
                    if (select.value) {
                        selectedItems.add(select.value);
                    }
                });

                itemSelects.forEach(select => {
                    let currentValue = select.value;
                    let options = select.querySelectorAll("option");

                    options.forEach(option => {
                        if (option.value && selectedItems.has(option.value) && option.value !== currentValue) {
                            option.hidden = true; 
                        } else {
                            option.hidden = false; 
                        }
                    });
                });
            }

            itemSelects.forEach(select => {
                select.addEventListener("change", updateOptions);
            });

            updateOptions(); 

            const addItemBtn = document.querySelector(".add-item-btn");
            const addDeleteBtn = document.querySelector(".add-delete-btn");
            const allPickupItems = document.querySelector(".all-pickup-item");
            const maxItems = <?php echo $maxItemCount; ?>; 

            function updateDeleteButtonState() {
                const allItems = document.querySelectorAll(".pickup-item-quantity-div");
                allItems.forEach((item, index) => {
                    const deleteBtn = item.querySelector(".delete-item-btn");
                    if (allItems.length === 1) {
                        deleteBtn.disabled = true;
                        addDeleteBtn.style.cursor = "not-allowed";
                        deleteBtn.classList.add("disabled-delete-btn");
                    } else {
                        deleteBtn.disabled = false;
                        addDeleteBtn.style.cursor = "pointer";
                        deleteBtn.classList.remove("disabled-delete-btn");
                    }
                });
            }

            function updateAddButtonState() {
                const currentItemCount = document.querySelectorAll(".pickup-item-quantity-div").length;
                if (currentItemCount >= maxItems) {
                    addItemBtn.disabled = true;
                    addItemBtn.classList.add("disabled-add-btn");
                } else {
                    addItemBtn.disabled = false;
                    addItemBtn.classList.remove("disabled-add-btn");
                }
            }

            function getSelectedItems() {
                let selectedItems = [];
                document.querySelectorAll(".pickup-item-quantity-div select").forEach(select => {
                    if (select.value) {
                        selectedItems.push(select.value);
                    }
                });
                return selectedItems;
            }
            
            function reindexItems() {
                const items = document.querySelectorAll(".pickup-item-quantity-div");

                items.forEach((item, index) => {
                    const itemNumber = index + 1;
                    item.querySelector(".item-div label").textContent = "Item " + itemNumber;
                    item.querySelector("select").name = `item_${itemNumber}`;
                    item.querySelector("input").name = `quantity_${itemNumber}`;
                });
            }

            function updateAvailableOptions() {
                let selectedItems = getSelectedItems();
                document.querySelectorAll(".pickup-item-quantity-div select").forEach(select => {
                    let currentValue = select.value;
                    select.querySelectorAll("option").forEach(option => {
                        if (option.value && selectedItems.includes(option.value) && option.value !== currentValue) {
                            option.hidden = true;
                        } else {
                            option.hidden = false;
                        }
                    });
                });
            }
            
            addItemBtn.addEventListener("click", function (e) {
                e.preventDefault();

                let existingItems = document.querySelectorAll(".pickup-item-quantity-div");
                if (existingItems.length >= maxItems) return;

                let newItem = existingItems[0].cloneNode(true);
                let itemNumber = existingItems.length + 1;
                newItem.querySelector(".item-div label").textContent = "Item " + itemNumber;
                newItem.querySelector("input").value = "";
                newItem.querySelector("select").selectedIndex = 0;
                newItem.querySelector("select").name = `item_${itemNumber}`;
                newItem.querySelector("input").name = `quantity_${itemNumber}`;

                allPickupItems.insertBefore(newItem, addItemBtn);
                updateDeleteButtonState();
                updateAddButtonState();
                updateAvailableOptions(); 
            });

            allPickupItems.addEventListener("click", function (e) {
                if (e.target.classList.contains("delete-item-btn")) {
                    e.preventDefault();
                    let itemToRemove = e.target.closest(".pickup-item-quantity-div");
                    if (itemToRemove) {
                        itemToRemove.remove();
                        updateItemLabels();
                        reindexItems();
                        updateDeleteButtonState();
                        updateAddButtonState();
                        updateAvailableOptions();
                    }
                }
            });

            function updateItemLabels() {
                let allItems = document.querySelectorAll(".pickup-item-quantity-div");
                allItems.forEach((item, index) => {
                    item.querySelector(".item-div label").textContent = "Item " + (index + 1);
                });
            }

            document.querySelectorAll(".pickup-item-quantity-div select").forEach(select => {
                select.addEventListener("change", updateAvailableOptions); 
            });

            setInterval(() => {
                updateAvailableOptions();
            }, 500);

            updateDeleteButtonState();
            updateAddButtonState();
            updateAvailableOptions();

        });

        document.getElementById("date-select").addEventListener("change", function() {
            var selectedDate = this.value;
            var timeSelect = document.getElementById("time-select");

            // Always reset and show "Select a time first" option
            timeSelect.innerHTML = '<option value="">Select a time</option>';

            // If user selects the default option, do not fetch time slots
            if (selectedDate === "") {
                return;
            }

            // Fetch available time slots if a valid date is selected
            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href.split('?')[0] + "?date=" + encodeURIComponent(selectedDate), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    timeSelect.innerHTML += xhr.responseText; // Append time options dynamically
                }
            };
            xhr.send();
        });

        function unformatMalaysianPhone(formattedNumber) {
            return formattedNumber.replace(/\D/g, '');
        }

        document.getElementsByClassName("pickup-form-popup")[0].addEventListener("submit", function(event){
            const date = document.getElementsByName("date-pickup")[0];
            const time = document.getElementsByName("time-pickup")[0];
            const phoneNo = document.getElementsByName("phone-no-pickup")[0];
            const address = document.getElementsByName("address-pickup")[0];
            const itemName = document.querySelectorAll(".pickup-item-quantity-div select");
            const quantity = document.querySelectorAll(".pickup-item-quantity-div input");
            const image = document.getElementsByName("image-pickup")[0];
            const previewImage = document.getElementById("pickup-image-preview");

            const dateEM = document.getElementsByClassName("p-date-error-message")[0];
            const timeEM = document.getElementsByClassName("time-error-message")[0];
            const phoneEM = document.getElementsByClassName("p-phone-error-message")[0];
            const addressEM = document.getElementsByClassName("address-error-message")[0];
            const itemEM = document.querySelectorAll(".item-error-message");
            const quantityEM = document.querySelectorAll(".quantity-error-message");
            const imageEM = document.getElementsByClassName("image-error-message")[0];

            let error = false;

            if (date.value.trim() === ""){
                dateEM.style.display = "block";
                error = true;
            }else{
                dateEM.style.display = "none";
            }

            if (time.value.trim() === ""){
                timeEM.style.display = "block";
                error = true;
            }else{
                timeEM.style.display = "none";
            }

            const phonePattern = /^01[0-46-9]\d{7,8}$/;
            if (phoneNo.value.trim() === ""){
                phoneEM.textContent = "Please enter your phone number.";
                phoneEM.style.display = "block";
                error = true;
            }else if(!phonePattern.test(phoneNo.value.trim())) {
                phoneEM.textContent = "Please enter valid phone number.";
                phoneEM.style.display = "block";
                error = true;
            } else {
                phoneEM.style.display = "none";
            }

            if (address.value.trim() === ""){
                addressEM.style.display = "block";
                error = true;
            }else{
                addressEM.style.display = "none";
            }

            itemName.forEach((item, index) => {
                if (item.value.trim() === ""){
                    itemEM[index].style.display = "block";
                    error = true;
                }else{
                    itemEM[index].style.display = "none";
                }
            });

            quantity.forEach((number, index) => {
                if (number.value.trim() < 0){
                    quantityEM[index].textContent = "Quantity cannot less than zero.";
                    quantityEM[index].style.display = "block";
                    error = true;
                }else if (number.value.trim() === ""){
                    quantityEM[index].textContent = "Quantity cannot be empty.";
                    quantityEM[index].style.display = "block";
                    error = true;
                }else if (number.value.trim() == 0){
                    quantityEM[index].textContent = "Quantity cannot be zero.";
                    quantityEM[index].style.display = "block";
                    error = true;
                }else{
                    quantityEM[index].style.display = "none";
                }
            });

            if (image.value.trim() === "" && previewImage.value.trim() === ""){
                imageEM.style.display = "block";
                error = true;
            }else{
                imageEM.style.display = "none";
            }

            if (error){
                event.preventDefault();
            }
        });

        document.getElementsByClassName("dropoff-form-popup")[0].addEventListener("submit", function(event){
            const dateInput = document.querySelector("input[name='date']");
            const locationSelect = document.querySelector("select[name='location']");
            const acknowledgeCheckbox = document.querySelector("input[name='acknowledge']");

            let hasError = false;

            removeError(dateInput);
            removeError(locationSelect);
            removeError(acknowledgeCheckbox);

            if (!dateInput.value.trim()) {
                addError(dateInput);
                hasError = true;
            }

            if (!locationSelect.value.trim()) {
                addError(locationSelect);
                hasError = true;
            }

            if (!acknowledgeCheckbox.checked) {
                acknowledgeCheckbox.classList.add("error-checkbox");
                hasError = true;
            }

            if (hasError) {
                event.preventDefault(); 
            }
        });

        function addError(element) {
            element.classList.add("error");
        }

        function removeError(element) {
            element.classList.remove("error");
        }

        const dateInput = document.querySelector("input[name='date']");
        const locationSelect = document.querySelector("select[name='location']");
        const acknowledgeCheckbox = document.querySelector("input[name='acknowledge']");

        dateInput.addEventListener("input", () => removeError(dateInput));
        locationSelect.addEventListener("change", () => removeError(locationSelect));
        acknowledgeCheckbox.addEventListener("change", () => acknowledgeCheckbox.classList.remove("error-checkbox"));

        document.getElementsByName("image-pickup")[0].addEventListener("change", function(event){
            const previewImage = document.getElementById("pickup-image-preview");
            document.getElementById("pickup-image-id-hidden").value = "";
            previewImage.style.display = "none";
            previewImage.value = "";
        });

        function showPickupPopup(id, type) {
            const overlay = document.querySelector(".pickup-form-overlay");
            const modal = document.querySelector(".pickup-form-popup");
            const body = document.body;

            if (!overlay || !modal) {
                console.error("Overlay or modal not found!");
                return;
            }

            overlay.style.display = "block"; 
            modal.style.visibility = "visible"; 
            body.style.overflow = "hidden";

            setTimeout(() => {
                overlay.classList.add("show");
                modal.classList.add("show");
                body.style.overflow = "hidden";
            
            }, 10);

            fetch('User-Profile-PickupSchedulingForm.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, type }),
            })
            .then(response => response.json()) 
            .then(data => {
                console.log("Fetched Data:", data);

                const dateSelect = document.getElementById("date-select");
                const timeSelect = document.getElementById("time-select");

                if (dateSelect) {
                    [...dateSelect.options].forEach(option => {
                        if (option.value === data.date) {
                            option.selected = true;
                        }
                    });
                }

                if (timeSelect) {
                    const timeOption = document.createElement("option");
                    timeOption.value = data.time;
                    timeOption.text = data.time;
                    timeOption.selected = true;
                    timeSelect.appendChild(timeOption);
                }

                const prID = document.getElementsByName("pickup-request-id")[0];
                prID.value = id;
                document.querySelector('input[name="phone-no-pickup"]').value = unformatMalaysianPhone(data.contact_no);
                document.querySelector('textarea[name="address-pickup"]').value = data.address;
                document.querySelector('textarea[name="remark-pickup"]').value = data.remark;
                document.querySelector('input[name="image-pickup"]').value = "";

                if (data.item_image) {
                    const existingPreview = document.querySelector("#pickup-image-preview");
                    if (existingPreview) {
                        existingPreview.remove();
                    }

                    const preview = document.createElement("iframe");
                    preview.id = "pickup-image-preview";
                    preview.name = "pickup-image-preview";
                    preview.src = `https://drive.google.com/file/d/${data.item_image}/preview`;
                    preview.value = data.item_image;
                    preview.style.marginTop = "10px";
                    document.getElementById("pickup-image-id-hidden").value = data.item_image;

                    document.querySelector('input[name="image-pickup"]').insertAdjacentElement("afterend", preview);
                }

                const allItemContainer = document.querySelector(".all-pickup-item");
                const addItemBtn = document.querySelector(".add-item-btn");

                const existingItems = allItemContainer.querySelectorAll(".pickup-item-quantity-div");
                existingItems.forEach((itemDiv, index) => {
                    if (index !== 0) itemDiv.remove();
                });

                const firstItem = data.items[0];
                if (firstItem) {
                    const firstItemSelect = document.querySelector('select[name="item_1"]');
                    const firstQuantityInput = document.querySelector('input[name="quantity_1"]');

                    [...firstItemSelect.options].forEach(option => {
                        if (option.value === firstItem.item_name) {
                            option.selected = true;
                        }
                    });

                    firstQuantityInput.value = firstItem.quantity;
                }

                for (let i = 1; i < data.items.length; i++) {
                    const item = data.items[i];

                    addItemBtn.click();

                    const allSelects = document.querySelectorAll('.item-div select');
                    const allQuantities = document.querySelectorAll('.quantity-div input');

                    const newItemSelect = allSelects[allSelects.length - 1];
                    const newQuantityInput = allQuantities[allQuantities.length - 1];

                    [...newItemSelect.options].forEach(option => {
                        if (option.value === item.item_name) {
                            option.selected = true;
                        }
                    });

                    newQuantityInput.value = item.quantity;
                }
            })
            .catch(error => console.error("Error fetching data:", error));
        }

        function showDropOffPopup(id, type) {
            const overlay = document.querySelector(".dropoff-form-overlay");
            const modal = document.querySelector(".dropoff-form-popup");
            const body = document.body;

            if (!overlay || !modal) {
                console.error("Overlay or modal not found!");
                return;
            }

            overlay.style.display = "block"; 
            modal.style.visibility = "visible"; 
            body.style.overflow = "hidden";

            setTimeout(() => {
                overlay.classList.add("show");
                modal.classList.add("show");
                body.style.overflow = "hidden";
            
            }, 10);

            fetch('User-Profile-DropoffForm.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, type }),
            })
            .then(response => response.json()) 
            .then(data => {
                console.log("Fetched Data:", data);
                document.getElementsByName("date")[0].value = data.date;
                document.getElementsByName("location")[0].value = data.location;
                document.getElementsByName("dropoff-id")[0].value = id;

            })
            .catch(error => console.error("Error fetching data:", error));
        }
       
        let selectedImage = null;
        document.getElementsByClassName('change-profile')[0].addEventListener('click', function () {
            const chooseImage = document.getElementsByClassName('chooseImageOverlay')[0];
            const currentImageSrc = document.getElementById('currentProfileImg').src;
            const currentImageFile = currentImageSrc.substring(currentImageSrc.lastIndexOf('/') + 1);
            chooseImage.style.display = "flex";
            document.getElementsByClassName('right-container')[0].style.zIndex = "-1";
            const containers = document.querySelectorAll('.ImageContainer');
            
            containers.forEach(container => {
                console.log(currentImageFile);
                if (container.getAttribute('data-value') === (currentImageFile)){
                    container.classList.add('ImageSelected');
                }else{
                    container.classList.remove('ImageSelected');
                }
            });

            containers.forEach(container => {
                container.addEventListener('click', () => {
                    containers.forEach(div => div.classList.remove('ImageSelected'));
                    container.classList.add('ImageSelected');
                    selectedImage = container.getAttribute('data-value');
                });
            });
        });

        document.getElementsByClassName("chooseImgBtn")[0].addEventListener('click', function() {
            const chooseImage = document.getElementsByClassName('chooseImageOverlay')[0];
            const currentProfileImage = document.getElementById('currentProfileImg');
            const currentProfileImageInput = document.getElementById('currentProfileImageInput');
            
            if (selectedImage) { 
                currentProfileImage.src = selectedImage;
                currentProfileImageInput.value = selectedImage;
            }

            chooseImage.style.display = "none";
            document.getElementsByClassName('right-container')[0].style.zIndex = "1";

        });

        // right container
        // document.getElementById("defaultOpen").click();   


        function showPopup(id,type) {
            const overlay = document.querySelector(".review-form-overlay");
            const modal = document.querySelector(".review-form-popup");
            const prOrDrID = document.getElementById("pr_dr_id");
            const prOrDrType = document.getElementById("pr_dr_type");

            if (!overlay || !modal) {
                console.error("Overlay or modal not found!");
                return;
            }

            overlay.style.display = "block"; 
            modal.style.visibility = "visible"; 
            prOrDrID.value = id;
            prOrDrType.value = type;

            setTimeout(() => {
                overlay.classList.add("show");
                modal.classList.add("show");
            }, 10);
        }

        function givenStar(star){
            const star1 = document.getElementsByClassName("star1")[0];
            const star2 = document.getElementsByClassName("star2")[0];
            const star3 = document.getElementsByClassName("star3")[0];
            const star4 = document.getElementsByClassName("star4")[0];
            const star5 = document.getElementsByClassName("star5")[0];
            const star_given = document.getElementById("star-given");

            if (star == 1){
                star1.classList.add("starSelected");
                star2.classList.remove("starSelected");
                star3.classList.remove("starSelected");
                star4.classList.remove("starSelected");
                star5.classList.remove("starSelected");
            }else if (star == 2){
                star1.classList.add("starSelected");
                star2.classList.add("starSelected");
                star3.classList.remove("starSelected");
                star4.classList.remove("starSelected");
                star5.classList.remove("starSelected");
            }else if (star == 3){
                star1.classList.add("starSelected");
                star2.classList.add("starSelected");
                star3.classList.add("starSelected");
                star4.classList.remove("starSelected");
                star5.classList.remove("starSelected");
            }else if (star == 4){
                star1.classList.add("starSelected");
                star2.classList.add("starSelected");
                star3.classList.add("starSelected");
                star4.classList.add("starSelected");
                star5.classList.remove("starSelected");
            }else if (star == 5){
                star1.classList.add("starSelected");
                star2.classList.add("starSelected");
                star3.classList.add("starSelected");
                star4.classList.add("starSelected");
                star5.classList.add("starSelected");
            }
            star_given.value = star;

        }

        const stars = document.querySelectorAll(".review-form-star i");

        stars.forEach(star => {
            star.addEventListener("mouseover", function () {
                let starValue = parseInt(this.getAttribute("data-star"));
                highlightStars(starValue);
            });

            star.addEventListener("mouseout", function () {
                resetStars();
            });

            star.addEventListener("click", function () {
                let starValue = parseInt(this.getAttribute("data-star"));
                selectStars(starValue);
            });
        });

        function highlightStars(starValue) {
            stars.forEach(star => {
                let value = parseInt(star.getAttribute("data-star"));
                if (value <= starValue) {
                    star.style.color = "#f8c455"; // Highlight on hover
                } else {
                    star.style.color = "lightgrey";
                }
            });
        }

        function resetStars() {
            stars.forEach(star => {
                if (!star.classList.contains("starSelected")) {
                    star.style.color = "lightgrey"; // Reset if not selected
                }
            });
        }

        function selectStars(starValue) {
            stars.forEach(star => {
                let value = parseInt(star.getAttribute("data-star"));
                if (value <= starValue) {
                    star.classList.add("starSelected");
                    star.style.color = "#f8c455";
                } else {
                    star.classList.remove("starSelected");
                    star.style.color = "lightgrey";
                }
            });
        }

        document.getElementsByClassName("review-form-popup")[0].addEventListener("submit", function(event) {
            const starGiven = document.getElementById("star-given");
            const reviewText = document.getElementById("review-text");
            const starError = document.getElementsByClassName("star-error-message")[0];
            const textError = document.getElementsByClassName("review-text-error-message")[0];
            let error = false;

            if (starGiven.value.trim() === "") {
                starError.style.display = "block";
                error = true;
            } else {
                starError.style.display = "none";
            }

            if (reviewText.value.trim() === "") {
                textError.style.display = "block";
                error = true;
            } else {
                textError.style.display = "none";
            }

            if (error) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>