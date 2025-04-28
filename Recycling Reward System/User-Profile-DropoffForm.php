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
    $getDropoffRequestQuery = mysqli_query($conn, "SELECT dr.dropoff_date AS date, location_id AS location FROM dropoff dr 
                                            WHERE dropoff_id = '$id'");
    $getDropoffRequestResult = mysqli_fetch_assoc($getDropoffRequestQuery);
    // Final response
    $result = [
        "date" => $getDropoffRequestResult['date'],
        "location" =>$getDropoffRequestResult['location']
    ];

    echo json_encode($result);
?>