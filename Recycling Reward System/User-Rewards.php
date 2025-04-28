<?php
    session_start();

    // if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    //     die("Form not submitted via POST.");
    // }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_login"])) {
        echo isset($_SESSION["user_id"]) ? "true" : "false";
        exit();
    }

    $user_id = $_SESSION["user_id"] ?? null;

    //var_dump($user_id); 

    require 'google-api-php-client/vendor/autoload.php'; 

    $con = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $unreadCount = 0; 

    if ($user_id) { 
        $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
        $unreadResult = mysqli_query($con, $unreadQuery);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $unreadCount = $unreadData['unread_count'];
    }

    $categoryQuery = "SELECT DISTINCT category FROM reward WHERE status = 'Available'";
    $categoryResult = mysqli_query($con, $categoryQuery);

    $categories = [];
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = htmlspecialchars($row['category']); 
    }

    $sort_order = 'ASC'; 

    if (isset($_POST['sort'])) {
        $sort_order = $_POST['sort'] === 'high-low' ? 'DESC' : 'ASC';

        $rewardquery = "SELECT * FROM reward WHERE status = 'Available' ORDER BY point_needed $sort_order";
        $result = mysqli_query($con, $rewardquery);

        if (!$result) {
            echo "Error: " . mysqli_error($con);
            exit();
        }

        $output = "";

        while ($row = mysqli_fetch_assoc($result)) {
            $file_id = htmlspecialchars($row["reward_image"]);
            $preview_url = "https://drive.google.com/file/d/$file_id/preview";

            $user_points = 0;
            if ($user_id) { 
                $pointsQuery = "SELECT points FROM user WHERE user_id = '$user_id'";
                $pointsResult = mysqli_query($con, $pointsQuery);
                if ($pointsResult) {
                    $pointsData = mysqli_fetch_assoc($pointsResult);
                    $user_points = $pointsData['points'];
                }
            }

            $output .= '<div class="reward-item" data-category="' . htmlspecialchars($row["category"]) . '" data-points="' . $row["point_needed"] . '">
                            <iframe src="' . $preview_url . '" allow="autoplay"></iframe>
                            <div class="reward-details">
                                <h2>' . htmlspecialchars($row["reward_name"]) . '</h2>
                                <h3>' . $row["point_needed"] . ' Points</h3>
                            </div>
                            <button class="redeem-btn" type="button" onclick="redirectToForm(' . $row["reward_id"] . ', ' . $user_points . ')">Redeem</button>
                        </div>';
        }
        
        echo $output;
        exit();
    }

    $rewardquery = "SELECT * FROM reward WHERE status = 'Available' ORDER BY point_needed $sort_order";
    $result = mysqli_query($con, $rewardquery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards - Green Coin</title>
    <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCuzWuyPcG8GwD5dRIHV0sFm3FdvJW_y3o&callback=initMap&libraries=maps.marker&v=beta"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
</head>

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
        z-index: 50;
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
        z-index: 10;
    }

    #scrollTopBtn:hover {
        background-color: darkgreen;
        transform: scale(1.1);
    }

    .reward-container{
        width: 100%;
        padding: 50px;
    }

    .reward-header{
        background-image: url('User-Rewards-Header.svg');
        background-position: top center;
        background-repeat: no-repeat;
        background-size: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* padding: 50px 30px 60px 30px; */
        padding: 6vh 5vh 12vh;
        border-radius: 30px;
        color: white;
    }

    .reward-header-title{
        font-size: 40px;
        font-family: "Playpen Sans", cursive;
        line-height: 2.1;
        letter-spacing: 2px;
    }

    .reward-header-desc{
        font-size: 14px;
        text-align: center;
        font-family: "Playpen Sans", cursive;
        letter-spacing: 0.5px;
    }

    .animated-button {
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

    .animated-button:hover {
        background: transparent;
        color: rgb(209, 137, 42);
    }

    .animated-button:active {
        box-shadow: 0 0 10px #78A24C;
    }

    .reward-content {
        display: flex;
        padding: 6vh 5vh;
        gap: 20px;
    }

    .reward-filter {
        /* width: 250px; */
        width: 15.8vw;
        /* background:rgb(250, 245, 228); */
        background:rgba(236, 235, 232, 0.43);
        /* border:2px solid rgb(164, 163, 163); */
        padding: 20px;
        border-radius: 10px;
        height: 100%;
    }

    .reward-filter h3 {
        font-family: "Playpen Sans", cursive;
        font-size: 17px;
        padding-bottom: 10px; 
        padding-left: 5px;
    }

    .reward-filter ul {
        list-style: none;
        padding: 0;
    }

    .reward-filter li {
        cursor: pointer;
        display: block;
        padding: 8px 0px 8px 20px;
        border-radius: 5px;
        transition: 0.3s;
        font-family: "Playpen Sans", cursive;
        margin-bottom: 5px;
        position: relative;
        transition: 0.3 ease;
        font-size: 14px;
        color:rgb(76, 76, 76);
        line-height: 1.5;
    }

    .reward-filter li::before {
        content: '';
        height: 16px;
        width: 3px;
        position: absolute;
        top: 11.5px;
        left: 5px;
        background-color: #d9892a;
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .reward-filter li:hover::before,
    .reward-filter li.active::before {
        opacity: 1;
    }

    .reward-filter li:hover,
    .reward-filter li.active {
        transform: translateX(-3px);
        color: #d9892a;
    }

    .filter-checkbox label {
        font-family: "Playpen Sans", cursive;
        cursor: pointer;
        font-size: 14px;
        color:rgb(76, 76, 76);
        line-height: 3;
    }

    /* .filter-checkbox input {
        margin-right: 8px;
        accent-color: #d9892a;
    } */

    .filter-checkbox input {
        display: none;
    }

    .filter-checkbox span{
        vertical-align:middle;
        font-family: "Playpen Sans", cursive;
    }

    .filter-checkbox label::before {
        content: "";
        display: inline-block;
        width: 10px;
        height: 10px;
        border: 2px solid rgb(171, 171, 170); 
        border-radius: 4px;
        margin-right: 9px;
        transition: all 0.3s ease;
        vertical-align:middle;
    }

    .filter-checkbox:hover label::before {
        border-color: #d9892a; 
    }

    .filter-checkbox input:checked + label::before {
        background-color: #d9892a; 
        border-color: #d9892a;
        content: "✔";
        font-size: 10px;
        color: white;
        text-align: center;
        line-height: 10px;
    }

    hr{
        border: none;
        height: 1.5px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
    }

    .reward-catalogue {
        flex: 1;
        display: flex;
        flex-direction: column;
        /* margin-left: 40px; */
        margin-left: 4.3vh;
    }

    .category-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px; 
        font-size: 23px;
        color: rgb(158, 102, 19); 
    }

    .reward-sort {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        padding: 10px;
        margin-bottom: 20px;
        font-size: 15px;
        /* margin-right: 15px; */
    }

    .reward-sort label {
        font-weight: 400;
        color: rgb(97, 97, 97); ;
    }

    .custom-select {
        position: relative;
        width: 180px; 
    }

    .custom-select select {
        font-size: 14px;
        padding: 8px 12px;
        border: 2px solid #d9892a;
        border-radius: 8px;
        background-color: #fffaf0;
        color: #333;
        cursor: pointer;
        outline: none;
        appearance: none; 
        width: 100%;
        text-align: left;
    }

    .custom-select::after {
        content: "▼";
        font-size: 10px;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color:rgb(147, 91, 23);
        pointer-events: none; 
    }

    .custom-select select:hover {
        background-color:rgb(252, 236, 216);
        border-color: #b96b18;
    }

    .reward-items {
        display: grid;
        /* grid-template-columns: 318px 318px 318px;  */
        /* grid-template-columns: 21.6vw 21.6vw 21.6vw;  */
        grid-template-columns: repeat(3, minmax(300px, 1fr));
        /* grid-template-columns: repeat(3,318px); */
        gap: 20px;
    }

    .reward-item {
        background: white;
        /* padding: 20px; */
        padding: 2.4vh;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transition: 0.3s;
        display: flex;
        flex-direction: column;
        justify-content: space-between; 
        height: 100%;
        /* height: 40vh; */
        /* width: 20.75vw; */
        /* height: auto; */
    }

    .reward-item:hover {
        transform: translateY(-5px);
    }

    /* .reward-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 10px;
        background: white;
    } */

    .reward-item iframe {
        /* width: 100%; */
        /* height: 200px; */
        /* height: 23.9vh; */
        aspect-ratio: 5/3.592988791;
        border-radius: 10px;
        background: white;
        border: 1px solid rgb(155, 155, 155);
        /* border: 1px solid rgb(220, 219, 219); */
        /* border: 1px solid black; */
        object-fit: cover;
        pointer-events: none;
    }

    .reward-details {
        display: flex;
        flex-grow: 1;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }

    .reward-item h2 {
        font-size: 16px;
        color: #333;
        text-align: left;
        flex-grow: 1;
        width: 64.5%;
        /* padding-right: 15px; */
    }

    .reward-item h3 {
        font-size: 16px;
        color: #d9892a;
        text-align: right;
        width: 35.5%;
    }

    .redeem-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 8px 16px;
        font-size: 14px;
        border-radius: 5px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 15px;
        width: 100%;
        padding-bottom: 10px;
    }

    .redeem-btn:hover {
        background-color: #218838;
    }

    .reward-item.hidden {
        display: none;
    }

    .popup-overlay {
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

    .popup-overlay.show {
        opacity: 1;
        display: block;
    }

    .popup-overlay.hide {
        opacity: 0;
    }

    .modal-content {
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

    .modal-content.show {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        visibility: visible;
    }

    .modal-content.hide {
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

    .form-container {
        padding-left: 50px;
        padding-right: 50px;
        padding-bottom: 30px;
        padding-top: 20px;
    }

    .form-container h1 {
        font-size: 30px;
        line-height: 1.8;
    }

    #subtitle {
        font-size: 16px;
        line-height: 1.5;
        color:rgb(89, 89, 89);
    }

    #modal-reward-name {
        font-size: 18px;
        color: black;
    }

    .form-container h3 {
        font-size: 16px;
        line-height: 1.8;
        color: green;
    }

    .form-container h4 {
        font-size: 14px;
        line-height: 1.5;
        color:rgb(89, 89, 89);
        font-weight: 300;
    }

    .form-container p {
        font-size: 12px;
        line-height: 1.5;
        color:rgb(89, 89, 89);
    }

    .form-container iframe {
        width: 181px;
        height: 130px;
        border-radius: 10px;
        background: white;
        border: 1px solid black;
        margin-bottom: 4px;
        pointer-events: none;
    }

    .form-container label {
        color:rgb(89, 89, 89);
        font-size: 13px;
    }

    .checkbox-container {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        color: #333;
        margin-top: 10px;
    }

    .checkbox-container input[type="checkbox"] {
        width: 15px;
        height: 15px;
        cursor: pointer;
        accent-color: rgb(78, 120, 49); 
    }

    .error {
        border-color:rgb(207, 62, 59) !important;
        box-shadow: 0 0 8px rgba(216, 27, 96, 0.2);
    }

    .error-checkbox {
        outline: 1px solid rgb(207, 62, 59) !important;
        border-radius: 2px; 
    }

    #modal-redeem-btn {
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

    #modal-redeem-btn:hover {
        background: linear-gradient(135deg, rgb(78, 120, 49), rgb(56, 90, 35)); 
        box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px); 
    }

    #modal-redeem-btn:active {
        transform: scale(0.98); 
    }

    #modal-redeem-btn:disabled {
        background: rgb(146, 157, 139); 
        cursor: not-allowed; 
        opacity: 0.6;
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

    .popup-error-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5); 
        backdrop-filter: blur(5px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 50; 
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
</style>

<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
            <li><a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
            <li><a class="active" onclick="window.location.href='User-Rewards.php'">Rewards</a></li>
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

    <script>
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
    </script>

    <button id="scrollTopBtn">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

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
    </script>

    <div class="reward-container">
        <div class="reward-header">
            <h1 class="reward-header-title">Rewards</h1>
            <p class="reward-header-desc">
                Redeem exciting rewards using your collected points! 
                <br>Browse available rewards and make the most of your recycling efforts.
            </p>
            <br><br>
            <button class="animated-button" type="button" onclick="window.location.href = 'User-Profile.php'">
                Check My Points Balance
            </button>
        </div>

        <div class="reward-content">
            <div class="reward-filter">
                <h3>Browse by</h3>
                <hr>
                <br>
                <ul>
                    <li onclick="browseBy('All')">All</li>
                    <?php foreach ($categories as $category): ?>
                        <li onclick="browseBy('<?php echo $category; ?>')"><?php echo $category; ?></li>
                    <?php endforeach; ?>
                </ul>

                <br><br><br><br>

                <h3>Filter by</h3>
                <hr>
                <br>
                <?php foreach ($categories as $category): ?>
                    <div class="filter-checkbox">
                        <input type="checkbox" id="<?php echo $category; ?>" onclick="filterRewards()">
                        <label for="<?php echo $category; ?>">
                            <span><?php echo $category; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>

                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        browseBy("All");
                    });

                    function browseBy(category) {
                        let rewardItems = document.querySelectorAll(".reward-item");
                        let categoryTitle = document.getElementById("category-name");

                        categoryTitle.textContent = category === "All" ? "All Rewards" : category;

                        document.querySelectorAll(".filter-checkbox input").forEach((checkbox) => {
                            checkbox.checked = false;
                        });

                        rewardItems.forEach((item) => {
                            let itemCategory = item.getAttribute("data-category");
                            item.style.display = category === "All" || itemCategory === category ? "flex" : "none";
                        });

                        document.querySelectorAll(".reward-filter li").forEach((li) => {
                            li.classList.remove("active");
                        });

                        let selectedCategory = document.querySelector(`.reward-filter li[onclick="browseBy('${category}')"]`);
                        if (selectedCategory) {
                            selectedCategory.classList.add("active");
                        }
                    }

                    function filterRewards() {
                        let selectedCategories = [];
                        document.querySelectorAll(".filter-checkbox input:checked").forEach((checkbox) => {
                            selectedCategories.push(checkbox.id);
                        });

                        let rewardItems = document.querySelectorAll(".reward-item");

                        rewardItems.forEach((item) => {
                            let category = item.getAttribute("data-category");

                            if (selectedCategories.length === 0 || selectedCategories.includes(category)) {
                                item.style.display = "flex";
                            } else {
                                item.style.display = "none";
                            }
                        });

                        document.getElementById("category-name").textContent = "All Rewards";

                        document.querySelectorAll(".reward-filter li").forEach((li) => {
                            li.classList.remove("active");
                        });

                        document.querySelector(".reward-filter li[onclick=\"browseBy('All')\"]").classList.add("active");
                    }
                </script>
            </div>

            <div class="reward-catalogue">
                <div class="category-title">
                    <h2 id="category-name">All</h2>
                </div>
                
                <div class="reward-sort">
                    <label for="sort">Sort by:</label>
                    <div class="custom-select">
                        <select id="sort" onchange="sortRewards()">
                            <option value="low-high">Lowest Points First</option>
                            <option value="high-low">Highest Points First</option>
                        </select>
                    </div>
                </div>

                <script>
                    function sortRewards() {
                        let sortValue = document.getElementById("sort").value;

                        let xhr = new XMLHttpRequest();
                        xhr.open("POST", "User-Rewards.php", true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                let rewardContainer = document.getElementById("reward-items");
                                // rewardContainer.innerHTML = ""; 
                                rewardContainer.innerHTML = xhr.responseText; 
                            }
                        };

                        xhr.send("sort=" + encodeURIComponent(sortValue));
                    }
                </script>

                <div class="reward-items" id="reward-items">
                    <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            $file_id = htmlspecialchars($row["reward_image"]); 
                            $preview_url = "https://drive.google.com/file/d/$file_id/preview";

                            $user_points = 0;
                            if ($user_id) { 
                                $pointsQuery = "SELECT points FROM user WHERE user_id = '$user_id'";
                                $pointsResult = mysqli_query($con, $pointsQuery);
                                if ($pointsResult) {
                                    $pointsData = mysqli_fetch_assoc($pointsResult);
                                    $user_points = $pointsData['points'];
                                }
                            }
                        ?>
                        <div class="reward-item" data-category="<?php echo htmlspecialchars($row["category"]); ?>" data-points="<?php echo $row["point_needed"]; ?>">
                            <iframe src="<?php echo $preview_url; ?>" allow="autoplay"></iframe>
                            <div class="reward-details">
                                <h2><?php echo htmlspecialchars($row["reward_name"]); ?></h2>
                                <h3><?php echo $row["point_needed"]; ?> Points</h3>
                            </div>
                            <button class="redeem-btn" type="button" onclick="redirectToForm(<?php echo $row['reward_id']; ?>, <?php echo $user_points; ?>)">Redeem</button>
                        </div>
                    <?php
                        }
                        // mysqli_close($con);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const body = document.body;
        
        function redirectToForm(rewardId, userPoints) {
            fetch("User-Rewards.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "check_login=true"
            })
            .then(response => response.text())
            .then(isLoggedIn => {
                console.log("Login Check Response:", isLoggedIn.trim());

                if (isLoggedIn.trim() === "true") {
                    console.log("User is logged in. Fetching reward details...");
                    fetchRewardDetails(rewardId, userPoints);
                } else {
                    console.log("User is NOT logged in. Showing error popup.");
                    showErrorPopup("Log in to your account to redeem reward."); 
                }
            })
            .catch(error => console.error("Error checking login:", error));
        }

        function fetchRewardDetails(rewardId, userPoints) {
            fetch("User-Rewards-FetchReward.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "reward_id=" + rewardId
            })
            .then(response => response.json())
            .then(data => {
                console.log("Raw response data:", data);
                console.log("Submitting form with reward_id:", rewardId);

                if (data.status === "success") {
                    console.log("Reward details:", data);

                    document.getElementById("modal-reward-id").value = rewardId;
                    document.getElementById("modal-reward-name").textContent = data.reward_name;
                    document.getElementById("modal-reward-image").src = "https://drive.google.com/file/d/" + data.reward_image + "/preview";
                    document.getElementById("modal-points-needed").textContent = data.point_needed;
                    document.getElementById("modal-user-points").textContent = userPoints;

                    const redeemButton = document.getElementById("modal-redeem-btn");
                    if (userPoints < data.point_needed) {
                        redeemButton.disabled = true;  
                    } else {
                        redeemButton.disabled = false;  
                    }

                    showDropoffPopup();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Error fetching reward details:", error));
        }

        function showDropoffPopup() {
            console.log("Opening popup form...");

            const overlay = document.querySelector(".popup-overlay");
            const modal = document.querySelector(".modal-content");

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
            }, 10);
        }

        function showErrorPopup(message) {
            const errorPopup = document.querySelector(".popup-error");
            const errorMessage = document.querySelector(".popup-error-message");
            const errorOverlay = document.querySelector(".popup-error-overlay");
            const okButton = document.querySelector(".error-ok");

            errorMessage.textContent = message;
            errorOverlay.style.display = "flex";
            errorPopup.style.visibility = "visible";
            body.style.overflow = "hidden";

            document.querySelector(".popup-overlay").style.display = "none";
            document.querySelector(".modal-content").style.visibility = "hidden";

            okButton.onclick = function () {
                window.location.href = "User-Login.php";
            };
        }

        function handlePopupClose() {
            const errorPopup = document.querySelector(".popup-error");
            const errorOverlay = document.querySelector(".popup-error-overlay");

            errorOverlay.style.display = "none";
            errorPopup.style.visibility = "hidden";
            body.style.overflow = "auto";
        }

        function handlePopupCloseBtn() {
            const errorPopup = document.querySelector(".popup-error");
            const errorOverlay = document.querySelector(".popup-error-overlay");

            errorOverlay.style.display = "none";
            errorPopup.style.visibility = "hidden";
            body.style.overflow = "auto";
        }
    </script>

    <div class="popup-error-overlay" style="display: none;">
        <div class="popup-error">
            <span class="popup-error-close"  onclick="handlePopupCloseBtn()">&times;</span>
            <div class="error-message-container">
                <img src="User-SignUp-ErrorIcon.png" width="110">
                <p class="popup-error-message"></p>
                <button class="error-ok" type="button" onclick="handlePopupClose()">OK</button>
            </div>
        </div>
    </div>

    <div class="popup-overlay"></div>

    <div class="modal">
        <form class="modal-content animate" action="User-Rewards.php" method="post">
            <div class="close-container">
                <span class="close" id="closePopup">&times;</span>
            </div>

            <div class="form-container">
                <h1>Redeem Your Reward</h1>
                <p id="subtitle">
                    Once you redeem your reward, you can visit any drop-off point to collect it!
                </p>
                <br>
                <center>
                    <iframe id="modal-reward-image" src="" allow="autoplay"></iframe>
                    <h3 id="modal-reward-name">Reward Name</h3>
                    <h3><span id="modal-points-needed"></span> Points</h3>
                </center>
                <br>

                <input type="hidden" name="reward_id" id="modal-reward-id">

                <h4>Terms and Conditions</h4>
                <p>
                    1. Points are deducted upon redemption. <br>
                    2. Redemptions cannot be cancelled, and points are non-refundable or exchanged for cash.<br>
                </p>
                <br>

                <div class="checkbox-container">
                    <input type="checkbox" id="acknowledge" name="acknowledge">
                    <label for="acknowledge">
                        I acknowledge that I have read and agree to the terms and conditions for redeeming this reward.
                    </label>
                </div>
                <br>

                <button id="modal-redeem-btn" type="submit" name="redeemBtn" value="submit">Redeem</button>
                <center>
                    <p>
                        Points available: <span id="modal-user-points"></span>
                    </p>
                </center>
            </div>
        </form>
    </div>

    <div id="successPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Reward redeemed successfully!</p>
    </div>

    <?php
        // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //     echo "<pre>";
        //     print_r($_POST);
        //     echo "</pre>";
        // }

        // if (isset($_POST['redeemBtn'])) {
        //     echo "redeemBtn is set";
        // } else {
        //     echo "redeemBtn is NOT set";
        // }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeemBtn']) ) {
            $user_id = $_SESSION['user_id'];
            $reward_id = mysqli_real_escape_string($con,$_POST['reward_id']);

            $rewardItemQuery = "SELECT * FROM reward WHERE reward_id = '$reward_id'";
            $rewardResult = mysqli_query($con, $rewardItemQuery);
            $rewardRow = mysqli_fetch_assoc($rewardResult);
            $requiredPoints = $rewardRow['point_needed'];
            $rewardName = $rewardRow['reward_name'];
        
            $system_announcement = "Great news! 🎁 You've successfully redeemed $rewardName for $requiredPoints points. 
                                    You can now visit any drop-off point to collect it. 
                                    Thank you for making a positive impact on the environment! 🌱♻️";

            $system_announcement = mysqli_real_escape_string($con, $system_announcement);

            $redeemSuccessfulNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
                                        ('$user_id', NOW(), 'Reward Redeemed Successfully! 🎁', '$system_announcement', 'unread')";
            
            // echo $redeemSuccessfulNotiQuery; 

            $result = mysqli_query($con, $redeemSuccessfulNotiQuery);
            
            if ($result) {
                $admin_announcement = "A user has redeemed $rewardName. Please ensure the reward is prepared and available for collection at all designated drop-off points.";
                
                $admin_announcement = mysqli_real_escape_string($con, $admin_announcement);

                $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
                                        ('$user_id', NOW(), '🎁 New Reward Redemption!', '$admin_announcement', 'unread')";
                
                $insertAdminNoti = mysqli_query($con, $newRequestNotiQuery);

                $updateRewardQuery = "UPDATE reward SET quantity = quantity - 1 WHERE reward_id = '$reward_id'";
                $updateRewardResult = mysqli_query($con, $updateRewardQuery);

                $updateUserQuery = "UPDATE user SET points = points - $requiredPoints WHERE user_id = '$user_id'";
                $updateUserResult = mysqli_query($con, $updateUserQuery);

                $insertRedeemRewardQuery = "INSERT INTO redeem_reward (user_id, redeem_datetime, reward_id, status) 
                                            VALUES ('$user_id', NOW(), '$reward_id', 'Unredeemed')";
                $insertRedeemResult = mysqli_query($con, $insertRedeemRewardQuery);
        
                if ($updateRewardResult && $updateUserResult && $insertRedeemResult) {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const popup = document.getElementById('successPopup');
                            popup.style.display = 'block';
                            setTimeout(function() {
                                popup.style.display = 'none';
                                window.location.href = 'User-Rewards.php';
                            }, 4000); 
                        });
                    </script>";
                } else {
                    echo "Error updating reward, user points, or inserting redeem record: " . mysqli_error($con);
                }
            } else {
                echo "Error inserting notification: " . mysqli_error($con);
            }
        }
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const openPopupBtn = document.getElementById("openPopup");
            const openPopupImg = document.getElementById("openPopupImg");
            const closePopupBtn = document.getElementById("closePopup");
            const modal = document.querySelector(".modal-content");
            const overlay = document.querySelector(".popup-overlay");
            const errorOverlay = document.getElementsByClassName("popup-error-overlay")[0];
            const errorModal = document.getElementsByClassName("popup-error")[0];
            const body = document.body;

            function closePopup() {
                modal.classList.remove("show");
                overlay.classList.remove("show");
                body.style.overflow = "auto";

                setTimeout(() => {
                    modal.classList.add("hide");
                    overlay.classList.add("hide");
                }, 300);

                setTimeout(() => {
                    modal.style.visibility = "hidden"; 
                    overlay.style.display = "none"; 
                    modal.classList.remove("hide"); 
                    overlay.classList.remove("hide");
                }, 350);
            }

            if (openPopupBtn) {
                openPopupBtn.addEventListener("click", redirectToForm);
            }

            if (openPopupImg) {
                openPopupImg.addEventListener("click", redirectToForm);
            }

            if (closePopupBtn) {
                closePopupBtn.addEventListener("click", closePopup);
            }

            overlay.addEventListener("click", function (event) {
                if (event.target === overlay) {
                    closePopup();
                }
            });

            errorOverlay.addEventListener("click", function(event){
                if (event.target === errorOverlay ){
                    errorOverlay.style.display = "none";
                    errorModal.style.visiblility = "hidden";
                    body.style.overflow = "auto";
                }
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.querySelector("form");
            const acknowledgeCheckbox = document.querySelector("input[name='acknowledge']");

            form.addEventListener("submit", function (event) {
                let hasError = false;

                removeError(acknowledgeCheckbox);

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

            acknowledgeCheckbox.addEventListener("change", () => acknowledgeCheckbox.classList.remove("error-checkbox"));
        });
        localStorage.setItem('activeTabIndex', 0);

    </script>

    <div class=“stickman-container”>
        <img style="margin-left: 550px; margin-top: 10px; margin-bottom: -57px;" src ="User-Rewards-Stickman.svg" width="750">
    </div>
</body>

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
</html>