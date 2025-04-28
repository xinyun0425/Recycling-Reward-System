<?php
    session_start();

    $user_id = $_SESSION["user_id"] ?? null;

    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $unreadCount = 0; 

    if ($user_id) { 
        $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
        $unreadResult = mysqli_query($conn, $unreadQuery);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $unreadCount = $unreadData['unread_count'];
    }

    if (isset($_GET['page-nr'])){
        $id = $_GET['page-nr'];
    }else{
        $id = 1;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
    <title>Review - Green Coin</title>
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

        .top-container{
            display:flex;
            background-image:url("User-Review-TopContainer-Stickman.svg");
            background-position:top center;
            background-repeat: no-repeat;
            background-size:100%;
            margin:50px 52px;
            padding: 6vh 5vh 12vh;
            border-radius: 30px;
        }

        .title-container{
            margin:auto;
            width: 45%;
        }

        .title-container h1{
            text-align:center;
            margin-bottom:20px;
            font-family: "Playpen Sans", cursive;
            font-size:48px;
            line-height: 2.1;
            letter-spacing: 2px;
        }

        .title-container p{
            font-size: 15px;
            text-align:center;
            font-family: "Playpen Sans", cursive;
        }

        .rating-container{
            width: 25%;
            margin:20px 20px 20px 20px;
            place-items: center;
            border:2px solid rgb(154, 154, 154);
            display:flex;
            flex-direction:column;
            gap:20px;
            border-radius:20px;
            background-color: #fef9d7;
            height:fit-content;
        }

        .average-rating{
            padding:30px 30px 0px;
            
        }

        .rating-number h1{
            text-align:center;
            font-size:40px;
            margin-bottom:5px;
            font-family:"Playpen Sans", sans-serif;
        }

        .rating-number p {
            text-align:center;
            font-family:"Playpen Sans", sans-serif;
            margin-bottom:5px;
        }

        .star-container{
            position:relative;
            display:inline-block;
        }

        .star{
            position:absolute;
            top:0;
            left:0;
            width: 0%;
            overflow:hidden;
        }
        
        .average-rating .star-container::before{
            content:"\2605 \2605 \2605 \2605 \2605";
            color: lightgrey;
            font-size:25px;
        }

        .average-rating .star::before{
            content:"\2605 \2605 \2605 \2605 \2605";
            color: #f8c455;
            font-size:25px;
        }

        .all-rating{
            width: 85%;
            padding:0px 10px 10px;
        }

        .all-rating-div{
            margin: 0px 0px 20px 0px;
        }

        .rating-progress-bar{
            display:flex;
            align-items:center;
            column-gap: 10px;
            justify-content: space-evenly;
            height: 20px;
            margin:5px 0px;
        }

        .progress-star{
            font-size:20px;
            color:rgb(229, 156, 0);
        }

        .progress{
            flex:1 1 0;
            height:12px;
            background-color:white;
            border-radius:25px;
            width: 100%;
            border:1px solid black;
        }

        .bar{
            height:100%;
            background-color: #f8c455;
            border-radius:25px;
        }

        .num-rate{
            padding:10px;
            width: 30px;
            width: 50%;
            text-align:right;
        }
        
        .num-rate p{
            font-family:"Playpen Sans", sans-serif;
        }

        .star-num{
            padding:10px;
            width: 50%;
            text-align:left;
        }
        
        .review-container{
            margin:-15px 0px 10px;
            width: 75%;
            min-height:120vh;
        }

        .review-row{
            /* width: 80vw; */
            margin: 35px auto 10px;
            border-radius:25px;
            border: 2px solid lightgrey;
            display:flex;
            flex-direction:column;
            padding:20px 10px;
        }

        .reply-review{
            /* width: 80vw; */
            margin: 0px auto 5px;
            border-radius:25px;
            border: 2px solid lightgrey;
            display:flex;
            flex-direction:column;
            padding:20px 10px;
            background-color:rgb(231, 231, 231);
        }

        .review-top{
            display:flex;
            flex-direction: row;
            width: 100%;
        }

        .review-profile-div{
            display:flex;
            flex-direction:row;
            width: 85%;
            margin-left:10px;
        }

        .review-profile-img{
            width: 5%;
            margin:5px 20px 0px 10px;
        }

        .review-profile-img img{
            border:1.5px solid black;
            border-radius:50%;
            padding:5px;
        }

        .review-profile-detail{
            width: 80%;
            margin:auto 5px;
        }

        .review-username{
            font-size:16px;
            font-weight:bold;
            padding-bottom:5px;
        }

        .review-item{
            color:grey;
            font-size:12px;
            font-weight:bold;
        }

        .user-star-rating-div{
            position:relative;
            display:inline-block;
            margin-left:20px;
            margin-bottom:5px;
        }

        .user-star-rating-filled{
            overflow:hidden;
            font-size: 25px;
            color: #f8c455;
        }

        .user-star-rating-empty{
            overflow:hidden;
            font-size: 25px;
            color: lightgrey;
        }

        .review-date-div{
            width: 15%;
            margin:20px 20px 20px 0px;
            text-align:right;
        }

        .review-date-div p{
            color:grey;
            font-size:12px;
            font-weight:bold;
        }

        .review-text-div{
            align-items:right;
            place-items:right;
            margin:0px 30px 10px 0px;
            margin-left:20px;
            line-height:1.4;
        }

        .reply-review-top{
            display:flex;
            flex-direction: row;
            width: 100%;
        }

        .reply-review-profile-div{
            display:flex;
            flex-direction:row;
            width: 85%;
            margin-left:10px;
        }

        .reply-review-profile-img{
            width: 5%;
            margin:0px 20px 0px 10px;
            
        }

        .reply-review-profile-img i{
            font-size:25px;
            padding:10px 12px;
            border:2px solid black;
            border-radius:50%;
            background-color:white;

        }

        .reply-review-profile-detail{
            width: 80%;
            margin:auto 5px;
        }

        .reply-review-date-div{
            width: 15%;
            margin:auto 20px auto 0px;
            text-align:right;
        }

        .reply-review-date-div p{
            color:grey;
            font-size:12px;
            font-weight:bold;
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

        .pagination{
            position:relative;
            width: 100%;
            display: flex;
            flex-direction:row;
            margin-bottom:50px;
            margin-top:50px;
            justify-content:center;
            align-items:center;
        }

        .pagination a , .page-numbers a{
            padding: 8px 16px;
            margin: 5px;
            color:rgb(212, 212, 212);
            text-decoration: none;
            border-radius: 5px;
            font-weight:bold;
            font-family:"Playpen Sans", sans-serif;
        }

        .page-numbers a:hover {
            color: grey !important;
        }

        .pagination a:hover {
            color: grey;
        }

        .selected-page{
            color:black !important;
            pointer-events: none;
        }

    </style>
</head>
<body>
    <input type="hidden" value="<?php echo $id; ?>" class="page-id">
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a  onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
            <li><a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
            <li><a onclick="window.location.href='User-Rewards.php'">Rewards</a></li>
            <li><a class="active" onclick="window.location.href='User-Review.php'">Review</a></li>
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
    
    <button id="scrollTopBtn">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <div class="top-container">
        <div class="title-container">
            <h1>Review</h1>
            <p>
                See what our users have to say about their e-waste recycling experience! <br>
                Read real reviews from people who have used our platform to locate drop-off points, 
                track their impact, and earn rewards for responsible recycling.
            </p>
        </div>
    </div>
    <div style="display:flex; flex-direction:row; gap:40px; margin:auto; width:82%;">
        <div class="rating-container">
            <div class="average-rating">
                <div class="rating-number">
                    <h1></h1>
                    <p></p>
                </div>
            </div>
            <div class="all-rating"></div>
            <div class="star-container">
                <div class="star"></div>
            </div>
        </div>
        <div class="review-container">
            <?php 
                $start = 0;
                $rows_per_page = 3;
                $countRowInReviewQuery = mysqli_query($conn, "SELECT * FROM review");
                $totalRowsInReview = mysqli_num_rows($countRowInReviewQuery);
                
                $pages = ceil($totalRowsInReview / $rows_per_page);

                if ((isset($_GET['page-nr']))){
                    $page = $_GET['page-nr'] - 1;
                    $start = $page * $rows_per_page;
                }

                $getReviewQuery = mysqli_query($conn, "SELECT * FROM review ORDER BY date DESC LIMIT $start, $rows_per_page");
                while($getReviewResult = mysqli_fetch_assoc($getReviewQuery)){
                    $rating = $getReviewResult['star'];
                    $filled = str_repeat('★', $rating);
                    $empty = str_repeat('★', 5 - $rating);

                    if ($getReviewResult['pickup_request_id'] != null){
                        $pickupRequestID = $getReviewResult['pickup_request_id'];
                        $getReviewUserQuery = mysqli_query($conn, "SELECT u.username AS username, u.profile_image AS profileImg FROM pickup_request pr 
                                                                    INNER JOIN user u ON pr.user_id = u.user_id
                                                                    INNER JOIN item_pickup ipr ON pr.pickup_request_id = ipr.pickup_request_id 
                                                                    WHERE pr.pickup_request_id = '$pickupRequestID'"); 

                        $getReviewItemQuery = mysqli_query($conn, "SELECT i.item_name AS itemName, ipr.quantity AS Quantity FROM item_pickup ipr
                                                                    INNER JOIN item i ON ipr.item_id = i.item_id 
                                                                    WHERE ipr.pickup_request_id = '$pickupRequestID'");
                    }else if ($getReviewResult['dropoff_id'] != null){
                        $dropoffID = $getReviewResult['dropoff_id'];
                        $getReviewUserQuery = mysqli_query($conn, "SELECT u.username AS username, u.profile_image AS profileImg FROM dropoff dr 
                                                                    INNER JOIN user u ON dr.user_id = u.user_id
                                                                    INNER JOIN item_dropoff idr ON dr.dropoff_id = idr.dropoff_id
                                                                    WHERE dr.dropoff_id = '$dropoffID'"); 

                        $getReviewItemQuery = mysqli_query($conn, "SELECT i.item_name AS itemName, idr.quantity AS Quantity FROM item_dropoff idr
                                                                    INNER JOIN item i ON idr.item_id = i.item_id 
                                                                    WHERE idr.dropoff_id = '$dropoffID'");
                    }
                    $getReviewUserResult = mysqli_fetch_assoc($getReviewUserQuery);
                    echo '<div class="userReview-ReplyReview-div">';
                        echo '<div class="review-row">';
                            echo '<div class="review-top">';
                                echo '<div class="review-profile-div">';
                                    echo '<div class="review-profile-img">';
                                        echo '<img src="'.$getReviewUserResult['profileImg'].'" width="50">';
                                    echo '</div>';
                                    echo '<div class="review-profile-detail">';
                                        echo '<h3 class="review-username">'.$getReviewUserResult['username'].'</h3>';
                                        echo '<p class="review-item">';
                                            $count = 1;
                                            while($getReviewItemResult = mysqli_fetch_assoc($getReviewItemQuery)){
                                                if ($count == 1){
                                                    echo '<span>'.$getReviewItemResult['itemName'].' (x'.$getReviewItemResult['Quantity'].')<span>';
                                                }else{
                                                    echo '<span>, '.$getReviewItemResult['itemName'].' (x'.$getReviewItemResult['Quantity'].')<span>';
                                                }
                                                $count += 1;
                                            }
                                        echo '</p>';
                                    echo '</div>';
                                echo '</div>';
                                echo '<div class="review-date-div">';
                                    echo '<p>'.$getReviewResult['date'].'</p>';
                                echo '</div>';
                            echo '</div>';
                            echo '<div class="user-star-rating-div">';
                                echo '<div> <span class="user-star-rating-filled">'. $filled .'</span><span class="user-star-rating-empty">'. $empty .'</span></div>';
                            echo '</div>';
                            echo '<div class="review-text-div">';
                                echo '<p>'.$getReviewResult['review'].'</p>';
                            echo '</div>';
                        echo '</div>';
                        $reviewID = $getReviewResult['review_id'];
                        $getReplyReviewQuery = mysqli_query($conn, "SELECT * FROM reply_review WHERE review_id = '$reviewID'");
                        while ($getReplyReviewResult = mysqli_fetch_assoc($getReplyReviewQuery)){
                            echo '<div class="reply-review">';
                                echo '<div class="reply-review-top">';
                                    echo '<div class="reply-review-profile-div">';
                    
                                    
                                        echo '<div class="reply-review-profile-img">';
                                            echo '<center><i class="fa-solid fa-user-tie"></i></center>';
                                        echo '</div>';
                                        echo '<div class="reply-review-profile-detail">';
                                            echo '<h3 class="review-username" style="padding:0;">Green Coin</h3>';
                                        echo '</div>';
                                    echo '</div>';
                                    echo '<div class="reply-review-date-div">';
                                        echo '<p>'.$getReplyReviewResult['date'].'</p>';
                                    echo '</div>';
                                echo '</div>';
                                echo '<div class="review-text-div" style="margin-top:15px;">';
                                    echo '<p>'.$getReplyReviewResult['review'].'</p>';
                                echo '</div>';
                            echo '</div>';
                        }
                    echo '</div>';
                }
                $range = 2;
                $id = isset($_GET['page-nr']) ? (int)$_GET['page-nr'] : 1;
                echo '<div class="pagination">';
                    echo '<div>';
                        echo '<a href="?page-nr=1"> << </a>';
                    echo '</div>';

                    echo '<div>';
                        if (isset($_GET['page-nr']) && $_GET['page-nr'] > 1){
                            $previous_page = $_GET['page-nr'] - 1; 
                            echo '<a href="?page-nr='.$previous_page.'"> < </a>';
                        }else{
                            echo '<a> < </a>';
                        }
                    echo '</div>';

                    if ($pages > 5) {
                        if ($id > $range + 2) {
                            echo '<a href="?page-nr=1">1</a>';
                            echo '<span>...</span>';
                        }
                    }          

                    echo '<div class="page-numbers">';
                        for ($i = max(1, $id - $range); $i <= min($pages, $id + $range); $i++) {
                            // for($i = 1; $i <= $pages; $i++) {
                                if ($i == $id) {
                                    echo '<a class="selected-page" href="?page-nr='.$i.'">'.$i.'</a>'; // Highlight current page
                                } else {
                                    echo '<a href="?page-nr='.$i.'">'.$i.'</a>';
                                }
                            // }
                        }
                    echo '</div>';

                    if ($pages > 5 && $id < $pages - $range - 1) {
                        echo '<span>...</span>';
                        echo '<a href="?page-nr=' . $pages . '">' . $pages . '</a>';
                    }
                    
                
                    echo '<div>';
                        if (!isset($_GET['page-nr'])){
                            $next_page = 2; 
                            echo '<a href="?page-nr='.$next_page.'"> > </a>';
                        }else{
                            if ($_GET['page-nr'] >= $pages){
                                echo '<a > > </a>';
                            }else{
                                $next_page = $_GET['page-nr'] + 1;
                                echo '<a href="?page-nr='.$next_page.'"> > </a>';
                            }
                        }
                        echo '</div>';
                
                    echo '<div>';
                        echo '<a href="?page-nr='.$pages.'"> >> </a>';
                    echo '</div>';
                echo '</div>';
            ?>
        </div>
    </div>
    <div class=“stickman-container”>
        <img style="margin-top: -700px; margin-bottom: -4px; margin-left:50px;position:relative;" src ="User-Review-ContentBottom-Stickman.svg" width="450">
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
    <?php
        $star1_num_Query = mysqli_query($conn, "SELECT count(*) AS count FROM review WHERE star = '1' ");
        $star2_num_Query = mysqli_query($conn, "SELECT count(*) AS count FROM review WHERE star = '2' ");
        $star3_num_Query = mysqli_query($conn, "SELECT count(*) AS count FROM review WHERE star = '3' ");
        $star4_num_Query = mysqli_query($conn, "SELECT count(*) AS count FROM review WHERE star = '4' ");
        $star5_num_Query = mysqli_query($conn, "SELECT count(*) AS count FROM review WHERE star = '5' ");
        
        $star1_num = mysqli_fetch_assoc($star1_num_Query)['count'] ?? 0;
        $star2_num = mysqli_fetch_assoc($star2_num_Query)['count'] ?? 0;
        $star3_num = mysqli_fetch_assoc($star3_num_Query)['count'] ?? 0;
        $star4_num = mysqli_fetch_assoc($star4_num_Query)['count'] ?? 0;
        $star5_num = mysqli_fetch_assoc($star5_num_Query)['count'] ?? 0;

        $star1_num = $star1_num ?: 0;
        $star2_num = $star2_num ?: 0;
        $star3_num = $star3_num ?: 0;
        $star4_num = $star4_num ?: 0;
        $star5_num = $star5_num ?: 0;
    ?>

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

        
        let data = [
            {'star': 5, 'count': <?php echo $star5_num; ?>},
            {'star': 4, 'count': <?php echo $star4_num; ?>},
            {'star': 3, 'count': <?php echo $star3_num; ?>},
            {'star': 2, 'count': <?php echo $star2_num; ?>},
            {'star': 1, 'count': <?php echo $star1_num; ?>}
        ];

        let total_rating = 0;
        let rating_based_on_stars = 0;
        data.forEach(rating =>{
            total_rating += rating.count;
            rating_based_on_stars += rating.count * rating.star;
        });

        data.forEach(rating =>{
            let rating_progress = `
                <div class="all-rating-div">
                    <div class="rating-progress-bar">
                    <p class="star-num">
                        ${"<span class='progress-star'>★</span>".repeat(rating.star)} 
                    </p>
                        <div class="num-rate"><p>${rating.count.toLocaleString()}</p></div>
                    </div>
                    <div class="progress">
                        <div class="bar" style="width:${(rating.count / total_rating) * 100}%"></div>
                    </div>
                </div>
            `;
            document.querySelector('.all-rating').innerHTML += rating_progress;
        });

        let rating_average = (rating_based_on_stars / total_rating).toFixed(1);
        document.querySelector('.rating-number h1').innerHTML = rating_average;
        document.querySelector('.rating-number p').innerHTML = total_rating.toLocaleString() + "  reviews";
        document.querySelector('.star').style.width = (rating_average / 5) * 100 + "%";
        

        let links = document.querySelectorAll('.page-numbers > a');
        let bodyIdElement = document.querySelector('.page-id');
        let bodyId = bodyIdElement ? parseInt(bodyIdElement.value) - 1 : 0; 
        if (bodyId >= 0 && bodyId < links.length) {
            links.forEach(link => link.style.color = "rgb(212, 212, 212)");
        }

        localStorage.setItem('activeTabIndex', 0);

    </script>
</body>
</html>