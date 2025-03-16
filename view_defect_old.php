<?php
session_start();
include 'db.php'; // Include the database connection script


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to the login page
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Check if defect ID is provided in the URL
if (!isset($_GET['id'])) {
    echo "No defect ID provided.";
    exit;j
        d.id, d.created_at, d.title, d.description, d.username, d.status, v.room AS venue, d.votes 
    FROM 
        defect_table d
    JOIN 
        venue v 
    ON 
        d.venue::bigint = v.id
    WHERE 
        d.id = ?
";
$stmt = $db->prepare($query); // Prepare the query
$stmt->execute([$defect_id]); // Execute the query with the defect ID
$defect = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the defect details

// If defect not found, display an error message
if (!$defect) {
    echo "Defect not found.";
    exit;
}

// Check if the user has already voted on this defect
$vote_query = "SELECT vote FROM votes WHERE user_id = ? AND defect_id = ?";
$vote_stmt = $db->prepare($vote_query);
$vote_stmt->execute([$user_id, $defect_id]);
$user_vote = $vote_stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user's vote if it exists

// Handle upvote/downvote requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    // Check if the user has already voted
    if (!$user_vote) {
        $vote = $_POST['vote'] === 'up' ? 1 : -1; // Determine the vote direction

        // Insert the vote into the votes table
        $insert_vote_query = "INSERT INTO votes (user_id, defect_id, vote) VALUES (?, ?, ?)";
        $insert_vote_stmt = $db->prepare($insert_vote_query);
        $insert_vote_stmt->execute([$user_id, $defect_id, $vote]);

        // Update the votes count in the defect_table
        $update_defect_query = "UPDATE defect_table SET votes = votes + ? WHERE id = ?";
        $update_defect_stmt = $db->prepare($update_defect_query);
        $update_defect_stmt->execute([$vote, $defect_id]);

        // Reload the page to reflect the changes
        header("Location: view_defect.php?id=$defect_id");
        exit;
    } else {
        echo "You have already voted on this defect.";
    }
}

// Handle status update by admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status']; // Get the new status
    $stmt = $db->prepare("UPDATE defect_table SET status = ? WHERE id = ?");
    $stmt->execute([$status, $defect_id]); // Update the status in the database
    header("Location: view_defect.php?id=$defect_id"); // Reload the page
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Defect</title>
    <link rel="stylesheet" href="styles2.css">
</head>

<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($defect['title']); ?></h1>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($defect['created_at']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($defect['username']); ?></p>
        <p><strong>Venue:</strong> <?php echo htmlspecialchars($defect['venue']); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($defect['description'])); ?></p>
        <p><strong>Votes:</strong> <?php echo htmlspecialchars($defect['votes']); ?></p>

        <!-- Status Display -->
        <p><strong>Status:</strong> 
            <?php
            switch ($defect['status']) {
                case 'gray': echo '<span class="status gray">Not Read</span>'; break;
                case 'red': echo '<span class="status red">Rejected</span>'; break;
                case 'yellow': echo '<span class="status yellow">Work In Progress</span>'; break;
                case 'green': echo '<span class="status green">Resolved</span>'; break;
                default: echo 'Unknown';
            }
            ?>
        </p>

        <!-- Upvote/Downvote Buttons -->
        <?php if (!$user_vote): ?>
            <form method="POST">
                <button type="submit" name="vote" value="up">Upvote</button>
                <button type="submit" name="vote" value="down">Downvote</button>
            </form>
        <?php else: ?>
            <p>You have already voted on this defect.</p>
        <?php endif; ?>

        <!-- Admin Status Update -->
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <form method="POST">
                <label for="status">Update Status:</label>
                <select name="status" id="status">
                    <option value="gray" <?php if ($defect['status'] === 'gray') echo 'selected'; ?>>Not Read</option>
                    <option value="red" <?php if ($defect['status'] === 'red') echo 'selected'; ?>>Rejected</option>
                    <option value="yellow" <?php if ($defect['status'] === 'yellow') echo 'selected'; ?>>Work In Progress</option>
                    <option value="green" <?php if ($defect['status'] === 'green') echo 'selected'; ?>>Resolved</option>
                </select>
                <button type="submit">Update Status</button>
            </form>
        <?php endif; ?>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>

</html>