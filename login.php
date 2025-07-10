<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
error_reporting(0);

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

// 🔁 Get request data
$step = $_POST['step'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$otp = $_POST['otp'] ?? '';
$sentOtp = $_POST['sent_otp'] ?? '';
$email = $_POST['email'] ?? '';

// ✅ STEP 0: Register New User
if ($step == "register" && !empty($username) && !empty($password) && !empty($email)) {
    // Check if user exists
    $check = $conn->prepare("SELECT id FROM Users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["status" => "exists", "message" => "Username already taken."]);
        $check->close();
        $conn->close();
        exit();
    }
    $check->close();

    // Hash password and insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed, $email);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "✅ User registered successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Failed to register user."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// ✅ STEP 1: Check if user exists
if ($step == "check_user" && !empty($username)) {
    $stmt = $conn->prepare("SELECT email FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($email_found);
        $stmt->fetch();
        echo json_encode(["status" => "exists", "message" => "User found.", "email" => $email_found]);
    } else {
        echo json_encode(["status" => "not_found", "message" => "User not found."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// ✅ STEP 2: Verify OTP
if ($step == "verify_otp" && !empty($otp) && !empty($sentOtp)) {
    if ($otp === $sentOtp) {
        echo json_encode(["status" => "verified", "message" => "OTP verified."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid OTP."]);
    }
    $conn->close();
    exit();
}

// ✅ STEP 3: Reset Password
if ($step == "reset_password" && !empty($username) && !empty($newPassword)) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password."]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

// ✅ STEP 4: Login
if ($step == "login" && !empty($username) && !empty($password)) {
    $stmt = $conn->prepare("SELECT password FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            echo json_encode(["status" => "success", "message" => "Login successful."]);
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

// ❌ Default
echo json_encode(["status" => "error", "message" => "Invalid request."]);
$conn->close();
?>
