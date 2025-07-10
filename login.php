<?php
ini_set('display_errors', 1); // Development only
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "hopper.proxy.rlwy.net";
$port = 26459;
$dbname = "railway";
$user = "root";
$pass = "nSGAVaoqepMDEZqkJPKMBZSEfGDyNvVq";

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (!isset($_POST['username'], $_POST['password'])) {
    die("❌ Missing username or password.");
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

$stmt = $conn->prepare("SELECT password FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  $stmt->bind_result($hashed_password);
  $stmt->fetch();
  if (password_verify($password, $hashed_password)) {
    echo "✅ Login successful!";
  } else {
    echo "❌ Incorrect password.";
  }
} else {
  echo "❌ User not found.";
}

$stmt->close();
$conn->close();
?>
