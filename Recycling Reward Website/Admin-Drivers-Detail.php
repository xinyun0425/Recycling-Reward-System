<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin-Login.php");
    exit();
}
?>

<?php
require 'google-api-php-client/vendor/autoload.php'; 
$servername = "localhost";
$username = "root";  
$password = "";  
$dbname = "cp_assignment";  

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function uploadToGoogleDrive($fileTmpName, $fileName) {
    $client = new Google\Client();
    $client->setHttpClient(new GuzzleHttp\Client(['verify' => false])); 
    $client->setAuthConfig('keen-diode-454703-r9-50de3e47685d.json');
    $client->addScope(Google\Service\Drive::DRIVE_FILE);

    $service = new Google\Service\Drive($client);
    $fileMetadata = new Google\Service\Drive\DriveFile([
        'name' => $fileName,
        'parents' => ['1m1vF4txoCgpJsLV1zX6k87HS0URyEIh9']
    ]);
    $content = file_get_contents($fileTmpName);
    if (!$content) {
        die("Error: Unable to read file.");
    }
    
    $mimeType = mime_content_type($fileTmpName);
    try {
        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);
        if($file && isset($file->id)){
            $fileID = $file->id;
            error_log("Uploaded file ID: $fileID");
            error_log("View URL: https://drive.google.com/file/d/$fileID/preview");
    
            $permission = new Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
                'withLink' => true
            ]);
            $service->permissions->create($fileID, $permission);
            return $fileID;
        } else {
            return false;
        }
    } catch (Exception $e) {
        echo "Error uploading file: " . $e->getMessage();
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editbutton'])) {
    $driver_id = mysqli_real_escape_string($conn, $_POST['driver_id']);
    $driver_name = mysqli_real_escape_string($conn, $_POST['driver_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $number_plate = mysqli_real_escape_string($conn, $_POST['number_plate']);
    
    $driver_image = null;
    $driver_license = null;
    
    if ($_FILES["driver_image"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpName = $_FILES["driver_image"]["tmp_name"];
        $fileName = $_FILES["driver_image"]["name"];
        $driver_image = uploadToGoogleDrive($fileTmpName, $fileName);
        if (!$driver_image) {
            echo "<script>alert('Error uploading driver image.');</script>";
            exit;
        }
    }
    
    if ($_FILES["driver_license_image"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpName = $_FILES["driver_license_image"]["tmp_name"];
        $fileName = $_FILES["driver_license_image"]["name"];
        $driver_license = uploadToGoogleDrive($fileTmpName, $fileName);
        if (!$driver_license) {
            echo "<script>alert('Error uploading license image.');</script>";
            exit;
        }
    }

    $sql = "UPDATE driver SET 
            driver_name='$driver_name', 
            contact_no='$contact', 
            email='$email', 
            age='$age', 
            gender='$gender', 
            address='$address', 
            number_plate='$number_plate'";
    
    if ($driver_image) {
        $sql .= ", driver_image='$driver_image'";
    }
    if ($driver_license) {
        $sql .= ", driver_license_image='$driver_license'";
    }
    
    $sql .= " WHERE driver_id='$driver_id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('Driver detail updated successfully.');
                window.location.href='Admin-Drivers-Detail.php?driver_id=$driver_id'; 
              </script>";
    } else {
        echo "<script>alert('Error updating record: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Information - Green Coin</title>
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
        overflow-x:hidden;
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

    .driver_frame{
        background-color:white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        width:87.5%;
        padding:16px 25px 25px 25px;
        border-radius:12px;
        box-sizing: border-box;
        margin:15px 8px;
    }

    .driver_information{
        background-color:white;
        display:flex;
        flex-direction:column;
        width:70%;
    }

    .driver_frame h3 {
        border-bottom: 2px solid #ddd; 
        padding: 0 0 20px 10px; 
        color: #5D9C59 ;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        
    }

    .driver_table{
        display:flex;
        flex-direction:horizontal;
        margin-right:30px;
    }

    .driver-image{
        width: 25%;
        display: flex;
        flex-direction: column;
        justify-content:center;
        align-items: center;  
        justify-content: flex-start; 
        padding-top:10px;
        padding-right:20px; 
        border-radius:20px;
        position: relative;
        gap:15px;
    }

    .image-container{
        background: #f8f9fa;
        padding: 0px 20px 15px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        width: 100%;
        height:90%;
    }

    .image-container h4{
        color: #666;
        display:flex;
        justify-content:center;
    }

    .license-image-container{
        background: #f8f9fa;
        padding: 0px 20px 15px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-top:10px;
        width: 100%;
        height:90%;
    }

    .license-image-container h4{
        color: #666;
        display:flex;
        justify-content:center;        
    }

    .image-background {
        width: 100%;
        aspect-ratio: 1/1; 
        border-radius: 8px;
        background-color: #f8f9fa;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 15px 0;
    } 

    .image-background2 {
        width: 100%;
        height: 180px; 
        border-radius: 8px;
        background-color: #f8f9fa;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 15px 0;
    }

    .drive-iframe-wrapper,.drive-iframe-wrapper2  {
        position: relative;
        width: 100%;
        height: 90%;
        overflow: hidden;
    }

    .drive-iframe-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        pointer-events: none;
        transform: scale(1.6); 
        transform-origin: center center;
    }

    .drive-iframe-wrapper2 iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        pointer-events: none;
        transform: scale(1.125); 
        transform-origin: center center;
    }

    .driver-action {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 60px;
    }

    .action-button {
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

    .action-button i {
        font-size: 16px;
    }

    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .editbutton {
        background-color: #789AF4;
        color: white;
    }

    .deletebutton {
        background-color: #F56D6D;        
        color: white;
    }

    .driver-action form {
        margin: 0;
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

    .modal-scroll-wrapper{
        overflow-y:auto;
        max-height:80vh;
    }

    .modal-content p {
        margin-bottom: 20px;
        color: rgb(89,89,89);
        line-height: 1.5;
        font-size:16px;
    }

    .modal-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 25px;
    }

    .modal-details label {
        font-weight: 600;
        color: #555;
    }

    .modal-details span {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 6px;
        display: block;
    }

    .modal-header {
        font-size: 30px;
        line-height: 1.8;
    }

    .header-content {
        padding-left: 40px;
        padding-right: 40px;
        padding-bottom: 5px;
    }

    .modal-body {
        /* padding-bottom: 15px; */
        padding-top:15px;
        max-height: 36vh;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .modal-body{
        color: rgb(89,89,89);
    }

    .imgcontainer {
        text-align: right;
        margin: 10px 20px 0 40px;
        position: relative;
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

    .close {
        position: absolute;
        right: 5px;
        top: 5px;
        color:rgb(133, 133, 133);
        font-size: 35px;
        cursor:pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    input[type=text],[type=tel],[type=email],[type=number], select,textarea {
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color:#fff;
        font-family:Arial,sans-serif;
        border-radius:5px;
        resize:none;
        border: 1px solid #ccc; 
        outline: none; 
    }

    .gender-select{
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color:#fff;
        font-family:Arial,sans-serif;
        border-radius:5px;
        resize:none;
        border: 1px solid #ccc; 
        outline: none;
        appearance:none;
        background-image: url("data:image/svg+xml,%3Csvg fill='gray' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 24px 24px;
        padding-right: 40px;
    }

    .savebutton {
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

    .image-preview-container {
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }

    .image-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 1px solid #ddd;
        object-fit: cover;
        display: block;
    }

    .detail-box {
        flex: 1;
        background: #f8f9fa; 
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin:10px;
        width:80%;
        display:flex;
        justify-content:flex-start;
        text-align: left; 
        flex-direction: column; 
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

    .detail-box-end {
        flex: 1;
        background: #f8f9fa; 
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin:10px 10px 0px 10px;
        width:80%;
        display:flex;
        justify-content:flex-start;
        text-align: left; 
        flex-direction: column; 
    }

    .detail-box-end span {
        display: block;
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }

    .detail-box-end strong {
        font-size: 16px;
        color: #333;
    }

    .file-input {
        width: 100%;
        padding: 12px;
        margin: 8px 0px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size:16px;
        outline:none;
        transition: all 0.3s ease-in-out;
        box-sizing: border-box;
        background-color: white;
        cursor: pointer;
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
            <li class="active"><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
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
        $con = mysqli_connect("localhost", "root", "", "cp_assignment");

        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            exit();
        }

        if (isset($_GET['driver_id'])) {
            $driver_id = mysqli_real_escape_string($con, $_GET['driver_id']);
            $sql = "SELECT driver_id, driver_name, contact_no, email, age, gender, address, number_plate, driver_image,driver_license_image FROM driver WHERE driver_id='$driver_id'";
            $result = mysqli_query($con, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $driver_id = $row['driver_id'];
                $driver_name = $row['driver_name'];
                $contact = $row['contact_no'];
                $email = $row['email'];
                $age = $row['age'];
                $gender = $row['gender'];
                $address = $row['address'];
                $number_plate = $row['number_plate'];
                $driver_image=$row['driver_image'];
                $driver_license=$row['driver_license_image'];
            } else {
                echo "<script>alert('No driver details.'); window.location.href='Admin-Drivers.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('No driver selected.'); window.location.href='Admin-Drivers.php';</script>";
            exit();
        }
    ?>
    <div class="content">
        <h2 class='title'>
            <a href='Admin-Drivers.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Driver Information
        </h2>

        <hr style="width: 92%; margin-left:45px;">
        <center>
        <div>
            <div class="driver_frame">
                <h3><i class="fas fa-user"></i>Driver Information</h3>
                <div class="driver_table">
                    <div class="driver_information">
                        <div class="detail-box">
                            <span>Name </span>
                            <strong><?php echo htmlspecialchars($driver_name); ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Contact Number</span>
                            <strong><?php echo htmlspecialchars($contact); ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Email Address</span>
                            <strong><?php echo htmlspecialchars($email); ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Age</span>
                            <strong><?php echo htmlspecialchars($age); ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Gender</span>
                            <strong><?php echo htmlspecialchars($gender); ?></strong>
                        </div>
                        <div class="detail-box">
                            <span>Address</span>
                            <strong><?php echo htmlspecialchars($address); ?></strong>
                        </div>
                        <div class="detail-box-end">
                            <span>Car Plate</span>
                            <strong><?php echo htmlspecialchars($number_plate); ?></strong>
                        </div>
                    </div>
                    <div class="driver-image">
                        <div class="image-container">
                            <h4>Driver Image</h4>
                            <div class="image-background">
                                <div class="drive-iframe-wrapper">
                                    <iframe src="https://drive.google.com/file/d/<?= $driver_image ?>/preview" width="150" height="150" style="border-radius:20px; border:none;"></iframe>              
                                </div>
                            </div>
                        </div>
                        <div class="license-image-container">
                            <h4>Driver License Image</h4>
                            <div class="image-background2">
                                <div class="drive-iframe-wrapper2">
                                    <iframe src="https://drive.google.com/file/d/<?= $driver_license ?>/preview" width="175" height="150" style="border-radius:20px; border:none;"></iframe>              
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>   
        </center>    
        <div class="driver-action">
            <button type="button" class="action-button editbutton" onclick="openEditModal('<?php echo $driver_id; ?>','<?php echo htmlspecialchars($driver_name); ?>','<?php echo htmlspecialchars($contact); ?>','<?php echo htmlspecialchars($email); ?>','<?php echo htmlspecialchars($age); ?>','<?php echo htmlspecialchars($gender); ?>','<?php echo htmlspecialchars($address); ?>','<?php echo htmlspecialchars($number_plate); ?>','<?php echo $driver_image; ?>','<?php echo $driver_license; ?>')">            
            <i class="fa-solid fa-pen"></i> Edit
            </button>

            <form method="POST" onsubmit="return confirmDelete()" onclick="event.stopPropagation();">
                <input type="hidden" name="delete_driver_id" value="<?php echo $row['driver_id']; ?>">
                <button type="submit" name="deletebutton" class="action-button deletebutton">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div id="editdriver" class="modal">
        <form class="modal-content" action="#" method="post" enctype="multipart/form-data">
            <div class="imgcontainer">
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="container">
                <div>
                    <h2>Edit Driver Details</h2>
                    <p>Please update the driver details below.</p>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_driver_id" name="driver_id">

                    <label>Name</label>
                    <input type="text" id="edit_driver_name" name="driver_name" pattern="[A-Za-z\s'\-.,/]+" title="Please enter valid name" oninput="validateNameInput(event)" placeholder="Enter driver name" required><br><br>

                    <label>Contact Number</label>
                    <input type="text" id="edit_contact" name="contact_no" pattern="[0-9\-]+" title="Please enter valid contact number"  oninput="validateContactInput(event)" placeholder="Enter driver contact number" required><br><br>

                    <label>Email Address</label>
                    <input type="email" id="edit_email" name="email" placeholder="Enter driver email address" required><br><br>

                    <label>Age</label>
                    <input type="number" id="edit_age" name="age" placeholder="Enter driver age" required><br><br>

                    <label>Gender</label>
                    <select id="edit_gender" name="gender" class="gender-select" required>
                        <option value="" disabled selected>--Select gender--</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select><br><br>

                    <label>Address</label>
                    <textarea id="edit_address" name="address" required></textarea><br><br>

                    <label>Car Plate</label>
                    <input type="text" id="edit_number_plate" name="number_plate" placeholder="Enter driver's number plate" required><br><br>

                    <label>Driver Image</label>
                        <div>
                            <input type="file" id="edit_driver_image" name="driver_image" style="color:black" class="file-input" accept="image/*" ><br><br>
                        </div>

                    <label>Driver License Image</label>
                        <div>
                            <input type="file" id="edit_driver_license_image" name="driver_license_image" style="color:black" class="file-input" accept="image/*" >
                        </div>
                </div>
                    <button type="submit" class="savebutton" name="editbutton">Save Changes</button>
            </div>
        </form>
    </div>

    <?php
        if (isset($_POST['editbutton'])) {
            $driver_id = mysqli_real_escape_string($con, $_POST['driver_id']);
            $driver_name = mysqli_real_escape_string($con, $_POST['driver_name']);
            $contact = mysqli_real_escape_string($con, $_POST['contact_no']);
            $email = mysqli_real_escape_string($con, $_POST['email']);
            $age = mysqli_real_escape_string($con, $_POST['age']);
            $gender = mysqli_real_escape_string($con, $_POST['gender']);
            $address = mysqli_real_escape_string($con, $_POST['address']);
            $number_plate = mysqli_real_escape_string($con, $_POST['number_plate']);

            $sql = "UPDATE driver SET driver_name='$driver_name', contact_no='$contact', email='$email', age='$age', gender='$gender', address='$address', number_plate='$number_plate' WHERE driver_id='$driver_id'";

            if (mysqli_query($con, $sql)) {
                echo "<script>
                        alert('Driver detail updated successfully.');
                        window.location.href='Admin-Drivers-Detail.php'; 
                    </script>";
            } else {
                echo "<script>alert('Error updating record: " . mysqli_error($con) . "');</script>";
            }
        }
    ?>

    <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_driver_id'])) {
            $driver_id = mysqli_real_escape_string($con, $_POST['delete_driver_id']);

            $update_query = "UPDATE driver SET status = 'Unavailable' WHERE driver_id = '$driver_id'";
            if (mysqli_query($con, $update_query)) {
                echo "<script>alert('Driver deleted successfully.'); window.location.href = 'Admin-Drivers.php';</script>";
            } else {
                echo "<script>alert('Error deleting driver. Please try again.');</script>";
            }
        }

        $result = mysqli_query($con, "SELECT * FROM driver");
    ?>

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
    <script>
        function openEditModal(driver_id, driver_name, contact, email, age, gender, address, number_plate, driver_image, driver_license) {
            document.getElementById("edit_driver_id").value = driver_id;
            document.getElementById("edit_driver_name").value = driver_name;
            document.getElementById("edit_contact").value = contact;
            document.getElementById("edit_email").value = email;
            document.getElementById("edit_age").value = age;
            document.getElementById("edit_gender").value = gender;
            document.getElementById("edit_address").value = address;
            document.getElementById("edit_number_plate").value = number_plate;

            document.getElementById("editdriver").style.display = "flex";
            document.body.style.overflow = "hidden";
        }
        const body = document.body;

        function closeModal() {
            document.getElementById("editdriver").style.display = "none";
            body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                body.style.overflow = "auto";
            }
        }

        function clearImage(inputId, previewId) {
            document.getElementById(inputId).value = '';
            const preview = document.getElementById(previewId);
            preview.src = preview.dataset.originalSrc || '#'; 
            preview.style.display = "block";
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this driver?");
        }

        function validateNameInput(event) {
            const input = event.target;
            const regex = /^[A-Za-z\s'\-.,/]*$/; 
            
            if (!regex.test(input.value)) {
                input.value = input.value.slice(0, -1);
            }
        }

        function validateContactInput(event) {
            const input = event.target;
            input.value = input.value.replace(/[^0-9\-]/g, '');
        }
    </script>

</body>
</html>