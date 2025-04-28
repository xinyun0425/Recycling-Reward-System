<?php
// Database credentials
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'cp_assignment';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define the query to fetch the recent pickup request activities
$recentActivitiesQuery = "SELECT pickup_request_id, status, datetime_submit_form FROM pickup_request ORDER BY datetime_submit_form DESC LIMIT 5";

// Execute the query
$result = $conn->query($recentActivitiesQuery);

// Check if the query was successful
if ($result === false) {
    // If the query fails, output the error message
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

// Initialize an empty array to store activities
$activities = [];

// Fetch the results and add them to the array
while ($row = $result->fetch_assoc()) {
    $activities[] = [
        'pickup_id' => $row['pickup_request_id'],  // Renamed to 'pickup_id'
        'status' => $row['status'],  // Activity status
        'pickup_time' => $row['datetime_submit_form']  // Renamed to 'pickup_time'
    ];
}

// Check if any activities were retrieved
if (count($activities) === 0) {
    echo json_encode(['message' => 'No recent activities found.']);
    exit;
}

// Return the activities as JSON
echo json_encode($activities);

// Close the connection
$conn->close();
?>
