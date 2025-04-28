<?php
// Admin-Fetch-notification.php

// Include your database connection file or establish connection here
// Example:
// include 'db_connect.php';

// OR establish connection directly (make sure to handle errors)
$servername = "localhost";
$username = "root";
$password = ""; // Your database password
$dbname = "cp_assignment"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database Connection failed in Admin-Fetch-notification.php: " . $conn->connect_error);
    // Output an error message as an <li> so it appears in the list
    echo '<li><div class="no-notifications">Error connecting to database: ' . htmlspecialchars($conn->connect_error) . '</div></li>';
    exit();
}

// --- Fetch Notifications ---

// Get sorting parameters from GET request, if they exist (although we removed the dropdown,
// the client-side code might still pass them if not fully updated, or for future use)
// Default sort by datetime descending if no parameters are provided
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'datetime';
$sort_order = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'DESC';

// Validate sort_by to prevent SQL injection
$allowed_sort_columns = ['anoti_id', 'title', 'datetime', 'user_id', 'username']; // Add other sortable columns if needed
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'datetime'; // Default to datetime if invalid column requested
}

// Validate sort_order
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
    $sort_order = 'DESC'; // Default to DESC if invalid order
}


// SQL query to fetch notifications and user data, including profile_image
$sql = "SELECT
            n.anoti_id,
            n.title,
            n.announcement,
            n.datetime,
            n.user_id,
            u.username,
            u.profile_image     -- *** Added profile_image column ***
        FROM
            admin_notification n
        LEFT JOIN
            user u ON n.user_id = u.user_id
        ORDER BY
            n." . $sort_by . " " . $sort_order; // Use the validated sort parameters


$result = $conn->query($sql);

// --- Generate HTML Output ---

$output_html = ''; // Initialize an empty string

// Define the base path for user profile images
// *** Make sure this path is correct - you confirmed './' works! ***
$profile_image_base_path = './'; // Use './' for the same folder as the PHP file

// Define a default avatar image URL if a user has no image or no user is linked
// *** Update this path if your default avatar is also in the same folder ***
$default_avatar_url ='./default_avatar.png';// Example: If default avatar is also in the same folder


// Check if the query was successful AND returned rows
if ($result && $result->num_rows > 0) {
    // Loop through each notification row
    while($row = $result->fetch_assoc()) {

        // Determine the user's avatar URL
        $avatar_url = $default_avatar_url; // Start with the default
        // Check if a user is linked AND they have a profile image filename stored
        if (!empty($row['user_id']) && !empty($row['profile_image'])) {
             // Construct the full path to the user's image
             // Ensure the filename is safely encoded for use in a URL
            $avatar_url = $profile_image_base_path . urlencode($row['profile_image']);
        }

        // Format the timestamp
        $display_timestamp = $row['datetime'] ? date("Y-m-d H:i:s", strtotime($row['datetime'])) : 'N/A';

        // --- START OF HTML GENERATION FOR A SINGLE NOTIFICATION ITEM ---

        $output_html .= '<li>';
        $output_html .= '    <div class="notification-card">';
        $output_html .= '        <div class="card-header">';
        $output_html .= '            <div class="user-info">';

        // Display the user's avatar image
        $output_html .= '                <img src="' . htmlspecialchars($avatar_url) . '" alt="User Avatar" class="user-avatar">';

        // Display username
        $display_username = htmlspecialchars($row['username'] ?? 'Unknown User');
        $output_html .= '                <span class="username">' . $display_username . '</span>';

        $output_html .= '            </div>'; // End user-info

        // Display timestamp
        $output_html .= '            <span class="datetime">' . htmlspecialchars($display_timestamp) . '</span>';
        $output_html .= '        </div>'; // End card-header

        $output_html .= '        <div class="card-body">';

        // Display notification title
        $output_html .= '            <div class="title-bar"><strong>' . htmlspecialchars($row['title'] ?? 'No Title') . '</strong></div>';

        // Start content lines container - will now only contain the announcement
        $output_html .= '            <div class="content-lines">';

        // --- ONLY Display the Announcement content line ---
        $output_html .= '                <div class="line">';
        // Removed "<strong>Announcement:</strong>" label
        $output_html .= '                    <span>' . htmlspecialchars($row['announcement'] ?? 'No content.') . '</span>'; // Display only the announcement text
        $output_html .= '                </div>';

        // Removed the HTML generation for Notification ID and Linked User ID


        $output_html .= '            </div>'; // End content-lines

        $output_html .= '        </div>'; // End card-body
        $output_html .= '    </div>'; // End notification-card
        $output_html .= '</li>'; // End list item

        // --- END OF HTML GENERATION FOR A SINGLE NOTIFICATION ITEM ---

    } // End of while loop
} else {
    // If query failed or returned no rows
    if ($conn->error) {
        error_log("Error executing notification query in Admin-Fetch-notification.php: " . $conn->error);
        $output_html = '<li><div class="no-notifications">Error loading notifications: ' . htmlspecialchars($conn->error) . '</div></li>';
    } else {
        $output_html = '<li><div class="no-notifications">No notifications found.</div></li>';
    }
}

// --- Output the HTML ---
echo $output_html;

// Close the database connection
$conn->close();
?>