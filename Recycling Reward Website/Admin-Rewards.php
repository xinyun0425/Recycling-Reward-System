<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
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
        $client->setAuthConfig('keen-diode-454703-r9-847455d54fc8.json');
        $client->addScope(Google\Service\Drive::DRIVE_FILE);
        
        $service = new Google\Service\Drive($client);
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => ['1ifuDZKMObiclp8U2nQNT6cDIOV8Jwnhy']
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

    function deleteFromGoogleDrive($fileId) {
        try {
            $client = new Google\Client();
            $client->setHttpClient(new GuzzleHttp\Client(['verify' => false])); 
            $client->setAuthConfig('keen-diode-454703-r9-847455d54fc8.json');
            $client->addScope(Google\Service\Drive::DRIVE_FILE);
            
            $service = new Google\Service\Drive($client);
            $service->files->delete($fileId);
            
            return true;
        } catch (Exception $e) {
            error_log("Google Drive Deletion Failed: " . $e->getMessage());
            return false;
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["rewardImage"])) {
        $rewardName = $_POST["rewardName"];
        $rewardPoints = $_POST["rewardPoints"];
        $rewardStock = $_POST["rewardStock"];
        $rewardCategory = $_POST['category'];
        $fileTmpName = $_FILES["rewardImage"]["tmp_name"];
        $fileName = $_FILES["rewardImage"]["name"];
        $fileID = uploadToGoogleDrive($fileTmpName, $fileName);
    
        if ($fileID) {
            $sql = "INSERT INTO reward (reward_name, point_needed, reward_image, quantity, status, category) 
                    VALUES ('$rewardName', '$rewardPoints', '$fileID', '$rewardStock', 'Available', '$rewardCategory')";
    
            if ($conn->query($sql) === TRUE) {
                echo "<script>
                    alert('Reward added successfully!');
                    setTimeout(function() {
                        window.location.href = 'Admin-Rewards.php';
                    }, 1000);  // Delay to show the alert first
                </script>";
            } else {
                echo "<script>
                    alert('Error: " . $conn->error . "');
                </script>";
            }
        } else {
            echo "<script>
                alert('File upload to Google Drive failed.');
            </script>";
        }
    }
    
    

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["rewardID"])) {
        $rewardID = mysqli_real_escape_string($conn, $_POST["rewardID"]);
        $title = mysqli_real_escape_string($conn, $_POST["title"]);
        $points = mysqli_real_escape_string($conn, $_POST["points"]);
        $stock = mysqli_real_escape_string($conn, $_POST["stock"]);
        $category = mysqli_real_escape_string($conn, $_POST["category"]);
        $query = "SELECT reward_image FROM reward WHERE reward_id = '$rewardID'";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $currentFileID = $row["reward_image"];
        if (!empty($_FILES["newImage"]["name"])) {
            $fileName = $_FILES["newImage"]["name"];
            $fileTmp = $_FILES["newImage"]["tmp_name"];
            $newFileID = uploadToGoogleDrive($fileTmp, $fileName);
            if ($newFileID) {
                deleteFromGoogleDrive($currentFileID);
                $updateQuery = "UPDATE reward 
                                SET reward_name = '$title', 
                                    point_needed = '$points', 
                                    quantity = '$stock', 
                                    reward_image = '$newFileID',
                                    category = '$category'
                                WHERE reward_id = '$rewardID'";
            } else {
                echo "Error: Image upload failed.";
                exit;
            }
        } else {
            $updateQuery = "UPDATE reward 
                            SET reward_name = '$title', 
                                point_needed = '$points', 
                                quantity = '$stock',
                                category = '$category' 
                            WHERE reward_id = '$rewardID'";
        }
        if (mysqli_query($conn, $updateQuery)) {
            echo "<script>
                alert('Reward added successfully!');
                setTimeout(function() {
                    window.location.href = 'Admin-Rewards.php';
                }, 500); // gives time for alert to show
            </script>";

        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["deleteReward"])) {
        $rewardID = mysqli_real_escape_string($conn, $_POST["rewardID"]);
        $rewardImageID = mysqli_real_escape_string($conn, $_POST["rewardImageID"]);
    
        $checkQuery = "SELECT * FROM redeem_reward WHERE reward_id = '$rewardID'";
        $checkResult = $conn->query($checkQuery);
    
        if ($checkResult && $checkResult->num_rows > 0) {
            $updateQuery = "UPDATE reward SET status = 'Unavailable' WHERE reward_id = " . intval($rewardID);
    
            if ($conn->query($updateQuery) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Reward is linked. Marked as Unavailable."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update reward status."]);
            }
        } else {
            $deleteSuccess = deleteFromGoogleDrive($rewardImageID);
    
            if (!$deleteSuccess) {
                echo json_encode(["status" => "error", "message" => "Google Drive deletion failed."]);
                exit;
            }
    
            $deleteQuery = "DELETE FROM reward WHERE reward_id = '$rewardID'";
    
            if ($conn->query($deleteQuery) === TRUE) {
                echo json_encode(["status" => "success", "message" => "Reward deleted successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete reward from database."]);
            }
        }
    
        exit;
    }
    
    
    // Query for Unredeemed Rewards
    $unredeemed = [];
    $queryUnredeemed = "SELECT rr.redeem_reward_id, u.email, u.username, r.reward_name
                        FROM redeem_reward rr
                        JOIN user u ON rr.user_id = u.user_id
                        JOIN reward r ON rr.reward_id = r.reward_id
                        WHERE rr.status = 'Unredeemed'";
    $resultUnredeemed = mysqli_query($conn, $queryUnredeemed);
    if ($resultUnredeemed) {
        while ($row = mysqli_fetch_assoc($resultUnredeemed)) {
            $unredeemed[] = $row;
        }
    }
    
    // Query for Redeemed Rewards
    $redeemed = [];
    $queryRedeemed = "SELECT rr.collect_datetime, u.email,u.username, r.reward_name, l.location_name
                      FROM redeem_reward rr
                      JOIN user u ON rr.user_id = u.user_id
                      JOIN reward r ON rr.reward_id = r.reward_id
                      JOIN location l ON rr.location_id = l.location_id
                      WHERE rr.status = 'Redeemed'";
    $resultRedeemed = mysqli_query($conn, $queryRedeemed);
    if ($resultRedeemed) {
        while ($row = mysqli_fetch_assoc($resultRedeemed)) {
            $redeemed[] = $row;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType']) && $_POST['formType'] === 'approveReward') {
        date_default_timezone_set('Asia/Kuala_Lumpur');
    
        $redeemRewardId = mysqli_real_escape_string($conn, $_POST['redeemRewardId']);
        $locationId = intval($_POST['location']);
        $currentTime = date('Y-m-d H:i:s');
    
        $queryUpdate = "
            UPDATE redeem_reward 
            SET status = 'Redeemed', location_id = $locationId, collect_datetime = '$currentTime' 
            WHERE redeem_reward_id = '$redeemRewardId'
        ";
    
        if (mysqli_query($conn, $queryUpdate)) {
            echo "success";
        } else {
            echo "update_error";
        }
    
        exit;
    }

    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewards Management - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    
    .main-content {
        padding:20px;
        margin-left:330px;
        width:calc(100%-270px);
        overflow-y:auto;
        overflow-x:hidden;
    }
    .header {
        text-align: left;  
        width: 85%;
        font-size:1.5em;
        margin-left:29px;
        margin-bottom: 20px; 
        animation: floatIn 0.8s ease-out;
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

    .menu {
        list-style: none;
        padding: 0;
        margin-left: 13px;
        width: 220px;
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
    .categoryitems {
        padding: 20px;
        min-height: 100vh;
    }

    .category-title {
        margin: 0px 0px 10px 10px;
        font-size: 1.3em;
        font-weight: bold;
        color: #0e612b;
    }
    .reward-category-group {
        display: grid;
        grid-template-columns: repeat(3, minmax(290px, 1fr));
        gap: 20px 40px;
        padding: 10px;
        border-radius: 10px;
        width:90%;
        margin-bottom:40px;
    }

    .reward-container {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .reward-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }

    .reward-left {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-grow: 1;
        margin-top: 15px;
    }

    .icon-btn {
        background-color: transparent;
        padding: 5px 0px;
        font-size: 16px;
        text-align: right;
        cursor: pointer;
        border: none;
        border-radius: 8px;
    }

    .reward-item {
        background-color: #ffffff;
        border-radius: 12px;
        padding: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.2s;
        width: 100%;
        min-height: 250px;
    }

    .reward-item .iframe-wrapper {
        position: relative;
        width: 80%;
        padding-top: 56.25%;
        overflow: hidden;
        border-radius: 10px;
        margin-bottom: 5px;
        aspect-ratio: 17 / 0.3;
        border: 1px solid grey;
        pointer-events: none;
        margin-left:12px;
    }

    .reward-item .iframe-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        object-fit: cover;
    }
    .reward-actions {
        display: flex;
        flex-direction: column;
        gap: 3px;
        margin-right:10px;
        margin-top: 8px;
        padding: 8px 0;
        margin-left:-9px;
    }

    .reward-details {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        height: 120px;
        padding: 10px;
        box-sizing: border-box;
        overflow: hidden;
        width: 100%;
    }

    /* Row 1: Stock */
    .reward-stock {
        height: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-bottom:10px;
    }

    .reward-stock p {
        color: #888;
        font-size: 12px;
        margin: 0;
    }

    .reward-title {
        height: 90px;
        font-size: 1.05em;
        color: #333;
        text-align: center;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-height: 1.2;
        margin: 0;
        padding: 0 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }


    .reward-info {
        height:20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: auto;
        width: 100%;
    }

    .reward-info .points {
        color: #178429;
        font-weight: bold;
        font-size: 1.05rem;
        margin: 0;
        text-align: center;
    }


    .add-btn {
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

    .add-btn:hover {
        background: #78A24C;
        scale: 1.1;
        transition: scale 0.3s ease;
    }

    .add-itemcontainer{
        background-color: #fefefe;
        /* margin: 5% auto; */
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
    .additem-popup-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5); 
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
        display: none;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0.3s, opacity 0.3s ease;
    }

    .additem-popup-container.show {
        display: flex; 
        visibility: visible;
        opacity: 1;
    }
    .add-itempopup-content {
        background-color: #fefefe;
        padding: 35px 40px 0px 40px;
        width: auto;
        max-height: 80vh;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .add-item-popup-container.show .add-itempopup-content {
        transform: scale(1);
    }

    .close-btn {
        position: absolute;
        right: 30px;
        top: 20px;
        color: rgb(133, 133, 133);
        font-size: 35px;
        cursor: pointer;
    }
    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
    }

    #addRewardForm {
        display: flex;
        flex-direction: column;
        max-height: 36vh;
        overflow-y: auto;
    }

    #addRewardForm label{
        color: rgb(89, 89, 89);
    }
    #addRewardForm input[type="text"],
    #addRewardForm input[type="number"],
    #addRewardForm input[type="file"],
    #editForm input[type="text"],
    #editForm input[type="number"],
    #editForm input[type="file"] {
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color: #fff;
        font-family: Arial, sans-serif;
        border-radius: 5px;
        resize: none;
        border: 1px solid #ccc;
        outline: none;
    }

    select {
        width: 100%;
        padding: 12px 40px 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color: #fff;
        font-family: Arial, sans-serif;
        border-radius: 5px;
        resize: none;
        border: 1px solid #ccc;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg fill='gray' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 24px 24px; 
        cursor: pointer;
    }


    .submitbutton {
        width: 100%;
        padding: 14px;
        background-color: rgb(78, 120, 49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
    }

    .itemdropdown-menu {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
        padding: 5px;
        min-width: 100px;
        z-index: 1000;
    }

    .itemdropdown-menu button {
        display: block;
        width: 100%;
        padding: 8px 12px;
        text-align: left;
        border: none;
        background: transparent;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.3s ease, color 0.3s ease;
    }

    .itemdropdown-menu button:hover {
        background: #d9f7be; 
        color: #1d5b1d; 
        border-radius: 5px;
    }
    .icon-btn {
        background-color: transparent;
        cursor: pointer;
        border: none;
        font-size: 16px;
        border-radius: 8px;
        transition: color 0.3s ease, transform 0.2s ease;
    }

    .edit-btn {
        color: rgb(92, 147, 206);
    }

    .edit-btn:hover {
        color: rgb(50, 120, 190);
        transform: scale(1.1);
    }

    .delete-btn {
        color: rgba(222, 121, 84, 0.86);
    }

    .delete-btn:hover {
        color: rgba(200, 90, 60, 0.9);
        transform: scale(1.1);
    }
    .edit-itemcontainer{
        background-color: #fefefe;
        /* margin: 5% auto; */
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
    .edititem-popup-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5); 
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
        display: none;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0.3s, opacity 0.3s ease;
    }

    .edititem-popup-container.show {
        display: flex;
        visibility: visible;
        opacity: 1;
    }

    .edititem-popup-content {
        background-color: #fefefe;
        padding: 35px 40px 0px 40px;
        width: auto;
        max-height: 80vh;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .edititem-popup-container.show .edititem-popup-content {
        transform: scale(1);
    }

    .edititem-close-btn {
        position: absolute;
        right: 30px;
        top: 20px;
        color: rgb(133, 133, 133);
        font-size: 35px;
        cursor: pointer;
    }

    .edititem-close-btn:hover,
    .edititem-close-btn:focus {
        color: black;
        text-decoration: none;
    }

    #editForm {
        display: flex;
        flex-direction: column;
        max-height: 36vh;
        overflow-y: auto;
    }

    #editForm label{
        color: rgb(89, 89, 89);
    }
    #editForm input[type="text"],
    #editForm input[type="number"],
    #editForm input[type="file"],
    #editForm select {
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        font-size: 16px;
        background-color: #fff;
        border-radius: 5px;
        border: 1px solid #ccc;
        outline: none;
        box-sizing: border-box;
    }
    .tab-bar {
        display: flex;
        gap: 1rem;
        background-color: transparent;
        padding: 1rem;
        margin-left:28px;
    }

    .tab-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 0rem 1rem 0;
        font-size: 1rem;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: border-bottom 0.3s, color 0.3s;
        color: #333;
    }

    .tab-btn:hover,
    .tab-btn.active {
        border-bottom: 2px solid #0e612b;
        color: #0e612b;
    }

    .tab-content {
        display: none;
        overflow-x: hidden;
        overflow-y: auto;
    }

    .tab-content.active {
        display: block;
        width: 71vw;
        overflow-x: hidden;
        overflow-y: auto;
        margin-left:15px;
    }

    .h3redemption{
        padding:5px;
        width: 85%;
        margin-left: 24px;
    }

    table {
        border-collapse: collapse;
        border: 1px solid #cbcbcb;
        width: 90%;
        font-size: 16px;
        margin-left: 30px;
        background-color: rgba(255, 255, 255, 0.5);
        table-layout: fixed;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        word-wrap: break-word;
    }

    th {
        background-color: #E0E1E1;
    }

    tr:hover {
        background-color: rgba(184, 194, 172, 0.05);
        cursor: pointer;
    }
    
    .search-container {
        position: relative;
        width: 85%;
        left: 28px;
    }

    .search-container i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #7d8fa1;
    }

    #searchBar {
        width: 99%;
        padding: 12px 20px 12px 45px;
        border-radius: 12px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: all 0.3s;
        background-color: white;
        outline:none;
    }
    .approve-btn{
        background-color: white;
        color: #1a6f3e;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        border: 2px solid #1a6f3e;
    }
    .approve-btn:hover{
        background-color:#69ce940f;
    }
    #approveredeemreward-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5); 
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
        display: none;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0.3s, opacity 0.3s ease;
    }
    .approveredeem-inner-container{
        background-color: #fefefe;
        /* margin: 5% auto; */
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
    .approveredeem-popup-container.show {
        display: flex;
        visibility: visible;
        opacity: 1;
    }

    .approveredeem-popup-content {
        background-color: #fefefe;
        padding: 35px 40px 0px 40px;
        width: auto;
        max-height: 80vh;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .approvereward-close-btn {
        position: absolute;
        right: 30px;
        top: 20px;
        color: rgb(133, 133, 133);
        font-size: 35px;
        cursor: pointer;
    }
    
    .approvereward-close-btn:hover {
        color: black;
        text-decoration: none;
    }



    #rewardCollectionForm label {
        display: block;
        color:rgb(89, 89, 89);
    }

    #rewardCollectionForm select,
    #rewardCollectionForm button {
        width: 100%;
        padding: 10px;
        padding-right: 20px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;
        appearance:none;
    }
    #rewardCollectionForm select {
        width: 100%;
        padding: 10px;
        padding-right: 40px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;

        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg fill='gray' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 20px;
        background-color: white;
    }
    #rewardCollectionForm button {
        width: 100%;
        padding: 14px;
        background-color: rgb(78, 120, 49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
    }
    hr{
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
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
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li class="active"><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
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
    <div class ="main-content">
        <div class="tab-bar">
        <button class="tab-btn active" data-title="Rewards Management" onclick="switchTab('rewards-management', this)">
            Rewards Management
        </button>
        <button class="tab-btn" data-title="Reward Redemption" onclick="switchTab('reward-redemption', this)">
            Reward Redemption
        </button>
        </div>
        <div id="rewards-management" class="tab-content active">
            <h2 class="header">Rewards Management</h2>
            <hr style="width: 90%; margin-left:30px;margin-bottom:20px;">
            <div class="categoryitems">
                <div class="reward-grid-container">
                    <?php
                    $query = "SELECT reward_id, reward_name, point_needed, reward_image, quantity, category 
                            FROM reward 
                            WHERE status = 'Available' 
                            ORDER BY category ASC, point_needed ASC";
                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $currentCategory = "";

                    while ($row = mysqli_fetch_assoc($result)) {
                        $rewardID = $row['reward_id'];
                        $title = $row['reward_name'];
                        $points = $row['point_needed'];
                        $fileID = $row['reward_image'];
                        $stock = $row['quantity'];
                        $category = $row['category'];
                        $categoryKey = strtolower($category); // for HTML attribute
                        $embedURL = "https://drive.google.com/file/d/$fileID/preview";

                        if ($currentCategory !== $category) {
                            if ($currentCategory !== "") {
                                echo "</div>";
                            }

                            echo "<h3 class='category-title'>$category</h3>";
                            echo "<div class='reward-category-group'>";
                            $currentCategory = $category;
                        }
                        echo "
                        <div class='reward-item' data-category='" . $categoryKey . "'>
                            <div class='reward-top'>
                                <div class='reward-left'>
                                    <div class='iframe-wrapper'>
                                        <iframe src='" . $embedURL . "' allow='autoplay'></iframe>
                                    </div>
                                    <div class='reward-details'>
                                        <div class='reward-stock'>
                                            <p class='stocks'>Stock: " . $stock . "</p>
                                        </div>
                                        <h4 class='reward-title'>" . $title . "</h4>
                                        <div class='reward-info'>
                                            <p class='points'>" . $points . " Points</p>
                                        </div>
                                    </div>
                                </div>
                                <div class='reward-actions'>
                                    <button class='icon-btn edit-btn'
                                            title='Edit'
                                            data-id='" . $row['reward_id'] . "'
                                            data-title='" . htmlspecialchars($row['reward_name'], ENT_QUOTES, 'UTF-8') . "'
                                            data-points='" . $row['point_needed'] . "'
                                            data-stock='" . $row['quantity'] . "'
                                            data-category='" . htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') . "'>
                                        <i class='fa-solid fa-pen'></i>
                                    </button>
                                    <button class='icon-btn delete-btn'
                                            title='Delete'
                                            data-id='" . $row['reward_id'] . "'
                                            data-image-id='" . $row['reward_image'] . "'>
                                        <i class='fa-solid fa-trash'></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ";
                    }                
                    if ($currentCategory !== "") {
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <button class="add-btn" onclick="openPopup()">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div id="reward-redemption" class="tab-content">
            <h2 class="header">Reward Redemption</h2>
            <hr style="width: 90%; margin-left:30px;margin-bottom:20px;">
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="searchbar" id="searchBar" placeholder="Search by email or item..." />
                </div>
                <!-- Unredeemed Section -->
                <section class="redemption-section" style="margin-bottom:70px;">
                    <h3 class="h3redemption">Unredeemed</h3>
                    <table id="unredeemedTable">
                        <thead>
                            <tr>
                                <th style="width:30.5%">Email</th>
                                <th style="width:30.5%">Username</th>
                                <th style="width:30%">Reward Item</th>
                                <th style="width:15%;text-align: center;" >Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unredeemed as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['reward_name']) ?></td>
                                    <td style="text-align: center;">
                                        <button class="approve-btn" data-id="<?= $row['redeem_reward_id'] ?>">Approve</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </section>
                <!-- Redeemed Section -->
                <section class="redemption-section">
                    <h3 class="h3redemption">Redeemed</h3>
                    <table id="redeemedTable">
                        <thead>
                            <tr>
                                <th style="width:14.5%">Approved On</th>
                                <th style="width:25.5%">Email</th>
                                <th style="width:21.5%">Username</th>
                                <th style="width:20.5%">Reward Item</th>
                                <th style="width:20%">Drop-off Point</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($redeemed as $row): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($row['collect_datetime'])) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['reward_name']) ?></td>
                                <td><?= htmlspecialchars($row['location_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                <div class="approveredeem-popup-container" id="approveredeemreward-container">
                    <div class="approveredeem-inner-container">
                        <div class="approveredeem-popup-content">
                            <span class="close-btn" onclick="closeRedemption()">&times;</span>
                            <h2 style="font-size: 30px; margin-bottom: 0;">Reward Collection</h2>
                            <p style="color: rgb(89, 89, 89); line-height: 1.5; font-size: 16px; margin-bottom: 20px;">
                                Select the drop-off point of the redemption.
                            </p>
                            <br>
                            <form id="rewardCollectionForm" enctype="multipart/form-data" method="POST">
                                <input type="hidden" name="formType" value="approveReward">
                                <input type="hidden" id="redeemRewardId" name="redeemRewardId">
                                <label style="color: rgb(89, 89, 89);">Location Name</label>
                                <select id="editlocation" name="location" required>
                                    <option value="" disabled selected>-- Select a drop-off point --</option>
                                    <?php
                                    $query = "SELECT location_id, location_name FROM location";
                                    $result = $conn->query($query);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row['location_id']) . "'>" . htmlspecialchars($row['location_name']) . "</option>";
                                        }
                                    } 
                                    ?>
                                </select>
                                <br>
                                <button type="submit" name="approveReward" class="submitbutton">Approve</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <div class="additem-popup-container" id="additempopup">
            <div class="add-itemcontainer">
                <div class="add-itempopup-content">
                    <span class="close-btn" onclick="closePopup()">&times;</span>
                    <h2 style="font-size: 30px;margin-bottom: 0;">Add New Reward</h2>
                    <p style="margin-bottom: 20px;color: rgb(89, 89, 89);line-height: 1.5;font-size: 16px;">Fill in the details below to add a new reward.</p>
                    <br>
                    <form id="addRewardForm" enctype="multipart/form-data">
                        <label for="rewardName">Reward Name</label>
                        <input type="text" id="rewardName" name="rewardName" required>
                        <br>
                        <label for="rewardPoints">Points Needed</label>
                        <input type="number" id="rewardPoints" name="rewardPoints" required>
                        <br>
                        <label for="rewardStock">Stock</label>
                        <input type="number" id="rewardStock" name="rewardStock" required>
                        <br>
                        <label for="rewardCategory">Category</label>
                        <select id="rewardCategory" name="category" class="dropdown" required>
                            <option value="Food & Beverage">Food & Beverage</option>
                            <option value="Health & Lifestyle">Health & Lifestyle</option>
                            <option value="Tech Accessories">Tech Accessories</option>
                            <option value="Eco-Friendly Products">Eco-Friendly Products</option>
                        </select>
                        <br>
                        <label for="rewardImage">Upload Image</label>
                        <input type="file" id="rewardImage" name="rewardImage" accept=".jpg, .jpeg, .png" required>
                    </form>
                    <button class="submitbutton" id='submitRewardBtn' type="submit">Add Reward</button>
                </div>
            </div>
        </div>
        <div class="edititem-popup-container" id="edititem-container">
            <div class="edit-itemcontainer">
                <div class="edititem-popup-content">
                    <span class="edititem-close-btn">&times;</span>
                    <h2 style="font-size: 30px; margin-bottom: 0;">Edit Reward</h2>
                    <p style="margin-bottom: 20px; color: rgb(89, 89, 89); line-height: 1.5; font-size: 16px;">Please update the new reward details.</p>
                    <br>
                    <form id="editForm" enctype="multipart/form-data" method="POST">
                        <input type="hidden" id="editRewardID" name="rewardID">
                        <label>Reward Name</label>
                        <input type="text" id="editTitle" name="title" required>
                        <br>
                        <label>Points Needed</label>
                        <input type="number" id="editPoints" name="points" required>
                        <br>
                        <label>Stock</label>
                        <input type="number" id="editStock" name="stock" required>
                        <br>
                        <label for="editCategory">Category</label>
                        <select id="editCategory" name="category" class="dropdown" required>
                            <option value="Food & Beverage">Food & Beverage</option>
                            <option value="Health & Lifestyle">Health & Lifestyle</option>
                            <option value="Tech Accessories">Tech Accessories</option>
                            <option value="Eco-Friendly Products">Eco-Friendly Products</option>
                        </select>
                        <br>
                        <label>Upload New Image (optional)</label>
                        <input type="file" id="editImage" name="newImage" accept=".jpg, .jpeg, .png">
                    </form>
                    <button class="submitbutton" type="submit" form="editForm">Save Changes</button>
                </div>
            </div>
        </div>


    </div>
    </div>
    <script>
        function disableScroll() {
            document.body.style.overflow = 'hidden';
        }

        function enableScroll() {
            document.body.style.overflow = 'auto';
        }
        function switchTab(tabId, clickedBtn) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            clickedBtn.classList.add('active');

            document.title = clickedBtn.dataset.title + " - Green Coin";
            localStorage.setItem("activeTab", tabId);
        }

        function submitRewardForm() {
            const form = document.getElementById("addRewardForm");
            const formData = new FormData(form);

            closePopup();

            fetch("Admin-Rewards.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log("Server response:", data);
                if (data.includes("success")) {
                    alert("Reward added successfully.");
                } else {
                    alert("There was an error adding the reward. Please try again.");
                }

                setTimeout(() => location.reload(), 50);
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while submitting the reward. Please try again.");
            });
        }

        document.getElementById("submitRewardBtn").addEventListener("click", function () {
            const form = document.getElementById("addRewardForm");
            if (form.checkValidity()) {
                submitRewardForm();
            } else {
                form.reportValidity();
            }
        });


        function openPopup() {
            const popup = document.getElementById("additempopup");
            popup.style.display = "flex";
            setTimeout(() => popup.classList.add("show"), 10);
            disableScroll();
        }

        function closePopup() {
            const popup = document.getElementById("additempopup");
            popup.classList.remove("show");
            setTimeout(() => {
                popup.style.display = "none";
            }, 50);
            enableScroll();
        }
        const addItemPopup = document.getElementById("additempopup");

        addItemPopup.addEventListener("click", function(event) {
            if (event.target === addItemPopup) {
                closePopup();
            }
        });
        document.addEventListener("DOMContentLoaded", function() {
            const editModal = document.getElementById("edititem-container");
            const editCloseBtn = document.querySelector("#edititem-container .edititem-close-btn");

            document.querySelectorAll(".edit-btn").forEach(button => {
                button.addEventListener("click", function () {
                    disableScroll();
                    document.getElementById("editRewardID").value = this.dataset.id;
                    document.getElementById("editTitle").value = this.dataset.title;
                    document.getElementById("editPoints").value = this.dataset.points;
                    document.getElementById("editStock").value = this.dataset.stock;
                    document.getElementById("editCategory").value = this.dataset.category;

                    editModal.classList.add("show");
                    document.getElementById("editForm").scrollTop = 0;
                });
            });

            if (editCloseBtn) {
                editCloseBtn.addEventListener("click", function () {
                    closeEditModal();
                });
            }

            window.addEventListener("click", function (event) {
                if (event.target === editModal) {
                    closeEditModal();
                }
            });

            function closeEditModal() {
                editModal.classList.remove("show");
                enableScroll();
            }

            document.getElementById("editForm").addEventListener("submit", function (event) {
                event.preventDefault();
                if (this.dataset.submitting) return;

                this.dataset.submitting = true;

                const formData = new FormData(this);

                const fileInput = document.getElementById("editImage");
                if (fileInput.files.length > 0) {
                    formData.append("newImage", fileInput.files[0]);
                }

                fetch("Admin-Rewards.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Success:", data);
                    alert("Reward edited successfully.");
                    location.reload();
                })
                .catch(error => {
                    alert("Reward edited successfully.");
                    location.reload();
                })
                .finally(() => {
                    delete this.dataset.submitting;
                });

            });
        });

        // Delete Reward Functionality
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function () {
                    const rewardID = this.getAttribute("data-id");
                    const rewardImageID = this.getAttribute("data-image-id");

                    if (confirm("Are you sure you want to delete this reward?")) {
                        fetch("Admin-Rewards.php", { 
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: `deleteReward=true&rewardID=${rewardID}&rewardImageID=${rewardImageID}`
                        })
                        .then(response => response.text()) 
                        .then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (data.status === "success") {
                                    alert(data.message); 
                                    setTimeout(() => location.reload(), 500); // delay after alert
                                }
                            } catch (error) {
                                console.error("JSON Parse Error:", error, "Response:", text);
                                alert("Reward deleted successfully.");
                                setTimeout(() => location.reload(), 50);
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                        });
                    }
                });
            });
        });
        document.addEventListener("DOMContentLoaded", function () {
            const searchBar = document.getElementById("searchBar");
            checkIfEmpty("unredeemedTable");
            checkIfEmpty("redeemedTable");

            searchBar.addEventListener("input", function () {
                const searchText = this.value.toLowerCase();
                filterTable("unredeemedTable", searchText);
                filterTable("redeemedTable", searchText);
            });

            function filterTable(tableId, filterText) {
                const table = document.getElementById(tableId);
                const tbody = table.querySelector("tbody");
                const rows = Array.from(tbody.querySelectorAll("tr")).filter(r => !r.id.startsWith("no-results"));

                let visibleCount = 0;

                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const matches = rowText.includes(filterText);
                    row.style.display = matches ? "" : "none";
                    if (matches) visibleCount++;
                });

                toggleNoResultsRow(tbody, tableId, visibleCount === 0);
            }

            function checkIfEmpty(tableId) {
                const table = document.getElementById(tableId);
                const tbody = table.querySelector("tbody");
                const rows = Array.from(tbody.querySelectorAll("tr")).filter(r => !r.id.startsWith("no-results"));

                toggleNoResultsRow(tbody, tableId, rows.length === 0);
            }

            function toggleNoResultsRow(tbody, tableId, show) {
                let noResultsRow = document.getElementById(`no-results-${tableId}`);
                const table = document.getElementById(tableId);
                const columnCount = table.querySelector("thead tr").children.length;

                if (!noResultsRow) {
                    noResultsRow = document.createElement("tr");
                    noResultsRow.id = `no-results-${tableId}`;
                    noResultsRow.innerHTML = `<td colspan="${columnCount}" style="text-align: center; font-style: italic;">No results found.</td>`;
                    tbody.appendChild(noResultsRow);
                } else {
                    const td = noResultsRow.querySelector("td");
                    td.colSpan = columnCount;
                }

                noResultsRow.style.display = show ? "" : "none";
            }

        });


        document.addEventListener("DOMContentLoaded", function () {
            // Function to open the reward approval modal
            function openRedemption(redeemRewardId) {
                disableScroll();
                const modal = document.getElementById("approveredeemreward-container");
                const modalContent = modal.querySelector(".approveredeem-popup-content");
                const rewardIdInput = document.getElementById("redeemRewardId");

                if (!rewardIdInput) return;

                rewardIdInput.value = redeemRewardId;

                modal.style.display = "flex";
                modal.style.visibility = "visible";
                modal.style.opacity = "1";

                setTimeout(() => {
                    modalContent.style.opacity = 1;
                    modalContent.style.transform = 'scale(1)';
                }, 50);
            }


            // Close modal function
            window.closeRedemption = function () {
                const modal = document.getElementById("approveredeemreward-container");
                const modalContent = modal.querySelector(".approveredeem-popup-content");

                enableScroll();

                // Animate out
                modalContent.style.opacity = 0;
                modalContent.style.transform = 'scale(0.9)';

                // Hide after transition
                setTimeout(() => {
                    modal.style.opacity = "0";
                    modal.style.visibility = "hidden";
                    modal.style.display = "none";
                }, 50);
            }

            const modal = document.getElementById("approveredeemreward-container");
            modal.addEventListener("click", function (event) {
                const modalContent = modal.querySelector(".approveredeem-popup-content");

                // If the clicked target is NOT the modal content or any of its children
                if (!modalContent.contains(event.target)) {
                    closeRedemption();
                }
            });


            // Attach event listeners to all approve buttons
            const approveButtons = document.querySelectorAll(".approve-btn");
            approveButtons.forEach(button => {
                button.addEventListener("click", () => {
                    const redeemRewardId = button.getAttribute("data-id");
                    openRedemption(redeemRewardId);

                });
            });

            // AJAX form submission for reward approval
            const form = document.getElementById("rewardCollectionForm");
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                console.log("FormData:", [...formData.entries()]);
                const data = {
                    redeemRewardId: formData.get("redeemRewardId"),
                    location: formData.get("location"),
                    currentTime: new Date().toISOString().slice(0, 19).replace("T", " "),
                    formType: "approveReward"
                };

                fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams(data)
                })
                .then(res => res.text())
                .then(response => {
                    if (response === "success") {
                        alert("Reward approved successfully.");
                        window.location.reload();
                    } else if (response === "insert_error") {
                        alert("Failed to insert into redeemed_rewards.");
                    } else if (response === "update_error") {
                        alert("Failed to update redeem_reward.");
                    } else {
                        alert("Unexpected response: " + response);
                        alert("Reward approved successfully.");
                    }
                })
                .catch(err => {
                    alert("Reward approved successfully.");
                    alert("Network or fetch error: " + err.message);
                });

            });
        });
        document.addEventListener("DOMContentLoaded", function () {
            const defaultTabId = "rewards-management";
            const navigationType = performance.getEntriesByType("navigation")[0]?.type;

            if (navigationType === "reload") {
                const savedTabId = localStorage.getItem("activeTab");
                if (savedTabId) {
                    const savedBtn = document.querySelector(`.tab-btn[onclick*="${savedTabId}"]`);
                    if (savedBtn) {
                        switchTab(savedTabId, savedBtn);
                        return;
                    }
                }
            }

            const defaultBtn = document.querySelector(`.tab-btn[onclick*="${defaultTabId}"]`);
            if (defaultBtn) {
                switchTab(defaultTabId, defaultBtn);
            }

            localStorage.removeItem("activeTab");
        });

        // document.addEventListener("DOMContentLoaded", function () {
        //     const savedTabId = localStorage.getItem("activeTab");

        //     if (savedTabId) {
        //         const savedBtn = document.querySelector(`.tab-btn[onclick*="${savedTabId}"]`);
        //         if (savedBtn) {
        //             switchTab(savedTabId, savedBtn);
        //         }
        //     }
        // });
        document.getElementById("rewardImage").addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const validTypes = ["image/jpeg", "image/png"];
                if (!validTypes.includes(file.type)) {
                    alert("Only JPEG and PNG files are allowed.");
                    this.value = ""; // Clear the input
                }
            }
        });
        document.getElementById("newImage").addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const validTypes = ["image/jpeg", "image/png"];
                if (!validTypes.includes(file.type)) {
                    alert("Only JPEG and PNG files are allowed.");
                    this.value = ""; // Clear the input
                }
            }
        });
    </script>


</body>
</html>
