<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin-Login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drop-off Points Management - Green Coin</title>
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

    .profile-container{
        width:100%;
        margin-top:130px;
        bottom:12px;
        margin-top:0px;
    }

    .profile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f8f9fa ;
        border-radius: 10px;
        border:2px solid rgba(116, 116, 116, 0.76);
        padding: 15px; 
        padding-left:20px;
        width: 93%;
        position: relative;
        margin: 15px;
        box-sizing: border-box;
    }

    .profileicon {
        font-size: 30px;
        color: #333;
    } 

    .profile-info {
        font-size: 14px;
        flex-grow: 1;
        padding-left: 15px;
    }

    .profile-info p {
        margin: 0;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin-left:13px;
        width:220px;
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

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%; 
        background: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        width: 150px; 
        text-align: left;
        z-index: 10;
        padding: 5px 0;
    }

    .dropdown-btn {
        border: none;
        background-color: transparent;
        cursor: pointer;
        font-size: 16px;
    }

    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
        text-align: center;
    }

    .dropdown a:hover {
        background: #E4EBE6;
        color: rgb(11, 91, 19);
    }

    .content{
        padding:20px;
        margin-left:300px;
        width:calc(100%-270px);
        overflow-y:auto;
    }

    .title{
        display:flex;
        flex-direction: column;
        align-items:left;
        justify-content: center;  
        margin-left:73px;
        animation:floatIn 0.8s ease-out;
    }

    .map-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 86.5%; 
        height: 400px; 
        margin: 30px auto; 
        border: 2px solid #ccc; 
        border-radius: 10px; 
    }

    .detail{
        display:flex;
        justify-content:flex-end;
        width:88.9%;
        margin:30px auto;
    }

    .addcollectionbutton{
        background-color:#3BB143;
        padding:10px 20px;
        font-size:16px;
        float:right;         
        cursor:pointer;
        border-radius:8px;
        border:2px solid #3BB143;
        color:white;
    }

    .addcollectionbutton:hover{
        background-color:transparent;
        color:rgb(73, 110, 9);
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

    .detail-card{
        display:flex;
        flex-direction:column;
        width:85.5%;
        margin:15px auto;
        background-color: #F9FFA4;
        padding:0px 20px 20px;
        border-radius:8px;
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1); 
        transition: box-shadow 0.3s ease-in-out;
    }

    .detail-card:hover{
        box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.2);
    }

    .location-title{
        width: 100%;
        padding-top: 20px;
    }

    .editbutton{
        background-color: transparent;
        /* color:rgb(144, 144, 144); */
        padding:5px 8px;
        font-size:16px;
        text-align:right;
        cursor:pointer;
        border:none;
        border-radius:8px;
        margin-top: 20px;
    }

    .editbutton i{
        /* color:rgb(144, 144, 144); */
        color:rgb(92, 147, 206);
    }

    .deletebutton{
        background-color:transparent;
        padding:5px 10px 5px 0px;
        text-align:center;
        cursor:pointer;
        border:none;
        font-size:16px;
        margin-top: 20px;
        border-radius:8px;
    }

    .deletebutton i{
        /* color:rgb(144, 144, 144); */
        color:rgba(222, 121, 84, 0.86);
    }

    .editbutton i:hover, .deletebutton i:hover{
        scale: 1.3;
        transition: scale 0.2s ease;
    }

    .no-content {
        display:flex;
        flex-direction:column;
        width:70%;
        margin:15px auto;
        background-color:#d9d9d9;
        text-align:center;
        border-radius:8px;
        align-items:center;
    }

    .container{
        padding-left: 40px;
        padding-right: 40px;
        padding-top: 35px;
    }

    .container h2{
        font-size: 30px;
        line-height: 0.5;
    }

    .container label{
        color: rgb(89,89,89);
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow:auto;
        justify-content: center;
        align-items: center;
        background-color: rgba(0, 0, 0, 0.57);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: #fefefe;
        /* margin: 5% auto;  */
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

    .modal-content p {
        margin-bottom: 20px;
        color: rgb(89,89,89);
        line-height: 1.5;
        font-size:16px;
    }

    .modal-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 25px;
    }

    .modal-details label {
        font-weight: 600;
        color: #555;
    }

    .modal-details span {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 6px;
        display: block;
    }

    .modal-header {
        font-size: 30px;
        line-height: 1.8;
    }

    .modal-body {
        /* padding-left: 40px;
        padding-right: 20px; */
        padding-top:15px;
        max-height: 36vh;
        overflow-y: auto;
        overflow-x:hidden;
    }


    .close {
        position: absolute;
        right: 5px;
        top: 5px;
        color:rgb(133, 133, 133);
        font-size: 35px;
        cursor:pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    .imgcontainer {
        text-align: right;
        margin: 10px 20px 0 40px;
        position: relative;
    }

    input[type=text],textarea,[type=tel] {
        width: 100%;
        padding: 12px 10px;
        margin: 8px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px;
        background-color:#fff;
        font-family:Arial,sans-serif;
        border-radius:5px;
        resize:none;
        border: 1px solid #ccc; 
        outline: none;
    }

    .savechanges{
        width: 100%;
        padding: 14px;
        background-color:rgb(78,120,49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 30px;
        margin-bottom: 20px;
    }

    .info{
        display:none;
        margin-top:10px;
    }

    .info p{
        line-height: 0.8;
    }

    hr{
        border: none;
        height: 1.5px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
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
            <li class="active"><a href="Admin-DropoffPoints.php"><i class="fa-solid fa-map-location-dot"></i>Drop-off Points</a></li>
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable items</a></li>
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

    <?php
        $con = mysqli_connect("localhost", "root", "", "cp_assignment");  
        if (!$con) {
            die("Failed to connect to MySQL: " . mysqli_connect_error());
        }

        $sql = "SELECT location_id, location_name, address, contact_no, description FROM location WHERE status = 'Available' ORDER BY location_name";
        $result = mysqli_query($con, $sql);
        $locations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $locations[] = $row;
        }
        
        $locationsJSON = json_encode($locations);
    ?>
    <div class="content">
        <div class="title">
            <h2> Drop-off Points Management</h2>
        </div>
        
        <hr style="width: 92%; margin-left:45px;">
        <div class="map-container" id="map"></div>
        <!-- <div class="detail">
            <button class="addcollectionbutton" onclick="openAddModal()" name="addbutton"><i class="fa-solid fa-plus" style="padding-right:10px;"></i>Add Collection Center</button>
        </div> -->

        <?php if (!empty($locations)) { ?>
        <?php foreach ($locations as $location) { ?>
            <div class="detail-card" 
                
                style="cursor: pointer; padding: 0px 20px 20px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 5px; background: #f9f9f9; width: 82.9%;">

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="location-title" onclick="toggleInfo('<?php echo $location['location_id']; ?>')" >
                        <b style="font-size: 16px;"><?php echo htmlspecialchars($location['location_name']); ?></b>
                    </div>
                        <div style="display: flex; gap: 5px;">
                        <button class="editbutton" onclick="event.stopPropagation(); openEditModal(
                            '<?php echo $location['location_id']; ?>', 
                            '<?php echo htmlspecialchars($location['location_name']); ?>', 
                            '<?php echo htmlspecialchars($location['address']); ?>', 
                            '<?php echo htmlspecialchars($location['contact_no']); ?>', 
                            '<?php echo htmlspecialchars($location['description']); ?>')"><i class="fa-solid fa-pen"></i></button>
                        
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this drop-off point?');" style="margin: 0;">
                            <input type="hidden" name="delete_id" value="<?php echo $location['location_id']; ?>">
                            <button type="submit" name="deletebutton" class="deletebutton" onclick="event.stopPropagation();"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </div>

                <div id="info-<?php echo $location['location_id']; ?>" class="info" style="display: none; margin-top: 5px; margin-bottom: -10px; color: #333;">
                    <p><b>Address: </b><?php echo htmlspecialchars($location['address']); ?></p>
                    <p><b>Contact Number: </b><?php echo htmlspecialchars($location['contact_no']); ?></p>
                    <p><b>Opening Hour: </b><?php echo htmlspecialchars($location['description']); ?></p>
                </div>
            </div>
        <?php } ?>
        <?php } else { ?>
            <div class="no-content">
                <img src="admin_nothing_here.png" style="width:150px;">
                <p>No centre details are added.</p>
            </div>
        <?php } ?>
    </div>

    <button class="add-btn" onclick="openAddModal()" name="addbutton">
        <i class="fa-solid fa-plus"></i>
    </button>

    <div id="addcentre" class="modal">
        <form class="modal-content" action="#" method="post">
            <div class="imgcontainer">
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="container">
                <div>
                    <h2>Add Drop-off Point</h2>
                    <p>Fill in the details below to add a new drop-off point.</p>
                </div>
                <div class="modal-body">
                    <label>Location Name</label>
                    <input type="text" id="add-name" name="location_name" pattern="[A-Za-z\s'\-.,/]+" title="Please enter valid name" oninput="validateNameInput(event)" required><br><br>

                    <label>Address</label>
                    <textarea id="add-address" name="address" required></textarea><br><br>

                    <label>Contact Number</label>
                    <input type="tel" id="add-contact" name="contact_no" pattern="[0-9\-]+" title="Please enter valid contact number" oninput="validateContactInput(event)" required><br><br>

                    <label>Opening Hour</label>
                    <input type="text" id="add-description" name="description" required>
                </div>
                    <button class="savechanges" type="submit" name="addbutton">Add Drop-off Point</button>
            </div>
        </form>
    </div>
    <?php
        if(isset($_POST['addbutton'])){
        $con=mysqli_connect("localhost","root","","cp_assignment");
        if(!$con){
            echo "Failed to connect to MySQL:".mysqli_connect_error();
        }

        $centrename=mysqli_real_escape_string($con,$_POST['location_name']);
        $address=mysqli_real_escape_string($con,$_POST['address']);
        $contact_no=mysqli_real_escape_string($con,$_POST['contact_no']);
        $description=mysqli_real_escape_string($con,$_POST['description']);

        if(empty($centrename) || empty($address) || empty($contact_no) || empty($description)){
            echo "<script>alert('Error: Please enter full details.');</script>";
        }else{
            $sql_insert="INSERT INTO location(location_name,address,contact_no,description,status)
            VALUES ('$centrename','$address','$contact_no','$description','Available')";
            
        if (mysqli_query($con, $sql_insert)) {
            echo "<script>alert('Drop-off point added successfully.'); window.location.href='Admin-DropoffPoints.php';</script>";
        } else {
            echo "<script>alert('Error adding drop-off point: " . mysqli_error($con) . "');</script>";
        }
        }
        }
    ?>


    <div id="editcentre" class="modal">
        <form id="addForm" class="modal-content" action="#" method="post">
            <div class="imgcontainer">
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="container">
                <div class="form-header">
                    <h2>Edit Drop-off Point</h2>
                    <p>Please update the drop-off point details below.</p>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id" name="location_id">
                    <label>Location Name</label>
                    <input type="text" id="edit-name" name="location_name" pattern="[A-Za-z\s'\-.,/]+" title="Please enter valid name" oninput="validateNameInput(event)" required><br><br>

                    <label>Address</label>
                    <textarea id="edit-address" name="address" required></textarea><br><br>

                    <label>Contact Number</label>
                    <input type="text" id="edit-contact" name="contact_no" pattern="[0-9\-]+" title="Please enter valid contact number" oninput="validateContactInputEdit(event)" required><br><br>

                    <label>Opening Hour</label>
                    <input type="text" id="edit-description" name="description" required>
                </div>
                    <button class="savechanges" type="submit" name="editbutton">Save Changes</button>
            </div>
        </form>
    </div>
    <?php
        if(isset($_POST['editbutton'])){
            $location_id = mysqli_real_escape_string($con, $_POST['location_id']);                   
            $locationName=mysqli_real_escape_string($con,$_POST['location_name']);
            $locationAddress=mysqli_real_escape_string($con,$_POST['address']);
            $locationContact=mysqli_real_escape_string($con,$_POST['contact_no']);
            $locationDescription=mysqli_real_escape_string($con,$_POST['description']);

            $update_sql = "UPDATE location 
                            SET location_name='$locationName', address='$locationAddress', 
                            contact_no='$locationContact', description='$locationDescription' 
                            WHERE location_id='$location_id'";

            if (mysqli_query($con, $update_sql)) {
                echo "<script>alert('Drop-off point updated successfully.'); window.location.href='Admin-DropoffPoints.php';</script>";
            } else {
                echo "<script>alert('Error updating record: " . mysqli_error($con) . "');</script>";
            }
        }

        if (isset($_POST['deletebutton'])) {
            $location_id = mysqli_real_escape_string($con, $_POST['delete_id']);

            $delete_sql = "UPDATE location SET status = 'Unavailable' WHERE location_id='$location_id'";
            
            if (mysqli_query($con, $delete_sql)) {
                echo "<script>alert('Drop-off point deleted successfully.'); window.location.href='Admin-DropoffPoints.php';</script>";
            } else {
                echo "<script>alert('Error deleting record: " . mysqli_error($con) . "');</script>";
            }
        }
        
    ?>
    <script>
        function toggleDropdown(event) {
            event.stopPropagation(); 
            let dropdown = document.getElementById("profileDropdown");
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        }
        document.addEventListener("click", function(event) {
            let dropdown = document.getElementById("profileDropdown");
            let button = document.querySelector(".dropdown-btn");
            if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });
    </script>
    <script>
        var locations = <?php echo $locationsJSON; ?>;

        function initMap() {
            var map = new google.maps.Map(document.getElementById("map"), {
                // center: { lat: 3.0555, lng: 101.7005 }, 
                // zoom: 14,
                zoom: 11.9,
                center: { lat: 3.125, lng: 101.686 }  
            });

            var locations = <?php echo json_encode($locations); ?>; 
            var geocoder = new google.maps.Geocoder();

            locations.forEach(function (location) {
                geocoder.geocode({ address: location.address }, function (results, status) {
                    if (status === "OK") {
                        var marker = new google.maps.Marker({
                            map: map,
                            position: results[0].geometry.location,
                            title: location.location_name,
                        });

                        var infoWindow = new google.maps.InfoWindow({
                            content: "<b>" + location.location_name + "</b><br>" + location.address,
                        });

                        marker.addListener("click", function () {
                            infoWindow.open(map, marker);
                        });
                    } else {
                        console.log("Geocode failed: " + status);
                    }
                });
            });
        }

        function openEditModal(id, name, address, contact, description) {
            document.getElementById("editcentre").style.display = "flex";
            document.getElementById("edit-id").value = id;
            document.getElementById("edit-name").value = name;
            document.getElementById("edit-address").value = address;
            document.getElementById("edit-contact").value = contact;
            document.getElementById("edit-description").value = description;
            body.style.overflow = "hidden";
        }
        const body = document.body;

        function openAddModal(){
            document.getElementById("addcentre").style.display = "flex";
            body.style.overflow = "hidden";
        }

        function closeModal() {
            document.getElementById("editcentre").style.display = "none";
            body.style.overflow = "auto";
        }

        function closeAddModal() {
            document.getElementById("addcentre").style.display = "none";
            body.style.overflow = "auto";
        }

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this drop-off point?")) {
                document.getElementById('delete-id').value = id;
            }
        }

        document.addEventListener("DOMContentLoaded", function(){
            const addModal = document.getElementById('addcentre');
            addModal.addEventListener("click", function(event){
                if(event.target === addModal){
                    addModal.style.display = "none";
                    body.style.overflow = "auto";
                }
            });

            const editModal = document.getElementById('editcentre');
            editModal.addEventListener("click", function(event){
                if(event.target === editModal){
                    editModal.style.display = "none";
                    body.style.overflow = "auto";
                }
            });
        });
        
        function toggleInfo(locationId) {
            var infoDiv = document.getElementById("info-" + locationId);
            if (infoDiv.style.display === "none" || infoDiv.style.display === "") {
                infoDiv.style.display = "block";
            } else {
                infoDiv.style.display = "none";
            }
        }

        function validateNameInput(event) {
            const input = event.target;
            const regex = /^[A-Za-z\s'\-.,/]*$/; 

            if (!regex.test(input.value)) {
                input.value = input.value.slice(0, -1);
            }
        }

        function validateContactInput(event) {
            const input = event.target;
            const regex = /^[0-9\-]*$/;

            if (!regex.test(input.value)) {
                input.value = input.value.slice(0, -1);
            }
        }

        function validateContactInputEdit(event) {
            const input = event.target;
            input.value = input.value.replace(/[^0-9\-]/g, '');
        }

        
    </script>
    <script 
        async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCuzWuyPcG8GwD5dRIHV0sFm3FdvJW_y3o&callback=initMap">
    </script>
</body>
</html>