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

// Define the query to fetch the recent drop-off activities, including dropoff_id
$recentActivitiesQuery = "SELECT dropoff_id, status, dropoff_time FROM dropoff ORDER BY dropoff_time DESC LIMIT 5";

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
        'dropoff_id' => $row['dropoff_id'],  // Drop-off ID
        'status' => $row['status'],          // Activity status
        'dropoff_time' => $row['dropoff_time']  // Drop-off time
    ];
}

// Return the activities as JSON
echo json_encode($activities);

// Close the connection
$conn->close();
?>
