<?php
session_start(); // Start the session to manage user login state
include 'db.php'; // Include the database connection script

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username']; // Get the entered username
    $password = $_POST['password']; // Get the entered password

    // Query the database for the user with the given username
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]); // Execute the query with the entered username
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user from the database

    // Check if the user exists and the password matches
    if ($user && password_verify($password, $user['password'])) {
        // If the user is authenticated, store their details in the session
        $_SESSION['user_id'] = $user['id']; // Store the user ID
        $_SESSION['username'] = $user['username']; // Store the username
        $_SESSION['is_admin'] = $user['is_admin']; // Store the admin status

        // Redirect to a dashboard or home page after successful login
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password."; // Error message for invalid credentials
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="stylesheet" href="styles.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
   
<body>
    <h1>Login</h1>
    <!-- Display an error message if login fails -->
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        <br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>