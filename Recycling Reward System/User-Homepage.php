<?php
    session_start();

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

    $dropoff_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM dropoff"))['total'];
    $pickup_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM pickup_request"))['total'];
    $total_requests = $dropoff_count + $pickup_count;

    $user_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM user"))['total'];

    $location_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM location"))['total'];

    $reward_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM redeem_reward"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Coin</title>
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

    .videoContainer {
        position: relative;
        width: 100%;
        height:100vh;
        overflow: hidden;
    }

    .videoContainer video {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        min-width: 100%;
        min-height: 100%;
        z-index: -1;
    }

    .videoContainer .overlay {
        height: 100%;
        width: 100%;
        position: absolute;
        top: 0px;
        left: 0px;
        z-index: 0;
        background: black;
        opacity: 0.5;
    }

    .videoContent {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: white;
        z-index: 1; 
        max-width: 80%; 
    }

    .videoContent h1 {
        font-size: 35px;
        font-weight: bold;
    }

    .videoContent p {
        font-size: 17px;
        line-height: 1.5;
    }

    .animated-button {
        display: inline-block;
        padding: 15px 30px;
        font-size: 15px;
        color: white;
        background: #78A24C;
        border: 2px solid #78A24C;
        border-radius: 30px;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        font-family: "Playpen Sans", cursive;
    }

    .animated-button:hover {
        background: transparent;
        color: #78A24C;
    }

    .animated-button:active {
        box-shadow: 0 0 10px #78A24C;
    }

    html {
        scroll-behavior: smooth;
    }

    .section2 {
        background-color:white;
        padding: 60px 15%;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .section2 img {
        width: 45%;
        max-width: 600px;
        border-radius: 10px;
    }

    .typingContainer {
        padding: 20px;
        width: 50%;
        color: black;
    }

    .typingContainer h2 {
        font-size: 30px;
        color: rgb(40, 86, 15);
        font-family: "Playpen Sans", cursive;
    }

    .typingContainer h2 span {
        color:rgb(209, 137, 42);
        font-weight: bold;
        font-family: "Playpen Sans", cursive;
    }

    .typingContainer ul {
        margin-top: 20px;
        padding-left: 0px;
    }

    .typingContainer li {
        font-size: 17px;
        font-weight: bold;
        line-height: 1.2;
        margin-bottom: 10px;
        list-style: none;
        padding: 10px 40px;
        background-image: url("https://img.icons8.com/?size=100&id=zeRZbA_1nZ3n&format=png&color=000000");
        background-repeat: no-repeat;
        background-position: left center;
        background-size: 20px;
    }

    .typingContainer p {
        font-size: 16px;
        line-height: 1.8;
    }

    .section3 {
        background-color:rgb(226, 234, 210);
        padding: 60px 15%;
        align-items: center;
        justify-content: space-around;
        gap: 10px;
        text-align: center;
    }

    .section3 h1 {
        font-family: "Playpen Sans", cursive;
        font-size: 35px;
        color: #28560f;
        font-weight: bold;
        margin-bottom: 30px;
    }

    .section3 h1 span {
        font-family: "Playpen Sans", cursive;
        color: rgb(111, 152, 87);
        text-decoration: underline;
        text-decoration-color:rgb(246, 214, 6);
        text-decoration-thickness: 6px;
    }

    .count-up-wrapper {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .count-up-container {
        width: 25vmin;
        height: 25vmin;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        padding: 1em 0;
        position: relative;
        font-family: "Playpen Sans", cursive;
        font-size: 16px;
        border-radius: 15px;
        background-color: rgb(252, 252, 252, 0.5);
        border-bottom: 10px solid rgb(40, 86, 15);
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out;
    }

    .count-up-container i{
        color: rgb(209, 137, 42);
        font-size: 2.5em;
        text-align: center
    }

    .count-up-container span.num{
        color: rgb(137, 187, 108);
        display: grid;
        place-items: center;
        font-family: "Playpen Sans", cursive;
        font-weight: 600;
        font-size: 3em;
    }

    .count-up-container span.text{
        color: black;
        font-size: 1em;
        text-align: center;
        pad: 0.7em 0;
        font-weight: 400;
        line height: 0;
    }

    .section4 {
        background-color: #F4F4EB;
        /*background: linear-gradient( #F4F4EB, rgb(226, 234, 210));*/
        padding: 60px 15%;
        align-items: center;
        justify-content: space-between;
        gap: 40px;
        text-align: center;
    }

    .section4 h1 {
        font-family: "Playpen Sans", cursive;
        font-size: 30px;
        text-align: center;
        color: rgb(182, 110, 16);
    }

    .section4 h2 {
        font-family: "Playpen Sans", cursive;
        color:rgb(40, 86, 15);
        font-size: 18px;
        font-weight: 550;
    }

    .section4 h3 {
        font-family: "Playpen Sans", cursive;
        color:rgb(121, 121, 121);
        font-size: 16px;
        font-weight: 400;
    }

    .section4 p {
        font-size: 16px;
        line-height: 1.8;
    }

    .slider-wrapper {
        overflow: hidden;
        max-width: 1200px;
        margin: 0 70px 55px; 
    }

    .card-list .card-item {
        color: black;
        user-select: none;
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .swiper-pagination-bullet {
        background: rgb(182, 110, 16);
        height: 10px;
        width: 10px;
    }

    .swiper-slide-button{
        color: rgb(182, 110, 16);
        margin-top: -50px;
        transition: 0.2s ease;
    }

    .section5 {
        background-image: url("User-Homepage-Sec5-Bg.png");
        background-position: bottom center;
        background-repeat: no-repeat;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        padding: 60px 15%;
        align-items: center;
        justify-content: space-between;
    }

    .review-container{
        margin: auto;
        padding: 1rem;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 80px;
    }

    .review-intro{
        width: 31vw;
    }

    .review-intro h1{
        font-family: "Playpen Sans", cursive;
        font-size: 35px;
        color: #28560f;
        font-weight: bold;
        margin-bottom: 40px;
    }

    .review-intro p{
        font-size: 16px;
        line-height: 1.5;
    }

    mark {
        --color1:rgb(255, 225, 0);
        --color2:rgb(255, 225, 0);
        --height: 100%;    
        all: unset;
        background-image: linear-gradient(var(--color1), var(--color2));
        background-position: 0 100%;
        background-repeat: no-repeat;
        background-size: 0 var(--height);
        animation: highlight 2000ms 1 ease-out;
        animation-fill-mode: forwards;
        animation-play-state: paused; 
    }
        
    @keyframes highlight {
        to {
            background-size: 100% var(--height);
        }
    }

    .review-intro button{
        display: inline-block;
        padding: 15px 35px;
        font-family: "Playpen Sans", cursive;
        font-size: 15px;
        color: white;
        background: #78A24C;
        border: 2px solid #78A24C;
        border-radius: 30px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .review-intro button:hover {
        background: transparent;
        color: #78A24C;
    }

    .review-intro button:active {
        box-shadow: 0 0 10px #78A24C;
    }

    .review-content{
        display: grid;
        gap: 2rem;
        width: 31vw;
    }

    .card{
        padding: 25px;
        display: flex;
        align-items: flex-start;
        gap: 20px;
        background-color: white;
        border-radius: 1rem;
        box-shadow: 5px 5px 20px rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }

    .card img{
        max-width: 75px;
        border-radius: 100%;
    }

    .card-content{
        display: flex;
        gap: 1rem;
    }

    .card-content span i{
        font-size: 2rem;
        color: rgb(182, 110, 16);
    }

    .card-details p{
        font-family: "Playpen Sans", cursive;
        font-size: 14.5px;
        font-style: italic;
        color: rgb(94, 91, 88);
        margin-bottom: 1rem;
    }

    .card-details h4{
        text-align: right;
        font-family: "Playpen Sans", cursive;
        color: rgb(182, 110, 16);
        font-size: 14.5px;
        font-weight: 500;
    }

    .section6 {
        /*background-color:white;*/
        background: linear-gradient(#F4F4EB, white);
        padding: 60px 15%;
        align-items: center;
        justify-content: space-between;
        gap: 40px;
        text-align: center;
    }

    .section6 h1 {
        font-family: "Playpen Sans", cursive;
        font-size: 30px;
        text-align: center;
        color: rgb(182, 110, 16);
    }

    .section6 p {
        font-family: "Playpen Sans", cursive;
        color:rgb(119, 118, 118);
        font-size: 16px;
        line-height: 1.6;
        font-weight: 400;
    }

    .card-container {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-box .card-link {
        width: 450px;
        display: block;
        background: white;
        padding: 40px;
        margin: 18px;
        border-radius: 12px;
        text-decoration: none;
        border: 2px solid transparent;
        border-color:rgb(208, 208, 208);
        box-shadow: 0 10px 10px rgba(0, 0, 0, 0,05);
        transition: 0.2s ease;
    }

    .card-box .card-link:hover {
        border-color:rgb(145, 193, 119);
    }

    .card-box .card-link .card-image{
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        border-radius: 10px; 
    }

    .card-box .card-link .card-badge{
        color: rgb(158, 92, 16);
        padding: 8px 16px;
        font-family: "Playpen Sans", cursive;
        font-size: 0.95rem;
        font-weight: 550;
        margin: 16px 0 18px;
        background: rgb(247, 232, 150);
        width: fit-content;
        border-radius: 50px;
    }

    .card-box .card-link .card-badge.pickup{
        color: #b25a2b;
        background: #ffe3d2;
    }

    .card-box .card-link .card-badge.dropoff{
        color: #205c20;
        background: #d6f8d6;
    }

    .card-box .card-link .card-title{
        font-size: 15px;
        color: black;
        font-weight: 400;
        text-align: left;
        padding: 5px;
        line-height: 1.5;
    }

    .card-box .card-link .card-button{
        height: 35px;
        width: 35px;
        color: black;
        border-radius: 50%;
        margin: 30px 0 5px;
        align-items: left;
        background:rgb(168, 168, 167);
        cursor: pointer;
        border: 2px solid rgb(168, 168, 167);
        transform: rotate(-45deg);
        transition: 0.4s ease;
    }

    .card-box .card-link:hover .card-button{
        color: white;
        background:rgb(100, 170, 77);
        border: 2px solid rgb(100, 170, 77);
        transform: rotate(0deg);
    }

    footer{
        background-image: url("User-Homepage-Footer.png");
        background-position: bottom center;
        background-repeat: no-repeat;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        position: relative;
        width: 100%;
        /* height: 600px; */
        height: 70vh;
        min-height: 600px;  
        max-height: 700px;
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
        padding-top: 250px;
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
</style>

<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a class="active" onclick="window.location.href='User-Homepage.php'">Home</a></li>
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
            const coverPageHeight = document.querySelector(".videoContent").offsetHeight; 

            window.addEventListener("scroll", function () {
                if (window.scrollY > coverPageHeight) {
                    scrollTopBtn.style.display = "flex"; 
                } else {
                    scrollTopBtn.style.display = "none";
                }
            });

            scrollTopBtn.addEventListener("click", function () {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });
        });
    </script>

    <div class="videoContainer">
        <div class="overlay"></div>
        <video autoplay muted loop>
            <source src="User-Homepage-Cover Video.mp4" type="video/mp4">
        </video>
    </div>

    <div class="videoContent">
        <h1>E-waste Recycling in Kuala Lumpur</h1>
        <br><br><br>
        <p>
            Make e-waste recycling effortless and rewarding with Green Coin.
            Schedule pickups, locate drop-off points, and track your recycling progress—all while earning rewards for your contributions. <br><br>
            Join us in creating a cleaner and more sustainable future for Kuala Lumpur.
        </p>
        <br><br><br><br>
        <a href="#section2" class="animated-button">Learn More</a>
    </div>

    <div class="section2" id="section2">
        <img src="User-Homepage-Sec2.png">
        <div class="typingContainer">
            <h2>Perfect for <span class="auto-type"></span></h2>
            <br> 
            <p>
                Whether you're a homeowner, a small business owner, or part of a community initiative, Green Coin makes e-waste recycling simple and rewarding. 
                Our platform is designed to help you dispose of electronic waste responsibly while earning rewards for your contributions.
            </p>
            <br>
            <ul>
                <li>Convenient Pickup & Drop-off</li>
                <li>Transparent Point System</li>
                <li>Earn & Redeem Rewards</li>
                <li>Instant Notifications</li>
                <li>Review & Share Experience</li>
                <li>User-Friendly Platform</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>

    <script>
        var typed = new Typed(".auto-type", {
            strings: ["Households", "Small Businesses", "Communities"],
            typeSpeed: 100,
            backSpeed: 100,
            loop: true
        })
    </script>

    <div class="section3">
        <h1>Our <span>Green Coin</span> Recycling Journey</h1>
        
        <div class="count-up-wrapper">
            <div class="count-up-container">
                <i class="fa-solid fa-recycle"></i>
                <span class="num" data-val="<?php echo $total_requests; ?>">0</span>
                <span class="text">Requests Submitted</span>
            </div>

            <div class="count-up-container">
                <i class="fa-solid fa-user-plus"></i>
                <span class="num" data-val="<?php echo $user_count; ?>">0</span>
                <span class="text">Registered Users</span>
            </div>

            <div class="count-up-container">
                <i class="fa-solid fa-location-dot"></i>
                <span class="num" data-val="<?php echo $location_count; ?>">0</span>
                <span class="text">Drop-off Locations</span>
            </div>

            <div class="count-up-container">
                <i class="fa-solid fa-gift"></i>
                <span class="num" data-val="<?php echo $reward_count; ?>">0</span>
                <span class="text">Rewards Redeemed</span>
            </div>
        </div>
    </div>

    <script>
        let valueDisplays = document.querySelectorAll(".num");
        let interval = 1050;
        let hasCounted = false;

        function startCounting() {
            valueDisplays.forEach(valueDisplay => {
                let startValue = 0;
                let endValue = parseInt(valueDisplay.getAttribute("data-val"));
                let duration = Math.floor(interval / endValue);

                let counter = setInterval(() => {
                    startValue += 1;
                    valueDisplay.textContent = startValue;
                    if (startValue === endValue) {
                        clearInterval(counter);
                    }
                }, duration);
            });
        }

        function isSectionInMiddle(element) {
            const rect = element.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const elementMiddle = rect.top + rect.height / 1.4;
            const screenMiddle = viewportHeight / 1.4;

            return Math.abs(elementMiddle - screenMiddle) < 50;
        }

        function onScrollCheck() {
            const section = document.querySelector(".section3");

            if (!hasCounted && isSectionInMiddle(section)) {
                hasCounted = true;
                startCounting();
                window.removeEventListener("scroll", onScrollCheck); 
            }
        }

        window.addEventListener("scroll", onScrollCheck);
    </script>

    <div class="section4">
        <h1>Categories of E-waste We Accept</h1>
        <br>
        <h3>Anything with a plug, battery or cable can be recycled.</h3>
        <br><br>
        <div class="container swiper">
            <div class="slider-wrapper">
                <div class="card-list swiper-wrapper">
                    <div class="card-item swiper-slide">
                        <img src="User-Homepage-Sec4-Consumer Electronics.png" class="category-image">
                        <h2 class="category-name">Consumer Electronics</h2>
                        <br>
                        <p class="category-detail">Mobile phones, tablets, laptops, gaming consoles, smartwatches</p>
                    </div>

                    <div class="card-item swiper-slide">
                        <img src="User-Homepage-Sec4-Household Appliances.png" class="category-image">
                        <h2 class="category-name">Household Appliances</h2>
                        <br>
                        <p class="category-detail">Microwaves, refrigerators, washing machines, air conditioners</p>
                    </div>

                    <div class="card-item swiper-slide">
                        <img src="User-Homepage-Sec4-IT & OFfice Equipment.png" class="category-image">
                        <h2 class="category-name">IT & Office Equipment</h2>
                        <br>
                        <p class="category-detail">Desktops, printers, routers, monitors, servers</p>
                    </div>

                    <div class="card-item swiper-slide">
                        <img src="User-Homepage-Sec4-Industrial Electronics.png" class="category-image">
                        <h2 class="category-name">Industrial Electronics</h2>
                        <br>
                        <p class="category-detail">Circuit boards, power supplies, transformers, industrial sensors</p>
                    </div>

                    <div class="card-item swiper-slide">
                        <img src="User-Homepage-Sec4-Batteries & Accessories.png" class="category-image">
                        <h2 class="category-name">Batteries & Accessories</h2>
                        <br>
                        <p class="category-detail">Lithium-ion batteries, power banks, chargers, cables</p>
                    </div>
                </div>
            </div>

            <div class="swiper-pagination"></div>
            <div class="swiper-slide-button swiper-button-prev"></div>
            <div class="swiper-slide-button swiper-button-next"></div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        const swiper = new Swiper('.slider-wrapper', {
            loop: true,
            grabCursor: true,
            spaceBetween: 30,

            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },

            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },

            breakpoints: {
                0: {
                    slidesPerView: 1
                },
                620: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 3
                },
            }
        });
    </script>

    <div class="section5">
        <div class="review-container">
            <div class="review-intro">
                <h1> Read What Our Recyclers Love About Us</h1>
                <p>
                    We believe that every small effort in recycling makes a big difference! 
                    See what our dedicated recyclers have to say about their experience with Green Coin. <br><br>
                    From hassle-free pickups to exciting rewards, our users share how Greencoin has made e-waste recycling 
                    <mark>easier, rewarding, and more impactful</mark>.
                </p>
                <br><br><br>
                <button onclick="window.location.href='User-Review.php'">Read More Reviews</button>
            </div>

            <div class="review-content">
                <div class="card">
                <img src="User-Homepage-Sec5-Profile1.png">
                    <div class="card-content">
                        <span>
                            <i class="fa-solid fa-quote-left"></i>
                        </span>
                        <div class="card-details">
                            <p>
                                I used to struggle finding proper ways to recycle my old gadgets, but Greencoin made it so simple! 
                                Definitely the best e-waste recycling platform!
                            </p>
                            <h4>— Alicia Tan</h4>
                        </div>
                    </div>
                </div>

                <div class="card">
                <img src="User-Homepage-Sec5-Profile2.png">
                    <div class="card-content">
                        <span>
                            <i class="fa-solid fa-quote-left"></i>
                        </span>
                        <div class="card-details">
                            <p>
                                I love how I can just find a nearby drop-off point using the locator and track my recycling status through the website. 
                                The transparency and ease of use make recycling so much more convenient!
                            </p>
                            <h4>— Raymond Lee</h4>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <img src="User-Homepage-Sec5-Profile3.png">
                    <div class="card-content">
                        <span>
                            <i class="fa-solid fa-quote-left"></i>
                        </span>
                        <div class="card-details">
                            <p>
                                The reward system is a great initiative! 
                                It really motivates people to recycle their e-waste properly instead of just throwing it away. 
                                Highly recommended!
                            </p>
                            <h4>— Nur Aisyah</h4>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script type="text/javascript">
        (function (window, document) {
            const markers = document.querySelectorAll('mark');

            function isInMiddleOfScreen(el) {
                const rect = el.getBoundingClientRect();
                const elMiddle = rect.top + rect.height / 1.5;
                const screenMiddle = window.innerHeight / 1.5;

                return Math.abs(elMiddle - screenMiddle) < 50;
            }

            function checkHighlightTrigger() {
                markers.forEach(mark => {
                    if (isInMiddleOfScreen(mark) && mark.style.animationPlayState !== 'running') {
                        mark.style.animationPlayState = 'running';
                    }
                });
            }

            window.addEventListener('scroll', checkHighlightTrigger);
            window.addEventListener('load', checkHighlightTrigger); 
        })(window, document);
        localStorage.setItem('activeTabIndex', 0);
    </script>

    <div class="section6">
        <h1>Recycle Your E-Waste with Ease!</h1>
        <br>
        <p>
            Seeing all the great reviews? It's time for you to experience hassle-free e-waste recycling too! <br>
            Choose your preferred method below and start making a difference today.
        </p>
        <br><br>
        <div class="card-container">
            <div class="card-box">
                <a onclick="window.location.href='User-Pickup Scheduling.php'" class="card-link">
                    <img src="User-Homepage-Sec6-Pickup.png" class="card-image">
                    <p class="card-badge pickup">Schedule a Pickup</p>
                    <h2 class="card-title">
                        Too busy to drop off your items? Let us come to you! 
                        Book a convenient pickup and our team will handle the rest.
                    </h2>
                    <button class="card-button material-symbols-rounded">arrow_forward</button>
                <a>
            </div>   

            <div class="card-box">
                <a onclick="window.location.href='User-Drop-off Points.php'" class="card-link">
                    <img src="User-Homepage-Sec6-Dropoff.png" class="card-image">
                    <p class="card-badge dropoff">Find a Drop-Off Point</p>
                    <h2 class="card-title">
                        Prefer to drop off your e-waste? 
                        Locate the nearest collection center and dispose of your e-waste responsibly.
                    </h2>
                    <button class="card-button material-symbols-rounded">arrow_forward</button>
                <a>
            </div>
        </div> 
    </div>

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
</html>