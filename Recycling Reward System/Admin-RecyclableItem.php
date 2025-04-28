
<?php
    session_start();
    if (!isset($_SESSION['admin_id'])){
        header('Location:Admin-Login.php');
        exit();
    }
    $servername = "localhost"; 
    $username = "root";  
    $password = "";  
    $dbname = "cp_assignment";  

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
    }

    // First query:
    $recycleItem = [];
    $query1 = "
        SELECT 
            item_id,
            item_name,
            point_given
        FROM item
        WHERE status = 'Available'
        ORDER BY item_name ASC
    ";

    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $recycleItem[] = $row;
    }

    $message = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $itemID = $_POST["itemID"] ?? null;
        $itemName = $_POST["itemName"];
        $itemPoints = $_POST["itemPoints"];
    
        if (!empty($itemName) && !empty($itemPoints)) {
            $itemName = mysqli_real_escape_string($conn, $itemName);
            $itemPoints = mysqli_real_escape_string($conn, $itemPoints);
    
            if ($itemID) {
                $sql = "UPDATE item SET item_name='$itemName', point_given='$itemPoints' WHERE item_id='$itemID'";
                $successType = "edit";
            } else {
                $sql = "INSERT INTO item (item_name, point_given, status) VALUES ('$itemName', '$itemPoints', 'Available')";
                $successType = "add";
            }
    
            if ($conn->query($sql) === TRUE) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=$successType");
                exit();
            } else {
                $message = "Error: " . $conn->error;
            }
        } else {
            $message = "Please fill in all fields.";
        }
    }
    
    if (isset($_GET['disable_id'])) {
        $disable_id = intval($_GET['disable_id']);
        $updateQuery = "UPDATE item SET status = 'Unavailable' WHERE item_id = $disable_id";
    
        if ($conn->query($updateQuery)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=delete");
            exit();
        } else {
            echo "<script>alert('Failed to update item status.');</script>";
        }
    }
    
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'add':
                echo "<script>alert('Item added successfully.');</script>";
                break;
            case 'edit':
                echo "<script>alert('Item edited successfully.');</script>";
                break;
            case 'delete':
                echo "<script>alert('Item deleted successfully.');</script>";
                break;
        }
    
        echo "<script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>";
    }
    
    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recyclable Items Management - Green Coin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=notifications" />

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
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
    }
    
    .main-content {
        padding:20px;
        margin-left:300px;
        width:calc(100%-270px);
        overflow-y:auto;
        overflow-x:hidden;
    }
    .header {
        display: flex;
        flex-direction: column;
        align-items: left;
        justify-content: center;
        margin-left: 73px;
        animation: floatIn 0.8s ease-out;
    }

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
    }
    .logout:hover {
        background-color: rgba(249,226,226,0.91);
        transition:all 0.5s ease;
    }
    .logout i{
        padding-right:10px;
    }
    table {
        border-collapse: collapse;
        border: 1px solid #cbcbcb;
        width: 87%;
        font-size: 15px;
        margin-left: 74px;
        background-color: rgba(255, 255, 255, 0.5);
        table-layout: fixed;
        margin-bottom:30px;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        word-wrap: break-word;
    }

    th {
        background-color: #E0E1E1;
    }

    tr:hover {
        background-color: rgba(184, 194, 172, 0.05);
        cursor: pointer;
    }
    
    @media screen and (max-width: 768px) {
        th, td {
            font-size: 14px;
            padding: 10px;
        }
    }
    .editbutton{
        background-color: transparent;
        padding:5px 8px;
        font-size:16px;
        text-align:right;
        cursor:pointer;
        border:none;
        border-radius:8px;
    }

    .editbutton i{
        color:rgb(92, 147, 206);
    }

    .deletebutton{
        background-color:transparent;
        padding:5px 10px 5px 0px;
        text-align:center;
        cursor:pointer;
        border:none;
        font-size:16px;
        border-radius:8px;
    }

    .deletebutton i{
        color:rgba(222, 121, 84, 0.86);
    }

    .editbutton i:hover, .deletebutton i:hover{
        scale: 1.3;
        transition: scale 0.2s ease;
    }

    .add-btn {
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

    .add-btn:hover {
        background: #78A24C;
        scale: 1.1;
        transition: scale 0.3s ease;
    }

    .add-itemcontainer{
        background-color: #fefefe;
        /* margin: 5% auto; */
        padding: 20px;
        border: 1px solid #888;
        width: 600px;
        max-height: 80vh;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .additem-popup-container,
    .edititem-popup-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
        display: none;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0.3s, opacity 0.3s ease;
    }

    .additem-popup-container.show,
    .edititem-popup-container.show {
        display: flex;
        visibility: visible;
        opacity: 1;
    }

    .add-itempopup-content {
        background-color: #fefefe;
        padding: 35px 40px 0px 40px;
        width: auto;
        max-height: 80vh;
        overflow: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .popup-title {
        font-size: 30px;
        margin-bottom: 0;
    }

    .popup-description {
        margin-bottom: 20px;
        color: rgb(89, 89, 89);
        line-height: 1.5;
        font-size: 16px;
    }

    .close-btn {
        position: absolute;
        right: 30px;
        top: 20px;
        color: rgb(133, 133, 133);
        font-size: 35px;
        cursor: pointer;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
    }

    #addItemForm,
    #editForm {
        display: flex;
        flex-direction: column;
        max-height: 36vh;
        overflow-y: hidden;
    }

    #addItemForm label,
    #editForm label {
        color: rgb(89, 89, 89);
    }

    #addItemForm input[type="text"],
    #addItemForm input[type="number"],
    #addItemForm input[type="file"],
    #editForm input[type="text"],
    #editForm input[type="number"],
    #editForm input[type="file"] {
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color: #fff;
        font-family: Arial, sans-serif;
        border-radius: 5px;
        resize: none;
        border: 1px solid #ccc;
        outline: none;
    }
    #addItemForm label + input,
    #editForm label + input {
        margin-top: 10px; /* or whatever value you like */
    }

    .submitbutton {
        width: 100%;
        padding: 14px;
        background-color: rgb(78, 120, 49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
    }


    @keyframes popupFade {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    hr{
            border: none;
            height: 1.5px;
            background-color: rgb(197, 197, 196);
            opacity: 1;
        }
</style>
</head>
<body>
    <div class="sidebar">
        <div>
        <a href="Admin-Dashboard.php">
            <img src="User-Logo.png" 
                style="width: 200px; margin-bottom: 25px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;">
        </a>
        </div>
        <ul class="menu">
            <li><a href="Admin-Dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
            <li><a href="Admin-Notification.php"><i class="fa-solid fa-bell"></i>Notifications</a></li>
            <li><a href="Admin-Pickup-Pending.php"><i class="fa-solid fa-truck-moving"></i>Pickup Requests</a></li>
            <li><a href="Admin-PickupAvailability.php"><i class="fa-solid fa-calendar-check"></i>Pickup Availability</a></li>
            <li><a href="Admin-Drivers.php"><i class="fa-solid fa-id-card"></i>Drivers</a></li>
            <li><a href="Admin-Dropoff.php"><i class="fa-solid fa-box-archive"></i>Drop-off Requests</a></li> 
            <li><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li class="active"><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
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
    <div class ="main-content">
        <div class ="header">
            <h2>Recyclable Items Management</h2>
        </div>
        <hr style="width: 92%; margin-left:45px;margin-bottom:20px;">
        <div class="report-container">
            <table>
            <thead>
                <tr>
                    <th style="width:5%"></th>
                    <th style="width:55%">Item Name</th>
                    <th style="width:30%">Points Given</th>
                    <th style="width:10%;"></th>
                </tr>
            </thead>
            <tbody>
            <?php 
                $bil = 1;
                foreach ($recycleItem as $row) : 
                ?>
                    <tr>
                        <td><?php echo $bil++; ?></td>
                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['point_given']); ?></td>
                        <td><button class="editbutton"
                                data-id="<?php echo $row['item_id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>"
                                data-points="<?php echo $row['point_given']; ?>">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button 
                                class="deletebutton" 
                                data-id="<?php echo $row['item_id']; ?>" 
                                onclick="markAsUnavailable(this)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button class="add-btn" onclick="openPopup()">
            <i class="fa-solid fa-plus"></i>
        </button>
        <!-- Add Item Modal -->
        <div class="additem-popup-container" id="additempopup">
            <div class="add-itemcontainer">
                <div class="add-itempopup-content">
                    <span class="close-btn" onclick="closePopup()">&times;</span>
                    <h2 class="popup-title">Add New Item</h2>
                    <p class="popup-description">Fill in the details below to add a new item.</p>
                    <br>
                    <form id="addItemForm" method="POST" enctype="multipart/form-data">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" name="itemName" required>
                        <br>
                        <label for="itemPoints">Points Given</label>
                        <input type="number" id="itemPoints" name="itemPoints" required step="1" min="0" oninput="validity.valid||(value='');">
                        <button class="submitbutton" type="submit">Add Item</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <div class="edititem-popup-container" id="edititem-container">
            <div class="add-itemcontainer">
                <div class="add-itempopup-content">
                    <span class="close-btn" onclick="closeEditModal()">&times;</span>
                    <h2 class="popup-title">Edit Item</h2>
                    <p class="popup-description">Please update the new item details.</p>
                    <br>
                    <form id="editForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="editItemID" name="itemID">
                        <label for="editTitle">Item Name</label>
                        <input type="text" id="editTitle" name="itemName" required>
                        <br>
                        <label for="editPoints">Points Given</label>
                        <input type="number" id="editPoints" name="itemPoints" required step="1" min="0" oninput="validity.valid||(value='');">
                        <button class="submitbutton" type="submit">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    function disableScroll() {
        document.body.style.overflow = 'hidden';
    }

    function enableScroll() {
        document.body.style.overflow = 'auto';
    }

    // ---------- ADD ITEM ----------
    function openPopup() {
        document.getElementById("additempopup").classList.add("show");
        disableScroll();
    }

    function closePopup() {
        document.getElementById("additempopup").classList.remove("show");
        enableScroll();
    }

    document.getElementById("additempopup").addEventListener("click", function (e) {
        const popupContent = document.querySelector("#additempopup .add-itempopup-content");
        if (!popupContent.contains(e.target)) {
            closePopup();
        }
    });

    document.getElementById("addItemForm").addEventListener("submit", function () {
        location.reload(); // Simulate save & reload
    });

    // ---------- EDIT ITEM ----------
    function openEditPopup(id, name, points) {
        document.getElementById('editItemID').value = id;
        document.getElementById('editTitle').value = name;
        document.getElementById('editPoints').value = points;
        document.getElementById("edititem-container").classList.add("show");
        disableScroll();
    }

    function closeEditModal() {
        document.getElementById('edititem-container').classList.remove("show");
        enableScroll();
    }

    document.getElementById("edititem-container").addEventListener("click", function (e) {
        const editContent = document.querySelector("#edititem-container .add-itempopup-content");
        if (!editContent.contains(e.target)) {
            closeEditModal();
        }
    });

    document.querySelectorAll('.editbutton').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const points = button.getAttribute('data-points');
            openEditPopup(id, name, points);
        });
    });

    document.querySelector('.edititem-close-btn').addEventListener('click', closeEditModal);

    // Prevent E/e/+/- in number fields
    document.addEventListener("DOMContentLoaded", function () {
        const numericInputs = document.querySelectorAll("#itemPoints, #editPoints");

        numericInputs.forEach(input => {
            input.addEventListener("keydown", function (e) {
                if (["e", "E", "+", "-"].includes(e.key)) {
                    e.preventDefault();
                }
            });

            input.addEventListener("input", function () {
                this.value = this.value.replace(/[eE\+\-]/g, '');
            });

            input.addEventListener("paste", function (e) {
                const paste = (e.clipboardData || window.clipboardData).getData("text");
                if (/[eE\+\-]/.test(paste)) {
                    e.preventDefault();
                }
            });
        });
    });
    function markAsUnavailable(button) {
        const itemId = button.getAttribute("data-id");

        if (confirm("Are you sure you want to delete this item?")) {
            window.location.href = `?disable_id=${itemId}`;
        }
    }
</script>

</body>

</html>