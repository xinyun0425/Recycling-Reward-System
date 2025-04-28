<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'cp_assignment';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pickup Total Order
$totalOrdersQuery = "SELECT COUNT(*) AS total_orders FROM pickup_request";
$result = $conn->query($totalOrdersQuery);
$totalOrders = $result->fetch_assoc()['total_orders'];

// Pickup Pending
$pendingRequestsQuery = "SELECT COUNT(*) AS pending_requests FROM pickup_request WHERE status = 'Submitted'";
$result = $conn->query($pendingRequestsQuery);
$pendingRequests = $result->fetch_assoc()['pending_requests'];

// Dropoff Total Orders
$totalDropoffOrdersQuery = "SELECT COUNT(*) AS total_dropofforders FROM dropoff";
$result = $conn->query($totalDropoffOrdersQuery);
$totalDropoffOrders = $result->fetch_assoc()['total_dropofforders'];

// Dropoff Pending
$pendingDropoffRequestsQuery = "SELECT COUNT(*) AS pending_dropoff_requests FROM dropoff WHERE status = 'Submitted'";
$result = $conn->query($pendingDropoffRequestsQuery);
$pendingDropoffRequests = $result->fetch_assoc()['pending_dropoff_requests'];

// Prepare the response
$response = [
    'totalOrders' => $totalOrders,
    'pendingRequests' => $pendingRequests,
    'totalDropoffOrders' => $totalDropoffOrders,
    'pendingDropoffRequests' => $pendingDropoffRequests
];

// Return the JSON response
echo json_encode($response);

$conn->close();
?>
