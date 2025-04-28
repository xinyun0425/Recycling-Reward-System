<?php
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    if(isset($_POST['signUp'])){

        $email = $_POST['signup-email'];
        $address = $_POST['address'];
        $phone_no = $_POST['phone'];
        $phone_no = preg_replace('/^\+60\s?/', '0', $phone_no);
        $emailExistQuery = mysqli_query($conn, "SELECT count(*) AS count FROM user WHERE email ='$email' AND NOT status = 'Pending Verification'");
        $emailExist = mysqli_fetch_assoc($emailExistQuery);
        
        $phoneExist = 0;  

        if (!empty($phone_no)) {  
            $phoneExistQuery = mysqli_query($conn, "SELECT COUNT(*) AS countPhone FROM user WHERE phone_number = '$phone_no' AND NOT status = 'Pending Verification'");
            $phoneExistResult = mysqli_fetch_assoc($phoneExistQuery);
            $phoneExist = $phoneExistResult ? $phoneExistResult['countPhone'] : 0;
        }
       

        if($emailExist['count'] > 0 && $phoneExist > 0){
            
            echo '<div class="overlay">
                    <div class="popup-error">
                        <div class="error-message-container">
                            <img src="User-SignUp-ErrorIcon.png" width="110">
                            <p class="popup-error-message">Email and phone number are already registered.</p>
                            <button class="close" type="button">OK</button>
                        </div>
                    </div>
                </div>';        
        }else if ($emailExist['count'] > 0){
            
            echo '<div class="overlay">
                    <div class="popup-error">
                        <div class="error-message-container">
                            <img src="User-SignUp-ErrorIcon.png" width="110">
                            <p class="popup-error-message">Email is already registered.</p>
                            <button class="close" type="button">OK</button>
                        </div>
                    </div>
                </div>';

        }else if ($phoneExist > 0){
            
            echo '<div class="overlay">
                    <div class="popup-error">
                        <div class="error-message-container">
                            <img src="User-SignUp-ErrorIcon.png" width="110">
                            <p class="popup-error-message">Phone number is already registered.</p>
                            <button class="close" type="button">OK</button>
                        </div>
                    </div>
                </div>';

        }else {
            echo '<div class="overlay">
                <div class="popup-error">
                    <div class="error-message-container">
                        <img src="User-SignUp-EmailSentIcon.png" width="110">
                        <form method="post" id="direct-verification">
                            <p class="popup-error-message">We will send email to <strong>'. $email.'</strong>.</p>
                            <input type="hidden" name="signup-username1" value="' . $_POST['signup-username'] . '">
                            <input type="hidden" name="signup-email1" value="' . $_POST['signup-email'] . '">
                            <input type="hidden" name="SignUpPW1" value="' . $_POST['SignUpPW'] . '">
                            <input type="hidden" name="dob1" value="' . $_POST['dob'] . '">
                            <input type="hidden" name="address1" value="' . $_POST['address'] . '">
                            <input type="hidden" name="phone1" value="' . $_POST['phone'] . '">
                            <div style="display:flex; justify-content:center; align-items:center; gap:20px;">
                                <button style="width:40%;" class="close back" type="button">Back</button>
                                <button style="width:40%;" class="next" name="proceed" type="submit">OK</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
                
        }
        
    }
    if (isset($_POST['proceed'])){
        $username = $_POST['signup-username1'];
        $email = $_POST['signup-email1'];
        $password = $_POST['SignUpPW1'];
        $dob = $_POST['dob1'];
        $address = $_POST['address1'];
        $phone_no = $_POST['phone1'];
        $phone_no = preg_replace('/^\+60\s?/', '', $phone_no);
        
        $emailPendingVerifyQuery = mysqli_query($conn, "SELECT count(*) AS countEmail FROM user WHERE email ='$email' AND status = 'Pending Verification'");
        $emailPVResult = mysqli_fetch_assoc($emailPendingVerifyQuery);

        if ($emailPVResult['countEmail'] > 0){
            if (trim($address) == "" && $phone_no == ""){
                $updateUserQuery = mysqli_query($conn, "UPDATE user SET username='$username', password = '$password', dob = '$dob', address = NULL, 
                    phone_number = NULL, points = '0', profile_image =  'User-Profile-Avatar-1.png', created_at = NOW() WHERE email = '$email'");
            }else if ($phone_no == ""){
                $updateUserQuery = mysqli_query($conn, "UPDATE user SET username='$username', password = '$password', dob = '$dob', address = '$address', 
                    phone_number = NULL, points = '0', profile_image =  'User-Profile-Avatar-1.png', created_at = NOW() WHERE email = '$email'");
            }else if (trim($address) == "" ){
                $updateUserQuery = mysqli_query($conn, "UPDATE user SET username='$username', password = '$password', dob = '$dob', address = NULL, 
                    phone_number = '$phone_no', points = '0', profile_image =  'User-Profile-Avatar-1.png', created_at = NOW() WHERE email = '$email'");
            }else{
                $updateUserQuery = mysqli_query($conn, "UPDATE user SET username='$username', password = '$password', dob = '$dob', address = '$address', 
                    phone_number = '$phone_no', points = '0', profile_image =  'User-Profile-Avatar-1.png', created_at = NOW() WHERE email = '$email'");
            }
        }else{
            if (trim($address) == "" && $phone_no == ""){
                $addUserQuery = "INSERT INTO user(username, email, password, dob, address, phone_number, points, profile_image, created_at, status) VALUES
                    ('$username','$email','$password','$dob',NULL, NULL,'0', 'User-Profile-Avatar-1.png', NOW(), 'Pending Verification')";
            }else if ($phone_no == ""){
                $addUserQuery = "INSERT INTO user(username, email, password, dob, address, phone_number, points, profile_image, created_at, status) VALUES
                    ('$username','$email','$password','$dob','$address', NULL,'0', 'User-Profile-Avatar-1.png', NOW(), 'Pending Verification')";
            }else if (trim($address) == "" ){
                $addUserQuery = "INSERT INTO user(username, email, password, dob, address, phone_number, points, profile_image, created_at, status) VALUES
                    ('$username','$email','$password','$dob', NULL,'$phone_no','0', 'User-Profile-Avatar-1.png', NOW(), 'Pending Verification')";
            }else{
                $addUserQuery = "INSERT INTO user(username, email, password, dob, address, phone_number, points, profile_image, created_at, status) VALUES
                    ('$username','$email','$password','$dob','$address','$phone_no','0', 'User-Profile-Avatar-1.png', NOW(), 'Pending Verification')";
            }
            $addUser = mysqli_query($conn,$addUserQuery);   
        }
        $getUserIDQuery = mysqli_query($conn, "SELECT user_id FROM user WHERE email = '$email'");
        $userID = mysqli_fetch_assoc($getUserIDQuery)['user_id'];
        $title = "Welcome to Green Coin, ".$username."! 🌱";
        $announcement = "Hi, ".$username.". Welcome to Greencoin! 🎉 We\'re excited to have you on board as part of our mission to 
                        promote responsible e-waste recycling. With Greencoin, you can easily schedule pickups for
                        your old electronics, find nearby drop-off locations, and track your recycling progress. 
                        Plus, every item you recycle earns you points that can be redeemed for rewards! Start exploring 
                        now and make a positive impact on the environment while enjoying exclusive benefits. 
                        Happy recycling! 🌍♻️";
        $welcomeUserNotiQuery = "INSERT INTO user_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$userID', NOW(), '$title', '$announcement', 'unread')";
        $addWelcomeUserNotiQuery = mysqli_query($conn, $welcomeUserNotiQuery);

        $admin_announcement = "A new user have successfully signed up on the platform. They can now participate in recyling activities and earn reward.";
        $newRequestNotiQuery = "INSERT INTO admin_notification(user_id, datetime, title, announcement, status) VALUES 
        ('$userID', NOW(), '🆕 New User Sign-Up', '$admin_announcement', 'unread')";
        mysqli_query($conn, $newRequestNotiQuery);

        echo '<script>window.location.href="User-SignUp-Verification.php?email='.$email.'";</script>';
    } 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Green Coin</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            box-sizing: border-box;
            
        }
        @media only screen and (min-width: 320px) and (max-width: 767px) {
            .signup{
                position: relative;
                z-index: 1;
                background-color: white;
                width: 680px;
                padding: 20px 20px 00px 15px;
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
            
            .signup{
                position: relative;
                z-index: 1;
                background-color: white;
                display: block;
                height:90vh;
                width: 750px;
                left:6%;
                padding: 20px 40px 0px 40px;
                overflow: auto;  
                place-content: center;
            }

        }

        #signup{
            max-height: 670px;
            min-height:auto;
            margin: auto;
            vertical-align: middle;
            place-content: space-evenly;
        }
 
        h1{
            font-size: 35px;
            padding-bottom:5px;
            margin-left:60px;
            font-family: "Playpen Sans", cursive;
        }

        label{
            font-family: "Open Sans", sans-serif;
            font-size: 14px;
            padding: 0px 4px 0px 0px;
            margin-bottom: 15px;
        }

        input[type="text"], input[type="date"], input[type="tel"]{
            font-family: "Open Sans", sans-serif;
            padding: 8px 0px;
            width: 260px;
            margin-top: 10px;
            margin-bottom: 30px;
            font-size: 14px;
            border: none;
            border-bottom: 1px solid black;
            outline: none;
        }

        input[type="password"]{
            font-family: "Open Sans", sans-serif;
            padding: 8px 0px;
            width: 260px;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            border: none;
            border-bottom: 1px solid black;
            outline: none;
        }

        .sign-up{
            font-size: 18px;
            padding: 10px 10px;
            width: 84.5%;
            margin-top: 30px;
            margin-left:58px;
            margin-bottom:0px;
            background-color: #78a24c;
            border: none;
            cursor: pointer;
            color:white;
            border-radius:20px;
            font-family: "Playpen Sans", cursive;
        }

        .sign-up:hover{
            opacity: 0.8;
        }

        .name-error-message, .email-error-message, 
        .date-error-message, .phone-error-message, 
        .cpw-error-message{
            font-family: "Open Sans", sans-serif;
            display: none;
            font-size: 12px;
            color: #f7656d;
            padding:10px 0px;
            text-align: left;
        }

        .pw-error-message{
            font-family: "Open Sans", sans-serif;
            display: none;
            font-size: 12px;
            color: #f7656d;
            padding:10px 0px;
            text-align: left;
            width:280px;
        }
        .showHidePw, .showHideCPw{
            position: absolute;
            margin-top: 17px;
            margin-left: -35px;
            cursor: pointer;
        }

        .required-star{
            font-family: "Open Sans", sans-serif;
            position: relative;
            color:red;
            font-size: 20px;
        }

        .name, .email, .dob, .phone-no, .address, .password, .confirm-pw{
            font-family: "Open Sans", sans-serif;
            position: relative;
            font-size: 16px;
            opacity: 0;
            transition: 300ms ease;
        }

        .inputFields:focus::placeholder{
            opacity: 0;
            transition: 300ms ease;
        }

        .inputFields::placeholder{
            opacity: 1;
            transition: 300ms ease;
        }

        .direct-login{
            font-size: 14px;
            text-align: center;
            padding: 20px;
            font-family: "Playpen Sans", cursive;
        }

        .direct-login a{
            color: rgb(40, 86, 15);
            text-decoration:none;
            font-family: "Playpen Sans", cursive;
        }

        .direct-login a:hover{
            opacity: 0.8;
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
            border: 2px solid rgb(94, 174, 37);
        }

        .back {
            background-color:transparent !important;
            border-radius: 20px;
            border: 2px solid rgba(94, 174, 37, 0.64) !important;
            color: rgb(73, 144, 22) !important;
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

    
    <div class="signup">
        <form id="signup" action="" method="post">
            <div>
                <h1>Sign Up</h1>
                <br>
                <div style="margin:0px 0px 0px 62px;">
                    <div style="display: inline-block;">
                        <div style="margin-right:50px; float: left;">
                            <label class="name">Name <span class="required-star">*</span></label>
                            <br>
                            <input type="text" autocomplete="off" name="signup-username" class="inputFields" placeholder="Name*">
                            <p class="name-error-message">Please enter your name.</p>
                        </div>
                        <div style="float: right;">
                            <label class="email">Email<span class="required-star">*</span></label>
                            <br>
                            <input type="text" autocomplete="off" name="signup-email" class="inputFields" placeholder="Email*">
                            <p class="email-error-message">Please enter your email address.</p>
                        </div>
                    </div>
                    <div style="display: inline-block;">
                        <div style="margin-right:50px; float: left;">
                            <label class="dob">Date Of Birth<span class="required-star" >*</span></label>
                            <br>
                            <input type="text" autocomplete="off" onblur="(this.type='text')" name="dob" placeholder="Date Of Birth*" class="inputFields">
                            <p class="date-error-message">Please choose your date of birth.</p>
                            <br>
                        </div>
                        <div style="float: right;">
                            <label class="phone-no">Phone Number</label>
                            <br>
                            <input type="tel" autocomplete="off" name="phone" class="inputFields" placeholder="Phone Number">
                            <p class="phone-error-message">Please enter a valid phone number.</p>
                        </div>
                    </div>
                    
                    <br>
                    <div>
                        <label class="address">Address</label>
                        <br>
                        <input type="text" autocomplete="off" name="address" style="width: 570px;" class="inputFields" placeholder="Address">
                        <br>
                    </div>
                    <div style="display: inline-block;">
                        <div style="margin-right: 50px; float: left;">
                            <label class="password">Password<span class="required-star">*</span></label>
                            <br>
                            <div style="position: relative;">
                                <input autocomplete="off" type="password" name="SignUpPW" class="inputFields" placeholder="Password*">
                                <img src="User-HidePasswordIcon.png" width="25px" class="showHidePw">
                            </div>
                            <p class="pw-error-message">Please enter your password.</p>
                            <br>
                        </div>
                        <div style="float: right;">
                            <label class="confirm-pw">Confirm Password<span class="required-star">*</span></label>
                            <br>
                            <div style="position: relative;">
                                <input autocomplete="off" type="password" name="SignUpCPW" class="inputFields" placeholder="Confirm Password*">
                                <img src="User-HidePasswordIcon.png" width="25px" class="showHideCPw">
                            </div>
                            <p class="cpw-error-message">Please enter your password.</p>
                            <br>
                        </div>
                        
                    </div>
                </div>

                <button type="submit" class="sign-up" name="signUp">Sign Up</button>
            </div>
        </form>
        <p class="direct-login">Already have an account? <a href="User-Login.php">Login now</a></p>
    </div>

    
    <script>
        document.getElementById("signup").addEventListener("submit", function (event) {
            const nameInput = document.getElementsByName("signup-username")[0];
            const emailInput = document.getElementsByName("signup-email")[0];
            const dobInput = document.getElementsByName("dob")[0];
            const phoneInput = document.getElementsByName("phone")[0];
            const addressInput = document.getElementsByName("address")[0];
            const pwInput = document.getElementsByName("SignUpPW")[0];
            const cpwInput = document.getElementsByName("SignUpCPW")[0];

            const nameEM = document.getElementsByClassName("name-error-message")[0];
            const emailEM = document.getElementsByClassName("email-error-message")[0];
            const dobEM = document.getElementsByClassName("date-error-message")[0];
            const phoneEM = document.getElementsByClassName("phone-error-message")[0];
            const pwEM = document.getElementsByClassName("pw-error-message")[0];
            const cpwEM = document.getElementsByClassName("cpw-error-message")[0];

            const emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
            const phonePattern = /^01[0-46-9]\d{7,8}$/; 
            const passwordPattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

            sessionStorage.setItem("signup-username", nameInput.value);
            sessionStorage.setItem("signup-email", emailInput.value);
            sessionStorage.setItem("dob", dobInput.value);
            sessionStorage.setItem("phone", phoneInput.value);
            sessionStorage.setItem("address", addressInput.value);
            sessionStorage.setItem("SignUpPW", pwInput.value);
            sessionStorage.setItem("SignUpCPW", cpwInput.value);
            

            let hasError = false; 

            if (nameInput.value.trim() === "") {
                nameEM.style.display = "block";
                nameInput.style.borderBottom = "1px solid red";
                nameInput.style.marginBottom = "0px";
                nameEM.style.marginBottom = "15px";
                hasError = true;
            } else {
                nameEM.style.display = "none";
                nameInput.style.borderBottom = "1px solid black";
                nameInput.style.marginBottom = "30px";
            }

            if (emailInput.value.trim() === "") {
                emailEM.textContent = "Please enter your email address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottom = "1px solid red";
                emailInput.style.marginBottom = "0px";
                emailEM.style.marginBottom = "15px";
                hasError = true;
            }else if (!emailPattern.test(emailInput.value.trim())) {
                emailEM.textContent = "Please enter a valid Gmail address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottom = "1px solid red";
                emailInput.style.marginBottom = "0px";
                emailEM.style.marginBottom = "15px";
                hasError = true;
            }else{
                emailEM.style.display = "none";
                emailInput.style.borderBottom = "1px solid black";
                emailInput.style.marginBottom = "30px";
            }
            const enteredDOB = new Date(dobInput.value);
            const today = new Date();
            const minDOB = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            if (dobInput.value.trim() === "") {
                dobEM.textContent = "Please choose your date of birth.";
                dobEM.style.display = "block";
                dobInput.style.borderBottom = "1px solid red";
                dobInput.style.marginBottom = "0px";
                hasError = true;
            } else if (enteredDOB > minDOB){
                dobEM.textContent = "You must be at least 18 years old.";
                dobEM.style.display = "block";
                dobInput.style.borderBottom = "1px solid red";
                dobInput.style.marginBottom = "0px";
                hasError = true;
            }else{
                dobEM.style.display = "none";
                dobInput.style.marginBottom = "30px";
                dobInput.style.borderBottom = "1px solid black";
            }

            let phoneNumber = phoneInput.value.trim();
            if (phoneNumber.startsWith("+60 ")) {
                phoneNumber = phoneNumber.replace("+60 ", "0"); 
            }else{
                phoneNumber.value = "";
            }

            if (phoneNumber !== ""){
                if (!phonePattern.test(phoneNumber)) {
                    phoneEM.textContent = "Please enter a valid phone number.";
                    phoneEM.style.display = "block";
                    phoneInput.style.borderBottom = "1px solid red";
                    phoneInput.style.marginBottom = "0px";
                    hasError = true;
                }else{
                    phoneEM.style.display = "none";
                    phoneInput.style.borderBottom = "1px solid black";
                    phoneInput.style.marginBottom = "30px";
                }
            }else{
                phoneEM.style.display = "none";
                phoneInput.style.borderBottom = "1px solid black";
                phoneInput.style.marginBottom = "30px";
            }

            if (pwInput.value.trim() === "") {
                pwEM.textContent = "Please enter your password.";
                pwEM.style.display = "block";
                pwInput.style.borderBottom = "1px solid red";
                pwEM.style.paddingTop = "0px";
                hasError = true;
            }else if (!passwordPattern.test(pwInput.value.trim())){
                pwEM.textContent = "Password must have at least 8 characters, including an uppercase letter, a lowercase letter, and a number.";
                pwEM.style.display = "block";
                pwInput.style.borderBottom = "1px solid red";
                pwEM.style.paddingTop = "0px";
                hasError = true;
            
            }else {
                pwEM.style.display = "none";
                pwInput.style.borderBottom = "1px solid black";
            }

            if (cpwInput.value.trim() === "") {
                cpwEM.textContent = "Please enter your password.";
                cpwEM.style.display = "block";
                cpwInput.style.borderBottom = "1px solid red";
                cpwEM.style.paddingTop = "0px";
                hasError = true;
            }else if (cpwInput.value !== pwInput.value) {
                cpwEM.textContent = "Password do not match.";
                cpwEM.style.display = "block";
                cpwInput.style.borderBottom = "1px solid red";
                cpwEM.style.paddingTop = "0px";
                hasError = true;
            }else {
                cpwEM.style.display = "none";
                cpwInput.style.borderBottom = "1px solid black";
            }

            if (hasError) {
                event.preventDefault();
            }
        });

        function handlePopupClose() {
            let popup = document.querySelector(".overlay");
            if (popup) {
                let closeBtn = popup.querySelector(".close");
                let proceedBtn = popup.querySelector(".next");
                let closeTopBtn = popup.querySelector(".popup-error-close");
                closeBtn.addEventListener("click", function () {
                    popup.remove(); 
                });
                proceedBtn.addEventListener("click", function(){
                    const email = document.getElementsByClassName("email")[0].value;
                    document.getElementById("direct-verification").submit();
                });
            }
        }

        setInterval(() => {
            let popup = document.querySelector(".overlay");
            if (popup) {
                document.getElementsByName("signup-username")[0].value = sessionStorage.getItem("signup-username") || "";
                document.getElementsByClassName("name")[0].style.opacity = "1";
                document.getElementsByName("signup-email")[0].value = sessionStorage.getItem("signup-email") || "";
                document.getElementsByClassName("email")[0].style.opacity = "1";
                document.getElementsByName("dob")[0].value = sessionStorage.getItem("dob") || "";
                document.getElementsByClassName("dob")[0].style.opacity = "1";
                document.getElementsByName("phone")[0].value = sessionStorage.getItem("phone") || "";
                if (document.getElementsByName("phone")[0].value != ""){
                    document.getElementsByClassName("phone-no")[0].style.opacity = "1";
                }
                document.getElementsByName("address")[0].value = sessionStorage.getItem("address") || "";
                if (document.getElementsByName("address")[0].value != ""){
                    document.getElementsByClassName("address")[0].style.opacity = "1";
                }
                
                document.getElementsByName("SignUpPW")[0].value = sessionStorage.getItem("SignUpPW") || "";
                document.getElementsByClassName("password")[0].style.opacity = "1";
                document.getElementsByName("SignUpCPW")[0].value = sessionStorage.getItem("SignUpCPW") || "";
                document.getElementsByClassName("confirm-pw")[0].style.opacity = "1";

                popup.addEventListener("click", function(event){
                    if (event.target === popup ){
                        popup.remove(); 
                        // sessionStorage.setItem("fromLogin", true);        
                    }
                });

                handlePopupClose(); 
                clearInterval(this);
            }
        }, 500);

        const nameLabel = document.getElementsByClassName("name")[0];
        const nameInput = document.getElementsByName("signup-username")[0];

        nameInput.onfocus = function(){
            nameLabel.style.opacity = "1";
        }

        const emailLabel = document.getElementsByClassName("email")[0];
        const emailInput = document.getElementsByName("signup-email")[0];

        emailInput.onfocus = function(){
            emailLabel.style.opacity = "1";
        }

        const dobLabel = document.getElementsByClassName("dob")[0];
        const dobInput = document.getElementsByName("dob")[0];

        dobInput.onfocus = function(){
            dobLabel.style.opacity = "1";
            dobInput.type ="date";
            dobInput.max = new Date().toISOString().split("T")[0];
            if (nameInput.value.trim() === ""){
                nameLabel.style.opacity = "0";
            }

            if (emailInput.value.trim() === ""){
                emailLabel.style.opacity = "0";
            }
            
            if (phoneInput.value === "+60 "){
                phoneLabel.style.opacity = "0";
                phoneInput.value = "";
            }
           
            if (addressInput.value.trim() === ""){
                addressLabel.style.opacity = "0";
            }

            if (pwInput.value.trim() === ""){
                pwLabel.style.opacity = "0";
            }

            if (cpwInput.value.trim() === ""){
                cpwLabel.style.opacity = "0";
            }
            
        }

        const phoneLabel = document.getElementsByClassName("phone-no")[0];
        const phoneInput = document.getElementsByName("phone")[0];

        phoneInput.onfocus = function(){
            phoneLabel.style.opacity = "1";
            if (!phoneInput.value.startsWith("+60 ")) {
                phoneInput.value = "+60 ";
            }
        }

        phoneInput.addEventListener("keydown", function (event) {
            if (phoneInput.selectionStart <= 4 && (event.key === "Backspace" || event.key === "Delete")) {
                event.preventDefault(); 
            }
        });

        phoneInput.addEventListener("input", function () {
            phoneLabel.style.opacity = "1";
            if (!phoneInput.value.startsWith("+60 ")) {
                const digitsOnly = phoneInput.value.replace(/\D/g, ""); 
                phoneInput.value = "+60 " + digitsOnly;
                phoneInput.setSelectionRange(phoneInput.value.length, phoneInput.value.length); 
            }
        });

        phoneInput.addEventListener("focusin", function () {
            phoneLabel.style.opacity = "1";
            if (!phoneInput.value.startsWith("+60 ")) {
                const digitsOnly = phoneInput.value.replace(/\D/g, ""); 
                phoneInput.value = "+60 " + digitsOnly;
                phoneInput.setSelectionRange(phoneInput.value.length, phoneInput.value.length); 
            }
        });

        phoneInput.addEventListener("mouseleave", function () {
            const selection = window.getSelection();
            const isInputFocused = document.activeElement === phoneInput;
            
            if (isInputFocused && phoneInput.selectionStart !== phoneInput.selectionEnd) {
                phoneInput.blur(); 
            }
        });
        

        const addressLabel = document.getElementsByClassName("address")[0];
        const addressInput = document.getElementsByName("address")[0];

        addressInput.onfocus = function(){
            addressLabel.style.opacity = "1";
        }

        const pwLabel = document.getElementsByClassName("password")[0];
        const pwInput = document.getElementsByName("SignUpPW")[0];

        pwInput.onfocus = function(){
            pwLabel.style.opacity = "1";
        }

        const cpwLabel = document.getElementsByClassName("confirm-pw")[0];
        const cpwInput = document.getElementsByName("SignUpCPW")[0];

        cpwInput.onfocus = function(){
            cpwLabel.style.opacity = "1";
        }

        window.onclick = function(event){
            if (event.target != nameInput){
                if (nameInput.value == ""){
                    nameLabel.style.opacity = "0";
                }
            }

            if (event.target != emailInput){
                if (emailInput.value == ""){
                    emailLabel.style.opacity = "0";
                }
            }
        
            if (event.target != dobInput){
                if (dobInput.value == ""){
                    dobLabel.style.opacity = "0";
                }
            }

            if (event.target != phoneInput){
                if (phoneInput.value == "+60 "){
                    phoneLabel.style.opacity = "0";
                    phoneInput.value = "";
                }
            }

            if (event.target != addressInput){
                if (addressInput.value == ""){
                    addressLabel.style.opacity = "0";
                }
            }

            if (event.target != pwInput){
                if (pwInput.value == ""){
                    pwLabel.style.opacity = "0";
                }
            }

            if (event.target != cpwInput){
                if (cpwInput.value == ""){
                    cpwLabel.style.opacity = "0";
                }
            }
        }

        window.onkeyup = function(event){
            if (event.target != nameInput){
                if (nameInput.value == ""){
                    nameLabel.style.opacity = "0";
                }
            }

            if (event.target != emailInput){
                if (emailInput.value == ""){
                    emailLabel.style.opacity = "0";
                }
            }
        
            if (event.target != dobInput){
                if (dobInput.value == ""){
                    dobLabel.style.opacity = "0";
                }
            }

            if (event.target != phoneInput){
                if (phoneInput.value == "+60 "){
                    phoneLabel.style.opacity = "0";
                    phoneInput.value = "";
                }
            }

            if (event.target != addressInput){
                if (addressInput.value == ""){
                    addressLabel.style.opacity = "0";
                }
            }

            if (event.target != pwInput){
                if (pwInput.value == ""){
                    pwLabel.style.opacity = "0";
                }
            }

            if (event.target != cpwInput){
                if (cpwInput.value == ""){
                    cpwLabel.style.opacity = "0";
                }
            }
        }
        document.getElementsByClassName("showHidePw")[0].addEventListener("click",function(event){
            const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];
            const PWInput = document.getElementsByName("SignUpPW")[0];
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
            const CPWInput = document.getElementsByName("SignUpCPW")[0];
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