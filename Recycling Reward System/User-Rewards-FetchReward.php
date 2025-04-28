<?php
    header('Content-Type: application/json');

    session_start();
    $con = mysqli_connect("localhost", "root", "", "cp_assignment");

    if (mysqli_connect_errno()) {
        die(json_encode(["status" => "error", "message" => "Database connection failed."]));
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "User not logged in."]);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reward_id'])) {
        
        $reward_id = (int)$_POST['reward_id'];  

        $query = "SELECT reward_name, point_needed, reward_image FROM reward WHERE reward_id = ?";
        $stmt = $con->prepare($query);

        $stmt->bind_param("i", $reward_id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            echo json_encode([
                'status' => 'success',
                'reward_name' => $row['reward_name'],
                'point_needed' => $row['point_needed'],
                'reward_image' => $row['reward_image']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Reward not found.'
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request. Reward ID missing.'
        ]);
    }

    mysqli_close($con);
?>

