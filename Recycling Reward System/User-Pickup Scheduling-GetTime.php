<?php
    session_start();
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if(mysqli_connect_errno()){
        echo "Failed to connect to MySQL:".mysqli_connect_error();
    }

    $query = "SELECT date, time, no_driver_per_slot FROM time_slot";
    $result = $conn->query($query);
    $events = [];  

    while ($row = $result->fetch_assoc()) {
        $date = $row['date']; 
        $time = $row['time'];
        $no_driver_per_slot = $row['no_driver_per_slot'];
        
        $today = date('Y-m-d');
        $oneWeekLater = date('Y-m-d', strtotime('+5 days'));
        if ($date < $today || $date < $oneWeekLater) {
            $status = 'disabled';  
        }else if ($no_driver_per_slot == 0){
            $status = 'fully booked';
        }else{
            $status = 'enabled';
        }

        $timestamp = strtotime($time);
        $formattedTime = date("H:i ", $timestamp);
        $text = $formattedTime . " - " . date("H:i", $timestamp + 60 * 60);

        if (!isset($events[$date])) {
            $events[$date] = [];
        }
        
        $events[$date][] = ['status' => $status, 'content' => $text];  
    }

    $conn->close();
    echo json_encode($events);
?>