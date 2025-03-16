<?php
session_start();
include 'db.php'; // Include the database connection script

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to submit a defect report.";
    exit;
}

// Check if the form data is sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data safely
    $venue_id = $_POST['venue'];
    $item_id = $_POST['item'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // Handle image upload
    $image_path = null; // Default value
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_path = 'uploads/' . uniqid() . '.' . $image_extension;

        // Move uploaded file if exists
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image_path = null; // Reset if upload fails
        }
    }

    // Validate venue
    $stmt = $db->prepare("SELECT room FROM venue WHERE id = ?");
    $stmt->execute([$venue_id]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venue) {
        echo "Invalid venue selected.";
        exit;
    }
    $venue_name = $venue['room'];

    // Validate item
    $stmt = $db->prepare("SELECT name FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo "Invalid item selected.";
        exit;
    }
    $item_name = $item['name'];
} else {
    echo "No data submitted.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles.css"> <!-- Main CSS -->
    <link rel="stylesheet" href="preview_defect.css"> <!-- Extra CSS for this page -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Defect Report</title>
    <script>
        // Disable the submit button after it's clicked
        function disableSubmitButton() {
            document.querySelector('button[type="submit"]').disabled = true;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Preview Defect Report</h1>
        <p>Please review the details of the defect report before submitting.</p>

        <div class="preview-details">
            <p><strong>Venue of Incident:</strong> <?php echo htmlspecialchars($venue_name); ?></p>
            <p><strong>Related Item:</strong> <?php echo htmlspecialchars($item_name); ?></p>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($title); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($description)); ?></p>

            <!-- Display Image if Uploaded -->
            <?php if ($image_path): ?>
                <p><strong>Image:</strong></p>
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Uploaded Image" class="uploaded-image">
            <?php else: ?>
                <p><strong>Image:</strong> No image attached.</p>
            <?php endif; ?>
        </div>

        <!-- Form to Confirm and Submit -->
        <form action="submit_defect.php" method="POST" enctype="multipart/form-data" onsubmit="disableSubmitButton()">
            <input type="hidden" name="venue" value="<?php echo htmlspecialchars($venue_id); ?>">
            <input type="hidden" name="item" value="<?php echo htmlspecialchars($item_id); ?>">
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
            <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($image_path); ?>">
            <button type="submit" class="submit-button">Confirm and Submit</button>
        </form>

        <!-- Form to Go Back and Edit -->
        <form action="submission.php" method="POST">
            <input type="hidden" name="venue" value="<?php echo htmlspecialchars($venue_id); ?>">
            <input type="hidden" name="item" value="<?php echo htmlspecialchars($item_id); ?>">
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
            <button type="submit" class="edit-button">Go Back and Edit</button>
        </form>
    </div>
</body>
</html>