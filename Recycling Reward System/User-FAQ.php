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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=add" />
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
        font-family: "Open Sans", sans-serif;
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

    .faq-container{
        width: 100%;
        padding: 50px;
    }
    
    .faq-header{
        background-image: url('User-FAQ-Header-Stickman.svg');
        background-position: top center;
        background-repeat: no-repeat;
        background-size: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* padding: 50px 30px 60px 30px; */
        padding: 6vh 5vh 12vh;
        border-radius: 30px;
        max-height: 1000px;
        color: black;
    }

    .faq-header-title{
        font-size: 48px;
        font-family: "Playpen Sans", cursive;
        line-height: 1.7;
        letter-spacing: 3px;
    }

    .faq-header-desc{
        font-size: 15px;
        text-align: center;
        font-family: "Playpen Sans", cursive;
        letter-spacing: 1px;
    }

    .search{
        width: 600px;
        height: 50px;
        background-color: white;
        margin-top: 60px;
        border-radius: 30px;
        display: flex;
        justify-content: space-between;
        padding: 0px;
    }

    .search input{
        width: 80%;
        height: 50px;
        padding: 10px 30px;
        background: transparent;
        border: none;
        font-size: 15px;
        outline: none;
    }

    .search button{
        width: 100px;
        height: 40px;
        margin: 5px 5px;
        background-color:rgb(209, 137, 42);
        color: white;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-size: 14px;
    }

    .accordion-container{
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-direction: row;
        padding: 60px 10%;
        gap: 30px;
    }

    .category-list{
        width: 25%;
    }

    .category-list p{
        font-family: "Playpen Sans", cursive;
        font-weight: bold;
        color: black;
        font-size: 17px;
        line-height: 1.9;
    }

    .category-list a{
        font-family: "Playpen Sans", cursive;
        display: block;
        text-decoration: none;
        color: black;
        margin-bottom: 5px;
        position: relative;
        transition: 0.3 ease;
        font-size: 15px;
        color:rgb(76, 76, 76);
        line-height: 2.4;
    }

    .category-list a:before{
        content:'';
        height: 16px;
        width: 3px;
        position: absolute;
        top: 10px;
        left: -10px;
        background-color: green;
        transition: 0.3 ease;
        opacity: 0;
    }

    .category-list a:hover::before{
        opacity: 1;
    }

    .category-list a:hover{
        transform: translateX(-4px);
        color: green;
    }

    .category-list a.active{
        transform: translateX(-4px);
        color: green;
    }

    .category-list a.active::before {
        opacity: 1;
    }

    .accordion {
        display: flex;
        flex-direction: column;
        width: 75%; 
    }

    .category-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px; 
        font-size: 23px;
        color: rgb(158, 102, 19); 
    }

    .category-title i {
        font-size: 30px;
        height: auto;
        color: rgb(180, 139, 78); 
    }

    .faq{
        margin-top: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgb(208, 208, 208);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .question{
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
        cursor: pointer;
    }

    .question h3{
        font-size: 18px;
        line-height: 2.0;
        flex: 1;
    }

    .answer{
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-in-out, opacity 0.5s ease-in-out;
    }

    .answer p{
        padding-top: 10px;
        line-height: 1.7;
        font-size: 15px;
    }

    .faq.active .answer{
        max-height: 500px;
        opacity: 1;
    }

    .faq.active i{
        transform: rotate(180deg);
    }

    .faq i{
        font-size: 18px;
        line-height: 2.0;
        color: rgb(209, 137, 42);
        transition: transform 0.5s ease-in-out;
    }
    
    @keyframes fade {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to{
            opacity: 1;
            transform: translateY(0px);
        }
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
</style>

<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
            <li><a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
            <li><a onclick="window.location.href='User-Rewards.php'">Rewards</a></li>
            <li><a onclick="window.location.href='User-Review.php'">Review</a></li>
            <li><a class="active" onclick="window.location.href='User-FAQ.php'">FAQ</a></li>
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
                console.log("Login Check Response:", isLoggedIn); 

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


    <div class="faq-container">
        <div class="faq-header">
            <h1 class="faq-header-title">FAQ</h1>
            <p class="faq-header-desc">Frequently Asked Questions</p>
            <div class="search">
                <input type="text" id="search_faq" onkeyup="filterFAQ()" placeholder="Search...">
                <button>Search</button>
            </div>
        </div>

        <div class="accordion-container">
            
            <div class="category-list">
                <p>Table of Contents</p>
                <br>
                <a href="#" data-category="All" class="category-link active">All</a>
                <a href="#" data-category="General" class="category-link">General</a>
                <a href="#" data-category="Pickup Scheduling" class="category-link">Pickup Scheduling</a>
                <a href="#" data-category="Drop-off Points" class="category-link">Drop-off Points</a>
                <a href="#" data-category="Rewards" class="category-link">Rewards</a>
            </div>

            <div class="accordion">
                <div class="category-title">
                    <i id="category-icon" class="fa-solid fa-layer-group"></i>
                    <h2 id="category-name">All</h2>
                </div>

                <?php
                    $faq = "SELECT faq_id, question, answer, category FROM faq ORDER BY category;";       
                    $result = mysqli_query($con, $faq);
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                    <div class="faq" data-category="<?php echo $row['category']; ?>">
                        <div class="question">
                            <h3><?php echo $row['question']; ?></h3>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="answer">
                            <p><?php echo $row['answer']; ?></p>
                        </div>
                    </div>
                <?php
                    }
                    mysqli_close($con);
                ?>
            </div>
        </div>
    </div>

    <div class=“stickman-container”>
        <img style="margin-left: 650px; margin-top: -30px; margin-bottom: -4px;" src ="User-FAQ-Content-Stickman.svg" width="450">
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const categoryLinks = document.querySelectorAll(".category-link");
            const faqs = document.querySelectorAll(".faq");
            const categoryTitle = document.getElementById("category-name");
            const categoryIcon = document.getElementById("category-icon");

            const categoryData = {
                "All": { title: "All", iconClass: "fa-solid fa-layer-group" },
                "General": { title: "General", iconClass: "fa-book-open" },
                "Pickup Scheduling": { title: "Pickup Scheduling", iconClass: "fa-truck-moving" },
                "Drop-off Points": { title: "Drop-off Points", iconClass: "fa-map-location-dot" }, 
                "Rewards": { title: "Rewards", iconClass: "fa-gift" }
            };

            categoryLinks.forEach(link => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    const selectedCategory = this.getAttribute("data-category");

                    categoryLinks.forEach(link => link.classList.remove("active"));
                    this.classList.add("active");

                    if (categoryData[selectedCategory]) {
                        categoryTitle.textContent = categoryData[selectedCategory].title;
                        categoryIcon.className = `fa-solid ${categoryData[selectedCategory].iconClass}`;
                    }

                    faqs.forEach(faq => {
                        if (selectedCategory === "All" || faq.getAttribute("data-category") === selectedCategory) {
                            faq.style.display = "block";
                        } else {
                            faq.style.display = "none";
                        }
                    });
                });
            });

            document.querySelectorAll(".faq .question").forEach(question => {
                question.addEventListener("click", function () {
                    const faq = this.parentElement;
                    const answer = faq.querySelector(".answer");

                    if (faq.classList.contains("active")) {
                        faq.classList.remove("active");
                        answer.style.maxHeight = "0"; 
                        answer.style.opacity = "0";
                    } else {
                        document.querySelectorAll(".faq").forEach(item => {
                            item.classList.remove("active");
                            item.querySelector(".answer").style.maxHeight = "0";
                            item.querySelector(".answer").style.opacity = "0";
                        });

                        faq.classList.add("active");
                        answer.style.maxHeight = answer.scrollHeight + "px"; 
                        answer.style.opacity = "1";
                    }
                });
            });

            categoryTitle.textContent = categoryData["All"].title;
            categoryIcon.src = categoryData["All"].icon;
        });

        function filterFAQ() {
            var input, filter, faqs, question, i, txtValue;
            input = document.getElementById("search_faq");
            filter = input.value.toUpperCase();
            faqs = document.querySelectorAll(".faq");

            document.getElementById("category-name").textContent = "All";
            document.getElementById("category-icon").src = "icons/all.svg";

            document.querySelectorAll(".category-link").forEach(link => link.classList.remove("active"));
            document.querySelector('.category-link[data-category="All"]').classList.add("active");

            for (i = 0; i < faqs.length; i++) {
                question = faqs[i].querySelector(".question h3");
                if (question) {
                    txtValue = question.textContent || question.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        faqs[i].style.display = "block"; 
                    } else {
                        faqs[i].style.display = "none"; 
                    }
                }
            }
        }
        localStorage.setItem('activeTabIndex', 0);
    </script>
</body>
</html>