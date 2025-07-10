<?php
$host = "hopper.proxy.rlwy.net";   // Railway proxy host
$port = 26459;                     // Railway public port
$dbname = "railway";              // Your database name
$user = "root";                   // Your username
$pass = "nSGAVaoqepMDEZqkJPKMBZSEfGDyNvVq";     // Your Railway DB password

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Example login check
$username = $_POST['username'];
$password = $_POST['password'];

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
