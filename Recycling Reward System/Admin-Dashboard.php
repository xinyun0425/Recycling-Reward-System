<?php
// Admin-Dashboard.php

// Start a session at the beginning of the script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Optional: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: Admin-Login.php'); 
    exit();
}




// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Replace with your actual database password if you have one
$dbname = "cp_assignment";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection failed: " . $conn->connect_error);
    // Display a user-friendly message or redirect
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Enable error reporting for development (RECOMMENDED: Disable or restrict in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ** SECURITY NOTE: The following SQL queries are vulnerable to SQL Injection. **
// ** Use prepared statements ($stmt = $conn->prepare(...) and $stmt->bind_param(...) ) for safety. **

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentTime = time();
$today = date('Y-m-d');

// --- Data Fetching ---

// Fetch Today's Dropoff Counts
// Query uses CURDATE() which is often safer than PHP's date() for direct MySQL comparison
$totalDropoffsTodayQuery = "SELECT COUNT(*) AS total_dropoffs_today FROM dropoff WHERE dropoff_date = '$today' AND status IN ('Complete', 'Unread')";
$totalDropoffsTodayResult = $conn->query($totalDropoffsTodayQuery);
$totalDropoffsToday = $totalDropoffsTodayResult ? $totalDropoffsTodayResult->fetch_assoc()['total_dropoffs_today'] : 0;
if ($totalDropoffsTodayResult) $totalDropoffsTodayResult->free();

// Completed Dropoff requests submitted today
$completedDropoffsTodayQuery = "SELECT COUNT(*) AS completed_dropoffs_today FROM dropoff WHERE dropoff_date = '$today' AND status = 'Complete'"; // Check 'Completed' spelling/case
$completedDropoffsTodayResult = $conn->query($completedDropoffsTodayQuery);
$completedDropoffsToday = $completedDropoffsTodayResult ? $completedDropoffsTodayResult->fetch_assoc()['completed_dropoffs_today'] : 0;
if ($completedDropoffsTodayResult) $completedDropoffsTodayResult->free();

// Calculate Dropoff Percentage
$dropoffPercentage = ($totalDropoffsToday > 0) ? round(($completedDropoffsToday / $totalDropoffsToday) * 100) : 0;
$todayDropoffCount = $totalDropoffsToday;


// Fetch Today's Pickup Counts
$totalPickupsTodayQuery = "SELECT count(*) AS total_pickups_today FROM pickup_request INNER JOIN time_slot ON pickup_request.time_slot_id = time_slot.time_slot_id WHERE time_slot.date = '$today' AND status IN ('Completed', 'Assigned')"; // Query uses CURDATE()
$totalPickupsTodayResult = $conn->query($totalPickupsTodayQuery);
$totalPickupsToday = $totalPickupsTodayResult ? $totalPickupsTodayResult->fetch_assoc()['total_pickups_today'] : 0;
if ($totalPickupsTodayResult) $totalPickupsTodayResult->free();

// Completed Pickup requests submitted today
$completedPickupsTodayQuery = "SELECT count(*) AS completed_pickups_today FROM pickup_request INNER JOIN time_slot ON pickup_request.time_slot_id = time_slot.time_slot_id WHERE time_slot.date = '$today' AND status = 'Completed'"; // Check 'Completed' spelling/case
$completedPickupsTodayResult = $conn->query($completedPickupsTodayQuery);
$completedPickupsToday = $completedPickupsTodayResult ? $completedPickupsTodayResult->fetch_assoc()['completed_pickups_today'] : 0;
if ($completedPickupsTodayResult) $completedPickupsTodayResult->free();

// Calculate Pickup Percentage
$pickupPercentage = ($totalPickupsToday > 0) ? round(($completedPickupsToday / $totalPickupsToday) * 100) : 0;
$todayPickupCount = $totalPickupsToday;


// Fetch today's pickup events with time slot and driver name (for the time slot box)
$todaysEventsQuery = "
    SELECT ts.time, d.driver_name
    FROM pickup_request pr
    INNER JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id
    INNER JOIN driver d ON pr.driver_id = d.driver_id
    WHERE ts.date = '$today'
    ORDER BY ts.time ASC
    LIMIT 3;
";

$todaysEventsResult = $conn->query($todaysEventsQuery);

$todaysEvents = [];
if ($todaysEventsResult) {
    while ($row = $todaysEventsResult->fetch_assoc()) {
        $startTime = $row['time'];
        $endTime = date('H:i:s', strtotime("$startTime +1 hour"));
        $row['endTime'] = $endTime;
        $todaysEvents[] = $row;
    }
    $todaysEventsResult->free();
}


// Fetch pickup/dropoff volume for the week (for the chart)
$daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$pickupWeeklyData = array_fill_keys($daysOfWeek, 0);
$dropoffWeeklyData = array_fill_keys($daysOfWeek, 0);
$allWeeklyData = []; // Array to hold all pickup and dropoff data for max calculation

// Get the start date of the current week (Monday)
$startDate = date('Y-m-d', strtotime('monday this week'));

for ($i = 0; $i < 7; $i++) {
    $currentDate = date('Y-m-d', strtotime("{$startDate} +{$i} days"));
    $dayOfWeek = date('D', strtotime($currentDate)); // e.g., Mon, Tue

    // Fetch pickup count for the current day
    $weeklyPickupQuery = "SELECT COUNT(*) as pickup_count FROM pickup_request WHERE DATE(datetime_submit_form) = '$currentDate'";
    $weeklyPickupResult = $conn->query($weeklyPickupQuery);
    $pickupCount = $weeklyPickupResult ? $weeklyPickupResult->fetch_assoc()['pickup_count'] : 0;
    if ($weeklyPickupResult) $weeklyPickupResult->free();

    $pickupWeeklyData[$dayOfWeek] = $pickupCount;
    $allWeeklyData[] = $pickupCount; // Add to array for max calculation

    // Fetch dropoff count for the current day
    $weeklyDropoffQuery = "SELECT COUNT(*) as dropoff_count FROM dropoff WHERE DATE(dropoff_date) = '$currentDate'";
    $weeklyDropoffResult = $conn->query($weeklyDropoffQuery);
    $dropoffCount = $weeklyDropoffResult ? $weeklyDropoffResult->fetch_assoc()['dropoff_count'] : 0;
    if ($weeklyDropoffResult) $weeklyDropoffResult->free();

    $dropoffWeeklyData[$dayOfWeek] = $dropoffCount;
    $allWeeklyData[] = $dropoffCount; // Add to array for max calculation
}

// Reorder data based on $daysOfWeek for chart labels (ensures Mon-Sun order)
$orderedPickupWeeklyData = [];
$orderedDropoffWeeklyData = [];
foreach ($daysOfWeek as $day) {
    $orderedPickupWeeklyData[] = $pickupWeeklyData[$day];
    $orderedDropoffWeeklyData[] = $dropoffWeeklyData[$day];
}

// --- Calculate the overall maximum value for the chart Y-axis ---
// Find the highest value in the weekly data
$overallMaxData = 0;
if (!empty($allWeeklyData)) {
    $overallMaxData = max($allWeeklyData);
}

// Determine the suggested max for the chart Y-axis with padding and minimum
// If there is data, set max to the data value plus a small buffer (e.g., +1)
// If no data (max is 0), set a minimum axis max for visibility, like 5
$suggestedYAxisMax = ($overallMaxData > 0) ? $overallMaxData: 5;


// Fetch recent pickup orders (limited to 10) - Data is fetched but not currently used in HTML
$recentPickupsQuery = "
    SELECT pr.pickup_request_id, pr.status, pr.datetime_submit_form, ts.time AS time_slot, d.driver_name
    FROM pickup_request pr
    INNER JOIN time_slot ts ON pr.time_slot_id = ts.time_slot_id
    INNER JOIN driver d ON pr.driver_id = d.driver_id
    ORDER BY pr.datetime_submit_form DESC
    LIMIT 10";
$recentPickupsResult = $conn->query($recentPickupsQuery);
$recentPickups = [];
if ($recentPickupsResult) {
    while ($row = $recentPickupsResult->fetch_assoc()) {
        $recentPickups[] = $row;
    }
    $recentPickupsResult->free();
}


// Fetch recent dropoff orders (limited to 10) - Data is fetched but not currently used in HTML
$recentDropoffsQuery = "
    SELECT dropoff_id, status, dropoff_date
    FROM dropoff
    ORDER BY dropoff_date DESC
    LIMIT 10";
$recentDropoffsResult = $conn->query($recentDropoffsQuery);
$recentDropoffs = [];
if ($recentDropoffsResult) {
    while ($row = $recentDropoffsResult->fetch_assoc()) {
        $recentDropoffs[] = $row;
    }
    $recentDropoffsResult->free();
}

// Close database connection
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        @keyframes floatIn {
            0% { transform: translateY(-50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        /* --- Layout Container --- */
        .container {
            display: flex; /* Use flexbox for sidebar and main content */
            height: 100%; /* Take full height of body */
            width: 100%; /* Take full width */
            overflow: hidden; /* Ensure no overflow from children */
        }

        /* --- Sidebar Styles --- */
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

        .menu li i {
            color: rgb(134, 134, 134);
            width: 5px;
            padding-right: 18px;
        }

        .menu li.active {
            background-color: #E4EBE6;
            border-radius: 10px;
            color: rgb(11, 91, 19);
        }

        .menu a:hover,
        .menu a.active {
            background: #E4EBE6;
            color: rgb(11, 91, 19);
        }

        .menu li.active i,
        .menu li:hover i {
            color: green;
            background-color: #E4EBE6;
        }

        .menu li.active a,
        .menu li:hover a {
            color: rgb(11, 91, 19);
            background-color: #E4EBE6;
        }

        /* --- Logout Button and Modal Styles --- */
        .profile-container {
            width: 100%;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            padding: 0 15px;
            box-sizing: border-box;
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
             cursor: pointer; /* Added cursor */
             transition: all 0.3s ease; /* Added transition */
        }
        .logout:hover {
            background-color: rgba(249, 226, 226, 0.91);
        }
         .logout:active { /* Added active state */
             transform: translateY(1px);
         }
        .logout i {
            padding-right: 10px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);
            width: 350px;
            text-align: center;
        }

        .modal-container h2 {
            margin-bottom: 20px;
            font-size: 1.2em;
            color: #333;
        }

        .modal-container .button-container {
            display: flex;
            justify-content: space-around;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-container button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, opacity 0.3s;
            font-size: 1em;
        }
         /* Styles for modal buttons - ensure these match your desired look */
         .modal-container .confirm-btn {
             background-color: #4CAF50; /* Green */
             color: white;
         }
         .modal-container .cancel-btn {
             background-color: #f44336; /* Red */
             color: white;
         }
        .modal-container button:hover {
            opacity: 0.9;
        }


        .main-content {
        overflow-x: hidden;
        padding:20px;
        margin-left:300px; /* Adjust this based on sidebar width */
        /* width:calc(100% - 300px); Adjust width to account for fixed sidebar */
        overflow-y:auto;
        }
        .header {
            text-align: left;
            width: 100%;
            font-size: 1.5em;
            margin-bottom: 28px;
            animation: floatIn 0.8s ease-out;
            color: black;
            margin-left: 75px;
            font-weight: bold;
        }

        .header i {
            font-size: 1.0em;
            margin-right: 20px;
            color: rgb(134, 134, 134);
            cursor: pointer;
        }

        hr {
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
            margin: 0 45px 20px; /* Keep consistent with padding */
            width: calc(100% - 90px); /* Keep consistent with padding */
        }

        /* --- Dashboard Specific Component Styles --- */

        .dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 0 75px; /* Changed padding to match HR margins */
        }

        .top-container {
            display: flex;
            gap: 20px;
            /* Allow items to wrap on smaller screens */
            flex-wrap: wrap;
        }

        .today-dropoff,
        .today-pickup {
             background-color: #fff;
             border: 3px solid #ddd; /* Base border */
             border-radius: 10px;
             box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
             display: flex;
             flex-direction: column;
             justify-content: space-between;
             flex-shrink: 0;
             transition: border-color 0.3s ease; /* Transition for border color */
             cursor: pointer; /* Indicate it's interactive */
        }

        .pickup-time-slot-container {
             background-color: #fff;
             border: 3px solid #ddd; /* Base border */
             border-radius: 10px;
             box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
             display: flex;
             flex-direction: column;
             justify-content: space-between;
             flex-shrink: 0;
             transition: border-color 0.3s ease; /* Transition for border color */
        }

        .pickup-time-slot-container {
             /* Adjust width for responsiveness */
             /* It should take space but allow others to fit */
             flex-basis: 40vw; /* Start with a basis */
             /* flex-grow: 1.5; Allow it to grow more than others */
             min-width: 250px; /* Ensure a minimum width */
             padding: 30px; /* Keep larger padding for this box */
        }

         .today-dropoff, .today-pickup {
             flex-grow: 1; /* Allow these to grow and share space */
             min-width: 250px; /* Ensure a minimum width */
             padding:25px 0px 20px; /* Keep padding consistent */
         }


        .pickup-time-slot-container h3,
        .today-dropoff h3,
        .today-pickup h3 {
            font-size: 1.1em; /* Adjusted size */
            margin-top: 0;
            margin-bottom: 15px; /* Adjusted margin */
            color: #555;
            text-align: center;
        }


        .pickup-time-slot-container .time-slot-content {
            display: flex;
            /* align-items: center; */
            gap: 20px;
            flex-grow: 1;
            margin-top: 20px;
             /* Allow wrapping of date and events on smaller screens */
            flex-wrap: wrap;
             justify-content: center; /* Center content when wrapped */
        }

        .pickup-time-slot-container .date-container {
            text-align: center;
            flex-shrink: 0;
             /* Ensure date container doesn't shrink too much */
             min-width: 80px;
        }

        .pickup-time-slot-container .day {
            font-size: 1.1em;
            font-weight: bold;
            color: darkred;
            margin-bottom: 5px;
        }

        .pickup-time-slot-container .date {
            font-size: 2.6em;
            font-weight: bold;
            color: #333;
        }

        .pickup-time-slot-container .events-container {
            flex-grow: 1;
            margin-top:10px;
             /* Ensure it takes available space */
             flex-basis: 0; /* Allow it to shrink below min-content */
             min-width: 180px; /* Ensure event list has minimum readable width */
        }

        .pickup-time-slot-container .events-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pickup-time-slot-container .events-container li {
            margin-bottom: 8px;
        }

        .pickup-time-slot-container .event-item {
            background-color: #e9ecef;
            border-left: 5px solid #007bff;
            border-radius: 5px;
            padding: 8px;
            font-size: 0.9em;
        }

        .pickup-time-slot-container .time-slot {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 3px;
        }

        .pickup-time-slot-container .driver-name {
            color: #555;
            font-size: 0.9em;
        }

        .pickup-time-slot-container .no-events {
            background-color: #e9ecef;
            border-left: 5px solid #777;
            border-radius: 5px;
            padding: 15px;
            color: #777;
            text-align: center;
            font-size: 0.9em;
        }

        .pickup-time-slot-container .view-all {
            text-align: right;
            margin-top: 10px;
            font-size: 0.9em;
        }

        .pickup-time-slot-container .view-all a {
            color: #007bff;
            text-decoration: none;
        }

        /* --- Container Hover Styles --- */
        .today-dropoff:hover,
        .today-pickup:hover {
            border-color:rgba(120, 162, 76, 0.46); /* Green border on container hover */
        }


        .right-side-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            flex-grow: 1;
            min-width: 250px; /* Ensure a minimum width */
            margin-right:20px;
        }

        .today-dropoff .count,
        .today-pickup .count {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            text-align: center;
        }

        .progress-bar {
            background-color: #f0f0f0;
            border-radius: 5px;
            height: 8px;
            margin: 8px auto; /* Centered margin */
            overflow: hidden;
            width: 90%; /* Adjusted width */
        }

        .progress {
            background-color: #4CAF50;
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: width 2s ease-in-out;
        }

        .percentage-display {
            font-size: 0.8em;
            color: #555;
            text-align: center;
            margin-top: 5px;
        }


        .weekly-volume-chart-container {
            background-color: #ffffff;
            padding: 40px 40px 70px; /* Adjusted padding */
            border-radius: 8px;
            border: 3px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            max-height: 400px; /* Keep max height */
            overflow: visible; /* Ensure chart elements like tooltips are visible */
             flex-grow: 1; /* Allow the chart container to grow */
             margin-right: 20px;
        }

        .weekly-volume-chart-container h3 {
            font-size: 1.1em;
            margin-top: 0;
            margin-bottom: 20px;
            color: #555;
            text-align: center;
        }

         .view-all-chart {
             text-align: center; /* Center the link below the chart */
             margin-top: 10px; /* Space above the link */
         }
        .view-all-chart a {
            font-size: 0.9em;
            color: #007bff; /* Ensure link color */
            text-decoration: none; /* Ensure link decoration */
        }


        /* Base style for the animated circular button */
        .card-button {
            height: 35px;
            width: 35px;
            color: black; /* Initial icon/text color */
            border-radius: 50%;
            /* Position the button within its container - adjusted margin for centering */
            margin:15px auto 5px auto; /* Top, Right, Bottom, Left */
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgb(168, 168, 167); /* Initial background color */
            cursor: pointer;
            border: 2px solid rgb(168, 168, 167); /* Initial border color */
            transform: rotate(-45deg); /* Initial rotation */
            /* Keep the transition property here - it defines HOW the animation happens */
            transition: transform 0.4s ease, background-color 0.4s ease, border-color 0.4s ease, color 0.4s ease;
            box-shadow: none;
            flex-shrink: 0; /* Prevent button from shrinking */
        }

        /* --- NEW RULES: Trigger button animation when the CONTAINER is hovered --- */

        /* When the pickup container is hovered, apply these styles to the card-button inside it */
        
        .today-dropoff:hover .card-button,
        .today-pickup:hover .card-button{
            color: white; /* Hover icon/text color */
            background: #78A24C; /* Hover background color (use your desired green) */
            border-color: #78A24C; /* Hover border color (matches background) */
            transform: rotate(0deg); /* End rotation */
        }

        /* Active state (optional, for click feedback on the button itself) */
        .card-button:active {
            transform: scale(0.95) rotate(0deg); /* Add rotate(0deg) to prevent jumping if activated during transition */
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
        }

        /* Style for the Material Symbols icon inside the button */
        .card-button.material-symbols-rounded {
            font-size: 20px;
        }


        @media (max-width: 992px) { /* Adjusted breakpoint for better tablet view */
            /* Adjust sidebar for smaller screens */
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                flex-direction: row;
                flex-wrap: wrap;
                 padding: 10px; /* Reduced padding */
                 justify-content: center; /* Center items */
            }
             .sidebar > div:first-child { /* Logo container */
                 flex-basis: 100%;
                 text-align: center; /* Center logo */
             }
            .sidebar .menu {
                margin-left: 0;
                flex-direction: row; /* Arrange menu items horizontally */
                flex-wrap: wrap; /* Allow menu items to wrap */
                justify-content: center; /* Center menu items */
                gap: 5px; /* Space between menu items */
                flex-basis: 100%;
            }
             .sidebar .menu li {
                 flex: 1 1 auto; /* Allow list items to grow/shrink */
                 min-width: 120px; /* Minimum width for smaller items */
                 margin-bottom: 5px; /* Space below wrapped items */
             }
            .sidebar .menu li a {
                justify-content: center;
                padding: 10px; /* Reduced padding */
            }
            .sidebar .menu li i {
                width: auto;
                padding-right: 5px; /* Reduced padding */
            }
             .sidebar .profile-container {
                 flex-basis: 100%;
                 padding: 10px 0; /* Adjusted padding */
             }


            /* Adjust main content for smaller screens */
            .main-content {
                margin-left: 0;
                width: 100%;
                overflow-y: auto;
                padding: 10px; /* Reduced padding */
            }

            /* Adjust dashboard layout for smaller screens */
            .dashboard-content {
                 padding: 0 10px; /* Match main-content padding */
                 gap: 10px; /* Reduced gap */
             }

            .top-container {
                flex-direction: column; /* Stack top containers */
                gap: 10px; /* Reduced gap */
            }

            .pickup-time-slot-container {
                width: 100%; /* Take full width on small screens */
                flex-basis: auto; /* Remove basis */
                 min-width: auto; /* Remove min-width constraint */
                padding: 15px; /* Reduced padding */
            }
            .pickup-time-slot-container .time-slot-content {
                flex-direction: column; /* Stack date and events */
                gap: 10px; /* Reduced gap */
                 justify-content: center; /* Center items */
            }
             .pickup-time-slot-container .date-container {
                 min-width: auto; /* Remove min-width constraint */
             }
             .pickup-time-slot-container .events-container {
                 min-width: auto; /* Remove min-width constraint */
                 width: 100%; /* Take full width */
             }


            .right-side-container {
                flex-direction: column; /* Stack dropoff/pickup boxes */
                gap: 10px; /* Reduced gap */
                 min-width: auto; /* Remove min-width constraint */
            }
            .today-dropoff, .today-pickup {
                width: 100%; /* Take full width */
                margin-bottom: 0;
                padding: 15px 0; /* Keep vertical padding, maybe reduce horizontal */
            }

            .header {
                margin-left: 10px; /* Match main-content padding */
            }
            hr {
                margin: 0 10px 20px !important; /* Match main-content padding */
                width: calc(100% - 20px) !important;
            }
            .weekly-volume-chart-container {
                padding: 15px; /* Reduced padding */
            }
             .weekly-volume-chart-container canvas {
                 max-height: 300px; /* Optionally reduce chart height */
             }
        }

         @media (max-width: 576px) { /* Extra small screens */
             .sidebar .menu li {
                 flex-basis: 100%; /* Stack menu items */
                 margin-bottom: 5px;
             }
             .sidebar .menu li a {
                 padding: 8px 10px; /* Further reduce padding */
             }
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

         <div class ="main-content">
         <h2 class="header">
             <a href='Admin-Dashboard.php' style='text-decoration: none; color: inherit;'>
             </a>
             Dashboard</h2>
         <hr style="width: 92%; margin-left:45px;">
             <div class="dashboard-content">
                 <div class="top-container">
                     <div class="pickup-time-slot-container"">
                         <h3>Upcoming Pickup Today</h3>
                         <div class="time-slot-content">
                             <div class="date-container">
                                 <div class="day"><?php echo date('l'); ?></div>
                                 <div class="date"><?php echo date('d'); ?></div>
                             </div>
                             <div class="events-container">
                                 <?php if (!empty($todaysEvents)): ?>
                                     <ul>
                                         <?php foreach ($todaysEvents as $event): ?>
                                             <li>
                                                 <div class="event-item">
                                                 <div class="time-slot">
                                                     <?php echo htmlspecialchars(date('H:i', strtotime($event['time']))); ?> -
                                                     <?php echo htmlspecialchars(date('H:i', strtotime($event['endTime']))); ?>
                                                 </div>
                                                     <div class="driver-name"><?php echo htmlspecialchars($event['driver_name']); ?></div>
                                                 </div>
                                             </li>
                                         <?php endforeach; ?>
                                     </ul>
                                 <?php else: ?>
                                     <div class="no-events">No upcoming pickup today.</div>
                                 <?php endif; ?>
                             </div>
                         </div>
                         <!-- <button class="card-button material-symbols-rounded" >arrow_forward</button> -->
                     </div>
                     <div class="right-side-container">
                         <div class="today-dropoff" onclick="window.location.href='Admin-Dropoff.php'">
                             <h3>Today's Drop-off</h3>
                             <div class="count"><?php echo htmlspecialchars($todayDropoffCount); ?></div>
                             <div class="progress-bar">
                                 <div class="progress dropoff-progress" data-percentage="<?php echo htmlspecialchars($dropoffPercentage); ?>"></div>
                             </div>
                             <div class="percentage-display dropoff-percentage"><?php echo htmlspecialchars($dropoffPercentage); ?>% Completed</div>
                              <!-- Use a button for interaction instead of a link -->
                             <button class="card-button material-symbols-rounded">arrow_forward</button>
                         </div>
                         <div class="today-pickup" onclick="window.location.href='Admin-Pickup-Pending.php'">
                             <h3>Today's Pickup</h3>
                             <div class="count"><?php echo htmlspecialchars($todayPickupCount); ?></div>
                             <div class="progress-bar">
                                 <div class="progress pickup-progress" data-percentage="<?php echo htmlspecialchars($pickupPercentage); ?>"></div>
                             </div>
                             <div class="percentage-display pickup-percentage"><?php echo htmlspecialchars($pickupPercentage); ?>% Completed</div>
                             <!-- Use a button for interaction instead of a link -->
                              <button class="card-button material-symbols-rounded">arrow_forward</button>
                         </div>
                     </div>
                 </div>
                 <div class="weekly-volume-chart-container">
                     <h3>Weekly Pickup / Drop-off Volume</h3>
                     <canvas id="weeklyVolumeChart"></canvas>
                 </div>
             </div>
         </div>
     </div>

 <!-- This script block contains JavaScript code that relies on PHP variables -->
 <script>
     // --- Sidebar Active Link Functionality ---
     // Function to remove 'active' class from all menu items and add to the clicked one
     function activateLink(linkElement) {
         const items = document.querySelectorAll('.menu li');
         items.forEach(item => item.classList.remove('active'));
         const parentLi = linkElement.closest('li');
         if (parentLi) {
             parentLi.classList.add('active');
         }
     }

     // Function to set the active state on page load based on URL
     function setActivePage() {
         const currentPath = window.location.pathname.split('/').pop();
         const menuLinks = document.querySelectorAll('.menu a');
         menuLinks.forEach(link => {
             const linkPath = link.getAttribute('href').split('/').pop();
             // Check if the link path matches the current page path (or is the root/homepage)
             // Also handle the case where the current path is empty (index.php or root)
             if ((currentPath !== '' && linkPath === currentPath) || (currentPath === '' && linkPath === 'Admin-Dashboard.php')) {
                  // Using indexOf to check if the currentPath is part of the linkPath handle cases like / vs /index.php
                  // A more robust way for specific files:
                  const currentPageFile = window.location.pathname.split('/').pop() || 'index.php'; // Default to index.php if root
                  const linkFile = link.getAttribute('href').split('/').pop() || 'index.php';
                  if (currentPageFile === linkFile) {
                      activateLink(link);
                  }
             }
         });
     }

     // Set the active page when the DOM is fully loaded
     document.addEventListener('DOMContentLoaded', function() {
         // Set active sidebar link on load
         setActivePage();

         // Add event listeners to menu links for activation (optional if navigation handles it)
         const menuLinks = document.querySelectorAll('.menu a');
         menuLinks.forEach(link => {
             link.addEventListener('click', function() {
                 // If using client-side routing, you might uncomment this
                 // activateLink(this);
             });
         });


         // --- Progress Bar Animation ---
         const dropoffProgress = document.querySelector('.dropoff-progress');
         const pickupProgress = document.querySelector('.pickup-progress');

         if (dropoffProgress) {
             const dropoffPercentage = dropoffProgress.getAttribute('data-percentage');
             // Ensure the value is a number before setting width
             if (!isNaN(dropoffPercentage)) {
                 dropoffProgress.style.width = dropoffPercentage + '%';
             } else {
                 console.error("Invalid dropoff percentage data:", dropoffPercentage);
                 dropoffProgress.style.width = '0%'; // Default to 0 if invalid
             }
         }
         if (pickupProgress) {
             const pickupPercentage = pickupProgress.getAttribute('data-percentage');
              // Ensure the value is a number before setting width
             if (!isNaN(pickupPercentage)) {
                pickupProgress.style.width = pickupPercentage + '%';
             } else {
                 console.error("Invalid pickup percentage data:", pickupPercentage);
                 pickupProgress.style.width = '0%'; // Default to 0 if invalid
             }
         }


         // --- Weekly Volume Chart ---
         const ctx = document.getElementById('weeklyVolumeChart');
         // Check if the canvas element exists before trying to get context
         if (ctx) {
             const chartCtx = ctx.getContext('2d');

             const labels = <?php echo json_encode($daysOfWeek); ?>;
             const pickupData = <?php echo json_encode($orderedPickupWeeklyData); ?>;
             const dropoffData = <?php echo json_encode($orderedDropoffWeeklyData); ?>;
             const suggestedYMax = <?php echo json_encode($suggestedYAxisMax); ?>; // Max calculated in PHP

             const weeklyVolumeChart = new Chart(chartCtx, { // Use chartCtx here
                 type: 'bar',
                 data: {
                     labels: labels,
                     datasets: [{
                         label: 'Pickup',
                         data: pickupData,
                         backgroundColor: 'rgba(54, 162, 235, 0.6)', // Blue
                         borderColor: 'rgba(54, 162, 235, 1)',
                         borderWidth: 1
                     }, {
                         label: 'Drop-off',
                         data: dropoffData,
                         backgroundColor: 'rgba(255, 159, 64, 0.6)', // Orange
                         borderColor: 'rgba(255, 159, 64, 1)',
                         borderWidth: 1
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false, // Allow chart size to be controlled by parent container
                      // Added padding to the chart container for better spacing
                      layout: {
                           padding: {
                               left: 10,
                               right: 10,
                               top: 0,
                               bottom: 20 // Add space at the bottom for tick labels
                           }
                      },
                     scales: {
                         y: {
                             beginAtZero: true,
                              suggestedMax: suggestedYMax, // <--- THIS LINE uses the dynamic max
                              ticks: {
                                 stepSize: 1, // Ensure only whole numbers are shown on the Y axis
                                 // Optional: callback to hide 0 if desired, but 0 is usually useful
                                 // callback: function(value, index, values) {
                                 //      return value === 0 ? '' : value;
                                 // }
                             },
                             title: { // Optional: Add a y-axis title
                                 display: true,
                                 text: 'Volume (Request)'
                             }
                         },
                          x: {
                             ticks: {
                                 autoSkip: false, // Prevent skipping labels if space is limited
                             },
                             title: { // Optional: Add an x-axis title
                                 display: true,
                                 text: 'Day of Week'
                             }
                          }
                     },
                     plugins: {
                         legend: {
                             position: 'top', // Position legend at the top
                         },
                         title: {
                             display: false, // Title is in h3 above chart
                             text: 'Weekly Pickup / Dropoff Volume'
                         },
                         tooltip: { /* Customize tooltips */
                              mode: 'index',
                              intersect: false,
                              callbacks: { // Optional: Customize tooltip label
                                  label: function(context) {
                                      let label = context.dataset.label || '';
                                      if (label) {
                                          label += ': ';
                                      }
                                      if (context.parsed.y !== null) {
                                          label += context.parsed.y;
                                      }
                                      return label;
                                  }
                              }
                         }
                     }
                 }
             });
         } else {
             console.error("Canvas element with ID 'weeklyVolumeChart' not found!");
         }


         // --- Logout Modal Functionality ---
         const logoutBtn = document.querySelector('.logout');
         const logoutModal = document.getElementById('logoutModal');
         const modalContainer = document.querySelector('#logoutModal .modal-container');

         if (logoutBtn && logoutModal && modalContainer) { // Check if elements exist
             logoutBtn.addEventListener('click', function() {
                 logoutModal.style.display = 'flex'; // Use flex to center
             });

             // Close modal if clicking outside (on the overlay)
             logoutModal.addEventListener('click', function(event) {
                 if (event.target === logoutModal) {
                     logoutModal.style.display = "none";
                 }
             });

             // Add event listeners to modal buttons
             const cancelBtn = modalContainer.querySelector('.cancel-btn');
             const confirmBtn = modalContainer.querySelector('.confirm-btn');

             if (cancelBtn) {
                 cancelBtn.addEventListener('click', function() {
                     logoutModal.style.display = 'none';
                 });
             }

             if(confirmBtn) {
                 confirmBtn.addEventListener('click', function() {
                     logoutConfirmed(); // Call the logout function
                 });
             }
         } else {
             console.error("Logout modal elements not found.");
         }
     });

      // Global function for logout (called by modal confirm button)
      function logoutConfirmed() {
          // TODO: Add actual server-side logout logic here (e.g., making an AJAX call to a logout script)
          console.log("Logging out..."); // For testing

          // Redirect to login page after logout (client-side)
          window.location.href = 'Admin-Login.php';
      }

      // Global function to show the logout modal (called by logout button onclick)
      function logout() {
          const logoutModal = document.getElementById('logoutModal');
          if(logoutModal) {
              logoutModal.style.display = 'flex';
          }
      }

       // Global function to close the logout modal (called by modal cancel button onclick)
      function closeModal() {
         const logoutModal = document.getElementById('logoutModal');
         if(logoutModal) {
             logoutModal.style.display = 'none';
         }
      }

      
 </script>
</body>
</html>