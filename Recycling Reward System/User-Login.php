<?php
    session_start();
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(isset($_POST['login'])){
            $email = $_POST['loginEmail'];
            $password = $_POST['loginPW'];

            $userExistQuery = mysqli_query($conn, "SELECT user_id, password, COUNT(*) AS countEmail FROM user WHERE email = '$email' AND status = 'Verified'"); 
            $userExistResult = mysqli_fetch_assoc($userExistQuery);

            if ($userExistResult['countEmail'] == 0){
                echo '<div class="overlay">
                        <div class="popup-error">
                            <div class="error-message-container">
                                <img src="User-SignUp-ErrorIcon.png" width="110">
                                <p class="popup-error-message">The email has not been registered yet.</p>
                                <button class="close" type="button">OK</button>
                            </div>
                        </div>
                    </div>';   
            }else{
                if ($password == $userExistResult['password']){
                    $_SESSION['user_id'] = $userExistResult['user_id'];
                    $user_id = $_SESSION['user_id'];  
                    $getExpiredPickupRequestQuery = mysqli_query($conn, "SELECT ts.date AS date, ts.time AS time, pr.pickup_request_id AS pickup_id FROM pickup_request pr INNER JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id WHERE status = 'Unread'");
                    while($getExpiredPickupRequestResult = mysqli_fetch_assoc($getExpiredPickupRequestQuery)){
                        $dateCheck = $getExpiredPickupRequestResult['date'];
                        $timeCheck = $getExpiredPickupRequestResult['time'];
                        $pickupIdCheck = $getExpiredPickupRequestResult['pickup_id'];

                        if (strtotime($dateCheck) <= strtotime(date("Y-m-d"))){
                            $updatePickupRequestStatus = mysqli_query($conn, "UPDATE pickup_request SET status = 'Expired' WHERE pickup_request_id = '$pickupIdCheck' AND status = 'Unread'");
                            $system_announcement = "Your scheduled pickup request on ".$dateCheck." has expired as it was not processed in time.  
                                                    You may submit a new request to schedule another pickup.  
                                                    Thank you for your understanding and continued support for a cleaner environment! 🌿♻️";
                            $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
                            ('$user_id', NOW(), 'Pickup Request Expired ⌛', '$system_announcement', 'unread')";
                            mysqli_query($conn, $requestSubmittedNotiQuery);

                            $admin_announcement = "A user\'s pickup request scheduled on ".$dateCheck." has expired without being processed.  
                                                    Please review the request history and follow up if needed.  
                                                    Timely response helps us maintain service quality and user trust.";
                            $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
                            ('$user_id', NOW(), '⚠️ Pickup Request Expired', '$admin_announcement', 'unread')";
                            mysqli_query($conn, $newRequestNotiQuery);
                        }
                    }

                    $getExpiredDropoffRequestQuery = mysqli_query($conn, "SELECT dropoff_date, dropoff_id FROM dropoff WHERE status = 'unread'");
                    while($getExpiredDropoffRequestResult = mysqli_fetch_assoc($getExpiredDropoffRequestQuery)){
                        $dateDropoffCheck = $getExpiredDropoffRequestResult['dropoff_date'];
                        $dropoffIdCheck = $getExpiredDropoffRequestResult['dropoff_id'];

                        if (strtotime($dateDropoffCheck) < strtotime(date("Y-m-d"))){
                            $updatePickupRequestStatus = mysqli_query($conn, "UPDATE dropoff SET status = 'Expired' WHERE dropoff_id = '$dropoffIdCheck' AND status = 'unread'");
                            $system_announcement = "Your drop-off request for ".$dateDropoffCheck." has expired as it was not completed.  
                                                    Feel free to submit a new request when you\'re ready to drop off your items.  
                                                    We appreciate your effort in recycling and keeping the planet clean! 🌍💚";
                            $requestSubmittedNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
                            ('$user_id', NOW(), 'Drop-Off Request Expired ⌛', '$system_announcement', 'unread')";
                            mysqli_query($conn, $requestSubmittedNotiQuery);

                            $admin_announcement = "A user\'s drop-off request scheduled for ".$dateDropoffCheck." has expired and was not fulfilled.  
                                                    Kindly verify the user history and mark the request accordingly.  
                                                    Thank you for ensuring the integrity of our recycling process!";
                            $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
                            ('$user_id', NOW(), '⚠️ Drop-Off Request Expired', '$admin_announcement', 'unread')";
                            mysqli_query($conn, $newRequestNotiQuery);
                        }
                    }
                    echo '<script>window.location.href="User-Homepage.php";</script>';
                }else{
                    echo '<div class="overlay">
                        <div class="popup-error">
                            <div class="error-message-container">
                                <img src="User-SignUp-ErrorIcon.png" width="110">
                                <p class="popup-error-message">Incorrect password. Please try again.</p>
                                <button class="close" type="button">OK</button>
                            </div>
                        </div>
                    </div>'; 
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Green Coin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playpen+Sans:wght@100..800&display=swap');
        *{
            margin: 0px;
            padding: 0px;
            font-family: "Open Sans", sans-serif;
            box-sizing:border-box;
        }

        @media only screen and (min-width: 320px) and (max-width: 767px) {
            .login-div{
                position: absolute;
                background-color: white;
                display: block;
                height:auto;
                width: 650px;
                top:100px;
                left:30px;
                padding: 20px 40px 0px 40px;
                
            }
        }

        @media only screen and (min-width: 768px){
            .login-div{
                position: relative;
                z-index: 1;
                background-color: white;
                display: block;
                height: 90vh;
                width: 635px;
                left: 10%;
                padding: 20px 40px 0px 40px;
                overflow: auto;
            }

            body{
                background-image: url("User-Login-Wallpaper.png");
                background-repeat: no-repeat;
                background-size: 90% 90%;
                background-attachment: fixed;
                background-position: 90px 50px;
            }
        }

        h1{
            font-size: 35px;
            padding-left:35px;
            padding-bottom: 20px;
            padding-top: 90px;
            font-family: "Playpen Sans", cursive;
        }

        h2{
            font-size: 18px;
            padding-left:35px;
            padding-bottom:60px;
            font-weight:normal;
            font-family: "Playpen Sans", cursive;
        }

        label{
            font-size: 14px;
            padding: 30px 4px 0px 15px;
            margin-bottom: 30px;
            font-weight:bold;
            font-family: "Open Sans", sans-serif;

        }

        input[type="text"]{
            padding: 8px 0px;
            width: 90%;
            margin-top: 10px;
            margin-bottom: 30px;
            margin-left: 15px;
            font-size: 16px;
            outline: none;
            border: none;
            border-bottom:1px solid black ;
            font-family: "Open Sans", sans-serif;

        }

        input[type="password"]{
            padding: 8px 0px;
            width: 90%;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 16px;
            margin-left: 15px;
            outline: none;
            border: none;
            border-bottom:1px solid black ;
            font-family: "Open Sans", sans-serif;

        }

        .login-btn{
            font-size: 20px;
            padding: 10px 10px;
            width: 84%;
            margin-top: 70px;
            margin-bottom: 0px;
            margin-left: 35px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            border-radius:20px;
            font-family: "Playpen Sans", cursive;
        }

        .login-btn:hover{
            opacity: 0.8;
        }

        .a-fpw{
            font-size: 15px;
            padding: 10px 15px;
            text-decoration:none;
            color:rgb(40, 86, 15);
            position:absolute;
            font-family: "Playpen Sans", cursive;
        }

        .a-fpw:hover{
            opacity: 0.8;
        }

        .direct-signup{
            font-size: 16px;
            text-align: center;
            padding: 20px;
            font-family: "Playpen Sans", cursive;
            width: 96%;
        }

        .direct-signup a{
            text-decoration:none;
            color:rgb(40, 86, 15);
            font-family: "Playpen Sans", cursive;
        }

        .direct-signup a:hover{
            opacity: 0.8;
        }
        
        .email-error-message, .pw-error-message{
            display: none;
            font-size: 14px;
            color:#f7656d;
            padding:10px 15px;
            text-align: left;
            font-family: "Open Sans", sans-serif;

        }

        .showHidePw{
            position: absolute;
            margin-top: 15px;
            margin-left: -40px;
            cursor: pointer;
        }

        .email,.password{
            position: relative;
            font-size: 16px;
            opacity: 0;
            transition: 300ms ease;
            font-family: "Open Sans", sans-serif;

        }
        
        .inputFields:focus::placeholder{
            opacity: 0;
            transition: 300ms ease;
        }

        .inputFields::placeholder{
            opacity: 1;
            transition: 300ms ease;
            font-weight:bold;
        }

        .overlay {
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

        .material-icons{
            font-size: 30px;
            color:#78A24C;
            cursor:default;
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
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            transition: all 0.3 ease;
        }

        .nav-links a.active, .nav-links a:hover {
            color: white !important;
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

        .noti-button__badge {
            position: absolute;
            top: 5px;
            right: 0px;
            width: 20px;
            height: 20px;
            background: red;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
        }

        #login{
            height: 690px;
            margin: auto;
            vertical-align: middle;
            place-content: space-evenly;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" alt="Logo" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a href="User-Homepage.php">Home</a></li>
            <li><a href="User-Pickup Scheduling.php">Pickup Scheduling</a></li>
            <li><a href="User-Drop-off Points.php">Drop-off Points</a></li>
            <li><a href="User-Rewards.php">Rewards</a></li>
            <li><a href="User-Review.php">Review</a></li>
            <li><a href="User-FAQ.php">FAQ</a></li>
        </ul>
        <div class="header-icons">
            <button class="noti-button" type="button" onclick="redirectToNotifications()">
                <span class="material-icons">mail</span>
            </button>
            <button class="profile-button" type="button">
                <span class="material-icons">account_circle</span>
            </button>
        </div>
    </header>
    <div class="login-div" id="login-div">
        <form id="login" method="post" >
            <div class="login-form">
                <h1>Welcome Back!</h1>
                <h2>Login to your account and keep making a difference.</h2>
                <div style="margin: 0px 20px;">
                    <label class="email">Email</label>
                    <br>
                    <input autocomplete="off" type="text" name="loginEmail" placeholder="Email"  class="inputFields">
                    <p class="email-error-message">Please enter your email address.</p>
                    <br>
                    <br>
                    <label class="password">Password</label>
                    <br>
                    <div style="position: relative;">
                        <input autocomplete="off" type="password" name="loginPW" placeholder="Password" class="inputFields">
                        <img src="User-HidePasswordIcon.png" width="25px" class="showHidePw">
                    </div>
                    <p class="pw-error-message">Please enter your password.</p>
                    <a href="User-ForgotPW-Step1.php" class="a-fpw">Forgot Password?</a>
                </div>
                <br>
                <button type="submit" name="login" class="login-btn">Login</button>
            </div>
        
            
            
            <p class="direct-signup">Don't have an account? <a href="User-SignUp.php">Sign up now</a></p>
        </form>
    </div>
    <script>
        document.getElementsByName("loginEmail")[0].value = sessionStorage.getItem("loginEmail") || "";
        if(document.getElementsByName("loginEmail")[0].value != ""){
            document.getElementsByClassName("email")[0].style.opacity = "1";
        }
        document.getElementsByName("loginPW")[0].value = sessionStorage.getItem("loginPassword") || "";
        if (document.getElementsByName("loginPW")[0].value != ""){
            document.getElementsByClassName("password")[0].style.opacity = "1";
        }

        document.querySelectorAll("li > a").forEach(link => {
            link.addEventListener("click", () => {
                sessionStorage.clear();
            });
        });

        document.querySelector(".a-fpw").addEventListener("click", () => {
            sessionStorage.clear();
        });

        document.querySelector(".direct-signup").addEventListener("click", () => {
            sessionStorage.clear();
        });


        document.getElementById("login").addEventListener("submit", function(event) {
            const emailInput = document.getElementsByName("loginEmail")[0];
            const emailEM = document.getElementsByClassName("email-error-message")[0];
            const PWInput = document.getElementsByName("loginPW")[0];
            const PWEM = document.getElementsByClassName("pw-error-message")[0];

            const emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/; 

            sessionStorage.setItem("loginEmail", emailInput.value);
            sessionStorage.setItem("loginPassword", PWInput.value);

            let hasError = false; 

            if (emailInput.value.trim() === "") {
                emailEM.textContent = "Please enter your email address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottom= "1px solid red";
                emailInput.style.marginBottom = "0px";
                hasError = true;
            }else if(!emailPattern.test(emailInput.value.trim())){
                emailEM.textContent = "Please enter a valid Gmail address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottom= "1px solid red";
                emailInput.style.marginBottom = "0px";
                hasError = true;
            } else {
                emailEM.style.display = "none";
                emailInput.style.borderBottom= "1px solid black";
            }

            if (PWInput.value.trim() === "") {
                PWEM.style.display = "block";
                PWInput.style.borderBottom= "1px solid red";
                PWEM.style.paddingTop = "0px"
                hasError = true;
            } else {
                PWEM.style.display = "none";
                PWInput.style.borderBottom= "1px solid black";
            }

            if (hasError) {
                event.preventDefault(); 
            }
        });

        function handlePopupClose() {
            let popup = document.querySelector(".overlay");
            if (popup) {
                let closeBtn = popup.querySelector(".close");
                let closeTopBtn = popup.querySelector(".popup-error-close");
                closeBtn.addEventListener("click", function () {
                    popup.remove(); 
                    // sessionStorage.setItem("fromLogin", true);        
                    window.location.href="User-Login.php";
                });
                closeTopBtn.addEventListener("click", function () {
                    popup.remove(); 
                    // sessionStorage.setItem("fromLogin", true);        
                    window.location.href="User-Login.php";
                });
            }
        }

        setInterval(() => {
            let popup = document.querySelector(".overlay");
            if (popup) {
                document.getElementsByName("loginEmail")[0].value = sessionStorage.getItem("loginEmail") || "";
                document.getElementsByClassName("email")[0].style.opacity = "1";
                document.getElementsByName("loginPW")[0].value = sessionStorage.getItem("loginPassword") || "";
                document.getElementsByClassName("password")[0].style.opacity = "1";

                popup.addEventListener("click", function(event){
                    if (event.target === popup ){
                        popup.remove(); 
                        // sessionStorage.setItem("fromLogin", true);        
                        window.location.href="User-Login.php";
                    }
                });
                handlePopupClose(); 
                clearInterval(this);
            }
        }, 500);
        

        const emailLabel = document.getElementsByClassName("email")[0];
        const emailInput = document.getElementsByName("loginEmail")[0];

        emailInput.onfocus = function(){
            emailLabel.style.opacity = "1";
        }

        const pwLabel = document.getElementsByClassName("password")[0];
        const pwInput = document.getElementsByName("loginPW")[0];

        pwInput.onfocus = function(){
            pwLabel.style.opacity = "1";
        }

        document.getElementsByClassName("showHidePw")[0].addEventListener("click",function(event){
            const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];
            const PWInput = document.getElementsByName("loginPW")[0];
            const PWEM = document.getElementsByClassName("pw-error-message")[0];

            if (PWInput.type === "password"){
                showHidePWIcon.src = "User-ViewPasswordIcon.png";
                PWInput.type = "text";
                PWInput.style.marginBottom = "10px";
                PWEM.style.paddingTop = "0px"
            }else{
                showHidePWIcon.src = "User-HidePasswordIcon.png";
                PWInput.type = "password";
            }
            
        });

        window.onclick = function(event){
            if (event.target != emailInput){
                if (emailInput.value == ""){
                    emailLabel.style.opacity = "0";
                }
            }

            if (event.target != pwInput){
                if (pwInput.value == ""){
                    pwLabel.style.opacity = "0";
                }
            }
        }
        
        window.onkeyup = function(event){
            if (event.target != emailInput){
                if (emailInput.value == ""){
                    emailLabel.style.opacity = "0";
                }
            }

            if (event.target != pwInput){
                if (pwInput.value == ""){
                    pwLabel.style.opacity = "0";
                }
            }
        }

        window.addEventListener('beforeunload', () => {
            sessionStorage.removeItem('login-username');
            sessionStorage.removeItem('login-password');
        });


    </script>
</body>
</html>