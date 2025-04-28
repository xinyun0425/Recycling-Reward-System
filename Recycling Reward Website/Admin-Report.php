<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @keyframes floatIn {
        0% {
            transform: translateY(-50px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
    }
    
    .main-content {
        overflow-x: hidden;
        padding:20px;
        margin-left:300px;
        width:calc(100% - 350px);
        overflow-y:hidden;
    }

    .header {
        display: flex;
        flex-direction: column;
        align-items: left;
        justify-content: center;
        margin-left: 73px;
        animation: floatIn 0.8s ease-out;
    }
    .sidebar {
        width: 250px;
        height: 100vh; 
        background: #f8f9fa;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        position: fixed;
        overflow-y: auto;
        z-index: 100;
        display: flex;
        flex-direction: column;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin-left: 13px;
        width: 220px;
    }


    .menu li {
        border-radius: 5px;
    }

    .menu li a {
        text-decoration: none;
        color: black;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 10px;
        border-radius: 10px;
    }

    .menu li i{
        color:rgb(134, 134, 134);
        width: 5px;
        padding-right:18px;
    }

    .menu li.active{
        background-color: #E4EBE6;
        border-radius: 10px;
        color:rgb(11, 91, 19);
    }

    .menu a:hover,
    .menu a.active{
        background:#E4EBE6;
        color:rgb(11, 91, 19);
    }

    .menu li.active i,
    .menu li:hover i{
        color:green;
        background-color: #E4EBE6;
    }

    .menu li.active a,
    .menu li:hover a{
        color:rgb(11, 91, 19);
        background-color: #E4EBE6;
    }

    .logout {
        background-color: #fff5f5;
        margin-top: 30px;
        color: #c6433a;
        font-size: 15px;
        border: 2px solid #e2847e;
        box-shadow: none;
        border-radius: 25px;
        padding: 10px 50px;
        width: 100%;
    }
    .logout:hover {
        background-color: rgba(249,226,226,0.91);
        transition:all 0.5s ease;
    }
    .logout i{
        padding-right:10px;
    }
    .report-sections {
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-width: 66vw;
        margin: 0px 70px 0px 70px;
    }

    .report-category {
        display: grid;
        grid-template-columns: 1fr 2fr; 
        align-items: center;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out;
    }

    .report-category:hover {
        transform: translateY(-2px);
    }

    .report-header {
        padding-right: 20px;
    }

    .report-header h2 {
        font-size: 20px;
        color: #333;
        margin-bottom: 10px;
    }

    .report-header i{
        margin-right:8px;
    }
    
    .report-header p {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }

    .report-links a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px; 
        border-bottom: 1px solid #E0E0E0; 
        color: #333;
        text-decoration: none;
        font-size: 16px;
        background: white;
    }

    .report-links a:last-child {
        border-bottom: none; 
    }

    .report-links a i {
        margin-left: 5px;
        color: #666;
    }

    .report-links a:hover {
        color: #437439;
    }

    .report-item {
        transition: all 0.2s ease-in-out;
    }

    .hidden-reports {
        display: none; 
    }

    .show-more-btn {
        color: #007BFF;
        font-weight: bold;
        cursor: pointer;
        padding: 12px 16px;
        display: block;
        text-align: left;
    }

    .show-more-btn:hover {
        color: #007bff;
    }

    .dropdown-menu {
        display: none;
        flex-direction: column;
        margin-top: 5px;
    }

    .dropdown-menu a {
        font-size: 14px;
        padding: 5px 0;
        color: #444;
    }

    .search-container {
        position: relative;
        margin-bottom: 1vw;
        
    }

    .search-container i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #7d8fa1;
    }

    #reportSearch {
        padding: 12px 20px 12px 45px;
        border-radius: 12px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: all 0.3s;
        background-color: white;
        width:61.75vw;
        outline:none;
    }

    hr{
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
        }
</style>
</head>
<body>
<div class="sidebar">
        <div>
        <a href="Admin-Dashboard.php">
            <img src="User-Logo.png" 
                style="width: 200px; margin-bottom: 25px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;">
        </a>
        </div>
        <ul class="menu">
            <li><a href="Admin-Dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="Admin-Notification.php"><i class="fa-solid fa-bell"></i>Notifications</a></li>
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li ><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
            <li><a href="Admin-Review.php"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li class="active"><a href="Admin-Report.php"><i class="fa-solid fa-chart-column"></i>Report</a></li>
            <li><a href="Admin-FAQ.php"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
            <form action="Admin-Logout.php" method="post" style="display:inline;">
                <button type="submit" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </ul>
    </div>
    <div class ="main-content">
        <div class ="header">
            <h2>All Reports</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="reportcontainer" style="padding-bottom:50px;">
            <div class="report-sections">            
                <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="searchbar" id="reportSearch" placeholder="Search reports..." onkeyup="filterReports()">
            </div>
                <div class="report-category">
                    <div class="report-header">
                        <h2><i class="fa-solid fa-truck-ramp-box"></i> Pickup Reports</h2>
                        <p>Analyze pickup trends and performance.</p>
                    </div>
                    <div class="report-links">
                        <a href="Admin-Report-Pickup-PickupRequest.php" class="report-item">Pickup Requests Report</i></a>
                        <a href="Admin-Report-Pickup-Items.php" class="report-item">Pickup Items Report</a>
                        <a href="Admin-Report-Pickup-DriverActivity.php" class="report-item">Driver Activity Report</a>
                    </div>
                </div>
                <div class="report-category">
                    <div class="report-header">
                        <h2><i class="fa-solid fa-map-pin"></i> Drop-off Reports</h2>
                        <p>Monitor drop-off activity and trends.</p>
                    </div>
                    <div class="report-links">
                        <a href="Admin-Report-DropOff-DropOffRequest.php" class="report-item">Drop-off Requests Report</a>
                        <a href="Admin-Report-DropOff-DropOffLocations.php" class="report-item">Drop-off Locations Report</a>                        
                        <a href="Admin-Report-DropOff-DropOffItems.php" class="report-item">Drop-off Items Report</a>
                    </div>
                </div>
                
                <div class="report-category">
                    <div class="report-header">
                        <h2><i class="fa-solid fa-gifts"></i> Reward Reports</h2>
                        <p>Track reward claims and user redemptions.</p>
                    </div>
                    <div class="report-links">
                        <a href="Admin-Report-Reward-ItemRedemptions.php" class="report-item">Reward Redemptions Report </a>
                        <a href="Admin-Report-Reward-UserRedemptionHistory.php" class="report-item">User Redemption History Report</a>
                        <a href="Admin-Report-Reward-UserPoints.php" class="report-item">User Points Report</a>
                    </div>
                </div>
                <div class="report-category">
                    <div class="report-header">
                        <h2><i class="fa-solid fa-users"></i> User Reports</h2>
                        <p>Analyze user activity and engagement.</p>
                    </div>
                    <div class="report-links">
                        <a href="Admin-Report-Users-FirstTimeUsers.php" class="report-item">First Time Users Report</a>
                        <a href="Admin-Report-Users-ReviewTrend.php" class="report-item">Review Report</a>
                        <a href="Admin-Report-Users-ReturningClients.php" class="report-item">Returning Clients Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleDropdown(id) {
            var dropdown = document.getElementById(id);
            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
            } else {
                dropdown.style.display = "block";
            }
        }
        function toggleDropdown(id, btn) {
            var dropdown = document.getElementById(id);
            
            if (dropdown.style.display === "none" || dropdown.style.display === "") {
                dropdown.style.display = "block"; 
                btn.style.display = "none"; 
            }
        }
        function filterReports() {
            let input = document.getElementById("reportSearch").value.toLowerCase();
            let reportCategories = document.querySelectorAll(".report-category");

            if (input === "") {
                reportCategories.forEach(category => {
                    category.style.display = "grid"; 
                    category.querySelectorAll(".report-links a").forEach(report => {
                        report.style.display = "flex"; 
                    });
                });
                return;
            }

            // Filtering logic
            reportCategories.forEach(category => {
                let reports = category.querySelectorAll(".report-links a");
                let hasMatch = false;

                reports.forEach(report => {
                    if (report.textContent.toLowerCase().includes(input)) {
                        report.style.display = "flex"; 
                        hasMatch = true;
                    } else {
                        report.style.display = "none"; 
                    }
                });

                // If there's at least one matching report, keep the category visible
                category.style.display = hasMatch ? "block" : "none"; 
            });
        }

    </script>
</body>

</html>