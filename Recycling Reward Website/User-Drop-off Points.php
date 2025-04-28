<?php
    session_start();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_login"])) {
        echo isset($_SESSION["user_id"]) ? "true" : "false";
        exit();
    }

    $user_id = $_SESSION["user_id"] ?? null;

    //var_dump($user_id); 

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

    $locationQuery = "SELECT * FROM location WHERE status = 'Available' ORDER BY location_name";
    $result = mysqli_query($con, $locationQuery);

    $locations = [];
    if ($result->num_rows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {  
            $locations[] = $row;
        }
    }

    $con->close();  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop-off Points - Green Coin</title>
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

    .dropoff-container{
        width: 100%;
        padding: 50px;
    }

    .dropoff-header{
        background-image: url('User-Drop-off Points-Header.svg');
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

    .dropoff-header-title{
        font-size: 40px;
        font-family: "Playpen Sans", cursive;
        line-height: 2.1;
        letter-spacing: 2px;
    }

    .dropoff-header-desc{
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

    .timeline {
        position: relative;
        max-width: 1000px;
        margin: 50px auto;
    }

    .timeline::after {
        content: '';
        position: absolute;
        width: 5px;
        background: lightgray;
        top: 0;
        bottom: 0;
        left: 50%;
        margin-left: -2.5px;
    }

    .timeline-line {
        position: absolute;
        width: 5px;
        background: green;
        height: 260px;
        left: 50%;
        margin-left: -2.5px;
        top: 0;
        transition: top 0.3s ease-in-out;
        z-index: 10; 
    }

    .timeline-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding: 20px 0;
        position: relative;
    }

    .timeline-item img {
        width: 44%; 
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        filter: grayscale(100%);
        opacity: 0.5;
        transition: filter 0.3s, opacity 0.3s;
    }

    .timeline-item.active img {
        filter: grayscale(0%);
        opacity: 1;
    }

    .content {
        width: 45%;
        padding: 10px;
        background: white;
        text-align: left;
    }

    .content i{
        display: inline-flex;
        height: 35px;
        width: 35px;
        color: white;
        border-radius: 50%;
        margin: 10px 0 10px;
        background:rgb(12, 111, 42);
        border: 2px solid rgb(11, 91, 35);
        justify-content: center;
        align-items: center;
    }

    .content h4{
        font-size: 18px;
        padding-top: 15px;
    }

    .content p{
        font-size: 15px;
        line-height: 1.5;
    }
    
    .circle {
        position: absolute;
        width: 20px;
        height: 20px;
        background: lightgray;
        border-radius: 50%;
        left: 50%;
        transform: translateX(-50%);
        bottom: 0;
        transition: background 0.5s;
        z-index: 10;
    }

    .timeline-item:nth-child(odd) {
        flex-direction: row-reverse;
    }

    .location-title {
        padding: 40px 20px 30px;
        text-align: center;
    }

    .location-title h2 {
        font-family: "Playpen Sans", cursive;
        font-size: 27px;
        color: rgb(27, 108, 12);
        font-weight: bold;
    }

    .location-container {
        display: flex;
        height: 63vh;
        margin: 30px 60px;
        border: 2px solid rgb(199, 199, 199);
        border-radius: 5px;
    }

    .locations {
        width: 40%;
        overflow-y: auto;
        padding: 20px;
        background: #f4f4f4;
    }

    .map {
        width: 60%;
        height: 62.5vh;
        background: rgba(0,0,0,0.1);
    }

    #search_location{
        background-image: url('https://www.w3schools.com/css/searchicon.png');
        background-position: 12px 14px;
        background-repeat: no-repeat;
        height: 50px;
        background-color: white;
        width: 100%;
        padding: 12px 16px 12px 50px;
        margin: 3px 0 15px;
        border-radius: 8px; 
        border: 1px solid #ccc;
        font-size: 16px;
        box-sizing: border-box;
        outline: none;  
    }

    .location-item {
        background: white;
        padding: 10px 15px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;  
        justify-content: space-between; 
        gap: 10px;
    }

    .location-item h3{
        font-size: 18px;
        padding-bottom: 7px;
    }

    .location-info {
        flex: 1; 
    }

    .location-details {
        display: flex;
        gap: 5px;
    }

    .location-details i {
        font-size: 13px;
        line-height: 1.7;
        padding-right: 5px;
        color: rgb(121, 119, 119);
    }

    .location-details p {
        font-size: 15px;
        line-height: 1.5;
        color: rgb(57, 57, 57);
    }

    .location-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        color: rgb(187, 118, 33);
        padding: 5px 10px;
        text-decoration: none;
        margin-left: auto; 
        border-radius: 3px;
    }

    .location-btn i {
        font-size: 23px;
    }

    .location-btn p {
        font-size: 13px;
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

    input[type="text"], 
    input[type="date"], 
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

    input[type="date"] {
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

    input[type="date"]:hover, 
    input[type="date"]:focus, 
    select:hover, 
    select:focus {
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

    .stickman-container {
        position: relative;
        width: fit-content;
        margin: auto;
    }

    .fact{
        position: absolute;
        top: 95px; 
        left: 160px; 
        padding: 20px;
        max-width: 390px;
    }

    .fact h6{
        font-family: "Playpen Sans", cursive;
        font-size: 14px;
        color: white;
        padding-bottom: 10px;
        font-weight: 400; 
    }

    .fact h2{
        font-family: "Playpen Sans", cursive;
        font-size: 30px;
        color: white;
        line-height: 1.4;
    }

    .fact p{
        font-size: 13.5px;
        color: black;
        font-weight: 550;
        line-height: 1.6;
        padding-bottom: 15px;
    }

    /* .fact button {
        background: green;
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
    } */
    
    .fact button {
        position: relative;
        background-color: #5F9229;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 20px;
        font-family: "Playpen Sans", cursive;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 4px 4px  #C3E1A4;
        transition: all 0.3s ease-in-out;
    }

    .fact button:hover {
        transform: translateY(-2px); 
    }

    .fact button:active {
        transform: scale(0.98); 
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
            <li><a class="active" onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
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

    <div class="dropoff-container">
        <div class="dropoff-header">
            <h1 class="dropoff-header-title">Drop-off Points</h1>
            <p class="dropoff-header-desc">
                Locate a nearby recycling center and safely dispose of your e-waste 
                <br>while earning rewards for your contributions to a greener future.
            </p>
            <br><br>
            <button class="animated-button" type="button" id="openPopup" onclick="redirectToForm()">
                Ready to Recycle? Let's Get Started!
            </button>

            <script>
                const body = document.body;
                
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


                function redirectToForm() {
                    fetch("User-Drop-off Points.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "check_login=true"
                    })
                    .then(response => response.text())
                    .then(isLoggedIn => {
                        console.log("Raw Login Check Response:", isLoggedIn); 
                        console.log("Trimmed Login Check Response:", `"${isLoggedIn.trim()}"`); 

                        if (isLoggedIn.trim() === "true") {
                            console.log("User is logged in. Opening popup...");
                            showDropoffPopup(); 
                        } else {
                            console.log("User is NOT logged in. Showing error popup.");
                            showErrorPopup("Log in to your account to access this form."); 
                        }
                    })
                    .catch(error => console.error("Error checking login:", error));
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
        </div>

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

        <div class="steps-title">
            <h2><mark>Drop, Recycle & Earn</mark></h2><h3> - It's That Simple!</h3>
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

        <div class="timeline">
            <div class="timeline-line"></div>
            <div class="timeline-item">
                <img src="User-Drop-off Points-Step1.svg">
                <div class="content">
                    <i class="fa-solid fa-1"></i>
                    <h4>Choose a Drop-Off Location</h4>
                    <br>
                    <p>
                        Browse through our list of recycling centers and find the one that's most convenient for you. 
                        Whether it's near your home, office, or a place you frequently visit, 
                        we've got multiple locations to make recycling hassle-free.
                    </p>
                </div>
            </div>
            <div class="timeline-item">
                <img src="User-Drop-off Points-Step2.svg">
                <div style="text-align: right;" class="content">
                    <i class="fa-solid fa-2"></i>
                    <h4>Fill in the Drop-Off Form</h4>
                    <br>
                    <p>
                        Before heading over, fill out a simple form to let us know what e-waste you'll be dropping off. 
                        Select your chosen recycling center and pick a drop-off date that works for you. 
                        This helps us ensure smooth processing and accurate reward allocation.
                    </p>
                </div>
            </div>
            <div class="timeline-item">
                <img src="User-Drop-off Points-Step3.svg">
                <div class="content">
                    <i class="fa-solid fa-3"></i>
                    <h4>Drop Off Your Items</h4>
                    <br>
                    <p>
                        Take your e-waste to the selected recycling center on your chosen date. 
                        Our designated team will ensure proper disposal and make sure everything is processed correctly. 
                        Feel free to ask any questions if you need assistance at the location!
                    </p>
                </div>
            </div>
            <div class="timeline-item">
                <img src="User-Drop-off Points-Step4.svg">
                <div style="text-align: right;" class="content">
                    <i class="fa-solid fa-4"></i>
                    <h4>Get Rewarded for Going Green!</h4>
                    <br>
                    <p>
                        Once our team verifies your drop-off, we'll add reward points to your account 
                        - because recycling should be rewarding!
                    </p>
                    <br><br>
                </div>
            </div>
            <div class="circle"></div>
        </div>

        <script>
            document.addEventListener("scroll", function () {
                let items = document.querySelectorAll(".timeline-item");
                let timelineLine = document.querySelector(".timeline-line");
                let circle = document.querySelector(".circle");
                let timeline = document.querySelector(".timeline");

                let viewportMiddle = window.innerHeight / 2;
                let activeIndex = -1;
                let closestDistance = Infinity;

                items.forEach((item, index) => {
                    let rect = item.getBoundingClientRect();
                    let itemMiddle = rect.top + rect.height / 2;
                    let distance = Math.abs(viewportMiddle - itemMiddle);

                    if (distance < closestDistance) {
                        closestDistance = distance;
                        activeIndex = index;
                    }

                    item.classList.remove("active");
                });

                if (activeIndex !== -1) {
                    let activeItem = items[activeIndex];
                    activeItem.classList.add("active");

                    let timelineRect = timeline.getBoundingClientRect();
                    let activeRect = activeItem.getBoundingClientRect();
                    let newTop = activeItem.offsetTop + activeRect.height / 100;  

                    let lastItem = items[items.length - 1];
                    let lastItemBottom = lastItem.offsetTop + lastItem.offsetHeight;
                    let maxTop = lastItemBottom - 270;  

                    newTop = Math.min(newTop, maxTop);

                    timelineLine.style.transition = "top 0.2s linear"; 
                    if(activeIndex == 0){
                        newTop = 0;
                        timelineLine.style.top = `${newTop}px`;
                    }else{
                        timelineLine.style.top = `${newTop}px`;
                    }
                    if (activeIndex === items.length - 1) {
                        circle.style.background = "green";  
                    } else {
                        circle.style.background = "lightgray"; 
                    }
                }
            });
        </script>

        <div class="location-title">
            <h2>Find a Recycling Drop-Off Location Near You</h2>
        </div>

        <div class="location-container">
            <div class="locations" id="locationList">
                
                <div class="search">
                    <input type="text" id="search_location" onkeyup="filterLocation()" placeholder="Search...">
                </div>

                <?php foreach ($locations as $loc) : ?>
                    <div class="location-item">
                        <div class="location-info">
                            <h3><?php echo htmlspecialchars($loc['location_name']); ?></h3>
                            
                            <div class="location-details">
                                <i class="fa fa-map-marker-alt" style="font-size: 14px; padding-right: 7px;"></i>
                                <p><?php echo htmlspecialchars($loc['address']); ?></p>
                            </div>

                            <div class="location-details">
                                <i class="fa fa-phone"></i>
                                <p><?php echo htmlspecialchars($loc['contact_no']); ?></p>
                            </div>

                            <div class="location-details">
                                <i class="fa-solid fa-clock"></i>
                                <p><?php echo htmlspecialchars($loc['description']); ?></p>
                            </div>

                        </div>
                        <a class="location-btn" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($loc['address']); ?>" target="_blank">
                            <i class="fa-solid fa-location-arrow"></i>
                            <p>Directions</p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="map" id="map"></div>
        </div>

        <script>
            function filterLocation() {
                const input = document.getElementById("search_location");
                const filter = input.value.toUpperCase();
                const locationItems = document.querySelectorAll(".location-item");

                locationItems.forEach(item => {
                    const name = item.querySelector("h3").textContent.toUpperCase();
                    const address = item.querySelector(".location-details p").textContent.toUpperCase();

                    if (name.includes(filter)) {
                        item.style.display = "flex";
                    } else {
                        item.style.display = "none";
                    }
                });
            }
        </script>
        <script>
            let locations = <?php echo json_encode($locations); ?>;

            function initMap() {
                let map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 12,
                    center: { lat: 3.139, lng: 101.686 }  
                });

                let bounds = new google.maps.LatLngBounds();

                locations.forEach(loc => {
                    let geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ address: loc.address }, function(results, status) {
                        if (status === "OK") {
                            let marker = new google.maps.Marker({
                                position: results[0].geometry.location,
                                map: map,
                                title: loc.location_name
                            });

                            bounds.extend(results[0].geometry.location);

                            let infoWindow = new google.maps.InfoWindow({
                                content: `<h4>${loc.location_name}</h4><p>${loc.address}</p>`
                            });

                            marker.addListener("click", () => {
                                infoWindow.open(map, marker);
                            });

                            map.fitBounds(bounds);
                        }
                    });
                });
            }

            window.onload = initMap;
        </script>
    </div>

    <div class="popup-overlay"></div>

    <div class="modal">
        <form class="modal-content animate" action="#" method="post">
            <div class="close-container">
                <span class="close" id="closePopup">&times;</span>
            </div>

            <div class="form-container">
                <h1>Drop-Off Request</h1>
                <p>
                    Fill out this form before heading to the drop-off points
                    to ensure a smooth drop-off process and earn your reward points.
                </p>
                <br><br>

                <label for="date">Drop-off Date</label>
                <br>
                <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>" max="2100-12-31">
                <br><br>

                <label for="location">Drop-off Location</label>
                <?php
                    $con=mysqli_connect("localhost","root","","cp_assignment");
                                    
                    if(mysqli_connect_errno()){
                        echo "Failed to connect to MySQL:".mysqli_connect_error();
                    }

                    $sql = "SELECT * FROM location WHERE status = 'Available' ORDER BY location_name";
    
                    $location_result = mysqli_query($con, $sql);
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

                <button class="addformbtn" type="submit" name="submitBtn">Submit</button>
            </div>
        </form>
    </div>

    <div id="successPopup" class="success-popup">
        <i class="fa-solid fa-circle-check"></i>
        <br><br>
        <p>Drop-off request <br> submitted successfully!</p>
    </div>
    
    <?php
        if(isset($_POST['submitBtn'])){
            $date = mysqli_real_escape_string($con,$_POST['date']);
            $location_id = mysqli_real_escape_string($con,$_POST['location']);
            $user_id = $_SESSION['user_id'];

            $sql_insert="INSERT INTO dropoff (dropoff_date, status, user_id, location_id) 
            VALUES ('$date','unread','$user_id', '$location_id')";

            if (!mysqli_query($con,$sql_insert)){
                die('Error: ' . mysqli_error($con));
            }else{
                $system_announcement = "Your drop-off request has been successfully submitted! 📦♻️
                                        Please proceed to your selected drop-off location on the chosen date. 
                                        Points will be assigned upon verification of your drop-off. 
                                        Thank you for recycling and making a difference! 🌱✨";
                $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
                ('$user_id', NOW(), 'Drop-Off Request Submitted ✅', '$system_announcement', 'unread')";
                mysqli_query($con, $requestSubmittedNotiQuery);

                $admin_announcement = "A user has submitted a drop-off request. Please review and process it accordingly.";
                $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
                ('$user_id', NOW(), '📦 New Drop-off Request Received!', '$admin_announcement', 'unread')";
                mysqli_query($con, $newRequestNotiQuery);

                echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const popup = document.getElementById('successPopup');
                            popup.style.display = 'block';
                            setTimeout(function() {
                                popup.style.display = 'none';
                                window.location.href = 'User-Drop-off Points.php';
                            }, 4000); 
                        });
                    </script>";
            }
        }
    ?>

    <div class="stickman-container">
        <img 
            style="margin-top: 0px; margin-bottom: -5.5px;" 
            src="User-Drop-off Points-Stickman Facts.svg" 
            width="1070"
        >
        <div class="fact">
            <h6>DID YOU KNOW?</h6>
            <h2>Recycling your electricals is good for the planet</h2>
            <br>
            <p>
                We could save a whopping 2.8 million tonnes of carbon dioxide emissions 
                if we recycled all our small unwanted electricals instead of binning or holding on to them. 
                That's like taking more than a million cars off the road.
            </p>
            <button type="button" id="openPopup" onclick="redirectToForm()">
                Start Recycling Now!
            </button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const openPopupBtn = document.getElementById("openPopup");
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
            const dateInput = document.querySelector("input[name='date']");
            const locationSelect = document.querySelector("select[name='location']");
            const acknowledgeCheckbox = document.querySelector("input[name='acknowledge']");

            form.addEventListener("submit", function (event) {
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

            dateInput.addEventListener("input", () => removeError(dateInput));
            locationSelect.addEventListener("change", () => removeError(locationSelect));
            acknowledgeCheckbox.addEventListener("change", () => acknowledgeCheckbox.classList.remove("error-checkbox"));
        });
        localStorage.setItem('activeTabIndex', 0);
    </script>

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
</body>