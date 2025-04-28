<?php
    session_start();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_login"])) {
        echo isset($_SESSION["user_id"]) ? "true" : "false";
        exit();
    }

    $user_id = $_SESSION["user_id"] ?? null;

    $con = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $unreadCount = 0; 

    if ($user_id) { 
        $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
        $unreadResult = mysqli_query($con, $unreadQuery);
        $unreadData = mysqli_fetch_assoc($unreadResult);
        $unreadCount = $unreadData['unread_count'];
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
        $id = mysqli_real_escape_string($con, $_POST["id"]);
        
        $query = "UPDATE user_notification SET status='read' WHERE unoti_id='$id'";
        
        if (mysqli_query($con, $query)) {
            $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
            $unreadResult = mysqli_query($con, $unreadQuery);
            $unreadData = mysqli_fetch_assoc($unreadResult);
            echo $unreadData['unread_count']; 
        } else {
            echo "Error: " . mysqli_error($con);
        }
        exit();
    }

    $unreadQuery = "SELECT COUNT(*) AS unread_count FROM user_notification WHERE user_id = '$user_id' AND status = 'unread'";
    $unreadResult = mysqli_query($con, $unreadQuery);
    $unreadData = mysqli_fetch_assoc($unreadResult);
    $unreadCount = $unreadData['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
</head>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playpen+Sans:wght@100..800&display=swap');

    *{
        margin: 0px;
        padding: 0px;
        font-family: "Open Sans", sans-serif;
        box-sizing: border-box;
    }

    .material-icons{
        font-size: 30px;
    }

    header {
        position: sticky;
        z-index: 1000;
        top: 0;
        height: 73px;
        background-color:#78A24C;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 30px;
    }

    .logo-container img {
        height: 40px;
        cursor: pointer;
    }

    .nav-links {
        list-style-type: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 20px;
    }

    .nav-links li {
        display: inline;
    }

    .nav-links a {
        color: rgba(255, 255, 255, 0.6);
        font-size: 16px;
        text-align: center;
        padding: 14px 16px;
        text-decoration: none;
        transition: all 0.3 ease;
    }

    .nav-links a.active, .nav-links a:hover {
        color: white !important;
        cursor: pointer;
    }

    .header-icons {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .noti-button, .profile-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        color: #ffffff;
        background: #78A24C;
        border: none;
        outline: none;
        border-radius: 50%;
        position: relative;
    }

    .noti-button:hover, .profile-button:hover {
        cursor: pointer;
    }

    .noti-button__badge {
        position: absolute;
        top: 5px;
        right: 0px;
        width: 20px;
        height: 20px;
        background: red;
        color: #ffffff;
        font-family: "Playpen Sans", cursive;
        display: <?php echo ($unreadCount > 0) ? 'flex' : 'none'; ?>;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
    }

    .notification-container {
        display: flex;
        height: 78vh;
        padding: 0 40px 0 63px;
        background: white;
        overflow: hidden;
    }
    
    .notification-title h1 {
        font-size: 26px;
        font-family: "Playpen Sans", cursive;
        /* padding: 40px 0 10px 0; */
        padding: 25px 40px 20px 63px;
    }

    .notification-list {
        width: 30%;
        border-right: 2px solid #ddd;
        overflow-y: auto;
        height: 78vh;
        background:#F4F4EB;
        border: 2px solid rgb(189, 188, 188);
    }

    .notification-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .notification-list li {
        background: white;
        font-size: 15px;
        padding: 15px;
        border-bottom: 1px solid rgb(189, 188, 188);
        cursor: pointer;
        transition: background 0.3s;
    }

    .notification-list li:hover {
        background: rgba(248, 227, 124, 0.48) !important;
    }

    .notification-list li.selected {
        background: rgb(248, 227, 124) !important;
    }

    .notification-list li.read {
        background: #F4F4EB;
    }

    .notification-content {
        width: 70%;
        padding: 20px;
        background: #F4F4EB;
        border: 2px solid rgb(189, 188, 188);
        font-size: 15px;
        line-height: 1.6;
    }
</style>

<body>
    <header>
        <div class="logo-container">
            <img src="User-Logo.png" onclick="window.location.href='User-Homepage.php'">
        </div>
        <ul class="nav-links">
            <li><a onclick="window.location.href='User-Homepage.php'">Home</a></li>
            <li><a onclick="window.location.href='User-Pickup Scheduling.php'">Pickup Scheduling</a></li>
            <li><a onclick="window.location.href='User-Drop-off Points.php'">Drop-off Points</a></li>
            <li><a onclick="window.location.href='User-Rewards.php'">Rewards</a></li>
            <li><a onclick="window.location.href='User-Review.php'">Review</a></li>
            <li><a onclick="window.location.href='User-FAQ.php'">FAQ</a></li>
        </ul>
        
        <div class="header-icons">
            <button class="noti-button" type="button" onclick="redirectToNotifications()">
                <span class="material-icons">mail</span>
                <?php if ($user_id && $unreadCount > 0) { ?>
                    <span class="noti-button__badge" id="notiBadge"><?php echo $unreadCount; ?></span>
                <?php } ?>
            </button>
            <button class="profile-button" type="button" onclick="window.location.href = 'User-Profile.php'">
                <span class="material-icons">account_circle</span>
            </button>
        </div>
    </header>

    <div class=notification-title>
        <h1>Notifications</h1>
    </div>

    <div class=notification-container>
        <div class=notification-list>
            <ul>
                <?php
                    if ($user_id) {
                        $userAnnouncement = "SELECT unoti_id, user_id, datetime, title, announcement, status
                                            FROM user_notification WHERE user_id = '$user_id' ORDER BY datetime DESC;";
                        
                        $result = mysqli_query($con, $userAnnouncement);

                        while ($row = mysqli_fetch_array($result)) {
                            ?>
                            <li class="notification-item 
                            <?php echo ($row['status'] === 'read') ? 'read' : ''; ?>" 
                            data-id="<?php echo $row['unoti_id']; ?>"
                            data-title="<?php echo htmlspecialchars($row['title']); ?>" 
                            data-datetime="<?php echo htmlspecialchars($row['datetime']); ?>"
                            data-announcement="<?php echo htmlspecialchars($row['announcement']); ?>"
                            data-status="<?php echo $row['status']; ?>">
                                <strong><?php echo $row['title']; ?></strong><br>
                                <small><?php echo $row['datetime']; ?></small>
                            </li>
                            <?php
                        }

                        mysqli_close($con);
                    }
                ?>
            </ul>
        </div>

        <div class=notification-content>
            <h2 id="notifTitle">
                <img style="margin-left: -95px; margin-top: 30px; position: absolute;" src ="User-Notification-Choose Image.svg" width="700">
            </h2>
            <p id="notifDate"></p>
            <br>
            <p id="notification-text"></p>
        </div>
    </div>

    <script>
        function redirectToNotifications() {
            fetch("User-Notification.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "check_login=true"
            })
            .then(response => response.text())
            .then(isLoggedIn => {
                if (isLoggedIn.trim() === "true") {
                    window.location.href = 'User-Notification.php';
                } else {
                    window.location.href = 'User-Login.php'; 
                }
            })
            .catch(error => console.error("Error checking login:", error));
        }

        document.addEventListener("DOMContentLoaded", function () {
            const notificationItems = document.querySelectorAll(".notification-item");
            const notifTitle = document.getElementById("notifTitle");
            const notifDate = document.getElementById("notifDate");
            const notifText = document.getElementById("notification-text");
            const notiBadge = document.getElementById("notiBadge");

            notificationItems.forEach(item => {
                item.addEventListener("click", function () {
                    const id = this.getAttribute("data-id"); 
                    const title = this.getAttribute("data-title");
                    const datetime = this.getAttribute("data-datetime");
                    const announcement = this.getAttribute("data-announcement");
                    const status = this.getAttribute("data-status");

                    notifTitle.textContent = title;
                    notifDate.textContent = datetime;
                    notifText.textContent = announcement;

                    notificationItems.forEach(el => el.classList.remove("selected"));
                    this.classList.add("selected");

                    if (status !== "read") {
                        this.classList.add("read");
                        this.setAttribute("data-status", "read");

                        fetch("User-Notification.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "id=" + encodeURIComponent(id)
                        })
                        .then(response => response.text())
                        .then(data => {
                            console.log(data);

                            let currentCount = parseInt(notiBadge.textContent);
                            if (currentCount > 0) {
                                notiBadge.textContent = currentCount - 1;
                            }

                            if (notiBadge && parseInt(notiBadge.textContent) === 0) {
                                notiBadge.style.display = "none";
                            }
                        })
                        .catch(error => console.error("Error:", error));
                    }
                });
            });
        });
        localStorage.setItem('activeTabIndex', 0);
    </script>
</body>
</html>     