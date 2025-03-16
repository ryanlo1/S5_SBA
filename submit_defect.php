<?php
session_start();
include 'db.php'; // Include the database connection script

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to submit a defect report.";
    exit;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $venue = $_POST['venue'];
    $item = $_POST['item']; // Added item
    $title = $_POST['title'];
    $description = $_POST['description'];
    $username = $_SESSION['username'];
    $created_at = date('Y-m-d H:i:s');
    $status = 'gray'; // Default status
    $votes = 0; // Default votes
    $image_path = $_POST['image_path'] ?? null; // Retrieve image path from the form (if provided)

    // Validate the input data
    if (empty($venue) || empty($item) || empty($title) || empty($description)) {
        echo "All fields (venue, item, title, description) are required.";
        exit;
    }

    // Validate title length
    if (strlen($title) > 100) {
        echo "Title must be less than 100 characters.";
        exit;
    }

    // Validate description length
    if (strlen($description) < 10) {
        echo "Description must be at least 10 characters long.";
        exit;
    }
    if (strlen($description) > 500) {
        echo "Description must be less than 500 characters.";
        exit;
    }

    // Validate venue
    try {
        $stmt = $db->prepare("SELECT id FROM venue WHERE id = ?");
        $stmt->execute([$venue]);
        if (!$stmt->fetch()) {
            echo "Invalid venue selected.";
            exit;
        }
    } catch (PDOException $e) {
        echo "Error validating venue: " . $e->getMessage();
        exit;
    }

    // Validate item
    try {
        $stmt = $db->prepare("SELECT id FROM items WHERE id = ?");
        $stmt->execute([$item]);
        if (!$stmt->fetch()) {
            echo "Invalid item selected.";
            exit;
        }
    } catch (PDOException $e) {
        echo "Error validating item: " . $e->getMessage();
        exit;
    }

    // Insert the defect into the database
    try {
        $stmt = $db->prepare("
            INSERT INTO defect_table (created_at, title, description, username, venue, item, status, votes, image_path)
            VALUES (:created_at, :title, :description, :username, :venue, :item, :status, :votes, :image_path)
        ");
        $stmt->execute([
            ':created_at' => $created_at,
            ':title' => $title,
            ':description' => $description,
            ':username' => $username,
            ':venue' => $venue,
            ':item' => $item,
            ':status' => $status,
            ':votes' => $votes,
            ':image_path' => $image_path // Include image_path in the query
        ]);

        // Redirect to dashboard with success message
        header("Location: dashboard.php?success=Defect report submitted successfully!");
        exit;
    } catch (PDOException $e) {
        echo "Error inserting defect: " . $e->getMessage();
        exit;
    }
} else {
    echo "Invalid request.";
    exit;
}
?>