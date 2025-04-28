<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Get data from JS
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['type'])) {
        echo json_encode(["error" => "Missing ID or Type"]);
        exit;
    }

    $id = $data['id'];
    $type = $data['type'];

    // Connect to DB
    $conn = mysqli_connect("localhost", "root", "", "cp_assignment");
    if (!$conn) {
        echo json_encode(["error" => "DB connection failed"]);
        exit;
    }

    // Fetch pickup form info
    $getPickupRequestFormQuery = mysqli_query($conn, "SELECT time_slot_id, contact_no, address, remark, item_image FROM pickup_request WHERE pickup_request_id = '$id'");
    if (!$getPickupRequestFormQuery) {
        echo json_encode(["error" => "pickup_request query failed"]);
        exit;
    }
    $getPickupRequestFormResult = mysqli_fetch_assoc($getPickupRequestFormQuery);

    // Fetch date and time
    $timeSlotId = $getPickupRequestFormResult['time_slot_id'];
    $getDateTimeQuery = mysqli_query($conn, "SELECT date, time FROM time_slot WHERE time_slot_id = '$timeSlotId'");
    if (!$getDateTimeQuery) {
        echo json_encode(["error" => "time_slot query failed"]);
        exit;
    }
    $getDateTimeResult = mysqli_fetch_assoc($getDateTimeQuery);

    // Fetch items
    $getItemQuery = mysqli_query($conn, "SELECT i.item_name, ipr.quantity FROM item_pickup ipr INNER JOIN item i ON ipr.item_id = i.item_id WHERE ipr.pickup_request_id = '$id'");
    $items = [];
    if ($getItemQuery) {
        while ($row = mysqli_fetch_assoc($getItemQuery)) {
            $items[] = $row;
        }
    }

    // Final response
    $result = [
        "date" => $getDateTimeResult['date'],
        "time" => $getDateTimeResult['time'],
        "contact_no" => $getPickupRequestFormResult['contact_no'],
        "address" => $getPickupRequestFormResult['address'],
        "remark" => $getPickupRequestFormResult['remark'],
        "item_image" => $getPickupRequestFormResult['item_image'],
        "items" => $items
    ];

    echo json_encode($result);
?>