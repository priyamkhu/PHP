<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host = "hopper.proxy.rlwy.net";
$port = 26459;
$dbname = "railway";
$user = "root";
$pass = "nSGAVaoqepMDEZqkJPKMBZSEfGDyNvVq";

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$newPassword = $_POST['new_password'] ?? ''; // for reset

// STEP 1: FORGOT PASSWORD FLOW – Only username provided
if (!empty($username) && empty($password) && empty($newPassword)) {
    $stmt = $conn->prepare("SELECT id FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "exists", "message" => "User exists."]);
    } else {
        echo json_encode(["status" => "not_found", "message" => "❌ You are not authorized."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// STEP 2: PASSWORD RESET – username + new_password
if (!empty($username) && !empty($newPassword)) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "✅ Password updated."]);
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Failed to update password."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// STEP 3: LOGIN – username + password
if (!empty($username) && !empty($password)) {
    $stmt = $conn->prepare("SELECT password FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// DEFAULT: Invalid request
echo json_encode(["status" => "error", "message" => "Invalid request."]);
$conn->close();
?>
