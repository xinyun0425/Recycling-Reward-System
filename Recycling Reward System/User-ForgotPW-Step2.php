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
    if(isset($_GET['email'])){
        $emailEntered = $_GET['email'];
        $emailExistQuery = mysqli_query($conn, "SELECT user_id, username, count(*) AS count FROM user WHERE email = '$emailEntered' AND NOT status = 'Pending Verification'");
        $emailExistResult = mysqli_fetch_assoc($emailExistQuery);
        $emailExist = $emailExistResult['count'];
        $userName = $emailExistResult['username'];
        $userID = $emailExistResult['user_id'];

        $otpCheckQuery = mysqli_query($conn, "SELECT veri_code, created_at, expired_at FROM forgot_pw WHERE user_id = '$userID'");
        $otpResult = mysqli_fetch_assoc($otpCheckQuery);

        if ($otpResult){
            $code = random_int(100000, 999999);
            $updateVerificationQuery = mysqli_query($conn, "UPDATE forgot_pw 
                SET veri_code = '$code', created_at = NOW(), expired_at = NOW() + INTERVAL 10 MINUTE WHERE user_id='$userID'");
        } else {
            $code = random_int(100000, 999999);
            mysqli_query($conn, "INSERT INTO forgot_pw (veri_code, created_at, expired_at, user_id) 
                                VALUES ('$code', NOW(), NOW() + INTERVAL 10 MINUTE, '$userID')
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
            $mail->addAddress($emailEntered, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Green Coin - Password Reset Verification Code';
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
                                <h1 style="font-size:25px; text-align:center; color:black;">Password Reset Verification Code</h1>

                                <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Dear '. $userName.',</p>
                                <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">We received a request to reset your password for your Green Coin account.</p>
                                <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">To proceed, please use the following verification code:<br></p>
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
    }else if ($_SERVER["REQUEST_METHOD"] === "POST"){
        $emailEntered = $_GET['email'];
        $emailExistQuery = mysqli_query($conn, "SELECT user_id, username FROM user WHERE email = '$emailEntered' AND NOT status = 'Pending Verification'");
        $emailExistResult = mysqli_fetch_assoc($emailExistQuery);
        $userName = $emailExistResult['username'];
        $userID = $emailExistResult['user_id'];

        if (isset($_POST['resend'])){
            $code = random_int(100000, 999999);
            $updateVerificationQuery = mysqli_query($conn, "UPDATE forgot_pw 
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
                $mail->addAddress($emailEntered, $userName);
    
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Green Coin - Password Reset Verification Code';
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
                                    <h1 style="font-size:25px; text-align:center; color:black;">Password Reset Verification Code</h1>

                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">Dear '. $userName.',</p>
                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">We received a request to reset your password for your Green Coin account.</p>
                                    <p style="color:rgb(61, 61, 61); text-align:left; padding:5px;">To proceed, please use the following verification code:<br></p>
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
        echo '<script>window.location.href="User-ForgotPW-Step1.php";</script>';
    }

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Green Coin</title>
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
        }
        @media only screen and (min-width: 320px) and (max-width: 767px) {
           
            
        }
        @media only screen and (min-width: 768px){
           

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

        .container {
            margin: 20px auto;
        }

        .progress-container {
            text-align: center;
            width: 600px;
            margin: 90px auto 30px auto;
        }


        #progressbar {
            list-style-type: none;
            display: flex;
            justify-content: space-between;
            color: lightgrey;

        }

        #progressbar li {
            flex: 1;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            position: relative;
        }

        #progressbar li.active {
            color: #78A24C;
            transition-delay: 1s;
            transition: 2s ease;
        }

        .progress {
            height: 20px;
            border-radius: 25px;
            overflow: hidden;
        }

        #progressbar li strong{
            font-family: "Playpen Sans", cursive;
        }

        .progress-bar {
            background-color: #78A24C;
            width: 0;
            height: 100%;
            transition: width 0.4s ease-in-out;
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


        .step-container fieldset {
            border:none;
            background: white;
            border-radius: 5px;
            box-sizing: border-box;
            width:70%;
            margin: 50px auto;
            padding-bottom: 20px;
            position: relative;
            display: none;
        }

        .icon{
            display:block;
            margin:20px auto 20px auto;
        }

        .step-container fieldset:first-of-type {
            display: block;
        }

        .step-container h2 {
            font-family: 'Playpen Sans', cursive;
            color: black;
            margin-top: 10px;
            text-align: center;
            font-size:35px;
            
        }

        .input-forgot-pw{
            width: 90%;
            margin-left:360px;
            margin-top:50px; 
        }

        .input-forgot-pw label{
            font-family: 'Open Sans', sans-serif;
            font-size:20px;
            
        }

        .input-forgot-pw input[type='text']{
            padding: 5px 0px;
            width: 37%;
            margin-top: 20px;
            border:none;
            border-bottom:1px solid black;
            outline:none;
            font-family: 'Open Sans', sans-serif;
            font-size:19px;
        }

        .proceed-to-emailcode, .proceed-to-resetpw, .proceed-to-successfulmessage{
            width:33.5%;
            margin-left:360px;
            margin-top:20px;
            background-color: #78A24C;
            color:white;
            border-radius: 25px;
            font-size: 20px;
            font-family: "Playpen Sans", cursive;

        }

        .back-to-forgotpw {
            color:rgb(82, 144, 47);
            font-size:14px;
            font-weight:bold;
        }

        .next-step,
        .previous-step {
            border: 0 none;
            border-radius: 5px;
            cursor: pointer;
            padding: 10px 5px;
            font-family: "Playpen Sans", cursive;

        }

        .next-step:hover,
        .next-step:focus {
            opacity: 0.8;
            cursor: pointer;
        }

        .previous-step:hover,
        .previous-step:focus {
            opacity: 0.8;
            cursor: pointer;
        }

        .text {
            color:#78A24C;
            font-weight: normal;
        }

        .otp-container{
            width: 47%;
            /* margin-left:360px; */
            /* margin-top:30px;  */
            /* margin-bottom: 40px; */
            margin: 30px auto 10px auto;
        
        }

        input[type="tel"]{
            font-family: "Open Sans", sans-serif;
            font-size: 27px;
            width: 60px;
            height: 75px;
            border-radius: 5px;
            margin: 8px;
            border: 1px solid grey;
            text-align: center;
            outline:none;
        }

        .veri-btn{
            font-size:20px;
            width:40%;
            border-radius: 5px;
            padding:10px 5px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            font-family: "Playpen Sans", cursive;

        }

        .resend-btn{
            font-size: 14px;
            color:rgb(40, 86, 15);
            border: none;
            cursor: pointer;
            background-color: transparent;
            margin-left:-365px;
            font-family: "Playpen Sans", cursive;
            text-align:left;
            margin-bottom:30px;
        }

        .resend-btn:hover, .veri-btn:hover{
            opacity: 0.8;
        }

        .verify-successful{
            display:none;
            margin-top:70px;
        }

        label{
            font-size:20px;
            font-family:'Open Sans', sans-serif;
        }

        .fa{
            background-color: #ccc;
            width:16px;
            height:16px;
            color: #fff;
            border-radius:50%;
            padding: 6px 6px 5px;
            margin-top:10px;
        }

        .fa::after{
            content:'';
            background-color:#ccc;
            height:5px;
            width: 150px;
            display:block;
            position:absolute;
            right:65px;
            top:42px;
            z-index:-1;
        }

        .line-bar-1{
            background-color: #78a24c;
        }

        .line-bar-1::after{
            width:0;
            height:0;
        }

        .green-after::after {
            background-color: #78a42c !important;
            transition: 2s ease;
        }

        .green-icon{
            transition-delay: 2s;
            background-color: #78a24c;
            transition: 3s ease;
        }

        span{
            font-weight: bold;
            font-family: "Open Sans", sans-serif;
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
    <div class="container">
        <div class="progress-container">
            <ul id="progressbar">
                <li class="active" 
                    id="step1">
                    <strong>Send Email</strong><br>
                    <i class="line-bar-1 fa fa-check"></i>
                </li>
                <li id="step2" class="active">
                    <strong>Verify Code</strong><br>
                    <i class="green-after green-icon line-bar-2 fa fa-refresh"></i>
                </li>
                <li id="step3">
                      <strong>Reset Password</strong><br>
                      <i class="line-bar-3 fa fa-times" ></i>
                  </li>
                <li id="step4">
                    <strong>Success</strong><br>
                    <i class="line-bar-4 fa fa-times"></i>
                  </li>
            </ul>
        </div>
        <div class="step-container">
            <fieldset style="margin-top:10px;">
                <div class="input-code">
                    <img src="User-ForgotPW-Step2-Icon.svg" width="100" class="icon">
                    <h2>Check your email</h2>
                    <p style=" font-family: 'Open Sans', sans-serif; text-align:center; margin-top:30px;">We have send a 6-digit code to <strong style=" font-family: 'Open Sans', sans-serif;"><?php echo $emailEntered;?></strong>.</p>
                    <p style=" font-family: 'Open Sans', sans-serif; text-align:center; margin-top:10px;">The code will expire in <span id="timer">10:00</span>.</p>
                    <form id="otp-submit" method="post">
                        <center><div class="otp-container">
                            <input type="tel" maxlength="1" id="num" name="num1" class="otp-input">
                            <input type="tel" maxlength="1" id="num" name="num2" class="otp-input">
                            <input type="tel" maxlength="1" id="num" name="num3" class="otp-input">
                            <input type="tel" maxlength="1" id="num" name="num4" class="otp-input">
                            <input type="tel" maxlength="1" id="num" name="num5" class="otp-input">
                            <input type="tel" maxlength="1" id="num" name="num6" class="otp-input">
                            <br>
                        </div></center>
                        <center><div style="width:47%;">
                            <button class="resend-btn" name="resend" type="submit">Resend Code?</button>
                        </div></center>
                        <center><button name="verify" class="veri-btn" type="button">Verify</button><center>
                    </form>
                    <p style="font-size: 14px; text-align:center; margin-bottom:10px; margin-top:10px;">Need to change your email?<a href="User-ForgotPW-Step1.php" name="previous-step " class="previous-step back-to-forgotpw">Return</a></p>
                </div>
            </fieldset>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let timerElement = document.querySelector("#timer");

            let startTime = localStorage.getItem("otpStartTime");

            // let countdownDuration = 1 * 60 * 1000; 
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

            document.querySelector(".veri-btn").addEventListener("click", function(event) {
                event.preventDefault();
                const num1 = document.getElementsByName('num1')[0];
                const num2 = document.getElementsByName('num2')[0];
                const num3 = document.getElementsByName('num3')[0];
                const num4 = document.getElementsByName('num4')[0];
                const num5 = document.getElementsByName('num5')[0];
                const num6 = document.getElementsByName('num6')[0];

                const otpEntered = num1.value + num2.value + num3.value + num4.value + num5.value + num6.value;
                const otpGiven = "<?php echo $code;?>";
                const email = "<?php echo $emailEntered;?>";
                if (otpEntered == otpGiven){
                    document.querySelector(".veri-btn").innerText = "Successful!";                    
                    setTimeout(() => {
                        window.location.href = 'User-ForgotPW-Step3.php?email='+ email;
                    }, 1000);
                }else{
                    document.querySelector(".veri-btn").innerText = "Invalid Code!";
                    document.querySelector(".veri-btn").style.backgroundColor = '#f21000';
                    num1.style.border = '1px solid #f21000';
                    num2.style.border = '1px solid #f21000';
                    num3.style.border = '1px solid #f21000';
                    num4.style.border = '1px solid #f21000';
                    num5.style.border = '1px solid #f21000';
                    num6.style.border = '1px solid #f21000';

                    setTimeout(() => {
                        document.querySelector(".veri-btn").style.backgroundColor = '#78a42c';
                        document.querySelector(".veri-btn").innerText = "Verify";
                    }, 1000);
                }
                
            });

            
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

        document.getElementsByClassName("showHidePw")[0].addEventListener("click",function(event){
            const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];
            const PWInput = document.getElementsByName("forgotPW")[0];
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

        document.getElementsByClassName("showHideCPw")[0].addEventListener("click",function(event){
            const showHidePWIcon = document.getElementsByClassName("showHideCPw")[0];
            const CPWInput = document.getElementsByName("forgotCPW")[0];
            const PWEM = document.getElementsByClassName("cpw-error-message")[0];

            if (CPWInput.type === "password"){
                showHidePWIcon.src = "User-ViewPasswordIcon.png";
                CPWInput.type = "text";
                CPWInput.style.marginBottom = "10px";
                PWEM.style.paddingTop = "0px"
            }else{
                showHidePWIcon.src = "User-HidePasswordIcon.png";
                CPWInput.type = "password";
            }
            
        });
        
    </script>
</body>
</html>