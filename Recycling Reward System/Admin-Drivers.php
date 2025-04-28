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
            }else{
                return false;
            }
        } catch (Exception $e) {
            echo "Error uploading file: " . $e->getMessage();
            return false;
        }
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        $name = mysqli_real_escape_string($conn, $_POST['driver_name']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact_no']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $age = mysqli_real_escape_string($conn, $_POST['age']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $number_plate = mysqli_real_escape_string($conn, $_POST['number_plate']);
        $fileTmpName=$_FILES["driver_image"]["tmp_name"];
        $fileName=$_FILES["driver_image"]["name"];
        $imageTmpName=$_FILES["driver_license_image"]["tmp_name"];
        $imageName=$_FILES["driver_license_image"]["name"];

        if ($_FILES["driver_image"]["error"] !== UPLOAD_ERR_OK || $_FILES["driver_license_image"]["error"] !== UPLOAD_ERR_OK) {
            echo "<script>alert('File upload error. Please try again.');</script>";
            exit;
        }

        if (empty($name) || empty($contact) || empty($email) || empty($age) || empty($gender) || empty($address) || empty($number_plate)) {
            echo "<script>alert('Please fill in all required fields.');</script>";
            exit;
        }

        $fileID=uploadToGoogleDrive($fileTmpName, $fileName);
        $fileID2=uploadToGoogleDrive($imageTmpName, $imageName);

        if (!$fileID || !$fileID2) {
            echo "<script>alert('File upload to Google Drive failed. Please check your file permissions and try again.');</script>";
            exit;
        }
    
        $sql="INSERT INTO driver (driver_name, contact_no, email, age, gender, address, number_plate, driver_image, driver_license_image,status)
                VALUES ('$name', '$contact', '$email', '$age', '$gender', '$address', '$number_plate', '$fileID', '$fileID2','Available')";
    
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Driver added successfully.');window.location.href='Admin-Drivers.php';</script>";
        } else {
            echo "<script>alert('Error adding driver: " . mysqli_error($conn) . "');</script>";
        }
    
    }
    $con = mysqli_connect("localhost", "root", "", "cp_assignment");
    $result = mysqli_query($con, "SELECT * FROM driver ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drivers Management - Green Coin</title>
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
        overflow:auto;
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

    .upper-content{
        display:flex;
    }

    .addDriver {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: #78A24C;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 24px;
        border: none;
        cursor: pointer;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        transition: background 0.3s ease;
    }

    .addDriver:hover {
        background: #78A24C;
        scale: 1.1;
        transition: scale 0.3s ease;
    }

    .search-container {
        width: 87.2%;
        margin: 0 auto;
        position: relative;
        margin-left: calc(6% + 3px); 
    }

    .search-container i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #7d8fa1;
    }

    #search_driver {
        width: 100%;
        padding: 12px 20px 12px 45px;
        border-radius: 12px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: all 0.3s;
    }

    #search_driver:focus {
        outline: none;
    }

    .detail-card{
        display: flex;
        flex-direction: column;
        width: 79.5%;
        margin: 15px 60px 10px 75px;
        background-color: #ffffff;
        /* padding: 40px; */
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    #driver_table img {
        display: block;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    table {
        border-collapse:collapse;
        width:87%;
        font-size:15px;
        border:1px solid rgb(200, 200, 200);
        margin:20px 0px 20px 0px;
        position:center;
        background-color: rgba(255, 255, 255, 0.5);
    }

    table.center {
        margin-left: auto; 
        margin-right: auto;
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

    tr:hover {
        background-color: rgba(184, 194, 172, 0.05);
        cursor:pointer;
    }
    
    .row_hover:hover {
        background-color: rgba(184, 194, 172, 0.05);
        cursor: pointer
    } 

    .profile-pic-container {
        width: 60px;
        height: 60px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background-color: #f0f0f0; 
    }

    .drive-iframe-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .drive-iframe-wrapper iframe {
        position: absolute;
        top: -25%;
        left: -20%;
        width: 150%;
        height: 150%;
        border: none;
        pointer-events: none;
        transform-origin: center center;
    }

    #driver-image-preview, #license-image-preview {
        width: 125px;
        height: 100px;
        border: 1px solid black;
        display: none;
        object-fit: cover; 
        margin-bottom: 10px;
    }

    .show{
        display:block;
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

    .modal-body {
        /* padding-left: 40px;
        padding-right: 20px; */
        /* padding-bottom: 15px; */
        padding-top:15px;
        max-height: 36vh;
        overflow-y: auto;
        overflow-x:hidden;
    }

    .modal-body label{
        color: rgb(89,89,89);
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

    .imgcontainer {
        text-align: right;
        margin: 10px 20px 0 40px;
        position: relative;
    }

    input[type=text],[type=tel],[type=email],[type=number],textarea {
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

    .file-input{
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

    .savechanges{
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

    .delete-button{
        background-color: #ff6b6b; 
        border:none;
        padding: 8px 15px;
        border-radius:20px;
        color:white;
        cursor: pointer;
        font-size: 14px;
        align-items: center;
        gap: 5px;
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
    <div class="content">
        <div class="title">
            <h2>Drivers Management</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;">
        <center>
        <div class="upper-content">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search_driver" onkeyup="filterDriver()" placeholder="Search driver name...">
            </div>
        </div>
        </center>
        <div>
        <?php
            $con = mysqli_connect("localhost", "root", "", "cp_assignment");

            if (mysqli_connect_errno()) {
                echo "Failed to connect to MySQL: " . mysqli_connect_error();
            }

            $sql = "SELECT driver_id, driver_name, contact_no, email, age, gender, address, number_plate, driver_image, driver_license_image FROM driver WHERE status='Available' ORDER BY driver_name";
            $result = mysqli_query($con, $sql);

            if (!$result) {
                echo "Error fetching data: " . mysqli_error($con);
            }
            ?>

            <center>
            <div>
                <table id="driver_table">
                    <tr>
                        <th style="width:10%;"></th>
                        <th style="width:25%;">Name</th>
                        <th style="width:15%;">Car Plate</th>
                        <th style="width:15%;">Email</th>
                        <th style="width:18%;">Contact Number</th>
                    </tr>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        $fileID = $row['driver_image'];                     
                    ?>  
                    <tr class="row_hover" onclick="window.location.href='Admin-Drivers-Detail.php?driver_id=<?php echo $row['driver_id'];?>'">
                        <td>
                            <div class="profile-pic-container">
                                <?php if (!empty($row['driver_image'])): ?>
                                    <div class="drive-iframe-wrapper">
                                        <iframe src="https://drive.google.com/file/d/<?= $row['driver_image'] ?>/preview"></iframe>
                                    </div>
                                <?php else: ?>
                                    <i class="fa-solid fa-user-tie default-profile-icon"></i>
                                <?php endif; ?>
                            </div>
                        </td>   
                        <td><?php echo $row['driver_name'];?></td>
                        <td><?php echo $row['number_plate']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['contact_no']; ?></td>
                    </tr>
                <?php
                }
                ?>    
                </table>
            </div>
            </center>
        </div>
        <div>
            <button class="addDriver" name="addbutton" onclick="openAddModal()"><i class="fa-solid fa-plus"></i></button>
        </div>
    </div>

    <div id="adddriver" class="modal">
        <form id="addimage" enctype="multipart/form-data" method="POST" action="Admin-Drivers.php" class="modal-content">
            <div class="imgcontainer">
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="container">
                <div>
                    <h2>Add Driver</h2>
                    <p>Fill in the details below to add a driver.</p>
                </div>
                <div class="modal-body">
                    <label>Name</label>
                    <input type="text" id="add-name" name="driver_name" pattern="[A-Za-z\s'\-.,/]+" title="Please enter valid name" oninput="validateNameInput(event)" required><br><br>

                    <label>Contact Number</label>
                    <input type="tel" id="add-contactno" name="contact_no" pattern="[0-9\-]+" title="Please enter valid contact number" oninput="validateContactInput(event)" required ><br><br>

                    <label>Email Address</label>
                    <input type="email" id="add-email" name="email" required><br><br>

                    <label>Age</label>
                    <input type="number" id="add-age" name="age"  required><br><br>

                    <label>Gender</label>
                    <select id="edit_gender" name="gender" class="gender-select" required>
                        <option value="" disabled selected>-- Select gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select><br><br>

                    <label>Address</label>
                    <textarea id="add-address" name="address" required></textarea><br><br>

                    <label>Car Plate</label>
                    <input type="text" id="add-number-plate" name="number_plate"  required><br><br>

                    <label>Driver Image</label>
                        <div>
                            <img id="driver-image-preview" style="width:125px; height:100px; border: 1px solid black; display:none; object-fit:cover; margin-bottom:10px;">
                        </div>
                        <div>
                            <input type="file" class="file-input" id="add-driver-image" name="driver_image" accept="image/*" onchange="previewImage(event, 'driver-image-preview')" required><br><br>
                        </div>
                       
                    <label>Driver License Image</label>
                            <div>
                                <img id="license-image-preview" style="width:125px; height:100px; border: 1px solid black; display:none; object-fit:cover; margin-bottom:10px;">
                            </div>
                            <div>
                                <input type="file" class="file-input" id="add-driver-license-image" class="file-upload-input" name="driver_license_image" accept="image/*" onchange="previewImage(event, 'license-image-preview')" required>
                            </div>
                </div>
                    <button class="savechanges" type="submit" name="submit">Add Driver</button>
                
            </div>
         </form>
    </div>
    
    <?php
        $con = mysqli_connect("localhost", "root", "", "cp_assignment");

        if (mysqli_connect_errno()) {
            die("Failed to connect to MySQL: " . mysqli_connect_error());
        }

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

        const body = document.body;
        function openAddModal(){
            document.getElementById("adddriver").style.display = "flex";
            body.style.overflow = "hidden";
        }

        function closeAddModal() {
            document.getElementById("adddriver").style.display = "none";
            body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                body.style.overflow = "auto";
            }
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this driver?");
        }

        function filterDriver(){
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("search_driver");
            filter = input.value.toUpperCase();
            table = document.getElementById("driver_table");
            tr = table.getElementsByTagName("tr");
            
            var noResultsRow = document.getElementById("no-results-row");
            if (noResultsRow) {
                noResultsRow.remove();
            }
            
            var hasResults = false;
            
            for (i = 1; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        hasResults = true;
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
            
            if (!hasResults && filter.length > 0) {
                var tbody = table.getElementsByTagName('tbody')[0] || table;
                var newRow = tbody.insertRow(-1);
                newRow.id = "no-results-row";
                var newCell = newRow.insertCell(0);
                newCell.colSpan = 6; 
                newCell.style.textAlign = "center";
                newCell.style.padding = "20px";
                newCell.innerHTML = '<i class="fas fa-user-times" style="margin-right: 8px; color: #888;"></i>No driver found matching your search.';
            }
        }

        function previewImage(event, previewId) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    preview.src = e.target.result;
                    preview.style.display = "block";
                };
                reader.readAsDataURL(file);
            }
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
            const regex = /^[0-9\-]*$/;

            if (!regex.test(input.value)) {
                input.value = input.value.slice(0, -1);
            }
        }
            
    </script>

</body>
</html>