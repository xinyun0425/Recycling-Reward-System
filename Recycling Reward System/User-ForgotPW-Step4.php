<?php
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }
    if(isset($_POST['send-email'])){
        $emailEntered = $_POST['forgotemail'];
        $emailExistQuery = mysqli_query($conn, "SELECT count(*) AS count FROM user WHERE email = '$emailEntered' AND NOT status = 'Pending Verification'");
        $emailExist = mysqli_fetch_assoc($emailExistQuery)['count'];
        echo json_encode(["exists" => $emailExist > 0]); 
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
        
        #progressbar li strong{
            font-family: "Playpen Sans", cursive;
        }

        .progress {
            height: 20px;
            border-radius: 25px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #78A24C;
            width: 0;
            height: 100%;
            transition: width 0.4s ease-in-out;
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

        /* .pb-icon{
            display:block;
            margin:20px auto 10px auto;
            background-color: grey;
            border-radius:50%;
            padding:9px;
        } */

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
            color:rgb(40, 86, 15);
            background-color: white;
            font-size:14px;
            font-family: "Playpen Sans", cursive;
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

        .finish {
            text-align: center;
        }

        .otp-container{
            width: 90%;
            margin-left:300px;
            margin-top:30px; 
            margin-bottom: 40px;
        }

        input[type="tel"]{
            font-family: "Open Sans", sans-serif;
            font-size: 27px;
            width: 60px;
            height: 75px;
            border-radius: 5px;
            margin: 7px;
            border: 1px solid grey;
            text-align: center;
        }

        .veri-btn{
            font-size:20px;
            width:45%;
            border-radius: 5px;
            padding:10px 5px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            margin-left: 299px;
        }

        .resend-btn{
            font-size: 14px;
            color:rgb(40, 86, 15);
            border: none;
            cursor: pointer;
            background-color: transparent;
            margin-left:7px;
        }

        .resend-btn:hover, .veri-btn:hover{
            opacity: 0.8;
        }

        .verify-successful{
            display:none;
            margin-top:70px;
        }

        input[type="password"]{
            padding: 5px 0px;
            width: 40%;
            margin-top: 20px;
            border:none;
            border-bottom:1px solid black;
            outline:none;
            font-family: 'Open Sans', sans-serif;
            font-size:16px;
            margin-bottom:10px;
        }

        input[type="text"]{
            font-family: "Open Sans", sans-serif;
            padding: 5px 0px;
            width: 40%;
            margin-top: 20px;
            margin-bottom: 30px;
            font-size: 16px;
            border: none;
            border-bottom: 1px solid black;
            outline: none;
        }

        .forgot-email{
            font-family: "Open Sans", sans-serif;
            padding: 5px 0px;
            width: 35%;
            margin-top: 20px;
            margin-bottom: 30px;
            font-size: 16px;
            border: none;
            border-bottom: 1px solid black;
            outline: none;
        }

        .showHidePw, .showHideCPw{
            position: absolute;
            margin-top: 17px;
            margin-left: -35px;
            cursor: pointer;
        }

        .reset{
            width:36.5%;
            margin-left:350px;
            margin-top:20px;
            background-color: #78A24C;
            color:white;
            border-radius: 5px;
            font-size: 20px;
            padding:10px 5px;
            border:none;
        }

        .reset:hover{
            opacity:0.8; 
        }

        .reset-pw{
            width: 90%;
            margin-left:350px;
            margin-top:50px; 
        }

        label{
            font-size:20px;
            font-family:'Open Sans', sans-serif;
        }

        .direct-login{
            width:35%;
            border-radius: 5px;
            padding:10px 5px;
            font-size: 16px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            margin-top:70px;
            margin-bottom: 20px;
            font-family: "Playpen Sans", cursive;

        }

        .direct-login:hover{
            opacity:0.8;
        }
        
        .fa{
            background-color: #ccc;
            width:16px;
            height:16px;
            color: #fff;
            border-radius:50%;
            padding:6px 6px 5px;
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

        .cpw-error-message{
            font-family: "Open Sans", sans-serif;
            display: none;
            font-size: 14px;
            color: #f7656d;
            padding:10px 0px;
            text-align: left;
        }

        .pw-error-message{
            font-family: "Open Sans", sans-serif;
            display: none;
            font-size: 14px;
            color: #f7656d;
            padding:10px 0px;
            text-align: left;
            width: 35%;
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
                    <i class="green-after green-icon line-bar-2 fa fa-check"></i>
                </li>
                <li id="step3" class="active">
                      <strong>Reset Password</strong><br>
                      <i class="green-after green-icon line-bar-3 fa fa-check" ></i>
                </li>
                <li id="step4" class="active">
                    <strong>Success</strong><br>
                    <i class="green-after green-icon line-bar-4 fa fa-check"></i>
                </li>
            </ul>
        </div>
        <div class="step-container">
            <fieldset style="margin-top:60px; justify-content:center;">
                <img src="User-ForgotPW-Step4-Icon.svg" width="100" class="icon">
                <h2>Password reset successfully</h2>
                <p style="font-family: 'Open Sans', sans-serif; line-height:1.7;width:50%; margin-top:50px; text-align:center; margin-left:auto; margin-right:auto;">Your password has been reset successfully! You can now log in using your new password. Click the button below to access your account.</p>
                <center><input type="button" class="direct-login" onclick="window.location.href='User-Login.php'" value="Back to login" ></center>
            </fieldset>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const progressListItems = 
                document.querySelectorAll("#progressbar li");
            const progressBar =
                document.querySelector(".progress-bar");

            const progressBarIcon = 
                document.querySelectorAll("#progressbar li i");

            let currentStep = 0;

            function updateProgress() {
                progressListItems.forEach((item, index) => {
                    if (index === currentStep || index <= currentStep) {
                        item.classList.add("active");
                    }else {
                        item.classList.remove("active");
                    }
                });

                progressBarIcon.forEach((item, index) => {
                    if (currentStep == 3) {
                        item.classList.remove("fa-times");
                        item.classList.add("fa-check");
                        item.classList.add("green-after"); 
                        item.classList.add("green-icon"); 
                    }else if (index === currentStep){
                        item.classList.remove("fa-times");
                        item.classList.remove("fa-check");
                        item.classList.add("fa-refresh");
                        item.classList.add("green-after"); 
                        item.classList.add("green-icon");
                    }else if(index <= currentStep){
                        item.classList.remove("fa-refresh");
                        item.classList.remove("fa-times");
                        item.classList.add("fa-check");
                        item.classList.add("green-after"); 
                        item.classList.add("green-icon"); 
                    }else {
                        item.classList.remove("fa-refresh");
                        item.classList.remove("fa-check");
                        item.classList.add("fa-times");
                        item.classList.remove("green-after");  
                        item.classList.remove("green-icon");
                    }
                });

            }

            function showStep(stepIndex) {
                const steps =
                    document.querySelectorAll(".step-container fieldset");
                steps.forEach((step, index) => {
                    if (index === stepIndex) {
                        step.style.display = "block";
                    } else {
                        step.style.display = "none";
                    }
                });
            }

            function nextStep() {
                if (currentStep < progressListItems.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                    updateProgress();
                }
            }

            function prevStep() {
                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                    updateProgress();
                }
            }

            const nextStepButtons = 
                document.querySelectorAll(".next-step");
            const prevStepButtons = 
                document.querySelectorAll(".previous-step");

            nextStepButtons.forEach((button) => {
                button.addEventListener("click", nextStep);
            });

            prevStepButtons.forEach((button) => {
                button.addEventListener("click", prevStep);
            });

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

            document.getElementById("send-email").addEventListener("submit", function(event){
                event.preventDefault();
                const emailEntered = document.getElementsByName("forgot-email")[0].value;
                fetch("", { // ✅ Send to the same file
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `forgotemail=${encodeURIComponent(emailEntered)}`
                })
                .then(response => response.text()) // ✅ Expect a plain text response (alert message)
                .then(data => {
                    if (data.includes("Email exists")) {
                        nextStep();
                    }
                })
                .catch(error => console.error("Error:", error));
            });

            document.getElementById('otp-submit').addEventListener("submit", function(event){
                event.preventDefault();
                const num1 = document.getElementsByName('num1')[0];
                const num2 = document.getElementsByName('num2')[0];
                const num3 = document.getElementsByName('num3')[0];
                const num4 = document.getElementsByName('num4')[0];
                const num5 = document.getElementsByName('num5')[0];
                const num6 = document.getElementsByName('num6')[0];

                const otpEntered = num1.value + num2.value + num3.value + num4.value + num5.value + num6.value;
                if (otpEntered == '111111'){
                    document.querySelector(".veri-btn").innerText = "Verification Successful!";                    
                    setTimeout(() => {
                        nextStep();
                    }, 1000);
                }else{
                    document.querySelector(".veri-btn").innerText = "Invalid Code. Try Again!";
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
                        // num1.style.border = '1px solid grey';
                        // num2.style.border = '1px solid grey';
                        // num3.style.border = '1px solid grey';
                        // num4.style.border = '1px solid grey';
                        // num5.style.border = '1px solid grey';
                        // num6.style.border = '1px solid grey';
                    }, 1000);
                }
                
            });

            document.getElementById('resetpw-form').addEventListener("submit", function (event) {
                const pwInput = document.getElementsByName("forgotPW")[0];
                const cpwInput = document.getElementsByName("forgotCPW")[0];

                const pwEM = document.getElementsByClassName("pw-error-message")[0];
                const cpwEM = document.getElementsByClassName("cpw-error-message")[0];
 
                const passwordPattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
                
                let hasError = false; 

                if (pwInput.value.trim() === "") {
                    pwEM.textContent = "Please enter your password.";
                    pwEM.style.display = "block";
                    pwInput.style.borderBottom = "1px solid red";
                    // pwInput.style.marginBottom= "10px";
                    pwEM.style.paddingTop = "0px";
                    hasError = true;
                }else if (!passwordPattern.test(pwInput.value.trim())){
                    pwEM.textContent = "Password must have at least 8 characters, including an uppercase letter, a lowercase letter, and a number.";
                    pwEM.style.display = "block";
                    pwInput.style.borderBottom = "1px solid red";
                    // pwInput.style.marginBottom= "10px";
                    pwEM.style.paddingTop = "0px";
                    hasError = true;
                }else {
                    pwEM.style.display = "none";
                    pwInput.style.borderBottom = "1px solid black";
                    // pwInput.style.marginBottom= "30px";

                }

                if (cpwInput.value.trim() === "") {
                    cpwEM.textContent = "Please enter your password.";
                    cpwEM.style.display = "block";
                    cpwInput.style.borderBottom = "1px solid red";
                    // cpwInput.style.marginBottom= "10px";
                    cpwEM.style.paddingTop = "0px";
                    hasError = true;
                }else if (cpwInput.value !== pwInput.value) {
                    cpwEM.textContent = "Passwords do not match.";
                    cpwEM.style.display = "block";
                    cpwInput.style.borderBottom = "1px solid red";
                    // cpwInput.style.marginBottom= "10px";
                    cpwEM.style.paddingTop = "0px";
                    hasError = true;
                }else {
                    cpwEM.style.display = "none";
                    // cpwInput.style.marginBottom= "30px";
                    cpwInput.style.borderBottom = "1px solid black";
                }

                if (hasError) {
                    event.preventDefault();
                }else{
                    event.preventDefault();
                    nextStep();
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