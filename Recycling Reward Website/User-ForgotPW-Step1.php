<?php
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }
    if(isset($_POST['send-email'])){
        $emailEntered = $_POST['forgotemail'];
        $emailExistQuery = mysqli_query($conn, "SELECT count(*) AS count FROM user WHERE email = '$emailEntered' AND NOT status = 'Pending Verification'");
        $emailExistResult = mysqli_fetch_assoc($emailExistQuery);
        $emailExist = $emailExistResult['count'];

        if ($emailExist > 0){
            echo '<script>window.location.href = "User-ForgotPW-Step2.php?email='.$emailEntered.'";</script>';
        }else{
            echo '<div class="overlay">
                    <div class="popup-error">
                        <div class="error-message-container">
                            <img src="User-SignUp-ErrorIcon.png" width="110">
                            <p class="popup-error-message">Email entered does not registered yet.</p>
                            <button class="close" type="button" onclick="handlePopupClose()">OK</button>
                        </div>
                    </div>
                </div>';   
           
        }

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

        .progress-bar {
            background-color: #78A24C;
            width: 0;
            height: 100%;
            transition: width 0.4s ease-in-out;
        }

        #progressbar li strong{
            font-family: "Playpen Sans", cursive;
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
            width: 40%;
            /* margin-left:400px; */
            /* margin-top:50px;  */
            margin:50px auto 0px auto;
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

        label{
            font-size:18px;
            font-family:'Open Sans', sans-serif;
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

        .email-error-message{
            font-family: "Open Sans", sans-serif;
            display: none;
            font-size: 14px;
            color: #f7656d;
            padding:10px 0px;
            text-align: left;
            width: 100%;
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

        .popup-error-message {
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            padding: 15px;
            margin-bottom:10px;
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
                    <i class="line-bar-1 fa fa-refresh"></i>
                </li>
                <li id="step2">
                    <strong>Verify Code</strong><br>
                    <i class="line-bar-2 fa fa-times"></i>
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
            <fieldset>
                <img src="User-ForgotPW-Step1-Icon.svg" width="130" class="icon">
                <h2>Forgot password</h2>
                <form method="post" id="send-email">
                    <div class="input-forgot-pw">
                        <label>Email</label><br>
                        <input autocomplete="off" type="text" class="forgot-email" name="forgotemail">
                        <p class="email-error-message">Please enter your email address.</p>
                    </div>
                    <center><button type="submit" name="send-email" class="next-step proceed-to-emailcode">Next</button></center>
                </form>
            </fieldset>
        </div>
    </div>
    <script>
        document.getElementById("send-email").addEventListener("submit", function(event){
            const emailEntered = document.getElementsByName("forgotemail")[0];
            const emailEM = document.getElementsByClassName("email-error-message")[0];

            sessionStorage.setItem("forgot-email", emailEntered.value);

            const emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        
            let hasError = false; 

            if (emailEntered.value.trim() === "") {
                emailEM.textContent = "Please enter your email address.";
                emailEM.style.display = "block";
                emailEntered.style.borderBottom = "1px solid red";
                emailEntered.style.marginBottom = "0px";
                emailEM.style.marginBottom = "15px";
                hasError = true;
            }else if (!emailPattern.test(emailEntered.value.trim())) {
                emailEM.textContent = "Please enter a valid Gmail address.";
                emailEM.style.display = "block";
                emailEntered.style.borderBottom = "1px solid red";
                emailEntered.style.marginBottom = "0px";
                emailEM.style.marginBottom = "15px";
                hasError = true;
            }else{
                emailEM.style.display = "none";
                emailEntered.style.borderBottom = "1px solid black";
            }
            
            if(hasError){
                event.preventDefault(); 
            }
           
        });

        function handlePopupClose() {
            let popup = document.querySelector(".overlay");
            if (popup) {
                document.getElementsByName("forgotemail")[0].value = sessionStorage.getItem("forgot-email") || "";
                popup.remove(); 
            }
        }        
    </script>
</body>
</html>