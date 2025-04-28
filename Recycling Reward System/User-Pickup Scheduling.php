<?php
    session_start();
    date_default_timezone_set('Asia/Kuala_Lumpur');
    require 'google-api-php-client/vendor/autoload.php'; 

    $user_id = $_SESSION["user_id"] ?? null;

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $unreadCount = 0; 

    if ($user_id) { 
        $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
        $unreadResult = mysqli_query($conn, $unreadQuery);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $unreadCount = $unreadData['unread_count'];
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

   

    if (!file_exists('keen-diode-454703-r9-847455d54fc8.json')) {
        die("Error: Authentication file not found.");
    }

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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $date = $_POST['date-pickup'];
        $time = $_POST['time-pickup'];
        $phone = $_POST['phone-no-pickup'];
        $address = $_POST['address-pickup'];
        $fileTmpName = $_FILES["image-pickup"]["tmp_name"];
        $fileName = $_FILES["image-pickup"]["name"];
        $remark = $_POST['remark-pickup'];
        $fileID = uploadToGoogleDrive($fileTmpName, $fileName);
        
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
    
        $addNewPickupRequestQuery = "INSERT INTO pickup_request (time_slot_id, datetime_submit_form, address, contact_no, remark, item_image, status, user_id) 
                  VALUES ('$getTimeSlotID', NOW() , '$address', '$phone', '$remark', '$fileID', 'Unread', '$user_id')";


        $system_announcement = "Your pickup request has been successfully scheduled! 🚚♻️
        Please note that your request, scheduled for ".$date." at ".$time.", is currently under review.
        You will receive a confirmation once it has been approved by our team.
        Thank you for recycling and taking a step toward a cleaner, greener future! 🌿✨";
        $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), 'Pickup Request Submitted ✅', '$system_announcement', 'unread')";
        mysqli_query($conn, $requestSubmittedNotiQuery);

        $admin_announcement = "A user has submitted a pickup request for ".$date." at ".$time.". Please review and approve or reject the request as soon as possible.";
        $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$user_id', NOW(), '📅 New Pickup Request Received!', '$admin_announcement', 'unread')";
        mysqli_query($conn, $newRequestNotiQuery);

        // Execute the query
        if (mysqli_query($conn, $addNewPickupRequestQuery)) {
            $pickup_id = mysqli_insert_id($conn);
    
            for ($i = 0; $i < count($items); $i++) {
                $item = $items[$i];
                $quantity = $quantities[$i];

                $getItemIDQuery = mysqli_query($conn, "SELECT item_id FROM item WHERE item_name = '$item' AND status= 'Available'");
                $item_id = mysqli_fetch_assoc($getItemIDQuery)['item_id'];
    
                $addNewItemPickupQuery = "INSERT INTO item_pickup (item_id, quantity, pickup_request_id) 
                              VALUES ('$item_id', '$quantity', '$pickup_id')";
                mysqli_query($conn, $addNewItemPickupQuery);
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
        echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const popup = document.getElementById('successPopup');
                        popup.style.display = 'block';
                        setTimeout(function() {
                            popup.style.display = 'none';
                            window.location.href = 'User-Pickup Scheduling.php';
                        }, 4000); 
                    });
                </script>";
        // mysqli_close($conn);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
    <title>Pickup Scheduling - Green Coin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playpen+Sans:wght@100..800&display=swap');

        *{
            margin: 0px;
            padding: 0px;
            font-family: "Open Sans", sans-serif;
            box-sizing: border-box;
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

        #scrollTopBtn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 50%;
            display: none; 
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 100;
        }

        #scrollTopBtn:hover {
            background-color: darkgreen;
            transform: scale(1.1);
        }

        footer{
            background-color: rgb(226, 234, 210);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            position: relative;
            width: 100%;
            height: 370px;
            padding: 3rem 1rem;
        }

        .footer-container{
            max-width: 1140px;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .footer-container p{
            font-family: "Playpen Sans", cursive;
            font-size: 14.5px;
            color:rgb(76, 76, 76);
            line-height: 1.5;
        }

        .row{
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            width: 100%;
            flex-wrap: wrap;
        }

        .col{
            min-width: 250px;
            color: black;
            padding: 0 2rem;
        }

        .col .footer-logo{
            width: 200px;
            margin-bottom: 25px;
            background-color: #78A24C;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
        }

        .col h3{
            font-family: "Playpen Sans", cursive;
            color: black;
            font-size: 17px;
            font-weight: 500;
            margin-bottom: 20px;
            position: relative;
        }

        .col h3::after{
            content:'';
            height: 3px;
            width: 0px;
            background-color:rgb(226, 186, 54);
            position: absolute;
            bottom: 0;
            left: 0;
            transition: 0.3s ease;
        }

        .col h3:hover::after{
            width: 30px;
        }

        .col .social a i{
            color: black;
            font-size: 23px;
            margin-top: 40px;
            margin-right: 15px;
            transition: 0.3 ease;
        }

        .col .social a i:hover{
            transform: scale(1.5);
            filter: grayscale(25);
        }

        .col .footer-nav a{
            font-family: "Playpen Sans", cursive;
            display: block;
            text-decoration: none;
            color: black;
            margin-bottom: 5px;
            position: relative;
            transition: 0.3 ease;
            font-size: 14.5px;
            color:rgb(76, 76, 76);
            line-height: 1.9;
            cursor: pointer;
        }

        .col .footer-nav a:before{
            content:'';
            height: 16px;
            width: 3px;
            position: absolute;
            top: 5px;
            left: -10px;
            background-color: green;
            transition: 0.3 ease;
            opacity: 0;
        }

        .col .footer-nav a:hover::before{
            opacity: 1;
        }

        .col .footer-nav a:hover{
            transform: translateX(-4px);
            color: green;
        }

        .col .contact-details{
            font-family: "Playpen Sans", cursive;
            display: flex;
            justify-content: column;
            align-items: flex-start;
            gap: 10px;
        }

        .col .contact-details i{
            margin-right: 10px;
            margin-top: 2px;
            font-size: 16px; 
        }

        .top-container{
            display:flex;
            background-image:url("User-PickupScheduling-Header-Stickman.svg");
            background-position:top center;
            background-repeat: no-repeat;
            background-size:100%;
            margin:50px 52px;
            padding: 6vh 5vh 12vh;
            border-radius: 30px;
        }

        .title-container{
            margin:auto;
            width: 45%;
        }

        .title-container h1{
            text-align:center;
            font-family: "Playpen Sans", cursive;
            font-size:40px;
            line-height: 2.1;
            letter-spacing: 2px;
            color:white;
        }

        .title-container p{
            font-size: 14px;
            text-align:center;
            color:white;
            font-family: "Playpen Sans", cursive;
        }

        .top-container button{
            display: inline-block;
            padding: 15px 30px;
            font-size: 15px;
            color: white;
            background: rgb(209, 137, 42);
            border: 2px solid rgb(209, 137, 42);
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .top-container button:hover{
            background: transparent;
            color: rgb(209, 137, 42);
        }

        .steps-title {
            padding: 40px 20px 20px;
            text-align: center;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
        }

        .steps-title h2 {
            font-family: "Playpen Sans", cursive;
            font-size: 27px;
            color:rgb(132, 91, 15);
            font-weight: bold;
        }
        .steps-title h3 {
            font-family: "Playpen Sans", cursive;
            font-size: 27px;
            color:rgb(175, 109, 24);
            font-weight: bold;
            padding-left: 10px;
        }

        mark {
            --color1:rgba(255, 225, 0, 0.76);
            --color2:rgba(255, 225, 0, 0.76);
            --height: 100%;    
            all: unset;
            background-image: linear-gradient(var(--color1), var(--color2));
            background-position: 0 100%;
            background-repeat: no-repeat;
            background-size: 0 var(--height);
            animation: highlight 1000ms 1 ease-out;
            animation-fill-mode: forwards;
            animation-play-state: paused; 
        }
        
        @keyframes highlight {
            to {
                background-size: 100% var(--height);
            }
        }

        .step-container{
            width: 80%;
            margin:50px auto;
            display:flex;
            flex-direction:row;
            gap:70px;
            align-items: center;
        }

        .step-content-container{
            width: 50%;
        }

        .all-step{
            display:flex;
            flex-direction:row;
            margin:20px;
        }

        .step-content{
            width: 100%;
            margin: 12px 15px;
            color: grey;
            transition: color 0.4s ease;
        }

        .step-number{
            position: relative;
        }

        .step-number i{
            display: inline-flex;
            height: 35px;
            width: 35px;
            color: white;
            border-radius: 50%;
            margin: 10px 0 10px;
            background:rgb(183, 183, 183);
            border: 2px solid rgb(183, 183, 183);
            justify-content: center;
            align-items: center;
            transition: background 0.4s ease, border 0.4s ease ;
            cursor: pointer;
        }

        .step-number-selected{
            background:rgb(12, 111, 42) !important;
            border: 2px solid rgb(11, 91, 35) !important;
            transition: background 0.4s ease, border 0.4s ease ;
        }

        .step-content-selected{
            color:black !important;
            transition: color 1.3s ease;
        }

        .step-number i::after{
            content: '';
            position: absolute;
            width: 2px;
            background: lightgray;
            top: 10%;
            /* height:87vh; */
            height:115%;
            bottom: 0;
            z-index:-1;
        }

        .step-number-3 i::after{
            width: 0;
            height: 0;
        }

        .step-content h3{
            font-size: 18px;
            line-height: 1.6;
            margin-bottom:10px;
        }

        .step-content p{
            font-size: 15px;
            line-height: 1.6;
        }

        .step-image-container{
            /* width: 50%;
            height: 42.5vh;
            margin: auto; */
            /* background-color:rgb(220, 233, 197); */
            width: 50%;
            height: auto;
            display: flex;              
            justify-content: center;   
            align-items: center;       
            position: relative;  
        }

        .step-image{
            /* margin-top: 45px;
            margin-left: 3vw; */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
            position: absolute;
            border:2px solid grey;
            box-sizing: border-box;
            /* width: 41vw; */
            box-sizing: border-box;
            width: 100%;              
            max-width: 41vw;   
        }

        .step-image-show{
            opacity: 1;
            visibility: visible;
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
            max-height: 50vh;
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
            height: 60px;
            resize:none;
            display: block;
        }
 
        .submitBtn{
            background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
            color: white;
            padding: 14px 20px;
            margin: 8px 0;
            border: none;
            cursor: pointer;
            width: 96%;
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

        .popup-error-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1001; 
            backdrop-filter: blur(5px);
            display: none;
        }

        .popup-error {
            width: 400px;
            background: linear-gradient(145deg, #ffffff, #e6e6e6); ;
            border-radius: 20px;
            text-align: center;
            position: relative;
            padding: 20px;
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3), 
                        inset 0px -4px 6px rgba(0, 0, 0, 0.2);
            transform: scale(0.9); 
            animation: popupAppear 0.3s ease-out forwards;
        }

        @keyframes popupAppear {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .popup-error-close{
            font-size:23px;
            margin-right: -320px;
            cursor: pointer;
            color:grey;
        }

        .popup-error-close:hover{
            color:black;
        }

        .popup-error-message {
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .popup-error button {
            background-color:rgb(94, 174, 37);
            border-radius: 20px;
            border: none;
            color: white;
            padding:10px 50px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .popup-error button:hover {
            box-shadow: 0px 8px 8px rgba(0, 0, 0, 0.1);
        }

        .popup-error button:active {
            transform: translateY(2px);
            box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.3);
        }

        .error-message-container img {
            display: block;
            margin: 15px auto;
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

        .date-error-message,
        .time-error-message,
        .phone-error-message,
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

        .calendar-title {
            padding: 60px 20px 0px;
            /* text-align: left; */
            margin-left:150px;
        }

        .calendar-title h2 {
            font-family: "Playpen Sans", cursive;
            font-size: 30px;
            color: rgb(27, 108, 12);
            font-weight: bold;
            margin-bottom:20px;
        }

        .calendar-title h3 {
            font-family: "Playpen Sans", cursive;
            color:rgb(121, 121, 121);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .calendar {
            width: 80%;
            background-color: rgba(204, 182, 142, 0.36);
            margin: 20px auto 50px;
            /* border: 2px solid rgba(132, 91, 15, 0.81); */
            border: 2px solid rgb(110, 110, 110);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
        }

        .calendar-header button {
            background-color: transparent;
            border: none;
            font-size: 30px;
            cursor: pointer;
            margin-top:-5px;
        }

        #month-year {
            font-size: 1.2em;
            font-weight: bold;
        }

        .calendar-weekdays, .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .calendar-dates{
            /* border-top: 1px solid rgba(132, 91, 15, 0.81); */
            border-top: 1px solid rgb(110, 110, 110);
            background-color: #fff;
        }

        .calendar-weekdays div{
            text-align: center;
            padding: 10px;
        } 
        
        .calendar-dates div {
            text-align: left;
            padding: 3px;
        }

        .calendar-weekdays {
            background-color: rgba(132, 91, 15, 0.31);
        }

        .calendar-weekdays div {
            font-weight: bold;
        }

        .date-container {
            display: flex;
            border: 1px solid rgb(218, 218, 218);
            flex-direction: column;
            /* border: 1px solid rgba(132, 91, 15, 0.81); */
            transition: background-color 0.3s;
            height:15vh;
        }

        .date-container:hover {
            /* background-color:rgba(120, 164, 44, 0.11); */
            background-color:rgba(201, 201, 201, 0.19); ;
        }

        .dayDiv{
            font-weight: bold;
            margin-top: 0;
            width: 3vh;
            height:3vh;
            text-align:center;
            align-items:center;
            justify-content:center;
        }

        .prev-month, .next-month{
            color:rgb(180, 180, 180);
            font-weight: bold;
            margin-top: 0;
            width: 3vh;
            height:3vh;
            text-align:center;
            align-items:center;
            justify-content:center;
        }

        .day{
            text-align:center;
            margin:auto;
        }

        .current-date {
            background-color:rgb(252, 88, 88);
            color: #fff;
            border-radius: 50%;
        }

        .current-date .day{
            padding-top:2px;
        }

        .events {
            width: 100%;
            text-align: center;
            /* letter-spacing: 1.5px; */
        }

        .event-item {
            background-color:rgb(225, 239, 199);
            /* background-color: rgba(132, 91, 15, 0.81); */
            color: rgb(51, 81, 0);
            font-size: 12px;
            border-radius: 3px;
            margin:1px;
            margin-bottom: 5px;
            padding-left:5px !important;
        }

        .event-item i {
            padding-right: 5px;
            color:rgb(123, 181, 24);
        }

        .empty-cell{
            /* border: 1px solid rgba(132, 91, 15, 0.81); */
        }

        .disabled-event {
            background-color: rgb(227, 226, 226);
            color:rgb(124, 124, 124);
            pointer-events: none; 
        }

        .disabled-event i{
            color: rgb(148, 148, 148);
        }

        .fully-booked-event {
            background-color:rgb(246, 229, 229);
            color: rgba(173, 46, 46, 0.5);
            pointer-events: none; 
        }

        .fully-booked-event i{
            color: rgba(173, 46, 46, 0.4);
        }

        .enabled-event{
            cursor: pointer;
        }

        .calendar-legend{
            display:flex;
            flex-direction:row;
            width: 80%;
            margin: -20px auto 50px;
            gap:60px;
            justify-content:center;
        }

        .calendar-legend input{
            height:15px;
            width: 15px;
            vertical-align:middle;
            border:1.5px solid grey;
            border-radius:2px;
        }

        .calendar-legend label{
            font-family: "Playpen Sans", cursive;
            font-size:15px;
            vertical-align:middle;
        }

        .past-slot-legend input{
            background-color:rgb(215, 215, 215);
        }

        .available-slot-legend input{
            background-color:rgb(209, 232, 168);
        }

        .fullybooked-slot-legend input{
            background-color:rgb(237, 203, 203);
        }

        .education-div{
        /* background: linear-gradient( rgba(232, 225, 208, 0.29), white); */
            background-color: #F8F6F1;
            border:2px solid black;
            margin: 100px 0px 50px 0px;
            padding: 50px 0px 40px;
            width: 60%;
        }

        .education-title{
            width: 100%;
            margin: 0px auto 50px;
            padding-left: 50px;
            text-align:left;
        }

        /* .education-title h3{
            font-family: "Playpen Sans", cursive;
            font-size: 25px;
            text-align: center;
            line-height: 1.5;
            margin-bottom: 20px;
            font-weight: bold;
            color:rgb(175, 109, 24);
            color:rgb(27, 108, 12);
            color:rgb(139, 99, 33);
        } 
*/
        .education-title h6{
            font-family: "Playpen Sans", cursive;
            font-size: 14px;
            /* color: white; */
            color:rgb(219, 127, 6);
            padding-bottom: 10px;
            font-weight: 400; 
        }

        .education-title h3{
            font-family: "Playpen Sans", cursive;
            font-size: 30px;
            /* color: white; */
            color:rgb(151, 87, 3);
            line-height: 1.4;
        }

        .education-title p{
            font-family: "Playpen Sans", cursive;
            font-size: 15px;
            text-align: center;
            line-height: 1.5;
            font-weight: 400;
            /* color: rgb(121, 121, 121); */
            color: black;
            width: 85%;
            margin: auto;
        }

        .education-container{
            display:flex;
            flex-direction: row;
            width: 90%;
            margin: 0px auto;
            gap:30px;
            justify-content:center;
        }

        .education-content{
            width: 35%;
            border: 1px solid grey;
            padding: 0px;
            border-radius: 20px;
            /* background-color:rgba(232, 225, 208, 0.29); */
            background-color:white;
        }

        .education-content img{
            width: 100.6%;
            border-top-left-radius:20px;
            border-top-right-radius:19px;
            margin-left: -1px;
        }

        .education-text{
            padding: 20px 30px 40px;
        }

        .education-text h4{
            font-size: 20px;
            font-weight:600;
            margin-bottom: 15px;
            font-family: "Playpen Sans", cursive;
            color:rgb(27, 108, 12);
            /* color:rgb(151, 87, 3); */

        }

        .education-text p{
            font-size:13px;
            line-height:1.5;
            letter-spacing: 0.5px;
        }

        .stickman-container{
            /* background-color:rgba(232, 225, 208, 0.29); */
            margin: 50px auto;
            justify-content:center;
            display:flex;
            flex-direction:row;
            gap:50px;
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
            z-index: 999999;
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

    </style>

</head>
<body>
    
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a  onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a class="active" onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
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
    
    <button id="scrollTopBtn">
        <i class="fa-solid fa-arrow-up"></i>
    </button>
    
    <div class="top-container">
        <div class="title-container">
            <h1>Pickup Scheduling</h1>
            <p>
                Schedule a pickup for your e-waste and have it collected from your location with ease, ensuring responsible recycling and environmental sustainability.
            </p>
            <br><br>
            <center>
                <button type="button" id="openPopup" onclick="redirectToForm()" id="openPopup">
                    Ready to Recycle? Let's Get Started!
                </button>
            </center>
        </div>       
    </div>

    <div class="steps-title">
        <h2><mark>Schedule, Hand Over & Get Rewarded</mark></h2><h3> - Effortless & Eco-Friendly!</h3>
    </div>

    <script type="text/javascript">
        (function (window, document) {
            const markers = document.querySelectorAll('mark');
            const observer = new IntersectionObserver(entries => {
                entries.forEach((entry) => {
                if (entry.intersectionRatio > 0) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
                });
            }, {
                threshold: 1
            });
            
            markers.forEach(mark => {
                observer.observe(mark);
            });
        })(window, document);
    </script>

    <div class="step-container">
        <div class="step-content-container">
            <div class="all-step" id="step1">
                <div class="step-number">
                    <i class="fa-solid fa-1 step-number-selected"></i>
                </div>
                <div class="step-content step-content-selected">
                    <h3>Schedule a Pickup</h3>
                    <p>Browse the calendar below to check available pickup slots. Select a date and fill out the form with your location, preferred time, and details of the e-waste you wish to recycle.
                    </p>
                </div>
            </div>
            <div class="all-step" id="step2">
                <div class="step-number">
                    <i class="fa-solid fa-2"></i>
                </div>
                <div class="step-content">
                    <h3>Confirmation & Pickup Process</h3>
                    <p>After submitting your request, check your notifications for approval or rejection. If approved, prepare your e-waste, and our team will collect it at the scheduled time.</p>
                </div>
            </div>
            <div class="all-step"  id="step3">
                <div class="step-number  step-number-3">
                    <i class="fa-solid fa-3"></i>
                </div>
                <div class="step-content">
                    <h3>Earn Rewards</h3>
                    <p>Once the pickup is successfully completed and verified, reward points will be added to your account as a token of appreciation for your contribution to a greener planet!</p>
                </div>
            </div>
        </div>
        <div class="step-image-container">
            <video name="image1" class="step-image step-image-show" autoplay muted loop width="700">
                <source src="User-PickupScheduling-Step1-Video.mp4"  type="video/mp4" >
            </video>
            <video name="image2" class="step-image" autoplay muted loop width="700">
                <source src="User-PickupScheduling-Step2-Video.mp4"  type="video/mp4" >
            </video>

            <video name="image3" class="step-image" autoplay muted loop width="700">
                <source src="User-PickupScheduling-Step3-Video1.mp4"  type="video/mp4" >
            </video>
        </div>
    </div>
    
    <div class="calendar-title">
        <h2>Pickup Availability</h2>
        <h3>Choose the most convenient date and time by simply clicking on a day in the calendar.</h3>
    </div>
    <div>
        <img style="margin-left: 65vw; margin-bottom: -30px; margin-top:-150px; z-index:-1;" src="User-PickupScheduling-Content-Stickman2.svg" width="400">
    </div>
    <div class="calendar">
        <div class="calendar-header">
            <button id="prev-month">‹</button>
            <div id="month-year"></div>
                <button id="next-month">›</button>
            </div>
            <div class="calendar-body">
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
            </div>
            <div class="calendar-dates">
                <!-- Dates will be populated here -->
            </div>
        </div>
    </div>
    <div class="calendar-legend">
        <div class="past-slot-legend">
            <input type="text" disabled>
            <label>No Longer Available</label>
        </div>
        <div class="available-slot-legend">
            <input type="text" disabled>
            <label>Available for Pickup</label>
        </div>
        <div class="fullybooked-slot-legend">
            <input type="text" disabled>
            <label>Fully Booked</label>
        </div>
    </div>

   <center>
        <div class="stickman-container">
            <img style="margin-top:500px; margin-bottom:-50px;" src="User-PickupScheduling-Content-Stickman3.svg" width="200">
            <img style="z-index:-100; margin-top:8px; margin-left: -90px; position:absolute;" src="User-PickupScheduling-Content-Stickman4.svg" width="1300">
            <div class="education-div">
                <div class="education-title">
                    <h6>DID YOU KNOW?</h6>
                    <h3>What Are The Problems With Electrical Waste?</h3>
                    <!-- <p>
                        Electrical waste, or e-waste, is one of the fastest-growing types of waste worldwide. 
                        It poses serious environmental and health risks when not properly managed, and contributes 
                        to the loss of valuable resources.
                    </p> -->
                </div>
                <div class="education-container"> 
                    <div class="education-content">
                        <img src="User-PickupScheduling-Education-Image1.svg">
                        <div class="education-text">
                            <h4>Loss of Valuable Resources</h4>
                            <p>When electrical items are thrown away instead of recycled, valuable materials like gold, copper, and aluminum are lost forever. These resources could be reused to make new products and reduce the need for mining.</p>
                        </div>
                    </div>
                    <div class="education-content">
                        <img src="User-PickupScheduling-Education-Image2.svg">
                        <div class="education-text">
                            <h4>Energy Use and Emissions</h4>
                            <p>Making new electronics uses energy and creates carbon emissions. Recycling old devices helps cut down pollution and can reduce the impact on our planet's climate.</p>
                        </div>
                    </div>
                    <div class="education-content">
                        <img src="User-PickupScheduling-Education-Image3.svg">
                        <div class="education-text">
                            <h4>Rising Volume of Waste</h4>
                            <p>As technology changes quickly, more and more electronics are being thrown away. This growing pile of e-waste is hard to manage and harmful to both people and the environment.</p>
                        </div>
                    </div>
                </div>
            </div>
           
        
        </div>
    </center>

    <div class="pickup-form-overlay">
    </div>
    <div id="pickupFormDiv" class="modal">
        <form class="pickup-form-popup" method="POST" enctype="multipart/form-data">
            <div class="close-container">
                <span class="close" id="closePopup">&times;</span>
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
                        <div>
                            <label>Date</label>
                            <br>
                            <select id="date-select" name="date-pickup">
                                <option value="">Select a date</option>
                                <?php 
                                    $getAllAvailableDateQuery = mysqli_query($conn, "SELECT DISTINCT date FROM time_slot WHERE date > NOW() + INTERVAL 5 DAY AND no_driver_per_slot > 0 ORDER BY date "); 
                                    while ($getAllAvailableDateResult = mysqli_fetch_assoc($getAllAvailableDateQuery)){
                                        echo '<option value="'.$getAllAvailableDateResult['date'].'">';
                                            echo $getAllAvailableDateResult['date'];
                                        echo '</option>'; 
                                    }
                                ?>
                            </select>
                            <p class="date-error-message">Please select a date.</p>
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
                    <p class="phone-error-message">Please enter your phone number.</p>
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
                    <input type="file" name="image-pickup" accept="image/*">
                    <p class="image-error-message">Please upload an image of your e-waste.</p>
                    <br>
                    <label>Remark</label>
                    <br>
                    <textarea name="remark-pickup"></textarea>
                </div>
                <br><br><br>
                <button class="submitBtn" type="submit" name="submitBtn">Submit</button>
            </div>
        </form>
    </div>

    <div class="popup-error-overlay" style="display: none;">
        <div class="popup-error">
            <span class="popup-error-close" onclick="handlePopupCloseBtn()">&times;</span>
            <div class="error-message-container">
                <img src="User-SignUp-ErrorIcon.png" width="110">
                <p class="popup-error-message"></p>
                <button class="error-ok" type="button" onclick="handlePopupClose()">OK</button>
            </div>
        </div>
    </div>

    <div id="successPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Pickup request <br> submitted successfully!</p>
    </div>

    <!-- <div class="stickman-container">
        <img style="margin-left: 500px; margin-bottom: -13px; margin-top:50px; z-index:-1;" src="User-PickupScheduling-Content-Stickman2.svg" width="600">
    </div> -->

    <footer>
        <div class="footer-container">
            <div class="row">
                <div class="col" id="company">
                    <img src="User-Logo.png" class="footer-logo" onclick="window.location.href='User-Homepage.php'">
                    <p>
                        Join the Green Revolution! <br>
                        Start recycling e-waste and earn rewards <br>
                        with Green Coin today!
                    </p>
                    <div class="social">
                        <a href="https://www.facebook.com"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com"><i class="fab fa-youtube"></i></a>
                        <a href="https://x.com"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.linkedin.com/feed/"><i class="fab fa-linkedin"></i></a>
                    </div>
                    <br><br>
                    <p style="font-size: 12px;">Copyright © 2025 Green Coin. All Rights Reserved.</p>
                </div>

                <div class="col" id="navigation">
                    <h3>Navigation</h3><br>
                    <div class="footer-nav">
                        <a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a>
                        <a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a>
                        <a onclick="window.location.href='User-Rewards.php'">Rewards</a>
                        <a onclick="window.location.href='User-Review.php'">Review</a>
                        <a onclick="window.location.href='User-FAQ.php'">FAQ</a>
                    </div>
                </div>

                <div class="col" id="contact">
                    <h3>Contact</h3><br>
                    <div class="contact-details">
                        <i class="fa fa-location"></i>
                        <p>
                            No 63 & 63, 1, Jalan Radin Tengah, <br>
                            Bandar Baru Sri Petaling, <br>
                            57000 Kuala Lumpur
                        </p>
                    </div>
                    <br>
                    <div class="contact-details">
                        <i class="fa fa-phone"></i>
                        <p>03-9054 0493</p>
                    </div>
                    <br>
                    <div class="contact-details">
                        <i class="fa-solid fa-envelope"></i>
                        <p>greencoinreward@gmail.com</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const scrollTopBtn = document.getElementById("scrollTopBtn");

            scrollTopBtn.style.display = "flex"; 

            scrollTopBtn.addEventListener("click", function () {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });
        });

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

        document.addEventListener("DOMContentLoaded", function(){
            const stepNumber = document.querySelectorAll(".step-number i");
            const stepContent = document.querySelectorAll(".step-content");
            const step1 = document.getElementById("step1");
            const step2 = document.getElementById("step2");
            const step3 = document.getElementById("step3");
            const step4 = document.getElementById("step4");
            const image1 = document.getElementsByName("image1")[0];
            const image2 = document.getElementsByName("image2")[0];
            const image3 = document.getElementsByName("image3")[0];

            const video1 = image1.tagName.toLowerCase() === "video" ? image1 : null;
            const video2 = image2.tagName.toLowerCase() === "video" ? image2 : null;
            const video3 = image3.tagName.toLowerCase() === "video" ? image3 : null;

            step1.onclick = function(){
                stepNumber[0].classList.add("step-number-selected");
                stepContent[0].classList.add("step-content-selected");
                stepNumber[1].classList.remove("step-number-selected");
                stepContent[1].classList.remove("step-content-selected");
                stepNumber[2].classList.remove("step-number-selected");
                stepContent[2].classList.remove("step-content-selected");

                image1.classList.add("step-image-show");
                image2.classList.remove("step-image-show");
                image3.classList.remove("step-image-show");

                if (video1) video1.play();
                if (video2) video2.pause();
                if (video3) video3.pause();
                
            }

            step2.onclick = function(){
                stepNumber[0].classList.remove("step-number-selected");
                stepContent[0].classList.remove("step-content-selected");
                stepNumber[1].classList.add("step-number-selected");
                stepContent[1].classList.add("step-content-selected");
                stepNumber[2].classList.remove("step-number-selected");
                stepContent[2].classList.remove("step-content-selected");

                image1.classList.remove("step-image-show");
                image2.classList.add("step-image-show");
                image3.classList.remove("step-image-show");

                if (video1) video1.pause();
                if (video2) video2.play();
                if (video3) video3.pause();
            }

            step3.onclick = function(){
                stepNumber[0].classList.remove("step-number-selected");
                stepContent[0].classList.remove("step-content-selected");
                stepNumber[1].classList.remove("step-number-selected");
                stepContent[1].classList.remove("step-content-selected");
                stepNumber[2].classList.add("step-number-selected");
                stepContent[2].classList.add("step-content-selected");

                image1.classList.remove("step-image-show");
                image2.classList.remove("step-image-show");
                image3.classList.add("step-image-show");

                if (video1) video1.pause();
                if (video2) video2.pause();
                if (video3) video3.play();
            }


            const openPopupBtn = document.getElementById("openPopup");
            const closePopupBtn = document.getElementById("closePopup");
            const modal = document.querySelector(".pickup-form-popup");
            const overlay = document.querySelector(".pickup-form-overlay");
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

            const errorOverlay = document.getElementsByClassName("popup-error-overlay")[0];
            const errorModal = document.getElementsByClassName("popup-error")[0];

            errorOverlay.addEventListener("click", function(event){
                if (event.target === errorOverlay ){
                    errorOverlay.style.display = "none";
                    errorModal.style.visiblility = "hidden";
                    body.style.overflow = "auto";
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

            function reindexItems() {
                const items = document.querySelectorAll(".pickup-item-quantity-div");

                items.forEach((item, index) => {
                    const itemNumber = index + 1;
                    item.querySelector(".item-div label").textContent = "Item " + itemNumber;
                    item.querySelector("select").name = `item_${itemNumber}`;
                    item.querySelector("input").name = `quantity_${itemNumber}`;
                });
            }
            
            addItemBtn.addEventListener("click", function (e) {
                e.preventDefault();

                let existingItems = document.querySelectorAll(".pickup-item-quantity-div");
                if (existingItems.length >= maxItems) return;

                let newItem = existingItems[0].cloneNode(true);
                let itemNumber = existingItems.length + 1;
                newItem.querySelector(".item-div label").textContent = "Item " + itemNumber;
                newItem.querySelector("select").selectedIndex = 0;
                newItem.querySelector("input").value = "";
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

        function showPickupPopup() {
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
        }

        function redirectToForm(){
            const userID = "<?php echo $user_id; ?>";
            if (userID.trim() === ""){
                showErrorPopup("Log in to your account to access this form."); 
            }else{
                showPickupPopup();
            }
        }

        function showErrorPopup(message) {
            const errorPopup = document.querySelector(".popup-error");
            const errorMessage = document.querySelector(".popup-error-message");
            const errorOverlay = document.querySelector(".popup-error-overlay");
            const okButton = document.querySelector(".error-ok");
            const body = document.body;

            errorMessage.textContent = message;
            errorOverlay.style.display = "flex";
            errorPopup.style.visibility = "visible";
            body.style.overflow = "hidden";

            document.querySelector(".popup-overlay").style.display = "none";
            document.querySelector(".modal-content").style.visibility = "hidden";
        }

        function handlePopupClose() {
            const errorPopup = document.querySelector(".popup-error");
            const errorOverlay = document.querySelector(".popup-error-overlay");
            const body = document.body;

            errorOverlay.style.display = "none";
            errorPopup.style.visibility = "hidden";
            body.style.overflow = "auto";
            window.location.href = "User-Login.php";
        }

        function handlePopupCloseBtn() {
            const errorPopup = document.querySelector(".popup-error");
            const errorOverlay = document.querySelector(".popup-error-overlay");
            const body = document.body;

            errorOverlay.style.display = "none";
            errorPopup.style.visibility = "hidden";
            body.style.overflow = "auto";
        }

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
        
        function formatMalaysianPhone(number) {
            const cleaned = number.replace(/\D/g, '');

            if (!cleaned.startsWith("01") || (cleaned.length !== 10 && cleaned.length !== 11)) {
                return "Invalid number format";
            }

            const prefix = cleaned.slice(0, 3);
            if (cleaned.length === 11) {
                return `${prefix}-${cleaned.slice(3, 7)} ${cleaned.slice(7)}`;
            } else {
                return `${prefix}-${cleaned.slice(3, 6)} ${cleaned.slice(6)}`;
            }
        }

        document.getElementsByClassName("pickup-form-popup")[0].addEventListener("submit", function(event){
            const date = document.getElementsByName("date-pickup")[0];
            const time = document.getElementsByName("time-pickup")[0];
            const phoneNo = document.getElementsByName("phone-no-pickup")[0];
            const address = document.getElementsByName("address-pickup")[0];
            const itemName = document.querySelectorAll(".pickup-item-quantity-div select");
            const quantity = document.querySelectorAll(".pickup-item-quantity-div input");
            const image = document.getElementsByName("image-pickup")[0];

            const dateEM = document.getElementsByClassName("date-error-message")[0];
            const timeEM = document.getElementsByClassName("time-error-message")[0];
            const phoneEM = document.getElementsByClassName("phone-error-message")[0];
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

            if (image.value.trim() === ""){
                imageEM.style.display = "block";
                error = true;
            }else{
                imageEM.style.display = "none";
            }

            if (error){
                event.preventDefault();
            }else{
                phoneNo.value = formatMalaysianPhone(phoneNo.value.trim());
            }
        });

        const calendarDates = document.querySelector('.calendar-dates');
        const monthYear = document.getElementById('month-year');
        const prevMonthBtn = document.getElementById('prev-month');
        const nextMonthBtn = document.getElementById('next-month');
        
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        let events = {}; // This will be populated from the database

        async function fetchEvents() {
            try {
                const response = await fetch('User-Pickup Scheduling-GetTime.php'); // Fetch events from the backend
                events = await response.json();
            } catch (error) {
                console.error("Error fetching events:", error);
            }
        }
        function renderCalendar(month, year) {
            calendarDates.innerHTML = '';
            monthYear.textContent = `${months[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const prevMonthDays = new Date(year, month, 0).getDate();

            let dayEndCell = 35;
            if (daysInMonth + firstDay > 35) {
                dayEndCell = 42;
            }

            const today = new Date();
            const oneWeekFromNow = new Date();
            oneWeekFromNow.setDate(today.getDate() + 5);
            // Loop for previous month's days
            for (let i = firstDay - 1; i >= 0; i--) {
                const dateContainer = document.createElement('div');
                dateContainer.classList.add('date-container');
                // dateContainer.style.backgroundColor = "rgba(243, 243, 243, 0.8)";

                const prevDayDiv = document.createElement('div');
                const prevDayP = document.createElement('p');
                prevDayDiv.classList.add('prev-month');
                prevDayP.classList.add('day');
                const prevDayNumber = prevMonthDays - i;
                prevDayP.textContent = prevDayNumber;

                // Calculate the date of the previous month
                let prevMonthDate = new Date(year, month - 1, prevDayNumber);  // month - 1 to get the previous month

                // Format the dateKey for the previous month
                let dateKey = `${prevMonthDate.getFullYear()}-${String(prevMonthDate.getMonth() + 1).padStart(2, '0')}-${String(prevMonthDate.getDate()).padStart(2, '0')}`;

                const eventDiv = document.createElement('div');
                eventDiv.classList.add('events');

                // Check if events exist for this dateKey in the previous month
                if (events[dateKey]) {
                    events[dateKey].forEach(event => {
                        let eventItem = document.createElement('div');
                        eventItem.innerHTML = `<i class="fa-solid fa-calendar-days"></i> ${event.content}`;
                        eventItem.classList.add('event-item');

                        let eventDate = new Date(dateKey);
                        const eventIsDisabled = eventDate < today || eventDate < oneWeekFromNow;

                        if (event.status === "disabled") {
                            eventItem.classList.add('disabled-event');
                        }else if (event.status === "enabled"){
                            dateContainer.classList.add("enabled-event");
                        }else if (event.status === "fully booked"){
                            eventItem.classList.add('fully-booked-event');
                        }

                        if(eventIsDisabled){
                            eventItem.classList.remove('fully-booked-event');
                            eventItem.classList.add('disabled-event');
                            dateContainer.classList.remove("enabled-event");
                        }

                        eventDiv.appendChild(eventItem);
                    });
                }
                prevDayDiv.appendChild(prevDayP);
                dateContainer.appendChild(prevDayDiv);
                dateContainer.appendChild(eventDiv);
                calendarDates.appendChild(dateContainer);
            }

            // Loop for current month's days
            for (let i = 1; i <= daysInMonth; i++) {
                const dateContainer = document.createElement('div');
                dateContainer.classList.add('date-container');
                const dayDiv = document.createElement('div');
                const dayP = document.createElement('p');
                dayP.textContent = i;
                dayP.classList.add('day');
                dayDiv.classList.add('dayDiv');

                if (i === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
                    dayDiv.classList.add('current-date');
                }else if(year < today.getFullYear()){
                    dayP.style.color = "rgb(180, 180, 180)";
                }else if (year === today.getFullYear() && month < today.getMonth()){
                    dayP.style.color = "rgb(180, 180, 180)";
                }else if (i <= today.getDate() && year === today.getFullYear() && month === today.getMonth()){
                    dayP.style.color = "rgb(180, 180, 180)";
                }

                let dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const eventDiv = document.createElement('div');
                eventDiv.classList.add('events');

                // Check if events exist for this date
                if (events[dateKey]) {
                    events[dateKey].forEach(event => {
                        let eventItem = document.createElement('div');
                        eventItem.innerHTML = `<i class="fa-solid fa-calendar-days"></i> ${event.content}`;
                        eventItem.classList.add('event-item');

                        let eventDate = new Date(dateKey);
                        const eventIsDisabled = eventDate < today || eventDate < oneWeekFromNow;
                        
                        if (event.status === "disabled") {
                            eventItem.classList.add('disabled-event');
                        }else if (event.status === "enabled"){
                            dateContainer.classList.add("enabled-event");
                        }else if (event.status === "fully booked"){
                            eventItem.classList.add('fully-booked-event');
                        }

                        if(eventIsDisabled){
                            eventItem.classList.remove('fully-booked-event');
                            eventItem.classList.add('disabled-event');
                            dateContainer.classList.remove("enabled-event");
                        }

                        eventDiv.appendChild(eventItem);
                    });
                }

                dayDiv.appendChild(dayP);
                dateContainer.appendChild(dayDiv);
                dateContainer.appendChild(eventDiv);
                calendarDates.appendChild(dateContainer);
            }

            // Loop for next month's days
            let totalCells = firstDay + daysInMonth;
            let nextDays = dayEndCell - totalCells;

            for (let i = 1; i <= nextDays; i++) {
                const dateContainer = document.createElement('div');
                dateContainer.classList.add('date-container');
                // dateContainer.style.backgroundColor = "rgba(243, 243, 243, 0.8)";

                const nextDayDiv = document.createElement('div');
                const nextDayP = document.createElement('p');
                nextDayDiv.classList.add('next-month');
                nextDayP.classList.add('day');
                nextDayP.textContent = i;

                let nextMonth = month + 1;  // increment the month
                let nextYear = year;

                // Check if the month exceeds 12 (December to January)
                if (nextMonth > 11) {
                    nextMonth = 0;  // January (0)
                    nextYear += 1;  // Next year
                }

                let dateKey = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const eventDiv = document.createElement('div');
                eventDiv.classList.add('events');

                // Check if events exist for this next month date
                if (events[dateKey]) {
                    console.log("Events found for", dateKey, events[dateKey]);
                    events[dateKey].forEach(event => {
                        let eventItem = document.createElement('div');
                        
                        eventItem.innerHTML = `<i class="fa-solid fa-calendar-days"></i> ${event.content}`;
                        eventItem.classList.add('event-item');

                        let eventDate = new Date(dateKey);
                        const eventIsDisabled = eventDate < today || eventDate < oneWeekFromNow;
                        
                        if (event.status === "disabled") {
                            eventItem.classList.add('disabled-event');
                        }else if (event.status === "enabled"){
                            dateContainer.classList.add("enabled-event");
                        }else if (event.status === "fully booked"){
                            eventItem.classList.add('fully-booked-event');
                        }

                        if(eventIsDisabled){
                            eventItem.classList.remove('fully-booked-event');
                            eventItem.classList.add('disabled-event');
                            dateContainer.classList.remove("enabled-event");
                        }

                        eventDiv.appendChild(eventItem);
                    });
                }

                nextDayDiv.appendChild(nextDayP);
                dateContainer.appendChild(nextDayDiv);
                dateContainer.appendChild(eventDiv);
                calendarDates.appendChild(dateContainer);
            }
        }

        // Initialize the calendar with events
        async function initializeCalendar() {
            await fetchEvents(); // Fetch events before rendering the calendar
            renderCalendar(currentMonth, currentYear);
        }

        initializeCalendar();


        prevMonthBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });

        nextMonthBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        calendarDates.addEventListener('click', (e) => {
            let selectedDayElement = e.target.closest('.date-container')?.querySelector('.day');
            if (!selectedDayElement) {
                console.log("No valid day clicked.");
                return;
            }

            let selectedDay = selectedDayElement.textContent;
            console.log("Selected Day:", selectedDay);

            let dateContainer = e.target.closest('.date-container');
            let eventDiv = dateContainer.querySelector('.events');

            let clickedMonth = currentMonth;
            let clickedYear = currentYear;

            if (dateContainer.querySelector('.prev-month')) {
                clickedMonth = currentMonth - 1;
                if (clickedMonth < 0) {
                    clickedMonth = 11;
                    clickedYear -= 1;
                }
            }

            if (dateContainer.querySelector('.next-month')) {
                clickedMonth = currentMonth + 1;
                if (clickedMonth > 11) {
                    clickedMonth = 0;
                    clickedYear += 1;
                }
            }

            let selectedDate = `${clickedYear}-${String(clickedMonth + 1).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')}`;
            console.log("Final Selected Date:", selectedDate);

            if (!eventDiv) {
                console.log("No event div found.");
                return;
            }

            let hasAvailableEvent = Array.from(eventDiv.querySelectorAll('.event-item')).some(eventItem => !eventItem.classList.contains('disabled-event') && 
            !eventItem.classList.contains('fully-booked-event'));
            
            console.log("Has Available Event:", hasAvailableEvent);

            if (!hasAvailableEvent) {
                console.log("No available event on this date.");
                return;
            }

            document.getElementById("date-select").value = selectedDate;
            var timeSelect = document.getElementById("time-select");
            timeSelect.innerHTML = '<option value="">Select a time</option>';
            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href.split('?')[0] + "?date=" + encodeURIComponent(selectedDate), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    timeSelect.innerHTML += xhr.responseText;
                }
            };
            xhr.send();
            redirectToForm();
        });
        localStorage.setItem('activeTabIndex', 0);

    </script>
</body>
</html>