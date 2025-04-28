<?php
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }
    if (isset($_GET['email'])){
        $emailEntered = $_GET['email'];
        $emailExistQuery = mysqli_query($conn, "SELECT user_id, password FROM user WHERE email = '$emailEntered' AND NOT status = 'Pending Verification'");
        $emailExistResult = mysqli_fetch_assoc($emailExistQuery);
        $userID = $emailExistResult['user_id'];
        $currentPW = $emailExistResult['password'];
    }else{
        echo '<script>window.location.href="User-ForgotPW-Step1.php";</script>';
    }

    if (isset($_POST['reset'])){
        $newPW = $_POST['forgotPW'];
        $updatePWQuery = mysqli_query($conn, "UPDATE user SET password = '$newPW' WHERE user_id = '$userID'"); 
        echo '<script>window.location.href="User-ForgotPW-Step4.php";</script>';
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

        #progressbar li strong{
            font-family: "Playpen Sans", cursive;
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
            font-size:18px;
            
        }

        .input-forgot-pw input[type='text']{
            padding: 5px 0px;
            width: 100%;
            margin-top: 20px;
            border:none;
            border-bottom:1px solid black;
            outline:none;
            font-family: 'Open Sans', sans-serif;
            font-size:19px;
        }

        .proceed-to-emailcode, .proceed-to-resetpw, .proceed-to-successfulmessage{
            width:40%;
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

        input[type="password"]{
            padding: 5px 0px;
            width: 100%;
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
            width: 100%;
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
            width:40%;
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
            width: 40%;
            /* margin-left:390px;
            margin-top:50px;  */
            margin: 50px auto 0px auto;
        }

        label{
            font-size:18px;
            font-family:'Open Sans', sans-serif;
        }

        .direct-login{
            width:32%;
            border-radius: 5px;
            padding:10px 5px;
            font-size: 16px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            margin-top:100px;
            margin-bottom: 20px;
            margin-left: 370px;
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
            width: 100%;
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
                      <i class="green-after green-icon line-bar-3 fa fa-refresh" ></i>
                </li>
                <li id="step4">
                    <strong>Success</strong><br>
                    <i class="line-bar-4 fa fa-times"></i>
                  </li>
            </ul>
        </div>
        <div class="step-container">
            <fieldset>
                <img src="User-ForgotPW-Step3-Icon.svg" width="100" class="icon">
                <h2>Set new password</h2>
                <form id="resetpw-form" method="post">
                    <div class="reset-pw">
                        <label>New Password</label>
                        <div style="position: relative;">
                            <input autocomplete="off" type="password" name="forgotPW" class="inputFields">
                            <img src="User-HidePasswordIcon.png" width="22" class="showHidePw">
                        </div>
                        <p class="pw-error-message">Please enter your password.</p>
                        <br>
                        <label>Confirm New Password</label>
                        <div style="position: relative;">
                            <input autocomplete="off" type="password" name="forgotCPW" class="inputFields">
                            <img src="User-HidePasswordIcon.png" width="22" class="showHideCPw">
                        </div>
                        <p class="cpw-error-message">Please enter your password.</p>
                        <br>
                    </div>
                    <center><button type="submit" name="reset" class="reset">Reset</button></center>
                </form>
            </fieldset>
        </div>
    </div>
    <script>
        document.getElementById('resetpw-form').addEventListener("submit", function (event) {
            const pwInput = document.getElementsByName("forgotPW")[0];
            const cpwInput = document.getElementsByName("forgotCPW")[0];

            const pwEM = document.getElementsByClassName("pw-error-message")[0];
            const cpwEM = document.getElementsByClassName("cpw-error-message")[0];

            const passwordPattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
            const currentPW = "<?php echo $currentPW;?>";
            let hasError = false; 

            if (pwInput.value.trim() === "") {
                pwEM.textContent = "Please enter your password.";
                pwEM.style.display = "block";
                pwInput.style.borderBottom = "1px solid red";
                // pwInput.style.marginBottom= "10px";
                pwEM.style.paddingTop = "0px";
                hasError = true;
            }else if (pwInput.value.trim() == currentPW){
                pwEM.textContent = "New password must be different from the current password.";
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
            }else{
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