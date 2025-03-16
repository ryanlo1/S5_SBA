<?php
session_start();
include 'db.php'; // Include the database connection script
include 'topbar.php'; // Include the topbar (if needed)

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    echo "You must be logged in to submit a defect report.";
    exit;
}

// Fetch venues from the database
$stmt = $db->query("SELECT id, room FROM venue");
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch items from the database
$stmt = $db->query("SELECT id, name FROM items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pre-fill form fields if data is sent via POST (when editing), and validate
$venue_id = '';
$item_id = '';
$title = '';
$description = '';
$image_error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['venue']) || !isset($_POST['item']) || !isset($_POST['title']) || !isset($_POST['description'])) {
        $error = "All fields (venue, item, title, description) are required.";
    } else {
        $venue_id = $_POST['venue'];
        $item_id = $_POST['item'];
        $title = $_POST['title'];
        $description = $_POST['description'];

        // Handle image upload (optional)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/'; // Directory to store uploaded images
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Create the directory if it doesn't exist
            }

            $file_name = basename($_FILES['image']['name']);
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file type (only images allowed)
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_type, $allowed_types)) {
                $image_error = "Only JPG, JPEG, PNG, and GIF images are allowed.";
            } else {
                // Generate a unique file name to avoid conflicts
                $unique_name = uniqid() . '.' . $file_type;
                $image_path = $upload_dir . $unique_name;

                // Move the uploaded file to the uploads directory
                if (!move_uploaded_file($file_tmp, $image_path)) {
                    $image_error = "Failed to upload image.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Defect Report</title>
    <script>
        function validateForm() {
            const venue = document.getElementById('venue').value;
            const item = document.getElementById('item').value;
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();

            if (!venue) {
                alert('Please select a venue.');
                return false;
            }

            if (!item) {
                alert('Please select an item.');
                return false;
            }

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

            return true; // Allow form submission if validation passes
        }

        // Disable the submit button after it's clicked
        function disableSubmitButton(form) {
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
        }
    </script>
</head>
<body>
    <h1>Submit a Defect Report</h1>
    
    <!-- Display server-side validation error if present -->
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (isset($image_error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($image_error); ?></p>
    <?php endif; ?>

    <!-- Submission form -->
    <form action="preview_defect.php" method="POST" onsubmit="validateForm(); disableSubmitButton(this);" enctype="multipart/form-data">
        <label for="venue">Venue of Incident:</label>
        <select name="venue" id="venue" required>
            <option value="">-- Select Venue --</option>
            <?php foreach ($venues as $venue): ?>
                <option value="<?php echo $venue['id']; ?>" <?php echo $venue['id'] == $venue_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($venue['room']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label for="item">Related Item:</label>
        <select name="item" id="item" required>
            <option value="">-- Select Item --</option>
            <?php foreach ($items as $item): ?>
                <option value="<?php echo $item['id']; ?>" <?php echo $item['id'] == $item_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($item['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>
        <br><br>

        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
        <br><br>

        <label for="image">Upload Image (optional):</label>
        <input type="file" name="image" id="image" accept="image/*">
        <br><br>

        <button type="submit">Submit</button>
    </form>
</body>
</html>