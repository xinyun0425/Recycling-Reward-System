<?php
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: Admin-Login.php'); 
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <style>
        /* --- Base Styles --- */
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
            background-color: rgba(238, 238, 238, 0.7);
        }

        /* Keep @keyframes floatIn as it's used for header animation */
        @keyframes floatIn {
            0% { transform: translateY(-50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        /* --- Layout Container --- */
        /* This might not be strictly needed with fixed sidebar and margin on main-content, but keep for potential future layout changes */
        .container {
            display: flex;
            /* height: 100%; Removed as 100vh on sidebar/main-content handles height */
            width: 100%;
            overflow: hidden;
        }

        /* --- Sidebar Styles (Keep your existing sidebar styles) --- */
        .sidebar {
            width: 250px;
            height: 100vh; /* Use 100vh for full viewport height */
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
            margin-left: 13px; /* Adjust if needed based on overall sidebar padding */
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

        .menu li i {
            color: rgb(134, 134, 134);
            width: 5px; /* Adjusted width */
            padding-right: 18px; /* Adjusted padding */
            text-align: center; /* Center the icon within the allocated width */
        }

        .menu li.active {
            background-color: #E4EBE6;
            border-radius: 10px;
            color: rgb(11, 91, 19);
        }

        .menu a:hover,
        .menu li:hover a { /* Apply hover to the link inside li */
            background: #E4EBE6;
            color: rgb(11, 91, 19);
        }

        .menu li.active i,
        .menu li:hover i { /* Apply hover to the icon inside li */
            color: green;
            /* Removed background-color here as it should be on the li or a */
        }

        .menu li.active a,
        .menu li:hover a { /* Keep these styles */
            color: rgb(11, 91, 19);
            background-color: #E4EBE6;
        }

        /* --- Main Content Styles --- */
        .main-content {
            overflow-x: hidden;
            padding: 20px; /* Keep padding for overall content area */
            margin-left: 300px; /* Adjust margin to account for fixed sidebar width + some space */
            width: calc(100% - 320px); /* Adjust width to account for margin and padding */
            overflow-y: auto;
            min-height: 100vh; /* Ensure main content takes at least full viewport height */
            box-sizing: border-box; /* Include padding in the width calculation */
        }

        /* --- Header Styles (Adjusted margin-left) --- */
        .header {
            text-align: left;
            width: 100%; /* Use 100% for the header itself */
            font-size: 1.5em;
            margin-bottom: 28px;
            animation: floatIn 0.8s ease-out forwards; /* Added forwards to keep the final state */
            color: black;
            /* Removed margin-left: 50px; as it was inconsistent with HR */
            padding-left: 0; /* Remove padding-left added previously */
            box-sizing: border-box;
            /* Align the header text visually with the HR and notifications */
            /* The HR starts 45px from the main-content's inner left edge */
            padding-left: 75px; /* Align header text with HR/notifications */
            margin-left: 0; /* Remove the old margin-left */
        }

        .header i {
            font-size: 1.0em;
            margin-right: 20px;
            color: rgb(134, 134, 134);
            cursor: pointer;
        }

        /* --- HR Styles --- */
        hr {
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
            /* Keep margin as it defines the HR's position and width */
            margin: 0 45px 20px;
            width: calc(100% - 90px);
        }

        /* --- Logout Button Styles (Keep your existing logout styles) --- */
        .logout {
            background-color: #fff5f5;
            margin-top: 30px; /* Push the logout button to the bottom of the flex container */
            color: #c6433a;
            font-size: 15px;
            border: 2px solid #e2847e;
            box-shadow: none;
            border-radius: 25px;
            padding: 10px 50px;
            width: 100%;
            cursor: pointer;
             box-sizing: border-box; /* Include padding in width */
        }
        .logout:hover {
            background-color: rgba(249, 226, 226, 0.91);
            transition: all 0.5s ease;
        }
        .logout i {
            padding-right: 10px;
        }

        /* --- Notifications Container Styles (Adjusted padding for alignment) --- */
        .notifications {
             /* Adjust padding to align content with HR (HR has 45px left/right margin) */
             padding: 0 75px 20px 75px; /* Top, Right, Bottom, Left */
            /* Removed background-color and border-radius from the sorting container */
        }

        /* Removed sorting-options and sorting-select styles as the HTML is removed */
        /*
        .sorting-options { margin-bottom: 20px; }
        .sorting-select { ... }
        .sorting-select:hover { ... }
        .sorting-select:active { ... }
        .sorting-select:focus { ... }
        */

        /* --- Notification List Styles (Matching image_76377d.png) --- */
        #notification-list {
            list-style-type: none;
            padding: 0; /* Remove default list padding */
            margin: 0; /* Remove default list margin */
            display: flex; /* Use flexbox to stack list items vertically */
            flex-direction: column;
            gap: 15px; /* Space between notification cards (adjust if needed) */
            width: 100%; /* Take full width of parent (.notifications) */
            box-sizing: border-box;
        }

        #notification-list li {
            margin: 0; /* Remove default list item margin */
            padding: 0; /* Remove default list item padding */
            display: block;
            width: 100%; /* Ensure each list item takes the full width */
            box-sizing: border-box;
             margin-bottom: 0; /* Gap is handled by #notification-list gap */
        }


        .notification-card {
            background-color: white;
            border: 2px solid #ddd;
            border-radius: 20px;
            padding: 25px; /* Padding inside the card */
            display: flex; /* Use flexbox for layout within the card */
            flex-direction: column; /* Stack header and body vertically */
            width: 100%; /* Card takes full width of its list item */
            box-sizing: border-box;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            /* border-bottom: 1px solid #ddd9d9; */
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: white;
            border-radius: 50%;
            flex-shrink: 0;
            border:2px solid #c7c7c7;
            padding:5px;
        }

        .username {
            font-weight: bold;
            color: #333;
            font-size: 16px;
            word-break: break-word; /* Prevent long usernames from overflowing */
            margin-left:5px;
        }

        .timestamp {
            font-size: 0.8em;
            color: #777;
            flex-shrink: 0; /* Prevent timestamp from shrinking */
            margin-left: 10px;
             white-space: nowrap; /* Keep timestamp on a single line */
        }

        .card-body {
             /* No specific flex/grid needed unless you want complex inner layout */
        }

        /* Style for the title bar (text content is inside <strong> in your PHP) */
        .title-bar {
            margin-bottom: 10px;
            font-size: 20px;
            color: #333; /* Match title text color */
            font-weight: 500; /* Make the title bold */
        }

         /* Removed specific styling for strong inside title-bar if title-bar itself is bold */
        /* .title-bar strong { ... } */


        /* Style for the container of content lines */
        .content-lines {
    margin-top: 8px; /* Space above the content lines */
    /* Add some padding if you want space around the announcement within the body */
    padding: 5px 0;
    margin-left: 0px;
}
.content-lines .line {
    color: #555; /* Default text color */
    font-size: 16px; /* Increased font size for the announcement */
    margin-bottom: 0; /* Removed bottom margin as it's the only line now */
    line-height: 1.6; /* Improved line height for readability */
    word-break: break-word;
    display: block; /* Changed from flex to block as it's just one span now */
    /* Removed align-items and gap as they are not needed with display: block */
}
.content-lines .line span {
    color: #555; /* Keep text color */
     font-size: 1em; /* Ensure the span inherits or matches the parent line size */
     flex-grow: 1; /* Removed as display is block */
}
        .no-notifications {
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: #555;
            /* Add margin/padding to center it visually if needed */
             margin: 20px auto;
             max-width: 300px;
             background-color: #fff;
             box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        /* Add any other styles needed for your layout */

        /* --- Modal Styles (Assuming you have a logout modal somewhere) --- */
        /* You mentioned a logout modal earlier, include its styles here if they aren't already */
         .modal {
             display: none; /* Hidden by default */
             position: fixed; /* Stay in place */
             z-index: 1; /* Sit on top */
             left: 0;
             top: 0;
             width: 100%; /* Full width */
             height: 100%; /* Full height */
             overflow: auto; /* Enable scroll if needed */
             background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
             justify-content: center; /* Center content */
             align-items: center; /* Center content */
         }

         .modal-container {
             background-color: #fefefe;
             margin: auto;
             padding: 20px;
             border: 1px solid #888;
             width: 80%; /* Could be responsive width */
             max-width: 400px; /* Max width */
             border-radius: 10px;
             text-align: center;
             box-shadow: 0 4px 8px rgba(0,0,0,0.1);
         }

         .modal-container h2 {
             margin-top: 0;
             color: #333;
         }

         .modal-container p {
             margin-bottom: 20px;
             color: #555;
         }

         .modal-buttons {
             display: flex;
             justify-content: center;
             gap: 15px; /* Space between buttons */
         }

         .modal-buttons button, .modal-buttons a {
             padding: 10px 20px;
             border: none;
             border-radius: 5px;
             cursor: pointer;
             font-size: 1em;
             text-decoration: none; /* For links */
             display: inline-block; /* For links */
             text-align: center;
         }

         .modal-buttons .cancel-btn {
             background-color: #ccc;
             color: #333;
         }

         .modal-buttons .cancel-btn:hover {
             background-color: #bbb;
         }

         .modal-buttons .confirm-btn {
             background-color: #d9534f; /* Red for danger */
             color: white;
         }

         .modal-buttons .confirm-btn:hover {
             background-color: #c9302c;
         }
        

    .datetime{
        color:rgb(102, 102, 102);
        font-size: 14px;
    }

    .reviewText{
        margin-left: 6px;
    }


    </style>
</head>
<body>
    <div class="sidebar">
        <div>
        <img src="User-Logo.png" style="width: 200px; margin-bottom: 25px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;" onclick="window.location.href='Admin-Dashboard.php';">
        </div>
        <ul class="menu">
            <li class="active"><a href="Admin-Dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="Admin-Notification.php"><i class="fa-solid fa-bell"></i>Notifications</a></li>
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li>
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
            <li><a href="Admin-Review.php"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li><a href="Admin-Report.php"><i class="fa-solid fa-chart-column"></i>Report</a></li>
            <li><a href="Admin-FAQ.php"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
            <form action="Admin-Logout.php" method="post" style="display:inline;">
            <button type="submit" class="logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </button>
        </form>
        </ul>
    </div>


    <div class="main-content">
        <h2 class="header">
            <a href='Admin-Notification.php' style='text-decoration: none; color: inherit;'>
            </a>
            Notifications
        </h2>
        <hr> <div class="notifications">
            <ul id="notification-list">
                 <li><div class="no-notifications">Loading notifications...</div></li>
            </ul>
        </div>


    </div>

    <div id="logoutModal" class="modal">
         <div class="modal-container">
             <h2>Confirm Logout</h2>
             <p>Are you sure you want to log out?</p>
             <div class="modal-buttons">
                 <button class="cancel-btn">Cancel</button>
                 <a href="Admin-Login.php" class="confirm-btn">Logout</a> </div>
         </div>
     </div>


    <script>
        // --- Global Helper Functions ---

        // Function to activate the clicked link in the sidebar
        function activateLink(linkElement) {
            const items = document.querySelectorAll('.menu li');
            items.forEach(item => item.classList.remove('active')); // Remove active from all
            const parentLi = linkElement.closest('li');
            if(parentLi) {
               parentLi.classList.add('active'); // Add active to the parent li of the clicked link
            }
        }

        // Function to set the initial active sidebar link based on URL
        function setActivePage() {
            // Get the last part of the current URL path (e.g., "Admin-Notification.php")
            const currentPath = window.location.pathname.split('/').pop();
            const menuLinks = document.querySelectorAll('.menu a');

            menuLinks.forEach(link => {
                // Get the last part of the link's href (e.g., "Admin-Notification.php")
                const linkPath = link.getAttribute('href').split('/').pop();

                // Check if the link path matches the current path (and path is not empty)
                // Or if the current path is empty (root) and the link is for the homepage
                if ((linkPath === currentPath && currentPath !== '') || (currentPath === '' && linkPath === 'Admin-Dashboard.php')) { // Assuming Admin-Dashboard is the default/homepage
                     activateLink(link);
                }
            });
        }


        // --- Fetch Notifications Function (Modified to remove sorting logic) ---
        function fetchNotifications() {
            // Removed the code that gets the sort-by select value

            const xhr = new XMLHttpRequest();
            // Always request with default sorting (datetime DESC) - your PHP should handle this if parameters are missing
            const url = 'Admin-Fetch-notification.php'; // Removed query parameters

            xhr.open('GET', url, true);

            xhr.onload = function() {
                const notificationList = document.getElementById('notification-list'); // Get element inside onload
                if (notificationList) { // Check if element exists
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // If status is OK, replace the content with the response
                         notificationList.innerHTML = xhr.responseText;
                    } else {
                        // Log and show error on the page
                        console.error('Failed to fetch notifications. Status:', xhr.status);
                         notificationList.innerHTML = '<li><div class="no-notifications">Error fetching notifications. Status: ' + xhr.status + '</div></li>';
                    }
                    const reviewTextDiv = document.querySelectorAll(".content-lines");
                    if (getOS() == "Windows") {
                        reviewTextDiv.forEach(element => {
                            element.classList.add("reviewText");
                        });
                    }else{
                        reviewTextDiv.forEach(element => {
                            element.classList.remove("reviewText");
                        });
                    }
                } else {
                     console.error('Error: Could not find element with ID "notification-list" in xhr.onload.');
                }
            };

            xhr.onerror = function() {
                // Handle network errors
                console.error('Network error fetching notifications.');
                const notificationList = document.getElementById('notification-list'); // Get element again
                if (notificationList) { // Check if element exists
                     notificationList.innerHTML = '<li><div class="no-notifications">Network error fetching notifications.</div></li>';
                } else {
                    console.error('Error: Could not find element with ID "notification-list" on network error.');
                }
            };

            console.log('Sending AJAX request for notifications...');
            xhr.send();
        }

        // --- Logout Modal Functionality ---
        // Get the logout button and modal elements
        const logoutBtn = document.querySelector('.logout-btn');
        const logoutModal = document.getElementById('logoutModal');
        const modalContainer = document.querySelector('#logoutModal .modal-container');

        // Add event listener to show the modal when the logout button is clicked
        if(logoutBtn && logoutModal) { // Check if both elements exist
            logoutBtn.addEventListener('click', function() {
                logoutModal.style.display = 'flex'; // Use flex to enable centering
            });
        }

        // Add event listener to close the modal if clicking outside
        if(logoutModal) { // Check if modal element exists
             window.addEventListener('click', function(event) {
                 if (event.target === logoutModal) {
                     logoutModal.style.display = "none";
                 }
             });
        }


        // Add event listener to the cancel button inside the modal
        if(modalContainer) { // Check if modal container exists
             const cancelBtn = modalContainer.querySelector('.cancel-btn');
              // Check if cancelBtn exists and is not inside an <a> tag
             if(cancelBtn && cancelBtn.closest('a') === null) {
                 cancelBtn.addEventListener('click', function() {
                      logoutModal.style.display = 'none';
                 });
             }
        }

        // --- Execute on DOM Ready ---
        // Use DOMContentLoaded to ensure the HTML is fully loaded and parsed before running scripts
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded event fired. Running initial scripts.');

            // Set initial active sidebar link
            setActivePage();

            // Fetch notifications when the DOM is ready
            fetchNotifications();

            // --- Header Animation Re-trigger ---
            // Re-trigger the floatIn animation for the header on page load
            const headerElement = document.querySelector('.header');
            if (headerElement) {
                headerElement.style.animation = 'none'; // Temporarily remove animation
                headerElement.offsetHeight; // Trigger a reflow (browser recalculates layout)
                headerElement.style.animation = 'floatIn 0.8s ease-out forwards'; // Re-apply animation
            }

            // You can add other DOM-dependent code here
        });

        // --- Global AdminHomePage function (if used by the logo) ---
        function AdminHomePage() {
            window.location.href = 'Admin-Dashboard.php'; // Replace with the actual homepage URL
        }

        function getOS() {
            var userAgent = window.navigator.userAgent,
            platform = window.navigator.platform
            macosPlatforms = ["Macintosh", "MacIntel", "MacPPC", "Mac68K"],
            windowsPlatforms = ["Win32", "Win64", "Windows", "WinCE"],
            iosPlatforms = ["iPhone", "iPad", "iPod"],
            os = null;
        
            if (macosPlatforms.indexOf(platform) !== -1) {
            os = "Mac OS";
            } else if (iosPlatforms.indexOf(platform) !== -1) {
            os = "iOS";
            } else if (windowsPlatforms.indexOf(platform) !== -1) {
            os = "Windows";
            } else if (/Android/.test(userAgent)) {
            os = "Android";
            } else if (!os && /Linux/.test(platform)) {
            os = "Linux";
            }
        
            return os;
        }

        
    </script>
</body>
</html>