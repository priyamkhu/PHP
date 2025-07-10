<?php
header("Access-Control-Allow-Origin: *"); // Allow all origins (for development)
header("Content-Type: application/json"); // JSON response

$host = "hopper.proxy.rlwy.net";   // Railway proxy host
$port = 26459;                     // Railway public port
$dbname = "railway";              // Your database name
$user = "root";                   // Your username
$pass = "nSGAVaoqepMDEZqkJPKMBZSEfGDyNvVq";  // Your DB password

$conn = new mysqli($host, $user, $pass, $dbname, $port);

// Check connection
if ($conn->connect_error) {
  echo json_encode(["status" => "error", "message" => "Database connection failed."]);
  exit();
}

// Receive input from Flutter
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

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
?>
