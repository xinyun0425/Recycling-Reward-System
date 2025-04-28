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
        'parents' => ['1mEBcWnCo4RPJqXuUCnWo1xkhjAEJqS70']
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

$dropoff_id = mysqli_real_escape_string($conn, $_REQUEST['dropoff_id']);
if(!$dropoff_id) {
    echo "<script>alert('No dropoff request selected.'); window.location.href='Admin-Dropoff.php';</script>";
    exit();
}
    $sql = "SELECT 
        dropoff.dropoff_id,
        dropoff.dropoff_date,
        dropoff.status,
        dropoff.total_point_earned,
        dropoff.item_image,
        user.username,
        user.email,
        user.phone_number,
        user.user_id,  
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
    LEFT JOIN item ON item.item_id = item_dropoff.item_id    
    WHERE dropoff.dropoff_id = '$dropoff_id'";

    $items_sql = "SELECT 
        item_dropoff.quantity,
        item.item_name,
        item.item_id,
        item.point_given  
    FROM item_dropoff
    LEFT JOIN item ON item_dropoff.item_id = item.item_id
    WHERE item_dropoff.dropoff_id = '$dropoff_id'";

    $items_result = mysqli_query($conn, $items_sql);
    $items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
        
    $result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo "<script>alert('No dropoff request details found.'); window.location.href='Admin-Dropoff.php';</script>";
    exit();
}

$request = $result->fetch_assoc();

$items_query = "SELECT item_id, item_name, point_given FROM item";
$items_result = $conn->query($items_query);
$items = [];
if ($items_result && $items_result->num_rows > 0) {
    while($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
}

// Add this near the top of your PHP code (after database connection)
// Handle form submission for saving items
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_items'])) {
//     try {
//         $dropoff_id = mysqli_real_escape_string($conn, $_POST['dropoff_id']);
        
//         // Start transaction
//         mysqli_begin_transaction($conn);
        
//         // First delete existing items for this dropoff
//         $delete_query = "DELETE FROM item_dropoff WHERE dropoff_id = '$dropoff_id'";
//         if (!mysqli_query($conn, $delete_query)) {
//             throw new Exception("Failed to clear existing items");
//         }
        
//         // Process each item
//         if (isset($_POST['item_id'])) {
//             foreach ($_POST['item_id'] as $index => $item_id) {
//                 $item_id = mysqli_real_escape_string($conn, $item_id);
//                 $quantity = mysqli_real_escape_string($conn, $_POST['quantity'][$index]);
                
//                 // Handle file upload if exists
//                 $image_id = null;
//                 if (!empty($_FILES['item_image']['name'][$index])) {
//                     $file_tmp = $_FILES['item_image']['tmp_name'][$index];
//                     $file_name = $_FILES['item_image']['name'][$index];
//                     $image_id = uploadToGoogleDrive($file_tmp, $file_name);
//                     if (!$image_id) {
//                         throw new Exception("Failed to upload image");
//                     }

//                     $update_image = "UPDATE dropoff SET item_image = '$image_id' WHERE dropoff_id = '$dropoff_id'";
//                     if (!mysqli_query($conn, $update_image)) {
//                         throw new Exception("Failed to update dropoff image");
//                     }
//                 }
                
//                 // Insert item
//                 $insert_query = "INSERT INTO item_dropoff 
//                                 (dropoff_id, item_id, quantity) 
//                                 VALUES ('$dropoff_id', '$item_id', '$quantity')";

                
//                 if (!mysqli_query($conn, $insert_query)) {
//                     throw new Exception("Failed to save item");
//                 }
//             }
//         }
        
//         // Commit transaction
//         mysqli_commit($conn);
        
//         echo "<script>alert('Items saved successfully!');</script>";
        
//     } catch (Exception $e) {
//         mysqli_rollback($conn);
//         echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
//     }
// }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        mysqli_begin_transaction($conn);

        // Handle points assignment
        if (isset($_POST['action']) && $_POST['action'] === 'assign_points') {
            $dropoff_id = mysqli_real_escape_string($conn, $_POST['dropoff_id']);
            
            $items = [];
            $quantities = [];
            
            $itemCount = 1;
            while (isset($_POST["item_$itemCount"])) {
                $items[] = $_POST["item_$itemCount"]; 
                $quantities[] = $_POST["quantity_$itemCount"]; 
                $itemCount++;
            }

            for ($i = 0; $i < count($items); $i++) {
                $item = $items[$i];
                $quantity = $quantities[$i];
                $getItemIDQuery = mysqli_query($conn, "SELECT item_id FROM item WHERE item_name = '$item' AND status= 'Available'");
                $item_id = mysqli_fetch_assoc($getItemIDQuery)['item_id'];
    
                $insert_query = "INSERT INTO item_dropoff 
                                (dropoff_id, item_id, quantity) 
                                VALUES ('$dropoff_id', '$item_id','$quantity')";
    
                if (!mysqli_query($conn, $insert_query)) {
                    throw new Exception("Failed to insert item.");
                }
            }

            $image_id = null;
            $file_tmp = $_FILES["image_dropoff"]["tmp_name"];
            $file_name = $_FILES["image_dropoff"]["name"];
            $image_id = uploadToGoogleDrive($file_tmp, $file_name);

            if (!$image_id) {
                throw new Exception("Failed to upload image.");
            }

            // Optionally update dropoff image with the first uploaded image
            $update_dropoff_image = "UPDATE dropoff SET item_image = '$image_id' WHERE dropoff_id = '$dropoff_id'";
            mysqli_query($conn, $update_dropoff_image); // No need to throw here

            $total_points = (int)$_POST['total_points'];
            $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);

            $update_query = "UPDATE dropoff 
                            SET total_point_earned = $total_points,
                                status = 'Complete'
                            WHERE dropoff_id = '$dropoff_id'";

            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Failed to update points.");
            }

            $update_user_points = "UPDATE user SET points = points + $total_points WHERE user_id = '$user_id'";
            if (!mysqli_query($conn, $update_user_points)) {
                throw new Exception("Failed to update user points.");
            }

            $announcement = "Your drop-off has been successfully verified, and you've been awarded $total_points points!";
            $announcement = mysqli_real_escape_string($conn, $announcement);
            $notificationQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) 
                                VALUES ('$user_id', NOW(), 'Drop-off Completed & Points Awarded 📦', '$announcement', 'unread')";

            if (!mysqli_query($conn, $notificationQuery)) {
                throw new Exception("Failed to create notification.");
            }
        }

        mysqli_commit($conn);

        echo "<script>alert('Drop-off processed successfully.'); window.location.href='Admin-Dropoff-Complete.php?dropoff_id=$dropoff_id';</script>";
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
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

    .remove-item {
        background-color: #ff6b6b;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 10px;
        cursor: pointer;
        align-items: center;
        width: 50px;
        position: static;
        margin-left: auto;
        margin-top: 10px;
    }

    .user-detail-information {
        display:grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap:20px;
    }

    .user-detail h3 {
        color:#5D9C59;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .item-row {
        display: flex;
        gap: 50px;
        width: 20%;
        margin-bottom: 20px;
        align-items: flex-start;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-item span {
        background-color: #f8f9fa; 
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

    .add-button{
        width:150px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 15px;
        font-weight: 600;
        font-size: 14px;
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        margin-bottom:20px;
        background-color:#4E9F3D;
        color:white;
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

    input[type=text] {
        width: 100%;
        padding: 12px 15px;
        margin-top: 6px;
        display: inline-block;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 16px;
        border-radius: 8px;
        background-color:#d9d9d9;
        font-family:Arial,sans-serif;
        border-left: 4px solid #78A24C; 
    }

    .image-preview {
        width: 250px;
        height: 150px;
        border-radius: 8px;
        border-left: 4px solid #78A24C;
        background-color: #d9d9d9;
        margin-bottom: 10px;
        border: none;
    }

    .placeholder-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .item-select {
        width: 100%;
        padding: 12px 15px;
        margin-top: 6px;
        margin-bottom:15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        appearance:none;
        background-image: url("data:image/svg+xml,%3Csvg fill='gray' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 24px 24px;
        padding-right: 40px;
    }

    .item-select:focus {
        outline: none;
        /* border-color: #78A24C;
        box-shadow: 0 0 5px rgba(120, 162, 76, 0.3); */
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

    .modal-details {
        display: grid;
        /* grid-template-columns: 1fr 1fr; */
        gap: 15px;
        margin-bottom: 35px;
        margin-top:15px;
    }

    .modal-details div {
        flex-direction: row;
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
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
    }

    .status-complete {
        background-color: #FFF3CD;
        color: #856404;
    }

    .item-image-card {
        flex: 1;
        width: 150px;  
        height: 150px; 
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .item-image-card iframe {
        width: 100%;
        height: 100%;
        border: none;
        object-fit: cover; 
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
        font-size: 12px; 
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

    .detail-row-quantity {
        display: flex;
        gap: 15px;
        width: 100%;
    }

    .detail-row{
        display: flex;
        gap: 15px;
        width: 100%;
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

    .detail-row-quantity .detail-box {
        flex: 1;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        width: 50%;
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

    .item-image-container {
        width: 200px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .item-image-preview {
        width: 200px;
        height: 150px;
        border-radius: 8px;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .item-image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .item-details-container {
        display: flex;
        gap: 20px;
        margin-left:30px;
    }

    .item-name-box, .item-quantity-box {
        flex: 1;
    }

    .item-name-box select {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-size: 16px;
    }

    .item-name-box label{
        font-size:13px;
        color:#666;
    }

    .item-quantity-box input{
        width: 95%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-size: 16px;
        margin-top:6px;
    }

    .item-quantity-box input{
        outline:none;
    }

    .item-quantity-box label{
        font-size:13px;
        color:#666;
    }

    .file-upload-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 12px;
        background-color: #4E9F3D;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        width: 87%;
        transition: background-color 0.2s;
        margin-top:5px;
    }

    .file-upload-label:hover {
        background-color: #3BB143;
    }

    .file-upload-input {
        display: none;
    }

    .remove-item-btn{
        background-color:transparent;
        border:none;
        margin-left:-10px;
    }

    .remove-item-i{
        background-color: #ff6b6b;
        color: white;
        border: none;
        border-radius: 50%;
        padding: 5px 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        margin-top: 10px;
        justify-content: center;
        font-size:16px;
    }

    .remove-item-btn i:hover {
        background-color: #ff5252;
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

    .detail-row-full {
        width: 100%;
    }

    .items-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        flex-direction: column;
        width: 80%;
    }

    .image-upload-container {
        margin-top: 15px;
        width: 100%;
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
        <h2 class='title'>
            <a href='Admin-Dropoff.php' style='text-decoration: none; color: inherit;'>
                <i class='fa-solid fa-arrow-left-long'></i>
            </a>
            Drop-off Request Detail
        </h2>

        <hr style="width: 92%; margin-left:45px;">
        <div class="user-detail">
            <h3><i class="fas fa-user"></i> User Information</h3>
            <div class="items-container" style="width:100%;">
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
                        <div class="detail-box" style="visibility: hidden;">  
                            <strong>&nbsp;</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-detail">
            <h3><i class="fas fa-box-open"></i> Drop-off Item</h3>
            <form method="post" enctype="multipart/form-data" class="dropoff-form" id="dropoffForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div style="display:flex; flex-direction:row;">
                    <div class="item-row">
                        <div class="item-image-container">
                            <div class="item-image-preview">
                                <?php if (!empty($request['item_image'])): ?>
                                    <img src="https://drive.google.com/uc?export=view&id=<?php echo $request['item_image']; ?>" 
                                        class="image-preview" id="imagePreview">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 50px; color: #777;"></i>
                                <?php endif; ?>
                            </div>
                            <label for="item_image" class="file-upload-label">
                                <i class="fa-solid fa-upload"></i> Upload Image
                                <input type="file" id="item_image" name="item_image" accept="image/*" class="file-upload-input" onchange="previewImage(event,this)">
                            </label>
                            
                        </div>
                    </div>
                    <div class="items-container" id="items-container">
                        <div class="item-details-container">
                            <div class="item-name-box">
                                <label>Item Name</label>
                                <select name="item_1" class="item-select" required>
                                    <option value="">-- Select an item --</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['item_id']; ?>" data-points="<?php echo $item['point_given']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="item-quantity-box">
                                <label>Quantity</label>
                                <input type="number" name="quantity_1" placeholder="Enter quantity" required min="1" class="quantity-input">
                            </div>
                            <button type="button" class="remove-item-btn" onclick="removeItemRow(this)">
                                <i class="fa-solid fa-minus remove-item-i"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="add-button" onclick="addItemRow()">
                    <i class="fa-solid fa-plus"></i> Add Item
                </button>
                </div> 
                
                <input type="hidden" name="dropoff_id" value="<?php echo htmlspecialchars($_GET['dropoff_id']); ?>">
            </form>
        </div>
        <div class="assign-action">
            <button type="button" class="action-buttons assign-button" onclick="openAssignPointModal()">
                <i class="fa-solid fa-coins"></i> Assign Points
            </button>
        </div>

        <div id="modal-details" class="modal">
            <form class="modal-content" enctype="multipart/form-data" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="imgcontainer">
                    <span class="close" onclick="closeModal('modal-details')">&times;</span>
                </div>

                <div class="container">
                    <h2>Assign Points</h2>
                    <p>Review and adjust points for this drop-off request.</p>

                    <div class="modal-body">
                        <div class="all-detail-row">
                            <div class="modal-details">
                                <div class="detail-row-full">
                                    <div class="detail-box">
                                        <span>Item</span>
                                        <strong id="modalItemName"></strong>
                                    </div>
                                </div>
                                <div class="detail-row-quantity">
                                    <div class="detail-box">
                                        <span>Quantity</span>
                                        <div id="modalQuantityContainer"></div>
                                    </div>
                                    <div class="detail-box">
                                        <span>Points Per Item</span>
                                        <div id="modalPointsPerItemContainer"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="total-points-container">
                            <label><b>Total Points: </b></label>
                            <input type="number" name="total_points" id="modalTotalPoints" 
                                value="0"
                                min="0" class="total-points-value"
                                style="padding: 10px; border: 1px solid lightgrey; border-radius: 8px; text-align: center; font-weight: bold; width:73%;">
                        </div>
                    </div>

                    <input type="hidden" name="dropoff_id" value="<?php echo $dropoff_id; ?>">
                    <input type="hidden" name="action" value="assign_points">
                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                    <input type="file" name="image_dropoff">

                    <button type="submit" class="confirm-button">Confirm Assignment</button>
                </div>
            </form>
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

        

        // function openAssignPointModal() {
        //     const itemSelects = document.querySelectorAll('.item-select');
        //     const quantityInputs = document.querySelectorAll('.quantity-input');
        //     const image = document.getElementById('item_image');

        //     let totalPoints = 0;
        //     let itemsSummary = "";
        //     let quantitiesHtml = "";
        //     let pointsHtml = "";

        //     for (let i = 0; i < itemSelects.length; i++) {
        //         const itemSelect = itemSelects[i];
        //         const quantityInput = quantityInputs[i];

        //         if (itemSelect.value && quantityInput.value && quantityInput.value > 0) {
        //             const selectedItem = itemSelect.options[itemSelect.selectedIndex].text;
        //             const pointsPerItem = itemSelect.options[itemSelect.selectedIndex].getAttribute('data-points');
        //             const quantity = quantityInput.value;
        //             const points = pointsPerItem * quantity;
        //             totalPoints += points;

        //             itemsSummary += `${selectedItem}<br>`;
        //             quantitiesHtml += `<div><strong class="modal-quantity" data-quantity="${quantity}">${quantity}</strong></div>`;
        //             pointsHtml += `<div><strong class="points-static" data-points="${pointsPerItem}">${pointsPerItem}</strong></div>`;
        //         }
        //     }

        //     if (totalPoints === 0) {
        //         alert('Please add at least one valid item with quantity.');
        //         return false;
        //     }else if (!image.files || image.files.length === 0) {
        //         alert('Please upload an image of your e-waste.');
        //         return false;
        //     }

        //     document.getElementById('modalItemName').innerHTML = itemsSummary;
        //     document.getElementById('modalQuantityContainer').innerHTML = quantitiesHtml;
        //     document.getElementById('modalPointsPerItemContainer').innerHTML = pointsHtml;
        //     document.getElementById('modalTotalPoints').value = totalPoints;

        //     document.getElementById('modal-details').style.display = 'flex';
        //     return false;
        // }


        function openAssignPointModal() {
            body.style.overflow = "hidden";
            const itemSelects = document.querySelectorAll('.item-select');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const image = document.getElementById('item_image');
            const imageDropoffInput = document.querySelector('input[name="image_dropoff"]');
            let totalPoints = 0;
            let detailRowsHtml = '';

            for (let i = 0; i < itemSelects.length; i++) {
                const itemSelect = itemSelects[i];
                const quantityInput = quantityInputs[i];

                if (itemSelect.value && quantityInput.value && quantityInput.value > 0) {
                    const selectedItem = itemSelect.options[itemSelect.selectedIndex].text;
                    const pointsPerItem = itemSelect.options[itemSelect.selectedIndex].getAttribute('data-points');
                    const quantity = quantityInput.value;
                    const points = pointsPerItem * quantity;
                    totalPoints += points;

                    detailRowsHtml += `
                        <div class="modal-details">
                            <div class="detail-row-full">
                                <div class="detail-box">
                                    <span >Item ${i+1}</span>
                                    <strong>${selectedItem}</strong>
                                    <input type="hidden" name="item_${i+1}" value="${selectedItem}">
                                </div>
                            </div>
                            <div class="detail-row-quantity">
                                <div class="detail-box">
                                    <span >Quantity</span>
                                    <strong class="modal-quantity" data-quantity="${quantity}">${quantity}</strong>
                                    <input type="hidden" name="quantity_${i+1}" value="${quantity}">
                                </div>
                                <div class="detail-box">
                                    <span>Points Per Item</span>
                                    <strong class="points-static" data-points="${pointsPerItem}">${pointsPerItem}</strong>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }

            if (totalPoints === 0) {
                alert('Please add at least one valid item with quantity.');
                body.style.overflow = "auto";
                return false;
            } else if (!image.files || image.files.length === 0) {
                alert('Please upload an image.');
                body.style.overflow = "auto";
                return false;
            }

            document.querySelector('.all-detail-row').innerHTML = detailRowsHtml;

            document.getElementById('modalTotalPoints').value = totalPoints;

            document.getElementById('modal-details').style.display = 'flex';
            imageDropoffInput.parentNode.replaceChild(image, imageDropoffInput);
            image.setAttribute('name', 'image_dropoff');
            return false;

        }

        const body = document.body;

        function confirmAssignment() {
            const form = document.getElementById('dropoffForm');
            
            if (!form.reportValidity()) {
                return;
            }
            
            if (!document.querySelector('input[name="submit_dropoff"]')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'submit_dropoff';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
            }
            
            const modalForm = document.querySelector('.modal-content form');
            modalForm.submit();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target.classList.contains("modal")) {
                event.target.style.display = "none";
                body.style.overflow = "auto";
            }
        }

        function reindexItems() {
            const items = document.querySelectorAll(".item-details-container");
            
            items.forEach((item, index) => {
                const itemNumber = index + 1;
                
                const select = item.querySelector("select");
                if (select) select.name = `item_${itemNumber}`;
                
                const quantityInput = item.querySelector("input[type='number']");
                if (quantityInput) quantityInput.name = `quantity_${itemNumber}`;
            });
        }

        let currentVisibleRows = 1;

        function addItemRow() {
            const container = document.getElementById('items-container');
            const newIndex = container.querySelectorAll('.item-details-container').length + 1;

            const newRow = document.createElement('div');
            newRow.classList.add('item-details-container');

            newRow.innerHTML = `
                <div class="item-name-box">
                    <label>Item Name</label>
                    <select name="item_${newIndex}" class="item-select" required>
                        <option value="">Select an item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['item_id']; ?>" data-points="<?php echo $item['point_given']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="item-quantity-box">
                    <label>Quantity</label>
                    <input type="number" name="quantity_${newIndex}" placeholder="Enter quantity" required min="1" class="quantity-input">
                </div>

                <button type="button" class="remove-item-btn" onclick="removeItemRow(this)">
                    <i class="fa-solid fa-minus remove-item-i"></i>
                </button>
            `;

            container.appendChild(newRow);
            updateRemoveButtons(); 
        }

        function removeItemRow(button) {
            const rowToRemove = button.closest('.item-details-container');
            if (!rowToRemove) return;

            rowToRemove.remove();
            updateRemoveButtons(); 
        }

        function previewImage(event, input) {
            const previewDiv = input.closest('.item-image-container').querySelector('.item-image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.innerHTML = `<img src="${e.target.result}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function calculateTotal() {
            const pointInputs = document.querySelectorAll('.point-input');
            const quantityElements = document.querySelectorAll('.modal-quantity');
            let total = 0;

            for (let i = 0; i < pointInputs.length; i++) {
                const quantity = parseInt(quantityElements[i].dataset.quantity) || 0;
                const points = parseInt(pointInputs[i].value) || 0;
                total += quantity * points;
            }

            document.getElementById('modalTotalPoints').value = total;
        }
        
        function updateRemoveButtons() {
            const container = document.getElementById('items-container');
            const itemRows = container.querySelectorAll('.item-details-container');
            const removeButtons = container.querySelectorAll('.remove-item-btn');
            const removeIcons = container.querySelectorAll('.remove-item-i');

            if (itemRows.length <= 1) {
                removeButtons.forEach(btn => btn.disabled = true);
                removeIcons.forEach(icon => {
                    icon.style.cursor = "not-allowed";
                    icon.style.opacity = "0.6";
                });
            } else {
                removeButtons.forEach(btn => btn.disabled = false);
                removeIcons.forEach(icon => {
                    icon.style.cursor = "pointer";
                    icon.style.opacity = "1";
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateRemoveButtons();

            const removeButtons = document.querySelectorAll('.remove-item-btn');
            removeButtons.forEach(button => {
                button.onclick = function() {
                    removeItemRow(this);
                };
            });
        });

        // const container = document.getElementById('items-container');
        // const itemRows = container.querySelectorAll('.item-details-container');
        // const removeIcons = container.querySelectorAll('.remove-item-i'); 
        // removeIcons.forEach(icon =>{
        //     icon.addEventListener("click", function(event){
        //         if (itemRows.length <= 1) {
        //             removeIcons[0].style.cursor = "not-allowed";
        //             removeIcons[0].style.opacity = "0.6";
        //         }else{
        //             removeIcons.forEach(icon => {
        //                 icon.style.cursor = "pointer";
        //                 icon.style.opacity = "1";
        //             });
        //         } 
        //     });
        // });

    </script>
</body>
</html>