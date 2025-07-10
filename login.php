<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

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
$newPassword = $_POST['new_password'] ?? '';
$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';

// === Step 1: Check if username exists & send OTP ===
if (!empty($username) && !empty($email) && empty($otp) && empty($newPassword)) {
    $stmt = $conn->prepare("SELECT id FROM Users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $generatedOtp = rand(100000, 999999);
        $stmt = $conn->prepare("UPDATE Users SET otp = ? WHERE username = ?");
        $stmt->bind_param("ss", $generatedOtp, $username);
        $stmt->execute();

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom(getenv('EMAIL_USER'), 'OTP Verification');
            $mail->addAddress($email);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Hello $username,\nYour OTP code is: $generatedOtp";

            $mail->send();
            echo json_encode(["status" => "otp_sent", "message" => "OTP sent to email."]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Mail error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(["status" => "not_found", "message" => "Username/email not found."]);
    }
    exit();
}

// === Step 2: Verify OTP ===
if (!empty($username) && !empty($otp)) {
    $stmt = $conn->prepare("SELECT otp FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($storedOtp);
    $stmt->fetch();

    if ($otp == $storedOtp) {
        echo json_encode(["status" => "otp_verified", "message" => "OTP verified. Proceed to reset password."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Incorrect OTP."]);
    }
    exit();
}

// === Step 3: Reset Password ===
if (!empty($username) && !empty($newPassword)) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Users SET password = ?, otp = NULL WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password reset successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password."]);
    }
    exit();
}

// === Step 4: Login ===
if (!empty($username) && !empty($password)) {
    $stmt = $conn->prepare("SELECT password FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    if ($stmt->fetch() && password_verify($password, $hashedPassword)) {
        echo json_encode(["status" => "success", "message" => "Login successful."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    }
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid request."]);
$conn->close();
