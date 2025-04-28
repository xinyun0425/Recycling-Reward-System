<?php
    require 'google-api-php-client/vendor/autoload.php'; 
    $servername = "localhost";
    $username = "root";  
    $password = "";  
    $dbname = "cp_assignment";  
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewards Management</title>
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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html{
        background-color:rgba(238, 238, 238, 0.7);
        height:100%;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
        display:flex;
        align-items: flex-start; 
        min-height: 100vh;
    }
    
    .categoryitems {
        display: flex;
        flex-grow: 1; 
        overflow: hidden;
    }
    .header-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left:30px; 
    }
    .header {
        text-align: left;  
        width: 100%;
        margin-top: 20px;
        font-size:1.5em;
        margin-left:20px;
        margin-bottom: 30px; 
        animation: floatIn 0.8s ease-out;
    }

    .main-content {
        display: flex;
        flex-direction: column;
        flex: 1; 
        padding: 40px;
        min-height: auto; 
        overflow-y: auto;
        overflow-x: hidden;
        width: 80% ;
        max-height: 100vh;
    }

    .container {
        display: flex; 
        align-items: stretch; 
        min-height: 100vh; 
    }

    .sidebar {
        width: 250px;
        background: #f8f9fa;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        justify-content: space-between;
        flex-direction: column; 
        display:flex;
        position: relative; 
        min-height: 100vh;
    }

    .profile-container{
        width:100%;
        margin-top:130px;
    }

    .profile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f8f9fa ;
        border-radius: 10px;
        border:2px solid rgba(116, 116, 116, 0.76);
        padding: 10px; 
        width: 93%;
        position: relative;
        margin: 15px;
        box-sizing: border-box;
    }
    .profileicon {
        font-size: 30px;
        color: #333;
    } 

    .profile-info {
        font-size: 14px;
        flex-grow: 1;
        padding-left: 15px;
    }

    .profile-info p {
        margin: 0;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin-left:15px;
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

    .menu li.active
    {
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


    .notificationProfile {
        border: none; 
        background-color: transparent;
        cursor: pointer;        
        position: relative;
        display: flex; 
        align-items: center; 
        justify-content: center;
        width: 40px; 
        height: 40px;  
        border-radius: 50%; 
        font-size: 25px;
        transition: background-color 0.2s ease-in-out;
    }

    .notificationProfile:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        bottom: 100%; 
        background: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        width: 100px; 
        text-align: left;
        z-index: 10;
        padding: 5px 0;
    }


    .dropdown-btn {
        border: none;
        background-color: transparent;
        cursor: pointer;
        font-size: 16px;
    }

    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
        text-align: center;
    }

    .dropdown a:hover {
        background: #E4EBE6;
        color: rgb(11, 91, 19);
    }

    .reward-grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        width: 80%;
        margin-left:20px;
    }

    .reward-item {
        background:#E4EBE6;
        border-radius: 10px;
        text-align: center;
        padding: 10px;
        height:300px;
    }

    .reward-item iframe {
        display: block;
        margin: 0 auto;
        border-radius: 8px;
    }

    .reward-header h4 {
        font-size: 14px;
        margin: 0;
        flex: 1; 
        white-space: normal; 
        overflow-wrap: break-word;
        min-height: 32px; 
    }

    .reward-item p {
        font-size: 12px;
        color: #555;
    }
    .reward-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        margin-top:10px;
        position: relative; 
    }

    .ellipsis-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        color: #555;
    }

    .ellipsis-btn:hover {
        color: #000;
    }

    .reward-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 5px;
    }

    .points, .stocks {
        font-size: 12px;
        color: #555;
        margin: 0;
        white-space: nowrap;
    }
    .itemdropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 10;
        width: 100px;
    }

    .itemdropdown-menu a {
        display: block;
        padding: 8px 12px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
    }

    .itemdropdown-menu a:hover {
        background: #f0f0f0;
    }
    
    .additem-popup-container {
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s;
    }

    .itemdropdown-menu button:hover {
        background: #d9f7be; 
        color: #1d5b1d; 
        border-radius: 5px;
    }

    .categoryBar {
        width: 270px;
        background: #f8f8f8;
        padding: 20px;
        border-right: 2px solid #ddd;
    }

    .categoryBar h3 {
        margin-bottom: 10px;
        color: #8B5E3C;
        border-bottom: solid 1px #DDEAD1;
    }

    .categoryBar ul {
        list-style: none;
        padding: 0;
    }

    .categoryBar li {
        padding: 10px;
        cursor: pointer;
        color: #333;
        font-weight: bold;
    }

    .categoryBar li:hover, .categoryBar li.active {
        color: green;
        border-left:solid 1px #DDEAD1;
    }
    .tab-bar {
        display: flex;
        gap: 1rem;
        background-color: transparent;
        padding: 1rem;
    }

    .tab-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: border-bottom 0.3s, color 0.3s;
        color: #333;
    }

    .tab-btn:hover,
    .tab-btn.active {
        border-bottom: 2px solid #0e612b;
        color: #0e612b;
    }

    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }

</style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div>
            <img src="User-Logo.png" style="width: 200px; margin-bottom: 25px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;" onclick="AdminHomePage()">
        </div>
        <ul class="menu">
            <li><a href="#"><i class="fa-solid fa-house"></i>Home</a></li>
            <li><a href="#"><i class="fa-solid fa-envelope"></i>Inbox</a></li>
            <li><a href="#"><i class="fa-solid fa-truck-moving"></i>Pickup Request</a></li>
            <li><a href="#"><i class="fa-solid fa-map-location-dot"></i>Drop-Off Point</a></li>
            <li><a href="#"><i class="fa-solid fa-arrows-rotate"></i>Processing Status</a></li>
            <li class="active"><a href="#"><i class="fa-solid fa-gift"></i>Reward</a></li>
            <li><a href="#"><i class="fa-solid fa-scroll"></i>Report</a></li>
            <li><a href="#"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li><a href="#"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
        </ul>
        <div class="profile-container" style="position: relative; display: inline-block;">
            <div class="profile">
                <i class="profileicon fa-solid fa-circle-user"></i>
                <div class="profile-info">
                    <p><strong>Adeline Liow</strong></p>
                </div>
                <button class="dropdown-btn" onclick="toggleDropdown(event)">
                    <i class="fa-solid fa-chevron-down"></i>
                </button> 
            </div>
            <div class="dropdown" id="profileDropdown">
                <a href="#"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
    <div class ="main-content">
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('rewards-management')">Rewards Management</button>
            <button class="tab-btn" onclick="switchTab('reward-redemption')">Reward Redemption</button>
        </div>
        <div id="rewards-management" class="tab-content active">
            <h2 class="header">Rewards Management</h2>
            <hr style="width: 98%; margin-bottom:20px;">
            <div class = "categoryitems">
                <div class="categoryBar">
                    <h3>Browse by</h3>
                    <ul class="category-list">
                        <li class="category active" onclick="showContent('all')">All</li>
                        <li class="category" onclick="showContent('Food & Beverage')">Food & Beverage</li>
                        <li class="category" onclick="showContent('Health & Lifestyle')">Health & Lifestyle</li>
                        <li class="category" onclick="showContent('Tech Accessories')">Tech Accessories</li>
                        <li class="category" onclick="showContent('Eco-Friendly Products')">Eco-Friendly Products</li>
                    </ul>
                    <h3 style="margin-top:30px;">Filter by</h3>
                    <ul class="filter-list">
                        <li>
                            <label><input type="checkbox" value="Food & Beverage"> Food & Beverage</label>
                        </li>
                        <li>
                            <label><input type="checkbox" value="Health & Lifestyle"> Health & Lifestyle</label>
                        </li>
                        <li>
                            <label><input type="checkbox" value="Tech Accessories"> Tech Accessories</label>
                        </li>
                        <li>
                            <label><input type="checkbox" value="Eco-Friendly Products"> Eco-Friendly Products</label>
                        </li>
                    </ul>
                </div>
                <div class="reward-grid-container">
                    <?php
                        $query = "SELECT reward_id, reward_name, point_needed, reward_image, quantity, category 
                        FROM reward WHERE status = 'Available'";
                        $result = mysqli_query($conn, $query);

                        if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                        }

                        while ($row = mysqli_fetch_assoc($result)) {
                        $rewardID = $row['reward_id'];
                        $title = $row['reward_name'];
                        $points = $row['point_needed'];
                        $fileID = $row['reward_image']; 
                        $stock = $row['quantity'];
                        $category = strtolower($row['category']); 
                        $embedURL = "https://drive.google.com/file/d/$fileID/preview";

                        echo "<div class='reward-item' data-category='$category'>
                        <iframe src='$embedURL' width='200' height='200' allow='autoplay'></iframe>
                        <div class='reward-header'>
                        <h4>$title</h4>
                        <button class='ellipsis-btn'><i class='fa-solid fa-ellipsis-vertical'></i></button>
                        <div class='itemdropdown-menu'>
                            <button class='edit-btn' 
                                data-id='" . $row["reward_id"] . "' 
                                data-title='" . htmlspecialchars($row["reward_name"], ENT_QUOTES, "UTF-8") . "' 
                                data-points='" . $row["point_needed"] . "' 
                                data-stock='" . $row["quantity"] . "'>
                                Edit
                            </button>
                            <button class='delete-btn' 
                                data-id='" . $row["reward_id"] . "' 
                                data-image-id='" . $row["reward_image"] . "'>
                                Delete
                            </button>
                        </div>
                        </div>
                        <div class='reward-info'>
                        <p class='points'>Points: $points</p>
                        <p class='stocks'>Stock: $stock</p> 
                        </div>
                        </div>";}
                    ?>
                </div>
            </div>        
            <button class="add-btn" onclick="openPopup()">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
        <div class="additem-popup-container" id="additempopup">
            <div class="add-itempopup-content">
                <span class="close-btn" onclick="closePopup()">&times;</span>
                <h2>Add New Reward</h2>
                <form id="addRewardForm" enctype="multipart/form-data">
                    <label for="rewardName">Reward Name:</label>
                    <input type="text" id="rewardName" name="rewardName" required>
                    <label for="rewardPoints">Points Needed:</label>
                    <input type="number" id="rewardPoints" name="rewardPoints" required>
                    <label for="rewardStock">Stock:</label>
                    <input type="number" id="rewardStock" name="rewardStock" required>
                    <label for="rewardCategory">Category:</label>
                    <select id="rewardCategory" name="category" required>
                        <option value="food-beverage">Food & Beverage</option>
                        <option value="health-lifestyle">Health & Lifestyle</option>
                        <option value="tech-accessories">Tech Accessories</option>
                        <option value="eco-friendly">Eco-Friendly Products</option>
                    </select>
                    <label for="rewardImage">Upload Image:</label>
                    <input type="file" id="rewardImage" name="rewardImage" required>
                    <button class="submitbutton" type="submit">Add Reward</button>
                </form>
            </div>
        </div>
        
    </div>
    </div>
    <script>// Switch Tab Function
        function switchTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }



        // Category Filtering Functionality
        document.addEventListener("DOMContentLoaded", function () {
            const categories = document.querySelectorAll(".category");
            const rewardItems = document.querySelectorAll(".reward-item");

            categories.forEach(category => {
                category.addEventListener("click", function () {
                    const selectedCategory = this.textContent.trim().toLowerCase();
                    categories.forEach(cat => cat.classList.remove("active"));
                    this.classList.add("active");
                    rewardItems.forEach(item => {
                        const itemCategory = item.getAttribute("data-category");
                        if (selectedCategory === "all" || itemCategory === selectedCategory) {
                            item.style.display = "block";
                        } else {
                            item.style.display = "none";
                        }
                    });
                });
            });

            // Filter Items by Checkbox
            const filterCheckboxes = document.querySelectorAll(".filter-list input[type='checkbox']");

            filterCheckboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    const selectedCategories = Array.from(filterCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value.toLowerCase());
                    
                    categories.forEach(cat => cat.classList.remove("active"));
                    document.querySelector(".category.active")?.classList.remove("active");

                    rewardItems.forEach(item => {
                        const itemCategory = item.getAttribute("data-category");

                        if (selectedCategories.length === 0 || selectedCategories.includes(itemCategory)) {
                            item.style.display = "block";
                        } else {
                            item.style.display = "none";
                        }
                    });
                });
            });
        });

    
    </script>


</body>
</html>
