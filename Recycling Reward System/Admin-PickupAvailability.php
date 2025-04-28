<?php
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: Admin-Login.php'); 
        exit();
    }

    $con = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $result = $con->query("SELECT `name` FROM admin LIMIT 1"); 
    $testAdmin = $result->fetch_assoc();

    // Check if date has timeslots
    if (isset($_GET['action']) && $_GET['action'] === 'check_timeslots' && isset($_GET['date'])) {
        $date = $_GET['date'];
        $query = "SELECT COUNT(*) as count FROM time_slot WHERE date = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 's', $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        echo $row['count'] > 0 ? '1' : '0';
        exit();
    }

    // Remove timeslot
    if (isset($_GET['action']) && $_GET['action'] === 'remove_timeslot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $slotId = $_POST['slot_id'];
        
        // Check if there are any bookings
        $bookingCheck = mysqli_query($con, "SELECT COUNT(*) as booking_count FROM pickup_request WHERE time_slot_id = '$slotId'");
        $bookingData = mysqli_fetch_assoc($bookingCheck);
        
        if ($bookingData['booking_count'] > 0) {
            echo 'Cannot remove timeslot - it has existing bookings';
            exit();
        }
        
        // Delete the timeslot
        $deleteQuery = mysqli_query($con, "DELETE FROM time_slot WHERE time_slot_id = '$slotId'");
        
        if ($deleteQuery) {
            echo 'Timeslot removed successfully.';
        } else {
            echo 'Failed to remove timeslot.';
        }
        exit();
    }

    // AJAX timeslot fetch
    // In the AJAX timeslot fetch section, remove the echo of the timeslot display div
if (isset($_GET['date']) && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $date = $_GET['date'];

    $query = "
        SELECT ts.time_slot_id, ts.date, ts.time, ts.no_driver_per_slot,
               (SELECT COUNT(*) FROM pickup_request pr WHERE pr.time_slot_id = ts.time_slot_id) as booking_count
        FROM time_slot ts
        WHERE ts.date = ?
        ORDER BY ts.time
    ";

    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // In the AJAX timeslot fetch section (around line 90)
    $timeslots = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $time = $row['time'];
        $count = $row['no_driver_per_slot'];
        $slotId = $row['time_slot_id'];
        $bookingCount = $row['booking_count']; // Make sure this is included
        
        // Format time for display (full range)
        $startTime = date("h:i A", strtotime($time));
        $endTime = date("h:i A", strtotime($time) + 3600);
        $timeRange = $startTime . " - " . $endTime;
        
        $timeslots[] = [
            'id' => $slotId,
            'timeRange' => $timeRange,
            'count' => $count,
            'bookingCount' => $bookingCount // Include booking count
        ];
    }
    
    // Return JSON instead of HTML
    header('Content-Type: application/json');
    echo json_encode($timeslots);
    exit();
}

    // Handle timeslot creation via POST 
    if (isset($_GET['action']) && $_GET['action'] === 'get_timeslots') {
        $start = $_GET['start'];
        $end = $_GET['end'];
    
        $stmt = $con->prepare("
            SELECT ts.date, ts.time, 
                   (SELECT COUNT(*) FROM pickup_request pr WHERE pr.time_slot_id = ts.time_slot_id) as booking_count,
                   ts.no_driver_per_slot
            FROM time_slot ts
            WHERE ts.date BETWEEN ? AND ?
            ORDER BY ts.time
        ");
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $events = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $startTime = date("h:i A", strtotime($row['time']));
            $endTime = date("h:i A", strtotime($row['time']) + 3600);
            
            // Determine event class based on status
            $eventClass = '';
            $today = date('Y-m-d');
            $eventDate = $row['date'];
            $textColor = '#4E7831';
            
            if ($eventDate < $today) {
                $eventClass = 'past-event';
                $textColor = '#555';
            } else if ($row['booking_count'] >= $row['no_driver_per_slot']) {
                $eventClass = 'booked-event';
                $textColor = '#8B0000';
            } else {
                $eventClass = 'future-event';
            }
    
            $events[] = array(
                'title' => $startTime . ' - ' . $endTime,
                'start' => $row['date'],
                'className' => $eventClass,
                'textColor' => $textColor
            );
        }
    
        echo json_encode($events);
        exit();
    }

    // Handle timeslot creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date']) && isset($_POST['time'])) {
        $date = $_POST['date'];
        $time = $_POST['time'];
        
        // Check if timeslot already exists
        $checkQuery = "SELECT * FROM time_slot WHERE date = ? AND time = ?";
        $stmt = $con->prepare($checkQuery);
        $stmt->bind_param("ss", $date, $time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "This timeslot already exists!";
            exit();
        }
        
        $driverCountQuery = "SELECT COUNT(*) as driver_count FROM driver";
        $driverResult = $con->query($driverCountQuery);
        $driverData = $driverResult->fetch_assoc();
        $driverCount = $driverData['driver_count'] ?? 0;
        
        if ($driverCount <= 0) {
            echo "No drivers available to assign to this timeslot!";
            exit();
        }
        
        // Insert new timeslot with actual driver count
        $insertQuery = "INSERT INTO time_slot (date, time, no_driver_per_slot) VALUES (?, ?, ?)";
        $stmt = $con->prepare($insertQuery);
        $stmt->bind_param("ssi", $date, $time, $driverCount);
        
        if ($stmt->execute()) {
            echo "Timeslot created successfully with $driverCount drivers!";
        } else {
            echo "Error creating timeslot: " . $con->error;
        }
        exit();
    }

if (isset($_GET['action']) && $_GET['action'] === 'get_timeslot_dates') {
    $today = date('Y-m-d');
    $query = "SELECT DISTINCT date FROM time_slot WHERE date >= ? ORDER BY date";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, 's', $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $dates = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $formattedDate = date("d/m/Y", strtotime($row['date']));
        $dates[] = $formattedDate;
    }
    echo implode(',', $dates);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Pickup Availability - Green Coin</title>

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

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
    }
    
    .main-content {
        padding: 20px;
        margin-left: 270px; 
        width: calc(100% - 270px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar {
        width: 290px;
        height: 100vh;
        min-height: 816px;
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
        margin-top: 1px;
    }

    .menu li {
        border-radius: 5px;
    }

    .menu li a {
        text-decoration: none;
        color: black;
        display: flex;
        align-items: center;
        gap: 15px;
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
        max-width: 220px;
    }
    .logout:hover {
        background-color: rgba(249,226,226,0.91);
        transition:all 0.5s ease;
    }
    .logout i{
        padding-right:10px;
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

    .pickupavailability-container{
        width: 100%;
        padding: 0;
        margin: 0;
    }

    #selectedDateDisplay {
        font-weight: bold;
        font-size: 18px;
        color: #1a3c2f;
        border-bottom: 1px solid #d4d4d4;
        padding-bottom: 8px;
    }

    #timeslotTableContainer .timeslot-card {
        border: 1px solid #d5e5d0;
        border-left: 5px solid #4CAF50;
        padding: 12px 18px;
        background: #f6fff8;
        border-radius: 8px;
        transition: transform 0.2s;
    }

    #timeslotTableContainer .timeslot-card:hover {
        transform: scale(1.03);
        background-color: #e8ffe7;
    }

    #timeslotTableContainer h4 {
        margin: 0 0 5px;
        font-size: 16px;
        color: #333;
    }

    #timeslotTableContainer p {
        margin: 0;
        font-size: 14px;
    }

    #fullCalendar {
        margin: 0 auto;
        background-color: white;
        padding: 15px;
        border-radius: 10px;
        /* box-shadow: 0 2px 10px rgba(0,0,0,0.1); */
    }

    .fc .fc-daygrid-day-events {
        min-height: 0;
        max-height: 90px;  /* Increased from 80px */
        overflow: hidden;
    }

    .fc .fc-daygrid-day-top {
        pointer-events: none;
    }

    .fc .fc-daygrid-day-number {
        pointer-events: none; 
    }


    .fc .fc-event {
        pointer-events: none; 
    }

    /* Calendar styling */
    .fc .fc-day-past:not(.fc-day-today) .fc-daygrid-day-frame {
        pointer-events: none !important;
        background-color: #f9f9f9;
    }

    .fc .fc-day-past:not(.fc-day-today) .fc-daygrid-day-number {
        color: #999;
    }

    .fc .fc-day-past .fc-event {
        opacity: 0.7;
        pointer-events: none !important;
    }
    .fc .fc-event.past-event {
        background-color: #e0e0e0 !important;
        color: #888 !important;
    }

    /* Future event styling */
    .fc .fc-event.future-event {
        background-color: #E1EFC7 !important;
        color: #000 !important;
    }

    /* Booked event styling */
    .fc .fc-event.booked-event {
        background-color: #FFD3D3 !important;
        color: #000 !important;
    }

    /* Current day styling */
    .fc .fc-daygrid-day.fc-day-today {
        background-color: rgba(255, 220, 220, 0.3);
    }

    .fc .fc-daygrid-day-frame {
        height: 90px !important;
        padding: 5px;
        position: relative;
        cursor: pointer;
        max-height: 110px;
    }

    .fc-header-toolbar {
        margin-bottom: 0.5em; 
        font-size: 0.9em; 
        margin-right: 170px;
    }

    /* Day headers */
    .fc-col-header-cell {
        padding: 5px 0; 
        font-size: 0.8em; 
    }

    .fc .fc-event {
        background-color: transparent !important;
        border: none !important;
        padding: 2px 4px !important;
        margin: 1px 0 !important;
        pointer-events: none; 
    }

    .content {
        margin: 0;
        width: 100%;
    }

    .title h2{
        margin-top: 19.92px;
        margin-bottom:19.92px;
        animation: floatIn 0.8s ease-out;
        font-size: 24px;
    }

    .title{
        display:flex;
        flex-direction: column;
        align-items:left;
        justify-content: center;  
        margin-left:103px;
    }

    .calendar-table-wrapper {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
        height: 100%;
        max-width: 90vw;
    }

    section.calendar {
        flex: 1;
        min-height: 0; 
    }

    .containerz{
        padding-left: 40px;
        padding-right: 40px;
        padding-top: 55px;
    }

    .createSlotBtn{
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: #78A24C;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 24px;
        border: none;
        cursor: pointer;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        transition: background 0.3s ease;
    }

    select:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: #ccc !important; /* optional: control border color */
    }

    .createSlotBtn:hover {
        background: #78A24C;
        scale: 1.1;
        transition: scale 0.3s ease;
    }

    .fc-daygrid-day:hover {
        background-color:rgba(209, 209, 209, 0.49) !important; 
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    hr{
        border: none;
        height: 1.6px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
        margin: 7px 9.4px 10px 45px;
    }

    .modal-content {
            background-color: #fefefe;
            color: #595959;
            padding: 20px;
            border: 1px solid #888;
            width: 600px;
            max-height: 80vh; 
            overflow-x: hidden;
            border-radius: 8px;
            position: relative;
            display: flex;
            flex-direction: column;
            backdrop-filter:blur(5px);
            /* box-shadow: 0 10px 25px rgba(0,0,0,0.2); */
    }

    /* Close Button */
    .modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #666;
    }

    /* Header */
    .modal-header {
        font-size: 24px;
        color: #333;
        font-weight: bold;
        text-align: left;
    }

    /* Text and labels */
    .modal-text {
        margin-bottom: 20px;
        color: #666;
        font-size: 16px;
    }

    /* Button styling */
    .modal-submit {
        background: #78A24C;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    .modalExtra {
        background: white; 
        padding: 70px 60px 50px 60px; 
        border-radius: 10px; 
        width: 650px; 
        height: auto; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
        position: relative; 
        display: flex; 
        flex-direction: column;
    }

    /* Custom dropdown arrow styling */
    .custom-select {
        position: relative;
        width: 100%;
    }
    
    .select-with-arrow { 
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 40px;
        background-image: url("data:image/svg+xml;utf8,<svg fill='gray' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
        border: 1px solid #ccc;
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 24px 24px;
        border-radius: 5px;
        height: 40px;
        font-size: 16px !important;
        outline: none !important;
    }

    .select-with-arrow:focus {
        border-color: #ccc !important;
        box-shadow: none !important;
    }

    input[type="date"] {
        outline: none;
        border: 1px solid #ddd !important; /* Keep a light border */
    }

    /* Remove focus outline */
    input[type="date"]:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: #ddd !important;
    }
    #viewSlotModal {
        backdrop-filter: blur(5px);
    }

    .legend-container {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
    }

    .legend-color {
        width: 15px;
        height: 15px;
        border-radius: 3px;
    }

    .disabled-delete-btn {
        background-color: #d3a7a7 !important;
        color: white;
        cursor: not-allowed !important;
        pointer-events: none;
        opacity: 0.7;
    }


    .booking-info {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #595959;
        margin-bottom: 10px;
    }

    .booking-info i {
        margin-right: 6px;
        font-size: 16px;
    }




    /* ==================== RESPONSIVE MEDIA QUERIES ==================== */
@media (max-width: 1400px) {
    .sidebar {
        width: 260px;
    }
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
    }
}

@media (max-width: 1200px) {
    .sidebar {
        width: 240px;
        padding: 15px;
    }
    .main-content {
        margin-left: 240px;
        width: calc(100% - 240px);
    }
    .menu li a {
        width: 200px;
    }
}

@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    .main-content {
        margin-left: 220px;
        width: calc(100% - 220px);
    }
    #fullCalendar {
        margin-left: 40px;
        width: 85%;
    }
    .modal-content, .modalExtra {
        width: 80%;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        min-height: 0;
        padding-bottom: 20px;
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    .menu {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
    }
    .menu li {
        width: 45%;
    }
    .menu li a {
        width: 100%;
        padding: 12px 8px;
    }
    .logout {
        max-width: 200px;
        margin: 20px auto;
    }
    #fullCalendar {
        margin-left: 0;
        width: 100%;
        padding: 10px;
    }
    .calendar-table-wrapper {
        flex-direction: column;
    }
    .title h2 {
        margin-left: 0;
        text-align: center;
    }
    hr {
        margin: 10px 0;
        width: 100%;
    }
    .modal-content, .modalExtra {
        width: 90%;
        padding: 40px 30px 30px;
    }
    .containerz {
        padding: 20px;
    }
}

@media (max-width: 576px) {
    .menu li {
        width: 100%;
    }
    .fc-header-toolbar {
        flex-direction: column;
        align-items: flex-start;
        margin-right: 0;
    }
    .fc-toolbar-title {
        font-size: 1.2em;
        margin-bottom: 10px;
    }
    .fc-toolbar-chunk {
        margin-bottom: 10px;
    }
    .legend-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        margin-left: 20px;
    }
    .modal-content, .modalExtra {
        padding: 30px 20px;
    }
    input[type="date"], 
    .select-with-arrow {
        padding: 10px;
        font-size: 14px;
    }
    .modal-submit, #deleteTimeslotBtn {
        padding: 10px;
        font-size: 14px;
    }
    .createSlotBtn {
        width: 45px;
        height: 45px;
        font-size: 20px;
        bottom: 15px;
        right: 15px;
    }
}

@media (max-width: 400px) {
    .modal-content, .modalExtra {
        padding: 25px 15px;
    }
    .modal-header {
        font-size: 20px;
    }
    .modal-text {
        font-size: 14px;
    }
    .containerz {
        padding: 15px 10px;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("fullCalendar");
    const viewSlotModal = document.getElementById("viewSlotModal");
    const slotDateSelect = document.getElementById("slotDateSelect");
    const timeslotSelect = document.getElementById("timeslotSelect");
    const deleteTimeslotBtn = document.getElementById("deleteTimeslotBtn");
    const viewSlotContent = document.getElementById("viewSlotContent");

    // Calendar setup
    const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    height: "auto",
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: ''
    },
    contentHeight: 'auto',
    eventContent: function(arg) {
        return {
            html: `<div style="font-size:0.95em">${arg.event.title}</div>`
        };
    },
        dayCellDidMount: function(arg) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const cellDate = new Date(arg.date);
            if (cellDate < today) {
                arg.el.style.cursor = 'default';
                arg.el.style.backgroundColor = '#f9f9f9';
            }
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`Admin-PickupAvailability.php?action=get_timeslots&start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(events => {
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
        const dateStr = info.dateStr; // Y-m-d format
        const clickedDate = new Date(dateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Only allow future dates or today
        if (clickedDate < today) return;
        
        fetch(`Admin-PickupAvailability.php?action=check_timeslots&date=${dateStr}`)
            .then(response => response.text())
            .then(data => {
                if (data === '1') {
                    // Convert date to dd/mm/yyyy for display
                    const [year, month, day] = dateStr.split('-');
                    const displayDate = `${day}/${month}/${year}`;
                    loadAvailableDates(displayDate);
                    document.getElementById("viewSlotModal").style.display = "flex";
                } else {
                    alert('No timeslots available for this date.');
                }
            })
            .catch(error => {
                console.error('Error checking timeslots:', error);
                alert('Error checking timeslots. Please try again.');
            });
    }
    });

    calendar.render();

    // Add this to your existing script
    function adjustScrollbarSpace() {
        const mainContent = document.querySelector('.main-content');
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        
        if (scrollbarWidth > 0) {
            mainContent.style.paddingRight = '0';
        } else {
            mainContent.style.paddingRight = '17px';
        }
    }

    // Call on load and resize
    window.addEventListener('load', adjustScrollbarSpace);
    window.addEventListener('resize', adjustScrollbarSpace);

function loadAvailableDates(selectedDate = null) {
    fetch("Admin-PickupAvailability.php?action=get_timeslot_dates")
        .then(response => response.text())
        .then(datesStr => {
            const dates = datesStr.split(',');
            slotDateSelect.innerHTML = '<option value="">-- Select a date --</option>';
            
            dates.forEach(date => {
                if (date) { // Skip empty dates
                    const option = document.createElement('option');
                    option.value = date;
                    option.textContent = date;
                    if (selectedDate && date === selectedDate) {
                        option.selected = true;
                    }
                    slotDateSelect.appendChild(option);
                }
            });
            
            if (selectedDate) {
                // Convert the selectedDate from d/m/Y to Y-m-d for the API call
                const [day, month, year] = selectedDate.split('/');
                const apiFormattedDate = `${year}-${month}-${day}`;
                loadTimeslotsForDate(apiFormattedDate);
            }
        })
        .catch(error => {
            console.error('Error loading available dates:', error);
        });
}

    function loadTimeslotsForDate(date) {
        let apiDate = date;
        if (date.includes('/')) {
            const [day, month, year] = date.split('/');
            apiDate = `${year}-${month}-${day}`;
        }
        
        fetch(`Admin-PickupAvailability.php?date=${encodeURIComponent(apiDate)}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(timeslots => {
            timeslotSelect.innerHTML = '<option value="">-- Select a timeslot --</option>';
            
            timeslots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.id;
                option.textContent = slot.timeRange;
                // Store booking count as a data attribute
                option.setAttribute('data-booking-count', slot.bookingCount || 0);
                timeslotSelect.appendChild(option);
            });
            
            // Initialize button state
            updateDeleteButtonState();
        })
        .catch(error => {
            console.error('Error loading timeslots:', error);
            alert(`Error loading timeslots: ${error.message}`);
        });
    }

    function updateDeleteButtonState() {
    const selectedOption = timeslotSelect.options[timeslotSelect.selectedIndex];
    const bookingInfo = document.getElementById('bookingInfo');
    
    if (selectedOption && selectedOption.value) {
        const bookingCount = parseInt(selectedOption.getAttribute('data-booking-count')) || 0;
        
        if (bookingCount > 0) {
            // Timeslot has bookings - disable button and show info
            deleteTimeslotBtn.disabled = true;
            deleteTimeslotBtn.classList.add('disabled-delete-btn');
            bookingInfo.style.display = 'flex';
        } else {
            // Timeslot has no bookings - enable button and hide info
            deleteTimeslotBtn.disabled = false;
            deleteTimeslotBtn.classList.remove('disabled-delete-btn');
            bookingInfo.style.display = 'none';
        }
    } else {
        // No timeslot selected - disable button
        deleteTimeslotBtn.disabled = true;
        bookingInfo.style.display = 'none';
    }
}

    timeslotSelect.addEventListener('change', updateDeleteButtonState);

        //slot from guard
    document.getElementById("slotForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const selectedDate = new Date(formData.get('date'));
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert("Cannot create time slots for past dates");
            return;
        }
        
        if (!formData.get('time')) {
            alert("Please select a time slot");
            return;
        }
        
        fetch('Admin-PickupAvailability.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            closeSlotModal();
            // Reset form
            this.reset();
            // Refresh calendar
            calendar.refetchEvents();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating timeslot. Please try again.');
        });
    });


    timeslotSelect.addEventListener('change', function() {
        updateDeleteButtonState();
    });

    deleteTimeslotBtn.addEventListener('click', function() {
    const slotId = timeslotSelect.value;
    if (!slotId) return;

    if (confirm("Are you sure you want to delete this timeslot?")) {
        fetch(`Admin-PickupAvailability.php?action=remove_timeslot`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `slot_id=${slotId}`
        })
            .then(response => response.text())
            .then(data => {
                alert(data);
                // Refresh the timeslots for the current date
                const currentDate = slotDateSelect.value;
                if (currentDate) {
                    // Convert dd/mm/yyyy back to yyyy-mm-dd for API call
                    const [day, month, year] = currentDate.split('/');
                    const apiDate = `${year}-${month}-${day}`;
                    loadTimeslotsForDate(apiDate);
                }
                // Refresh the calendar
                calendar.refetchEvents();
            });
    }
});

    // Modal functions
    window.openSlotModal = function() {
        document.getElementById("createSlotModal").style.display = "flex";
    };
    
    window.closeSlotModal = function() {
        document.getElementById("createSlotModal").style.display = "none";
    };
    
    window.closeViewSlotModal = function() {
        document.getElementById("viewSlotModal").style.display = "none";
    };

    // Fix for create slot button
    document.getElementById("createSlotBtn").addEventListener("click", function() {
        openSlotModal();
    });

    // Modal close on background click
    window.onclick = function (event) {
        const createModal = document.getElementById('createSlotModal');
        const viewModal = document.getElementById('viewSlotModal');
        if (event.target === createModal) closeSlotModal();
        if (event.target === viewModal) closeViewSlotModal();
    };

    // Remove timeslot
    window.removeTimeslot = function (slotId) {
        if (confirm("Are you sure you want to remove this timeslot?")) {
            fetch(`Admin-PickupAvailability.php?action=remove_timeslot`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `slot_id=${slotId}`
            })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    // Refresh the timeslots for the current date
                    const currentDate = slotDateSelect.value;
                    if (currentDate) {
                        loadTimeslotsForDate(currentDate);
                    }
                    // Refresh the calendar
                    calendar.refetchEvents();
                });
        }
    };
});
</script>

</head>
<body>
    <div class="sidebar">
        <div>
        <a href="Admin-Dashboard.php" >
            <img src="User-Logo.png" 
                style="width: 220px; margin-bottom: 40px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;">
        </a>
        </div>
        <ul class="menu">
            <li><a href="Admin-Dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="Admin-Notification.php"><i class="fa-solid fa-bell"></i>Notifications</a></li>
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li class="active"><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li ><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
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

    <!-- cannot be deleted -->
    <button class="dropdown-btn" onclick="toggleDropdown(event)" style="display:none;">
                    <i class="fa-solid fa-chevron-down"></i>
    </button> 

    <div class="main-content">
        <div class="content">
            <div class="title">
                <h2>Pickup Availability</h2>
            </div>
        </div>
        <hr style="width: 90%; margin-left: 75px;">
        <div class="pickupavailability-container">
            <div class="calendar-table-wrapper" style="display: flex; flex-direction: row; gap: 30px; margin-top: 10px;">

<!-- Calendar -->
        <section class="calendar">
            <div id="fullCalendar" style="width: 86%; margin-left:100px; background-color: white; padding:10px; position: relative; border-radius: 10px 10px 0 0;"></div>
            <div class="legend-container" style="width: 86%; margin-left:100px; background-color: white; padding-top: 10px; padding-bottom: 20px; border-radius: 0 0 10px 10px; display: flex; justify-content: center; gap: 20px; margin-top: -1px;">
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #e0e0e0; border: solid 1px;"></div>
                    <span>No Longer Available</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #E1EFC7; border: solid 1px;"></div>
                    <span>Empty Slot</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #FFD3D3; border: solid 1px;"></div>
                    <span>Fully Booked</span>
                </div>
            </div>
        </section>
    </div>
    </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
        <button class="createSlotBtn" onclick="openSlotModal()" id="createSlotBtn">
            <i class="fa-solid fa-plus"></i>
        </button>
        </div>
    </div>




        <!-- Create Pickup Slot Modal -->
        <div id="createSlotModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center;">
        <div class="modalExtra">
        <button onclick="closeSlotModal()" style="position: absolute; right: 40px; top: 40px; background: none; border: none; font-size: 36px; cursor: pointer; color: #666;">&times;</button>
        
        <h2 style="margin-bottom: 20px; font-size: 30px; color: black; font-weight: bold; text-align: left;">Add New Pickup Slot</h2>
                
                <p style="margin-bottom: 30px; color: #595959; font-size: 16px; line-height: 1.5;">Please fill in this form to add a new pickup slot.</p>
                
                <form id="slotForm" method="POST" action="Admin-PickupAvailability.php" style="flex: 1; display: flex; flex-direction: column;">
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; margin-bottom: 10px; color: #595959; font-size: 16px; font-weight: 500;">Date</label>
                        <input type="date" name="date" required style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ddd; font-size: 16px; font-family: Arial, sans-serif;">
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="display: block; margin-bottom: 10px; color: #595959; font-size: 16px; font-weight: 500;">Time Slot</label>
                        <div class="custom-select">
                            <select name="time" id="selectedSlotInput" class="select-with-arrow" required style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ddd; font-size: 16px; color: #333;">
                                <option value="">-- Select a time slot --</option>
                                <option value="10:00:00">10:00 AM - 11:00 AM</option>
                                <option value="11:00:00">11:00 AM - 12:00 PM</option>
                                <option value="12:00:00">12:00 PM - 01:00 PM</option>
                                <option value="13:00:00">01:00 PM - 02:00 PM</option>
                                <option value="14:00:00">02:00 PM - 03:00 PM</option>
                                <option value="15:00:00">03:00 PM - 04:00 PM</option>
                                <option value="16:00:00">04:00 PM - 05:00 PM</option>
                                <option value="17:00:00">05:00 PM - 06:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: auto; margin-bottom: 10px; display: flex; justify-content: center;">
                        <button type="submit" style="background: #4E7831; color: white; padding: 12px 0; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500; width: 100%; max-width: 600px;">
                            Add Pickup Slot
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <!-- View Timeslot Modal -->
    <aside id="viewSlotModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center;">
        <div class="modal-content">
            <div class="containerz">
                <button class="modal-close" style="position: absolute; right:50px; top: 40px; background: none; border: none; font-size: 35px; cursor: pointer; color: #858585;" onclick="closeViewSlotModal()">&times;</button>
                <h2 style="margin-bottom: 20px; text-align: left; font-size: 30px; color: black;">Pickup Slots</h2>
                
                <p style="font-family: Arial, sans-serif; font-size: 16px; font-weight: normal; color: #595959; margin-bottom: 30px;">
                    Please select a date and timeslot for deletion.
                </p>
                <!-- Date Dropdown -->
                <div style="margin-bottom: 25px;">
                    <label for="slotDateSelect" style="font-size: 16px; color: #595959; margin-bottom: 10px; display: block;">Date</label>
                    <div class="custom-select">
                        <select id="slotDateSelect" class="select-with-arrow" style="width: 100%; padding: 15px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; height: 50px;">
                            <option value="">-- Select a date --</option>
                        </select>
                    </div>
                </div>
                
                <!-- Timeslot Dropdown -->
                <div style="margin-bottom: 25px;">
                    <label for="timeslotSelect" style="font-size: 16px; color: #595959; margin-bottom: 10px; display: block;">Timeslot</label>
                    <div class="custom-select">
                        <select id="timeslotSelect" class="select-with-arrow" style="width: 100%; padding: 15px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; height: 50px;">
                            <option value="">-- Select a timeslot --</option>
                        </select>
                    </div>
                </div>
                
                <!-- Booking Info (hidden by default) -->
                <div id="bookingInfo" class="booking-info" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span>There are existing bookings from user.</span>
                </div>

                <!-- Delete Button -->
                <div style="margin-top: 20px;">
                    <button id="deleteTimeslotBtn" 
                        style="background: #c6433a; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; margin: 10px 0px 20px;" 
                        disabled>
                        Delete Selected Timeslot
                    </button>
                </div>
            </div>   
        </div>
    </aside>
               
</body>
</html>