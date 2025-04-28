<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
    require 'phpmailer/src/Exception.php';

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    session_start(); 

    if (isset($_GET['email']) && $_SERVER["REQUEST_METHOD"] !== "POST") {
        $VerifyEmail = $_GET['email'];
        $userIDQuery = mysqli_query($conn, "SELECT user_id, username FROM user WHERE email = '$VerifyEmail'");
        $userIDResult = mysqli_fetch_assoc($userIDQuery);

        if ($userIDResult) {
            $userID = $userIDResult['user_id'];
            $userName = $userIDResult['username'];

            // Check if the user already has an OTP that is not expired
            $otpCheckQuery = mysqli_query($conn, "SELECT veri_code, created_at, expired_at FROM email_verification WHERE user_id = '$userID'");
            $otpResult = mysqli_fetch_assoc($otpCheckQuery);

            if ($otpResult){
                $code = random_int(100000, 999999);
                $updateVerificationQuery = mysqli_query($conn, "UPDATE email_verification 
                    SET veri_code = '$code', created_at = NOW(), expired_at = NOW() + INTERVAL 10 MINUTE WHERE user_id='$userID'");
            } else {
                $code = random_int(100000, 999999);
                mysqli_query($conn, "INSERT INTO email_verification (email, veri_code, created_at, expired_at, user_id) 
                                    VALUES ('$VerifyEmail', '$code', NOW(), NOW() + INTERVAL 10 MINUTE, '$userID')
                                    ON DUPLICATE KEY UPDATE veri_code = '$code', created_at = NOW(), expired_at = NOW() + INTERVAL 10 MINUTE");
            }

            $_SESSION['otp_expire'] = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            echo "<script>
                localStorage.removeItem('otpStartTime');
                localStorage.setItem('otp', '$code');
                localStorage.setItem('otpExpireTime', '" . strtotime($_SESSION['otp_expire']) . "000');
              </script>";

            $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'greencoinreward@gmail.com';
                    $mail->Password = 'oavq fmtf lzfn nxfn';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('greencoinreward@gmail.com', 'Green Coin Website');
                    $mail->addAddress($VerifyEmail, $userName);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Green Coin Verification Code';
                    $mail->Body = '
                    <html>
                        <body style="color:rgb(61, 61, 61); background:#DCE4D3; width:100%; justify-content:center; align-items:center; display:flex; line-height:1.6;">
                            <div style="padding: 20px; margin:auto;">
                                <div style="text-align:center; background-color:white; width: 550px; justify-content:center; align-items:center;">
                                    <div style="background-color:#78a24c; width:100%; height:100px; padding:0px;">
                                        <h1 style="font-size:30px; text-align:center; padding:25px;">
                                            <mark style="background-color:#78a24c; color:white;">GREEN</mark><mark style="background-color:#78a24c; color: #ffd740;">  COIN</mark>
                                        </h1>
                                    </div>
                                    <div style="padding:20px;">
                                        <h1 style="font-size:25px; text-align:center; color:black;">Your Verification Code</h1>

                                        <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Dear '. $userName.',</p>
                                        <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Thank you for signing up with Green Coin!</p>
                                        <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Enter this code in the next 10 minutes to sign up:<br></p>
                                        <p style="text-align:center; font-family: Arial, Helvetica, sans-serif; padding:5px; font-size: 55px; font-weight:bold; color:black;">'.$code.'<br></p>
                                        <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">If you didn\'t request this code, you can safely ignore this email. Someone else might have typed your email address by mistake.<br></p>
                                    </div>   
                                </div>  
                                <p style="color:rgba(61, 61, 61, 0.56); text-align:center; padding:5px; font-size:12px;">Copyright © 2025 Green Coin. All Rights Reserved.</p> 
                            </div>
                        </body>
                    </html>
                    ';

                    $mail->send();
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
        }
    }else if ($_SERVER["REQUEST_METHOD"] === "POST"){
        $VerifyEmail = $_GET['email'];
        $userIDQuery = mysqli_query($conn, "SELECT user_id, username FROM user WHERE email = '$VerifyEmail'");
        $userIDResult = mysqli_fetch_assoc($userIDQuery);
        $userID = $userIDResult['user_id'];
        $userName = $userIDResult['username'];

        if (isset($_POST['verify'])){
            $getCodeQuery = mysqli_query($conn,"SELECT veri_code, expired_at FROM email_verification WHERE user_id='$userID'");
            $getCodeResult = mysqli_fetch_assoc($getCodeQuery);
            $getCode = $getCodeResult['veri_code'];
            $expired = $getCodeResult['expired_at'];

            $codeInput = $_POST['num1'] . $_POST['num2'] . $_POST['num3'] . $_POST['num4'] . $_POST['num5'] . $_POST['num6'];
            if (strtotime(date("Y-m-d H:i:s")) > strtotime($expired)){
                echo '<div class="overlay">
                        <div class="popup-error">
                            <div class="error-message-container">
                                <img src="User-SignUp-ErrorIcon.png" width="110">
                                <p class="popup-error-message">The code you entered has expired.</p>
                                <button class="close" name="direct-login" value="0" type="button">OK</button>
                            </div>
                        </div>
                    </div>';
            }else{
                if ($codeInput == $getCode){
                    $updateExpired = mysqli_query($conn, "UPDATE email_verification SET expired_at = NOW() WHERE user_id = '$userID'");
                    $updateStatus = mysqli_query($conn,"UPDATE user SET status = 'Verified' WHERE user_id = '$userID'");
                    echo '<div class="overlay">
                        <div class="popup-error">
                            <div class="error-message-container">
                                <img src="User-SignUp-Verification-SuccessfulIcon.png" width="110">
                                <p class="popup-error-message">Your email has been verified successfully.</p>
                                <button class="close" name="direct-login" type="button" value="1">OK</button>
                            </div>
                        </div>
                    </div>';
                }else{
                    echo '<div class="overlay">
                        <div class="popup-error">
                            <div class="error-message-container">
                                <img src="User-SignUp-ErrorIcon.png" width="110">
                                <p class="popup-error-message">The code you entered is incorrect.</p>
                                <button class="close" name="direct-login" value="0" type="button">OK</button>
                            </div>
                        </div>
                    </div>';
                }
            }
        }else if (isset($_POST['resend'])){
            $code = random_int(100000, 999999);
            $updateVerificationQuery = mysqli_query($conn, "UPDATE email_verification 
                SET veri_code = '$code', created_at = NOW(), expired_at = NOW() + INTERVAL 10 MINUTE WHERE user_id='$userID'");

            $_SESSION['otp_expire'] = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            echo "<script>
                localStorage.removeItem('otpStartTime');
                localStorage.setItem('otp', '$code');
                localStorage.setItem('otpExpireTime', '" . strtotime($_SESSION['otp_expire']) . "000');
              </script>";
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'greencoinreward@gmail.com';
                $mail->Password = 'oavq fmtf lzfn nxfn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                // Recipients
                $mail->setFrom('greencoinreward@gmail.com', 'Green Coin Website');
                $mail->addAddress($VerifyEmail, $userName);
    
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Green Coin Verification Code';
                $mail->Body = '
                <html>
                    <body style="color:rgb(61, 61, 61); background:#DCE4D3; width:100%; justify-content:center; align-items:center; display:flex; line-height:1.6;">
                        <div style="padding: 20px; margin:auto;">
                            <div style="text-align:center; background-color:white; width: 550px; justify-content:center; align-items:center;">
                                <div style="background-color:#78a24c; width:100%; height:100px; padding:0px;">
                                    <h1 style="font-size:30px; text-align:center; padding:25px;">
                                        <mark style="background-color:#78a24c; color:white;">GREEN</mark><mark style="background-color:#78a24c; color: #ffd740;">  COIN</mark>
                                    </h1>
                                </div>
                                <div style="padding:20px;">
                                    <h1 style="font-size:25px; text-align:center; color:black;">Your Verification Code</h1>

                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Dear '. $userName.',</p>
                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Thank you for signing up with Green Coin!</p>
                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Enter this code in the next 10 minutes to sign up:<br></p>
                                    <p style="text-align:center; font-family: Arial, Helvetica, sans-serif; padding:5px; font-size: 55px; font-weight:bold; color:black;">'.$code.'<br></p>
                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">If you didn\'t request this code, you can safely ignore this email. Someone else might have typed your email address by mistake.<br></p>
                                </div>   
                            </div>  
                            <p style="color:rgba(61, 61, 61, 0.56); text-align:center; padding:5px; font-size:12px;">Copyright © 2025 Green Coin. All Rights Reserved.</p> 
                        </div>
                    </body>
                </html>
                ';
    
                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }
    }else{
        echo '<script>window.location.href="User-SignUp.php";</script>';
    }


    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - Sign Up - Green Coin</title>
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
            font-family: "Playpen Sans", cursive;
        }
        @media only screen and (min-width: 320px) and (max-width: 767px) {
            .veri-signup{
                position: relative;
                background-color: white;
                width: 550px;
                left:40px;
                top:100px;
                padding: 20px 15px 0px 40px;
            }
        }
        @media only screen and (min-width: 768px){
            body{
                background-image: url("User-SignUp-Wallpaper.png");
                background-repeat: no-repeat;
                background-size: 90% 90%;
                background-attachment: fixed;
                background-position:100px 30px;
            }
            
            .veri-signup{
                position: relative;
                background-color: white;
                width: 550px;
                left:150px;
                top:150px;
                padding: 20px 15px 0px 40px;
            }
        
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

        .email-background{
            background: #dff8d1;
            top:0;
            left:0;
            justify-content:center;
            align-items:center;
            padding:20px;
            padding-bottom:80px;
            display:flex;
            flex-direction:column;
        }

        .email-container{
            text-align:center;
            padding:20px;
            width: 500px;
            background-color:white;
            display:relative;
        }

        .email-heading{
            font-size:30px;
            text-align:center;
        }

        .email-content{
            text-align:left;
            padding:5px;
        }

        .verification-code{
            text-align:center;
            font-family: "Open Sans", sans-serif;
            padding:5px;
            font-size: 30px;
            font-weight:bold;
        }
        
        .website-name{
            text-align:center;
            font-size:45px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.2); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50; 
        }

        .popup-error {
            width: 350px;
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

        h1{
            font-size: 40px;
            padding-bottom: 50px;
            padding-left:80px;
        }

        h2{
            font-size: 18px;
            font-weight: normal;
            padding-bottom: 20px;
            font-family: "Open Sans", sans-serif;
        }

        p{
            font-size: 18px;
            padding-bottom: 40px;
            font-family: "Open Sans", sans-serif;
        }

        span{
            font-weight: bold;
            font-family: "Open Sans", sans-serif;
        }

        input[type="tel"]{
            font-family: "Open Sans", sans-serif;
            font-size: 30px;
            width: 60px;
            height: 80px;
            border-radius: 5px;
            margin: 10px;
            border: 1px solid grey;
            margin-bottom: 70px;
            text-align: center;
            outline:none;
        }

        .veri-btn{
            width:93%;
            border-radius: 20px;
            padding:15px;
            font-size: 20px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            margin-bottom: 20px;
        }

        hr{
            margin-left: -20px;
            margin-bottom: 20px;
            margin-right: 15px;
        }

        .resend-btn{
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            color:rgb(40, 86, 15);
            border: none;
            cursor: pointer;
            background-color: transparent;
        }

        .resend-btn:hover, .veri-btn:hover{
            opacity: 0.8;
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
    <div class="veri-signup">
        <h1>Verify Your Email</h1>
        <h2>We have send a 6-digit code to <strong style=" font-family: 'Open Sans', sans-serif;"><?php echo $VerifyEmail; ?></strong>.</h2>
        <p>Please check your inbox and enter the verification code below to verify your email address.
            The code will expire in <span id="timer">10:00</span>.
        </p>
        <form method="post" action="">
            <div class="otp-container">
                <input type="tel" maxlength="1" id="num" name="num1" class="otp-input">
                <input type="tel" maxlength="1" id="num" name="num2" class="otp-input">
                <input type="tel" maxlength="1" id="num" name="num3" class="otp-input">
                <input type="tel" maxlength="1" id="num" name="num4" class="otp-input">
                <input type="tel" maxlength="1" id="num" name="num5" class="otp-input">
                <input type="tel" maxlength="1" id="num" name="num6" class="otp-input">
            </div>
            <button class="veri-btn" name="verify" type="submit">Verify</button>
            <hr>
            <p style="font-size: 16px;">Don't receive code? <button class="resend-btn" name="resend" type="submit">Resend</button></p>
        </form>
    </div>

    <script>

        function handlePopupClose() {
            let popup = document.querySelector(".overlay");
            if (popup) {
                let closeBtn = popup.querySelector(".close");
                closeBtn.addEventListener("click", function () {
                    if (document.getElementsByName('direct-login')[0].value === "1"){
                        window.location.href = "User-Login.php";
                    }
                    popup.remove(); 
                });
                
            }
        }

        setInterval(() => {
            let popup = document.querySelector(".overlay");
            if (popup) {
                console.log("Popup detected!");
                handlePopupClose(); 
                clearInterval(this);
            }
        }, 500);

        document.addEventListener("DOMContentLoaded", function () {
            let timerElement = document.querySelector("#timer");

            let startTime = localStorage.getItem("otpStartTime");

            let countdownDuration = 10 * 60 * 1000; 

            if (!startTime) {
                startTime = Date.now();
                localStorage.setItem("otpStartTime", startTime);
            } else {
                startTime = parseInt(startTime);
            }

            function updateTimer() {
                let elapsedTime = Date.now() - startTime;
                let remaining = countdownDuration - elapsedTime;

                if (remaining <= 0) {
                    clearInterval(countdown);
                    localStorage.removeItem("otpStartTime");
                    timerElement.innerHTML = "00:00";

                    document.body.insertAdjacentHTML("beforeend", `
                        <div class="overlay">
                            <div class="popup-error">
                                <div class="error-message-container">
                                    <img src="User-SignUp-ErrorIcon.png" width="110">
                                    <p class="popup-error-message">Your code has expired. A new one has been sent to your email.</p>
                                    <form id="resendForm" method="post" action="">
                                        <button class="close" name="resend" value="1" type="submit">OK</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    `);
                   
                    return;
                }

                let minutes = Math.floor(remaining / 60000);
                let seconds = Math.floor((remaining % 60000) / 1000);

                timerElement.innerHTML = `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
            }

            updateTimer();
            let countdown = setInterval(updateTimer, 1000);
        });
  


        var num1 = document.getElementsByName("num1")[0];
        var num2 = document.getElementsByName("num2")[0];
        var num3 = document.getElementsByName("num3")[0];
        var num4 = document.getElementsByName("num4")[0];
        var num5 = document.getElementsByName("num5")[0];
        var num6 = document.getElementsByName("num6")[0];

        
        num1.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }

        num2.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }

        num3.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }

        num4.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }
        
        num5.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }
        
        num6.onkeypress = function(event){
            if (!/[0-9]/.test(event.key)){
                event.preventDefault();
            }
        }

        
        num1.addEventListener('keydown', ({key}) =>{
            if (event.key === "ArrowRight"){
                num2.focus();
            }else if ((key === "Backspace")){
                num1.value="";
            }else{
                num1.onkeyup = function(event){
                    if (num1.value.trim() == ""){
                        event.preventDefault();
                    }else if (/[0-9]/.test(event.key)){
                        num2.focus();
                        num2.value = key;
                    }
                }
            }
        });

        num2.addEventListener('keydown',({key})=> {
            if (key === "Backspace"){
                if (num2.value.trim() == ""){
                    num1.value = "";
                    num1.focus();
                }else{
                    event.preventDefault();
                    num2.value = "";
                    num1.focus();
                }
                
            }else if (event.key === "ArrowRight"){
                num3.focus();
            }else if (event.key === "ArrowLeft"){
                num1.focus();
            }else{
                num2.onkeyup = function(event){
                    if (num2.value.trim() == ""){
                        event.preventDefault();
                    }else if (/[0-9]/.test(event.key)){
                        num3.focus();
                        num3.value = key;
                    }
                }
            }
        });
        
        num3.addEventListener('keydown',({key})=> {
            if (key === "Backspace"){
                if (num3.value.trim() == ""){
                    num2.value = "";
                    num2.focus();
                }else{
                    event.preventDefault();
                    num3.value = "";
                    num2.focus();
                }
            }else if (event.key === "ArrowRight"){
                num4.focus();
            }else if (event.key === "ArrowLeft"){
                num2.focus();
            }else{
                num3.onkeyup = function(event){
                    if (num3.value.trim() == ""){
                        event.preventDefault();
                    }else if (/[0-9]/.test(event.key)){
                        num4.focus();
                        num4.value = key;
                    }
                }
            }
        });

        num4.addEventListener('keydown',({key})=> {
            if (key === "Backspace"){
                if (num4.value.trim() == ""){
                    num3.value = "";
                    num3.focus();
                }else{
                    event.preventDefault();
                    num4.value = "";
                    num3.focus();
                }
            }else if (event.key === "ArrowRight"){
                num5.focus();
            }else if (event.key === "ArrowLeft"){
                num3.focus();
            }else{
                num4.onkeyup = function(event){
                    if (num4.value.trim() == ""){
                        event.preventDefault();
                    }else if (/[0-9]/.test(event.key)){
                        num5.focus();
                        num5.value = key;
                    }
                }
            }
        });

        num5.addEventListener('keydown',({key})=> {
            if (key === "Backspace"){
                if (num5.value.trim() == ""){
                    num4.value = "";
                    num4.focus();
                }else{
                    event.preventDefault();
                    num5.value = "";
                    num4.focus();
                }
            }else if (event.key === "ArrowRight"){
                num6.focus();
            }else if (event.key === "ArrowLeft"){
                num4.focus();
            }else{
                num5.onkeyup = function(event){
                    if (num5.value.trim() == ""){
                        event.preventDefault();
                    }else if (/[0-9]/.test(event.key)){
                        num6.focus();
                        num6.value = key;
                    }
                }
            }
        });

        num6.addEventListener('keydown',({key})=> {
            if (key === "Backspace"){
                if (num6.value.trim() == ""){
                    num5.value = "";
                    num5.focus();
                }else{
                    event.preventDefault();
                    num6.value = "";
                    num5.focus();
                }
            }else if (event.key === "ArrowLeft"){
                num5.focus();
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            const inputs = document.querySelectorAll(".otp-input");

            inputs.forEach((input, index) => {
                input.addEventListener("paste", (event) => {
                    event.preventDefault(); // Prevent default paste behavior
                    let pastedData = (event.clipboardData || window.clipboardData).getData("text").trim();
                    
                    if (pastedData.length === inputs.length && /^\d+$/.test(pastedData)) {
                        // Fill each input box with corresponding digit
                        inputs.forEach((box, i) => (box.value = pastedData[i]));
                        inputs[inputs.length - 1].focus(); // Move focus to last input
                    }
                });

                input.addEventListener("input", () => {
                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                input.addEventListener("keydown", (event) => {
                    if (event.key === "Backspace" && input.value === "" && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });
        });

    </script>
</body>
</html>