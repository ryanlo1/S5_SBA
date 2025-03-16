<?php
include 'db.php'; // Include the database connection script

// Example: Insert a new user into the users table
$username = "user";
$password = "user"; // Plain-text password
$is_admin = 0; // 0 = regular user, 1 = admin

// Hash the password before storing it in the database
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
$stmt->execute([$username, $hashed_password, $is_admin]);

echo "User inserted successfully!";
?>