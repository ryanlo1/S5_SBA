<?php
// Start the session at the very top
session_start();

// Include the database connection script
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to the login page
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Check if defect ID is provided in the URL
if (!isset($_GET['id'])) {
    echo "No defect ID provided.";
    exit;
}

$defect_id = $_GET['id']; // Get the defect ID

// Fetch the defect details, including venue and item names
$query = "
    SELECT 
        d.id, d.created_at, d.title, d.description, d.username, d.status, v.room AS venue, i.name AS item, d.votes, d.image_path, d.remarks, d.has_new_remark 
    FROM 
        defect_table d
    JOIN 
        venue v 
    ON 
        d.venue::bigint = v.id
    LEFT JOIN 
        items i 
    ON 
        d.item = i.id::text
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

// Handle remarks update by admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remarks'])) {
    $remarks = $_POST['remarks']; // Get the remarks

    // Update the remarks and set has_new_remark to TRUE
    $stmt = $db->prepare("UPDATE defect_table SET remarks = ?, has_new_remark = TRUE WHERE id = ?");
    $stmt->execute([$remarks, $defect_id]); // Update the remarks in the database

    // Reload the page to reflect the changes
    header("Location: view_defect.php?id=$defect_id");
    exit;
}

// Handle image removal by admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_image'])) {
    // Remove the image path from the database
    $stmt = $db->prepare("UPDATE defect_table SET image_path = NULL WHERE id = ?");
    $stmt->execute([$defect_id]); // Update the image path to NULL
    header("Location: view_defect.php?id=$defect_id"); // Reload the page
    exit;
}

// Handle defect editing by admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_defect'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $venue = $_POST['venue'];
    $item = $_POST['item']; // Item ID from the form

    // Update the defect record
    $stmt = $db->prepare("UPDATE defect_table SET title = ?, description = ?, venue = ?, item = ? WHERE id = ?");
    $stmt->execute([$title, $description, $venue, $item, $defect_id]);

    // Reload the page to reflect the changes
    header("Location: view_defect.php?id=$defect_id");
    exit;
}

// Handle defect deletion by admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_defect'])) {
    // Start a transaction to ensure atomicity
    $db->beginTransaction();

    try {
        // Step 1: Delete all votes associated with the defect
        $stmt = $db->prepare("DELETE FROM votes WHERE defect_id = ?");
        $stmt->execute([$defect_id]);

        // Step 2: Delete the defect record
        $stmt = $db->prepare("DELETE FROM defect_table WHERE id = ?");
        $stmt->execute([$defect_id]);

        // Commit the transaction
        $db->commit();

        // Redirect to the dashboard after deletion
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction in case of an error
        $db->rollBack();
        echo "Error deleting defect: " . $e->getMessage();
    }
}

// Check if the user is the creator of the defect and if there is a new remark
if ($defect['username'] === $_SESSION['username'] && $defect['has_new_remark']) {
    echo "<script>alert('A remark has been added by the admin.');</script>";

    // Reset has_new_remark to FALSE after showing the alert
    $stmt = $db->prepare("UPDATE defect_table SET has_new_remark = FALSE WHERE id = ?");
    $stmt->execute([$defect_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Defect</title>
    <link rel="stylesheet" href="styles2.css">
    <link rel="stylesheet" href="view_defect.css">
    
    <script>
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function toggleRemarkBox() {
            const remarkBox = document.getElementById('remarkBox');
            remarkBox.style.display = remarkBox.style.display === 'none' ? 'block' : 'none';
        }

        function validateEditForm() {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const venue = document.getElementById('venue').value;
            const item = document.getElementById('item').value;

            if (!title) {
                alert('Please enter a title.');
                return false;
            }
            if (title.length > 100) {
                alert('Title must be less than 100 characters.');
                return false;
            }

            if (!description) {
                alert('Please enter a description.');
                return false;
            }
            if (description.length < 10) {
                alert('Description must be at least 10 characters long.');
                return false;
            }
            if (description.length > 500) {
                alert('Description must be less than 500 characters.');
                return false;
            }

            if (!venue) {
                alert('Please select a venue.');
                return false;
            }

            return true; // Allow submission if all validations pass
        }
    </script>
</head>
<body>
    <?php include 'topbar.php'; ?> <!-- Include topbar.php here, after headers are sent -->
    <div class="container">
        <h1><?php echo htmlspecialchars($defect['title']); ?></h1>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($defect['created_at']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($defect['username']); ?></p>
        <p><strong>Venue:</strong> <?php echo htmlspecialchars($defect['venue']); ?></p>
        <p><strong>Item:</strong> <?php echo htmlspecialchars($defect['item'] ?? 'None'); ?></p>
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

        <!-- Image Display -->
        <?php if (!empty($defect['image_path'])): ?>
            <p><strong>Image:</strong></p>
            <img src="<?php echo htmlspecialchars($defect['image_path']); ?>" alt="Defect Image" class="defect-image">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this image?');">
                    <button type="submit" name="remove_image">Remove Image</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p><strong>Image:</strong> No image attached.</p>
        <?php endif; ?>

        <!-- Upvote/Downvote Buttons -->
        <?php if (!$user_vote): ?>
            <form method="POST">
                <br></br><button type="submit" name="vote" value="up">👍</button>
                <button type="submit" name="vote" value="down">👎</button><br></br>
            </form>
        <?php else: ?>
            <p>You have already voted on this defect.</p>
        <?php endif; ?>
        
        <!-- Remarks Section -->
        <?php if (!empty($defect['remarks'])): ?>
            <h2>Remarks</h2>
            <p><?php echo nl2br(htmlspecialchars($defect['remarks'])); ?></p>
        <?php endif; ?>

        <!-- Admin Add Remarks Section -->
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <button onclick="toggleRemarkBox()">Add Remark</button><br></br>
            <div id="remarkBox" style="display: none;">
                <form method="POST">
                    <textarea name="remarks" rows="2" placeholder="Enter your remark here..."></textarea>
                    <button type="submit">Save Remark</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Admin Status Update Section -->
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

            <!-- Edit Button -->
            <button onclick="openEditModal()">Edit Defect</button>

            <!-- Edit Modal -->
            <div id="editModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>Edit Defect</h2>
                    <form method="POST" onsubmit="return validateEditForm()">
                        <label for="title">Title:</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($defect['title']); ?>" required>

                        <label for="description">Description:</label>
                        <textarea name="description" id="description" required><?php echo htmlspecialchars($defect['description']); ?></textarea>

                        <label for="venue">Venue:</label>
                        <select name="venue" id="venue" required>
                            <?php
                            $venue_query = $db->query("SELECT id, room FROM venue ORDER BY room");
                            $venues = $venue_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($venues as $venue): ?>
                                <option value="<?php echo $venue['id']; ?>" <?php if ($venue['room'] === $defect['venue']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($venue['room']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="item">Related Item:</label>
                        <select name="item" id="item">
                            <option value="">-- Select Item --</option>
                            <?php
                            $item_query = $db->query("SELECT id, name FROM items ORDER BY name");
                            $items = $item_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" <?php if ($item['name'] === $defect['item']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" name="edit_defect">Save Changes</button>
                        <button type="button" class="close-button" onclick="closeEditModal()">Close</button>
                    </form>
                </div>
            </div>

            <!-- Delete Defect Form -->
            <br></br>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this defect?');">
                <button type="submit" name="delete_defect">Delete Defect</button>
            </form>
        <?php endif; ?>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>