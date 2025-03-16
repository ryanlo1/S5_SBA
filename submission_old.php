<?php
session_start();
include 'db.php'; // Include the database connection script
include 'topbar.php';

// Check if the user is logged in (modify based on your session setup)
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to submit a defect report.";
    exit;
}

// Fetch venues from the database
$stmt = $db->query("SELECT id, room FROM venue"); // Fetch all venues
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch venues as an associative array
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Defect Report</title>
</head>
<body>
    <h1>Submit a Defect Report</h1>
    <!-- Submission form -->
    <form action="submit_defect.php" method="POST">
        <!-- Dropdown to select venue -->
        <label for="venue">Venue of Incident:</label>
        <select name="venue" id="venue" required>
            <option value="">-- Select Venue --</option>
            <?php foreach ($venues as $venue): ?>
                <option value="<?php echo $venue['id']; ?>">
                    <?php echo htmlspecialchars($venue['room']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <!-- Input field for title -->
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>
        <br><br>

        <!-- Textarea for description -->
        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="5" required></textarea>
        <br><br>

        <!-- Submit button -->
        <button type="submit">Submit</button>
    </form>
</body>
</html>