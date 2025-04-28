<?php
    session_start();
    // IMPORTANT SECURITY NOTE: Your current code is vulnerable to SQL Injection.
    // You should use prepared statements instead of directly embedding variables in the query.
    // Example using prepared statements is commented below for reference.

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        // It's generally better to log errors than echo them directly in production
        error_log("Failed to connect to MySQL:".mysqli_connect_error());
        // Display a generic error message to the user
        echo '<script>alert("Database connection error. Please try again laterLogin unsuccessful.");</script>';
        header("Location: Admin-Dashboard.php");
        exit; // Stop script execution if DB connection fails
    }

    if(isset($_POST['login'])){
        $email = $_POST['loginEmail'];
        $password = $_POST['loginPW']; // Storing plain text password - VERY INSECURE

        // --- Using Prepared Statements (More Secure) ---
        $stmt = mysqli_prepare($conn, "SELECT admin_id, password FROM admin WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $userExistQuery = mysqli_stmt_get_result($stmt);
        $userExistResult = mysqli_fetch_assoc($userExistQuery);
        mysqli_stmt_close($stmt);
        // --- End Prepared Statements ---

        if (!$userExistResult) {
            echo '<script>alert("Login unsuccessful."); window.location.href="Admin-Login.php";</script>';
            // header("Location: Admin-Dashboard.php");
        } else {
            // --- Password Verification (More Secure) ---
            // Assumes you are storing hashed passwords using password_hash()
            // if (password_verify($password, $userExistResult['password'])) {
            // --- Plain Text Comparison (INSECURE - As in original code) ---
            if ($password == $userExistResult['password']) {
                // Regenerate session ID upon login to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $userExistResult['admin_id'];
                 // Use PHP header for redirection BEFORE any HTML output
                 // Make sure no HTML/whitespace is output before this header() call
                 header("Location: Admin-Dashboard.php");
                 exit; // Important to prevent further script execution
                // echo '<script>window.location.href="Admin-HomePage.php";</script>'; // Less reliable than header()
            } else {
                echo '<script>alert("Login unsuccessful."); window.location.href="Admin-Login.php";</script>';
            }
            // --- End Password Verification ---
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
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
    * {
        margin: 0px;
        padding: 0px;
        font-family: "Roboto", sans-serif;
        box-sizing: border-box;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: #e0e0e0;
        position: relative;
        overflow: hidden;
        background-image: url('Admin-Login-Background.png'); /* Or .png, .svg */
        background-size: cover; /* Or contain, 100% auto, etc. */
        background-repeat: no-repeat; /* Or repeat */
    /* Add other background properties as needed */
    }
    

    .container {
        padding: 20px;
    }

    .login-div {
        background-color: white;
        /* --- Increased Max-Width Here --- */
        max-width: 700px; /* Increased from 600px - adjust as needed */
        /* ------------------------------ */
        width: 100%;
        padding: 60px; /* Keep the increased padding from before */
        border-radius: 25px;
        border: 1px solid black;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        z-index: 1;
        margin: 0 auto;
    }

    /* Styles for the logo container INSIDE the login box */
    .login-div .logo-container {
        text-align: center;
        margin-bottom: 45px;
        background-color:#78A24C;
        border-radius: 20px;
        padding: 10px;
        width: 50%;
    }

    .login-div .logo-container img {
        /* --- Decreased Height Here --- */
        height: 30px; /* Decreased from 60px - adjust as needed */
        /* --------------------------- */
        /* width: auto; /* Maintain aspect ratio */
        display: inline-block;
        vertical-align: middle;
        margin-left: -4px;
        /* Removed background-color and border-radius here if you want it only on container */
        /* background-color:#78A24C; Removed as it's on the container */
        /* border-radius: 20px; Removed as it's on the container */
    }

    h1 {
        font-size: 28px;
        text-align: center;
        margin-bottom: 20px;
    }

    h2 {
        font-size: 16px;
        text-align: center;
        font-weight: normal;
        margin-bottom: 30px;
        color: #666;
    }

    label {
        font-size: 16px; /* Keep the increased label size */
        font-weight: bold;
        margin-bottom: 10px;
        display: block;
    }

    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px 0;
        font-size: 15px; /* Keep the input text size */
        border: none;
        border-bottom: 1px solid #ccc;
        outline: none;
        margin-bottom: 5px;
        background-color: transparent;
        transition: border-color 0.3s ease;
    }

    /* Style placeholder text */
    ::placeholder {
      color: #aaa;
      opacity: 1;
      transition: opacity 0.3s ease-out, transform 0.3s ease-out;
      font-size: 16px; /* Keep the placeholder size */
    }
    ::-webkit-input-placeholder {
      color: #aaa; opacity: 1; transition: opacity 0.3s ease-out, transform 0.3s ease-out; font-size: 16px;
    }
    ::-moz-placeholder {
      color: #aaa; opacity: 1; transition: opacity 0.3s ease-out, transform 0.3s ease-out; font-size: 16px;
    }
    :-ms-input-placeholder {
      color: #aaa; opacity: 1; transition: opacity 0.3s ease-out, transform 0.3s ease-out; font-size: 16px;
    }
    ::-ms-input-placeholder {
      color: #aaa; opacity: 1; transition: opacity 0.3s ease-out, transform 0.3s ease-out; font-size: 16px;
    }

    /* Style inputs on focus */
    input[type="text"]:focus, input[type="password"]:focus {
        border-bottom-color: #78a24c;
    }

    /* Animate placeholder on input focus */
    input[type="text"]:focus::placeholder,
    input[type="password"]:focus::placeholder {
      opacity: 0;
      transform: translateY(-5px);
    }
     input[type="text"]:focus::-webkit-input-placeholder, input[type="password"]:focus::-webkit-input-placeholder { opacity: 0; transform: translateY(-5px); }
     input[type="text"]:focus::-moz-placeholder, input[type="password"]:focus::-moz-placeholder { opacity: 0; transform: translateY(-5px); }
     input[type="text"]:focus:-ms-input-placeholder, input[type="password"]:focus:-ms-input-placeholder { opacity: 0; transform: translateY(-5px); }
     input[type="text"]:focus::-ms-input-placeholder, input[type="password"]:focus::-ms-input-placeholder { opacity: 0; transform: translateY(-5px); }


     .login-btn {
        width: 100%;
        padding: 15px;
        font-size: 18px;
        /* Updated styles to match the image */
        background-color: #f3f6ef; /* Light background (white) */
        color: #007017; /* Green text color (using your existing green color) */
        border: 2px solid #0c8926; /* Green border (using your existing green color) */
        /* --- End Updated styles --- */
        border-radius: 20px; /* Keep rounded corners */
        cursor: pointer;
        margin-top: 20px;
        transition: transform 0.2s ease; /* Added border-color to transition */
    }

    .login-btn:hover {
        transform: translateY(-2px);
    }

     .login-btn:active {
        transform: scale(0.98);
     }

    .email-error-message, .pw-error-message {
        display: none;
        font-size: 14px;
        color: #f7656d;
        margin-top: 5px;
    }

    .showHidePw {
        position: absolute;
        right: 5px;
        bottom: 10px;
        cursor: pointer;
        width: 25px;
        height: auto;
    }

    .input-container {
        position: relative;
        margin-bottom: 20px;
    }

    /* --- Popup Styles (remain the same) --- */
    .overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.2); display: flex; justify-content: center;
        align-items: center; z-index: 50; opacity: 1; visibility: visible;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .popup-error {
        width: 90%; max-width: 400px; background: linear-gradient(145deg, #ffffff, #e6e6e6);
        border-radius: 20px; text-align: center; padding: 20px;
        box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3);
        animation: popupAppear 0.3s ease-out forwards;
    }
    @keyframes popupAppear { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
    .popup-error-message { font-size: 16px; padding: 15px; color: #333; }
    .popup-error button.close {
        background: linear-gradient(145deg, #7bc74d, #5a8e36); border-radius: 20px; border: none;
        color: white; width: 70%; padding: 15px; cursor: pointer; font-weight: bold;
        margin: 10px 0; box-shadow: 0px 4mpx 8px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .popup-error button.close:hover {
        transform: translateY(-2px); box-shadow: 0px 8px 12px rgba(0, 0, 0, 0.4);
        background: linear-gradient(145deg, #8bd85e, #6a9f47);
    }
    .popup-error button.close:active {
        transform: translateY(1px); box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.3);
        background: linear-gradient(145deg, #6aa63f, #4a7d25);
    }
    .error-message-container img { display: block; margin: 15px auto; }
    /* --- End Popup Styles --- */
</style>
</head>
<body>
    <div class="corner-circle-bottom-left"></div>
    <div class="corner-circle-top-right"></div>

    <div class="container">
        <div class="login-div">
        <center><div class="logo-container">
            <img src="User-Logo.png" alt="Green Coin Logo">
        </div></center>

            <form id="login" action="" method="post">
                <h1>Green Coin Admin Login</h1>
                <h2>Support a greener future - Login to manage recycling efforts.</h2>
                <br>
                <div class="input-container">
                    <label class="email">Email</label>
                    <input type="text" name="loginEmail" placeholder="Enter your email" class="inputFields" required>
                    <p class="email-error-message">Please enter your email address.</p>
                </div>
                <div class="input-container">
                    <label class="password">Password</label>
                    <input type="password" name="loginPW" placeholder="Enter your password" class="inputFields" required>
                    <img src="User-HidePasswordIcon.png" class="showHidePw" alt="Show/Hide Password">
                    <p class="pw-error-message">Please enter your password.</p>
                </div>
                <button type="submit" name="login" class="login-btn">Login</button>
            </form>
        </div> </div> <script>
        // --- Form Validation (remains the same) ---
        document.getElementById("login").addEventListener("submit", function(event) {
            const emailInput = document.getElementsByName("loginEmail")[0];
            const emailEM = document.getElementsByClassName("email-error-message")[0];
            const PWInput = document.getElementsByName("loginPW")[0];
            const PWEM = document.getElementsByClassName("pw-error-message")[0];

            // More robust email pattern
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

            let hasError = false;

            // Reset previous errors
            emailEM.style.display = "none";
            emailInput.style.borderBottomColor = "#ccc"; // Reset to default border color
            PWEM.style.display = "none";
            PWInput.style.borderBottomColor = "#ccc"; // Reset to default border color


            if (emailInput.value.trim() === "") {
                emailEM.textContent = "Please enter your email address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottomColor = "red"; // Use color instead of full border style
                hasError = true;
            } else if (!emailPattern.test(emailInput.value.trim())) {
                emailEM.textContent = "Please enter a valid email address.";
                emailEM.style.display = "block";
                emailInput.style.borderBottomColor = "red";
                hasError = true;
            }

            if (PWInput.value.trim() === "") {
                PWEM.textContent = "Please enter your password."; // Added text content
                PWEM.style.display = "block";
                PWInput.style.borderBottomColor = "red";
                hasError = true;
            }

            if (hasError) {
                event.preventDefault(); // Stop form submission if validation fails
            }
        });

        // --- Popup Closing (remains the same) ---
        function closePopup() {
            const popup = document.querySelector(".overlay");
            if (popup) {
                 popup.remove(); // Remove immediately
            }
        }

        // --- Show/Hide Password (remains the same) ---
         const showHidePWIcon = document.getElementsByClassName("showHidePw")[0];
         const pwInputForToggle = document.getElementsByName("loginPW")[0];

         if (showHidePWIcon && pwInputForToggle) {
            showHidePWIcon.addEventListener("click", function() {
                // Determine current base path if needed, otherwise assume images are accessible
                const basePath = ""; // Adjust if images are in a subfolder e.g., "images/"
                if (pwInputForToggle.type === "password") {
                    showHidePWIcon.src = basePath + "User-ViewPasswordIcon.png";
                    pwInputForToggle.type = "text";
                } else {
                    showHidePWIcon.src = basePath + "User-HidePasswordIcon.png";
                    pwInputForToggle.type = "password";
                }
            });
        } else {
            console.error("Could not find password toggle elements.");
        }

        // No other JS needed for label or placeholder animation

    </script>
</body>
</html>