<?php
    session_start();

    if (!isset($_SESSION['admin_id'])) {
        header('Location: Admin-Login.php'); 
        exit();
    }

    $con = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }

    $result = $con->query("SELECT `name` FROM admin LIMIT 1"); 
    $testAdmin = $result->fetch_assoc();

    // Fetch FAQ categories
    $categoryQuery = "SELECT DISTINCT category FROM faq";
    $categoryResult = mysqli_query($con, $categoryQuery);
    $categories = [];
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row['category'];
    }

    //  CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_faq'])) {
            $category = mysqli_real_escape_string($con, $_POST['faq_category']);
            $question = mysqli_real_escape_string($con, $_POST['faq_question']);
            $answer = mysqli_real_escape_string($con, $_POST['faq_answer']);

            $query = "INSERT INTO faq (question, answer, category) VALUES ('$question', '$answer', '$category')";
            $_SESSION['message'] = mysqli_query($con, $query) ? "FAQ added successfully." : "Error adding FAQ: " . mysqli_error($con);
        } elseif (isset($_POST['edit_faq'])) {
            $faq_id = (int)$_POST['faq_id'];
            $category = mysqli_real_escape_string($con, $_POST['faq_category']);
            $question = mysqli_real_escape_string($con, $_POST['faq_question']);
            $answer = mysqli_real_escape_string($con, $_POST['faq_answer']);

            $query = "UPDATE faq SET question='$question', answer='$answer', category='$category' WHERE faq_id=$faq_id";
            $_SESSION['message'] = mysqli_query($con, $query) ? "FAQ updated successfully." : "Error updating FAQ: " . mysqli_error($con);
        } elseif (isset($_POST['delete_faq_id'])) {
            $faq_id = (int)$_POST['delete_faq_id'];
            $query = "DELETE FROM faq WHERE faq_id=$faq_id";
            $_SESSION['message'] = mysqli_query($con, $query) ? "FAQ deleted successfully." : "Error deleting FAQ: " . mysqli_error($con);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Fetch data
    $faqQuery = "SELECT * FROM faq ORDER BY category, faq_id";
    $faqResult = mysqli_query($con, $faqQuery);
    $faqs = mysqli_fetch_all($faqResult, MYSQLI_ASSOC);

    // Fetch message before any echo or HTML
    $message = $_SESSION['message'] ?? null;
    $error = $_SESSION['error'] ?? null;

    unset($_SESSION['message'], $_SESSION['error']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css">
    <title>FAQ - Green Coin</title>  
<style>
    @import url('https://fonts.googleapis.com/css2?family=Playpen+Sans:wght@100..800&display=swap');
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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html{
        background-color:rgba(238, 238, 238, 0.7);
        height:100%;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color:rgba(238, 238, 238, 0.7);
    }

    .container {
        display: flex; 
        flex-direction: column; 
        width: 100%;
        padding-left: 48px;
        padding-right: 48px;
        padding-top: 20px;
    }

    
    .container h2 {
        font-size: 30px;
        margin-top: 24.9px;
        margin-bottom: 24.9px;
        color: black;
        line-height: 0.5;
        margin-left: 0px;
    }

    #addFAQ p {
        white-space: nowrap; 
        margin-bottom: 25px; 
        font-size: 16px; 
        color: #666;
    }

    .container label {
        display: block;
        color: rgb(89,89,89);
        font-size: 16px;
    }

    .sidebar {
        width: 290px;
        height: 100vh;
        min-height: 816px;
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
        /*margin-top:130px;*/
    }

    .profile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f8f9fa ;
        border-radius: 10px;
        border:2px solid rgba(116, 116, 116, 0.76);
        padding: 10px; 
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
        margin-left: 13px;
        width: 220px;
        margin-top: 1px;
    }

    .menu li {
        border-radius: 5px;
    }

    .menu li a {
        text-decoration: none;
        color: black;
        display: flex;
        align-items: center;
        gap: 15px;
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
        bottom: 100%; 
        background: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        width: 100px; 
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

    .header-container {
        position: absolute;
        left: 280px; 
        top: 20px;
        width: calc(100% - 300px);
        text-align: left;
        font-size: 18px ;
        font-weight: bold;
        padding: 30px;
        border-radius: 10px;
        margin-left: 100px; 
    }

    .main-content {
        padding:20px;
        margin-left:250px;
        width:calc(100%-270px);
        overflow-y:auto;
        overflow-x:hidden;
        background-color:rgba(238, 238, 238, 0.7);
    }


    .faq-container{
        flex: 1;
        width: 100%;
        padding: 0px 0px 50px;
        margin-left: 0px;
    }


    /* .faq-header{
        display: flex;
        flex-direction: column;
        align-items: left;
        max-height: 500px;
        color: black;
        width:calc(100%-270px);
        overflow-y:auto;
        
    } */

    .content{
        margin-left:73px;
        width: 200px !important;
        overflow-y:auto;
    }

    .faq-header-title{
        font-family: Arial, Sans-serif;
        display:flex;
        flex-direction: column;
        align-items:left;
        justify-content: center;  
        margin-bottom:20px;
        font-size:24px;
        margin-top: 0px;
        animation: floatIn 0.8s ease-out;
    }

    .savechanges {
        width: 100%;
        padding: 14px;
        background-color: rgb(78,120,49);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    /* .faq-header-desc{
        display:flex;
        flex-direction: column;
        align-items:left;
        justify-content: center;  
        margin-left:73px;
        margin-top: 10px;
    } */

    /*.search{
        width: 100%;
        width: 600px;
        height: 50px;
        background-color: white;
        margin-top: 60px;
        border-radius: 30px;
        display: flex;
        justify-content: space-between;
        padding: 0px;
    }

    .search input{
        width: 80%;
        height: 50px;
        padding: 10px 30px;
        background: transparent;
        border: none;
        font-size: 15px;
        outline: none;
    }

    .search button{
        width: 100px;
        height: 40px;
        margin: 5px 5px;
        background-color:rgb(209, 137, 42);
        color: white;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-size: 14px;
    }*/

    .accordion-container{
        display: flex;
        flex-direction: column; 
        padding: 0px 30px 0px 30px;
        gap: 30px;
    }

    .category-list {
        display: flex;
        gap: 0.5rem;
        padding: 1vh 0vh;
        padding-left: 35px;
        border-radius: 10px;
        width:100%;
    }

    .category-list p{
        font-family: Arial, Sans-serif;
        font-weight: bold;
        color: black;
        font-size: 17px;
        line-height: 1.9;
    }

    .category-list a {
        font-family:Arial, Sans-serif;
        display: inline-block;
        text-decoration: none;
        color: #333;
        padding: 13px 10px;
        border-radius: 5px;
        transition: all 0.3s ease;
        font-size: 15px;
        background-color: white;
        border: 1px solid #000;
        cursor: pointer;
        flex-grow: 0.5; 
        text-align: center; 
        max-width: 200px; 
        box-sizing: border-box; 
    }

    .category-list a:before{
        content:'';
        height: 16px;
        width: 3px;
        position: absolute;
        top: 10px;
        left: -10px;
        transition: 0.3 ease;
        opacity: 0;
    }

    .category-list a:hover::before{
        opacity: 0.5;
    }

    .category-list a:hover{
        transform: translateY(-2px);
        background-color:rgba(236,252,235,0.7);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .category-list a.active{
        background-color: #28a745;
        color: white;
        border-color: #28a745;
        font-weight: bold;
    }

    .category-list a.active::before {
        opacity: 1;
    }

    .category-list a.active:hover {
        background-color: #218838; /* Darker green on hover */
        border-color: #218838;
    }

    .accordion {
        flex:1;
        display: flex;
        flex-direction: column;
        width: 95%; 
        min-width:0;
        margin-left:40px;
    }

    .category-title {
        align-items: center;
        gap: 10px;
        margin-bottom: 25px; 
        font-size: 23px;
        color: rgb(158, 102, 19); 
    }

    .category-title i {
        font-size: 30px;
        height: auto;
        color: rgb(180, 139, 78); 
    }

    .faq{
        margin-top: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgb(208, 208, 208);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .question{
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 10px;
        width: 100%;
        cursor: pointer;
    }

    .question h3{
        font-size: 18px;
        line-height: 2.0;
        flex: 1;
        padding-right: 15px; 
        margin-right: 10px; 
    }

    .answer{
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-in-out, opacity 0.5s ease-in-out;
    }

    .answer p{
        padding-top: 10px;
        line-height: 1.7;
        font-size: 15px;
        padding-left: 10.5px;
        padding-right: 160px;
    }

    .faq.active .answer{
        max-height: 500px;
        opacity: 1;
    }

    .faq.active .chevron {
        transform: rotate(180deg);
    }


    .faq i{
        font-size: 18px;
        line-height: 2.0;
        color: rgba(222, 121, 84, 0.86);
        transition: transform 0.5s ease-in-out;
    }

    .faq .chevron {
        transition: transform 0.5s ease-in-out;
    }
    .faq.active .chevron {
        transform: rotate(180deg);
    }

    .edit_faq_category_drop.select-with-arrow {
        line-height: 1.5;
        padding: 10px 48px 10px 12px; /* top-right-bottom-left */
        height: auto;
    }

    .faq_category.select-with-arrow {
        line-height: 1.5;
        padding: 10px 48px 10px 12px; /* top-right-bottom-left */
        height: auto;
        } 
    @keyframes fade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to{
        opacity: 1;
        transform: translateY(0px);
    }
    }


    .faq-actions {
        display: flex;
        align-items: center;
        gap: 5px; 
        margin-right: 50px;
    }


    .edit-btn{
        background-color: transparent;
        /* color:rgb(144, 144, 144); */
        padding:5px 8px;
        font-size:16px;
        text-align:right;
        cursor:pointer;
        border:none;
        border-radius:8px;

    }

    .edit-btn i{
        /* color:rgb(144, 144, 144); */
        color:rgb(92, 147, 206);
    }

    .delete-btn {
       background-color:transparent;
        padding:5px 10px 5px 0px;
        cursor:pointer;
        border:none;
        font-size:16px;
        border-radius:8px;
    }

    .edit-btn:hover {
        scale: 1.1;
        transition: scale 0.2s ease;
    }

    .delete-btn:hover {
        scale: 1.1;
        transition: scale 0.2s ease;
    }

    .modal {
        display: none;
        justify-content: center;
        align-items: center;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow:auto;
        background-color: rgba(0, 0, 0, 0.57);
        backdrop-filter: blur(5px);
        text-align: left;
    }

    @keyframes fadeIn {
        from {opacity: 0}
        to {opacity: 1}
    }

    .modal-content {
        background-color: #fefefe;
        padding: 25px 15px;
        border: 1px solid #888;
        width: 600px !important;
        max-height: 90vh;
        height: auto;
        overflow-y: hidden;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    

    .modal-content h1 {
        font-size: 30px;
        margin-bottom: 10px;
        text-align: left;
        color:black;
    }

    .modal-content p {
        margin-bottom: 20px;
        color: rgb(89,89,89);
        line-height: 1.5;
        font-size: 16px;
        text-align: left;
    }

    .modal-content label {
        display: block;
        margin-bottom: 0px;
    }


    .modal-content input[type=text],
    .modal-content textarea,
    .modal-content select {
        width: 100%;
        padding: 12px 15px;
        margin: 8px 0 10px 0;
        display: inline-block;
        box-sizing: border-box;
        font-size: 16px !important;
        background-color: #fff;
        font-family: Arial, sans-serif;
        border-radius: 5px;
        resize: none;
        border: 1px solid #ccc; 
        outline: none;
    }

    .modal-content select{
        line-height: 15px;
    }


    .addformbtn {
        width: 100%;
        background-color: rgb(78,120,49);
        color: white;
        padding: 12px;
        font-size: 15px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .addformbtn:hover {
        background-color: #1b5e20;
    }

    .close {
        position: absolute;
        right: 5px;
        top: 5px;
        color: rgb(133, 133, 133);
        font-size: 35px;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    .category-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 25px;
        font-size: 23px;
        color: rgb(158, 102, 19);
        flex-wrap: wrap;
    }

    @keyframes toastFadeOut {
        0% { opacity: 1; }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-20px); }
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

    .imgcontainer {
        text-align: right;
        margin: 10px 20px 0 40px;
        position: relative;
    }

    hr{
        border: none;
        height: 1.6px;
        background-color: rgb(197, 197, 196);
        opacity: 1;
        margin: 7px 9.4px 20px 45px;
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


    .custom-select-wrapper {
        position: relative;
        width: 100%;
    }


    .custom-select-wrapper i.fas.fa-angle-down {
        position: absolute;
        right: 12px;
        top: 15%;
        transform: translateY(-50%);
        pointer-events: none; 
        font-size: 18px;
        color: #333;
    }

    .select-with-arrow { 
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 40px;
        background-image: url("data:image/svg+xml;utf8,<svg fill='gray' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
        border: 1px solid #ccc;
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 24px 24px;
        border-radius: 5px;
        height: 40px;
        font-size: 16px !important;
    }

    /* Responsive Styles */
@media (max-width: 1400px) {
    .sidebar {
        width: 260px;
    }
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
    }
}

@media (max-width: 1200px) {
    .sidebar {
        width: 240px;
        padding: 15px;
    }
    .main-content {
        margin-left: 240px;
        width: calc(100% - 240px);
    }
    .category-list {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 10px;
    }
    .category-list a {
        min-width: 150px;
    }
}

@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    .main-content {
        margin-left: 220px;
        width: calc(100% - 220px);
    }
    .container {
        padding-left: 30px;
        padding-right: 30px;
    }
    .category-list {
        gap: 0.5rem;
    }
    .modal-content {
        width: 90% !important;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
    }
    .category-list {
        padding-left: 15px;
        padding-right: 15px;
    }
    .category-list a {
        padding: 10px 8px;
        font-size: 14px;
    }
    .accordion {
        margin-left: 20px;
    }
    .modal-content {
        padding: 20px !important;
    }
}

@media (max-width: 576px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 15px;
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    .container {
        padding-left: 15px;
        padding-right: 15px;
    }
    .category-list {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 10px 5px;
    }
    .category-list a {
        min-width: 120px;
        padding: 8px 6px;
        font-size: 13px;
    }
    .modal-content {
        width: 95% !important;
        max-height: 80vh;
        padding: 15px !important;
    }
    .select-with-arrow {
        padding-right: 35px;
        background-position: right 8px center;
    }
}

@media (max-width: 480px) {
    .question h3 {
        font-size: 16px;
        padding-right: 5px;
    }
    .faq-actions {
        margin-right: 10px;
    }
    .modal-content {
        padding: 15px !important;
    }
    .modal-content h2 {
        font-size: 24px;
    }
    .modal-content p {
        font-size: 14px;
    }
    input[type=text], textarea, select {
        padding: 10px 8px !important;
    }
}


</style>    

<script>
    function openAddModal() {
        document.getElementById("addFAQ").style.display = "flex";
        document.body.style.overflow = "hidden";
    
    // Initialize auto-expand for the textarea
    
        adjustModalForMobile();
    }

    document.addEventListener("DOMContentLoaded", function(){
        const faqModal = document.getElementById('addFAQ');
        faqModal.addEventListener("click", function(event){
            if(event.target === faqModal){
                faqModal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        });
    });

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

        function openEditFaqModal(id, question, answer, category) {
            document.getElementById('edit_faq_id').value = id;
            document.getElementById('edit_faq_question').value = question;
            document.getElementById('edit_faq_answer').value = answer;
            document.getElementById('edit_faq_category').value = category;
            document.getElementById('editFAQ').style.display = 'flex';
            document.body.style.overflow = "hidden";

            adjustModalForMobile();
        }

    function confirmDeleteFaq(id) {
        if (confirm("Are you sure you want to delete this FAQ?")) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_faq_id';
            input.value = id;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function closeAddModal() {
        document.getElementById('addFAQ').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function closeEditModal() {
        document.getElementById('editFAQ').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Update your existing modal close event listeners
    document.addEventListener("DOMContentLoaded", function(){
        // Add FAQ modal close
        const addModal = document.getElementById('addFAQ');
        addModal.addEventListener("click", function(event){
            if(event.target === addModal || event.target.classList.contains('close')) {
                closeAddModal();
            }
        });
        
        // Edit FAQ modal close
        const editModal = document.getElementById('editFAQ');
        editModal.addEventListener("click", function(event){
            if(event.target === editModal || event.target.classList.contains('close')) {
                closeEditModal();
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const categoryLinks = document.querySelectorAll(".category-link");
        const faqs = document.querySelectorAll(".faq");
        // const categoryTitle = document.getElementById("category-name");
        const categoryIcon = document.getElementById("category-icon");

        const categoryData = {
            "All": { title: "All", iconClass: "fa-layer-group" },
            "General": { title: "General", iconClass: "fa-book-open" },
            "Pickup Scheduling": { title: "Pickup Scheduling", iconClass: "fa-truck-moving" },
            "Drop-off Points": { title: "Drop-off Points", iconClass: "fa-map-location-dot" }, 
            "Rewards": { title: "Rewards", iconClass: "fa-gift" }
        };

        categoryLinks.forEach(link => {
            link.addEventListener("click", function (event) {
                event.preventDefault();
                const selectedCategory = this.getAttribute("data-category");

                categoryLinks.forEach(link => link.classList.remove("active"));
                this.classList.add("active");

                // if (categoryData[selectedCategory]) {
                //     categoryTitle.textContent = categoryData[selectedCategory].title;
                //     categoryIcon.className = `fa-solid ${categoryData[selectedCategory].iconClass}`;
                // }

                faqs.forEach(faq => {
                    if (selectedCategory === "All" || faq.getAttribute("data-category") === selectedCategory) {
                        faq.style.display = "block";
                    } else {
                        faq.style.display = "none";
                    }
                });
            });
        });

        // Handle toggle while ignoring clicks on buttons
        document.querySelectorAll(".faq .question").forEach(question => {
            question.addEventListener("click", function (e) {
                if (e.target.closest("button")) return; // Prevent toggle when clicking button

                const faq = this.parentElement;
                const answer = faq.querySelector(".answer");

                // Close all FAQs except the clicked one
                document.querySelectorAll(".faq").forEach(item => {
                    if (item !== faq) {
                        item.classList.remove("active");
                        item.querySelector(".answer").style.maxHeight = "0";
                        item.querySelector(".answer").style.opacity = "0";
                    }
                });

                // Toggle the clicked FAQ
                faq.classList.toggle("active");

                if (faq.classList.contains("active")) {
                    answer.style.maxHeight = answer.scrollHeight + "px";  // Adjust maxHeight based on content
                    answer.style.opacity = "1";  // Show the answer
                } else {
                    answer.style.maxHeight = "0";  // Collapse the answer
                    answer.style.opacity = "0";  // Hide the answer
                }
            });
        });


        // categoryTitle.textContent = categoryData["All"].title;
        // categoryIcon.className = `fa-solid ${categoryData["All"].iconClass}`;
    });

    function filterFAQ() {
        //var input = document.getElementById("search_faq");
        var filter = input.value.toUpperCase();
        var faqs = document.querySelectorAll(".faq");

        document.getElementById("category-name").textContent = "All";
        document.getElementById("category-icon").className = "fa-solid fa-layer-group";

        document.querySelectorAll(".category-link").forEach(link => link.classList.remove("active"));
        document.querySelector('.category-link[data-category="All"]').classList.add("active");

        for (var i = 0; i < faqs.length; i++) {
            var question = faqs[i].querySelector(".question h3");
            if (question) {
                var txtValue = question.textContent || question.innerText;
                faqs[i].style.display = txtValue.toUpperCase().includes(filter) ? "block" : "none";
            }
        }
    }

    function showAlert(message, isError = false) {
        if (isError) {
            alert("Error: " + message);
        } else {
            alert(message);
        }
    }
    <?php if ($message): ?>
        showAlert("<?= addslashes($message) ?>");
    <?php endif; ?>
    <?php if ($error): ?>
        showAlert("<?= addslashes($error) ?>", true);
    <?php endif; ?>


    window.addEventListener('click', function(event) {
        const modal = document.getElementById('editFAQ');
        if (event.target === modal) {
            modal.style.display = 'none';
        }

        const addModal = document.getElementById('addFAQ');
        if (event.target === addModal) {
            addModal.style.display = 'none';
        }
    });

    window.addEventListener('resize', adjustModalForMobile);

    function adjustModalForMobile() {
    const modals = document.querySelectorAll('.modal-content');
    if (window.innerWidth <= 576) {
        modals.forEach(modal => {
            modal.style.width = '95%';
            modal.style.maxWidth = '100%';
        });
    } else {
        modals.forEach(modal => {
            modal.style.width = '600px';
            modal.style.maxWidth = '80vw';
        });
    }
}


    
</script>



</head>
<body>
<div class="sidebar">
        <div>
        <a href="Admin-Dashboard.php" >
            <img src="User-Logo.png" 
                style="width: 220px; margin-bottom: 40px; background-color: #78A24C; padding: 10px; border-radius: 10px; cursor: pointer; margin-left: 13px;">
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
            <li><a href="Admin-RecyclableItem.php"><i class="fa-solid fa-recycle"></i>Recyclable Items</a></li>
            <li ><a href="Admin-Rewards.php"><i class="fa-solid fa-gift"></i>Rewards</a></li>
            <li><a href="Admin-Review.php"><i class="fa-solid fa-comments"></i>Review</a></li>
            <li><a href="Admin-Report.php"><i class="fa-solid fa-chart-column"></i>Report</a></li>
            <li class="active"><a href="Admin-FAQ.php"><i class="fa-solid fa-circle-question"></i>FAQ</a></li>
            <form action="Admin-Logout.php" method="post" style="display:inline;">
                <button type="submit" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </form>
        </ul>
    </div>


<div class="container">

    <button class="dropdown-btn" onclick="toggleDropdown(event)" style="display:none;">
                    <i class="fa-solid fa-chevron-down"></i>
    </button> 
    <main class="main-content">
        <div class="faq-container">
            <!-- <header class="faq-header"> -->
                <div class="content">
                    <h1 class="faq-header-title">FAQ</h1>
                </div>
                <!-- <p class="faq-header-desc">Frequently Asked Questions</p> -->
                <hr style="width: 95%; margin-left:45px;">
                <!--<div class="search">
                    <input type="text" id="search_faq" onkeyup="filterFAQ()" placeholder="Search...">
                    <button>Search</button>
                </div>-->
            <!-- </header> -->

            <div class="accordion-container">
                <!--  For choosing categories -->
                <div class="category-list">
                    <br>
                    <a href="#" data-category="All" class="category-link active">All</a>
                    <a href="#" data-category="General" class="category-link">General</a>
                    <a href="#" data-category="Pickup Scheduling" class="category-link">Pickup Scheduling</a>
                    <a href="#" data-category="Drop-off Points" class="category-link">Drop-off Points</a>
                    <a href="#" data-category="Rewards" class="category-link">Rewards</a>
            </div>

                <div class="accordion">
                    <?php foreach ($faqs as $row): ?>
                        <section class="faq" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                            <div class="question">
                                <div style="flex: 1;">
                                    <h3><?php echo htmlspecialchars($row['question']); ?></h3>
                                </div>
                                <div class="faq-actions">
                                    <button class="edit-btn"
                                            onclick='event.stopPropagation(); openEditFaqModal(
                                                <?php echo (int)$row["faq_id"]; ?>,
                                                <?php echo json_encode($row["question"]); ?>,
                                                <?php echo json_encode($row["answer"]); ?>,
                                                <?php echo json_encode($row["category"]); ?>)'>
                                       <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="delete-btn"
                                            onclick="event.stopPropagation(); confirmDeleteFaq(<?php echo (int)$row['faq_id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <i class="fa-solid fa-chevron-down chevron"></i>
                                </div>
                            </div>
                            <div class="answer">
                                <p><?php echo htmlspecialchars($row['answer']); ?></p>
                            </div>
                        </section>
                    <?php endforeach; ?>


                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add FAQ Modal -->
<div id="addFAQ" class="modal">
    <form class="modal-content" action="#" method="post">
        <div class="imgcontainer">
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <div class="container">
            <div>
                <h2>Add New FAQ</h2>
                <p>Fill in the details below to add a new FAQ.</p>
            </div>
            <div class="modal-body">
                <label>Category</label>
                <select id="faq_category" name="faq_category" required class="faq_category select-with-arrow">
                    <option value="" disabled selected>--Select category--</option>
                    <option value="General">General</option>
                    <option value="Pickup Scheduling">Pickup Scheduling</option>
                    <option value="Drop-off Points">Drop-off Points</option>
                    <option value="Rewards">Rewards</option>
                </select>
                <br><br>

                <label>Question</label>
                <input type="text" id="faq_question" name="faq_question" required><br><br>
                <label>Answer</label>
                <textarea id="faq_answer" name="faq_answer" required style="height: 110px;"></textarea><br><br>
            </div>
            <button class="savechanges" type="submit" name="add_faq">Add FAQ</button>
        </div>
    </form>
</div>
</div>


<!-- Edit FAQ Modal -->
<div id="editFAQ" class="modal">
    <form id="editFAQForm" class="modal-content" action="#" method="post">
        <div class="imgcontainer">
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="container">
            <div>
                <h2>Edit FAQ</h2>
                <p>Please update the FAQ details below.</p>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_faq_id" name="faq_id">
                
                <label>Category</label>
                <select class="edit_faq_category_drop select-with-arrow" id="edit_faq_category" name="faq_category" required>
                    <option value="General">General</option>
                    <option value="Pickup Scheduling">Pickup Scheduling</option>
                    <option value="Drop-off Points">Drop-off Points</option>
                    <option value="Rewards">Rewards</option>
                </select>
                <br><br>

                <label>Question</label>
                <input type="text" id="edit_faq_question" name="faq_question" required><br><br>

                <label>Answer</label>
                <textarea id="edit_faq_answer" name="faq_answer" required style="height: 110px;"></textarea></textarea><br><br>
            </div>
                <button class="savechanges" type="submit" name="edit_faq">Save Changes</button>
            </div>
        </form>
    </div>
    </div>
</div>
<button class="add-btn" onclick="openAddModal()" name="addbutton">
        <i class="fa-solid fa-plus"></i>
    </button>    
</body>
</html>