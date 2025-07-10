<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

// DB Connection
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

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Send OTP function
function sendOTPEmail($to, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kumarhumepipe.help@gmail.com'; // YOUR EMAIL
        $mail->Password = 'szvpcbpkmsxtmxky'; // APP PASSWORD
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your@gmail.com', 'Your App');
        $mail->addAddress($to);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Your OTP is: $otp. It is valid for 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Inputs
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';

// STEP 1: Username exists check
if (!empty($username) && empty($email) && empty($password) && empty($newPassword) && empty($otp)) {
    $stmt = $conn->prepare("SELECT id FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "exists", "message" => "User exists."]);
    } else {
        echo json_encode(["status" => "not_found", "message" => "❌ User not found."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// STEP 2: Email check & send OTP
if (!empty($username) && !empty($email) && empty($password) && empty($newPassword) && empty($otp)) {
    $stmt = $conn->prepare("SELECT id FROM Users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "❌ Username and email do not match"]);
        exit();
    }

    // Generate OTP
    $otpCode = rand(100000, 999999);
    $expiresAt = date("Y-m-d H:i:s", time() + 300); // 5 mins

    // Invalidate previous OTPs
    $conn->query("UPDATE otp_codes SET is_used = 1 WHERE username = '$username'");

    $stmt2 = $conn->prepare("INSERT INTO otp_codes (username, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("ssss", $username, $email, $otpCode, $expiresAt);
    $stmt2->execute();

    if (sendOTPEmail($email, $otpCode)) {
        echo json_encode(["status" => "otp_sent", "message" => "✅ OTP sent to email"]);
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Failed to send OTP"]);
    }

    $stmt->close();
    $stmt2->close();
    $conn->close();
    exit();
}

// STEP 3: Verify OTP
if (!empty($username) && !empty($otp)) {
    $now = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("SELECT id FROM otp_codes WHERE username = ? AND otp_code = ? AND is_used = 0 AND expires_at > ?");
    $stmt->bind_param("sss", $username, $otp, $now);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Mark OTP used
        $conn->query("UPDATE otp_codes SET is_used = 1 WHERE username = '$username'");
        echo json_encode(["status" => "otp_valid", "message" => "✅ OTP verified"]);
    } else {
        echo json_encode(["status" => "invalid", "message" => "❌ OTP is invalid or expired"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// STEP 4: Reset Password
if (!empty($username) && !empty($newPassword)) {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "✅ Password updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Failed to update password"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// STEP 5: Login
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
            echo json_encode(["status" => "error", "message" => "Incorrect password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// Invalid fallback
echo json_encode(["status" => "error", "message" => "Invalid request"]);
$conn->close();
?>
