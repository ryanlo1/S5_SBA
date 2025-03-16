<?php
session_start();
include 'db.php'; // Include the database connection script
include 'topbar.php'; // Include the topbar (if needed)

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to the login page
    exit;
}

// Default filter and sorting options
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$venue_filter = isset($_GET['venue']) ? $_GET['venue'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'created_at';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] === 'asc' ? 'asc' : 'desc';

// Fetch venues for the dropdown
$venue_query = $db->query("SELECT id, room FROM venue");
$venues = $venue_query->fetchAll(PDO::FETCH_ASSOC);

// Build the SQL query dynamically based on filters
$query = "
    SELECT 
        d.id, d.created_at, d.title, d.username, d.status, v.room AS venue 
    FROM 
        defect_table d
    JOIN 
        venue v 
    ON 
        d.venue::bigint = v.id
    WHERE 1=1
";

// Add filters dynamically
if ($status_filter !== 'all') {
    $query .= " AND d.status = :status_filter";
}
if ($venue_filter !== 'all') {
    $query .= " AND d.venue = :venue_filter";
}
if (!empty($date_from)) {
    $query .= " AND d.created_at >= :date_from";
}
if (!empty($date_to)) {
    $query .= " AND d.created_at <= :date_to";
}

// Add ORDER BY clause for sorting
$query .= " ORDER BY $order_by $order_dir";

$stmt = $db->prepare($query);

// Bind parameters dynamically
if ($status_filter !== 'all') {
    $stmt->bindParam(':status_filter', $status_filter, PDO::PARAM_STR);
}
if ($venue_filter !== 'all') {
    $stmt->bindParam(':venue_filter', $venue_filter, PDO::PARAM_INT);
}
if (!empty($date_from)) {
    $stmt->bindParam(':date_from', $date_from, PDO::PARAM_STR);
}
if (!empty($date_to)) {
    $stmt->bindParam(':date_to', $date_to, PDO::PARAM_STR);
}

$stmt->execute(); // Execute the query
$defects = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all defect records
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* General container styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        h1, h2 {
            color: #333;
        }

        /* Filter button and panel styling */
        .filter-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .filter-button:hover {
            background-color: #0056b3;
        }

        .filter-panel {
            display: none;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-panel.open {
            display: block;
        }

        .filter-panel label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .filter-panel input, .filter-panel select {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
            width: 100%;
        }

        .filter-panel .apply-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .filter-panel .apply-button:hover {
            background-color: #218838;
        }

        .filter-panel .reset-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .filter-panel .reset-button:hover {
            background-color: #c82333;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        /* Status ball styling */
        .status-ball {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
        }

        .status-gray {
            background-color: gray;
        }

        .status-red {
            background-color: red;
        }

        .status-yellow {
            background-color: yellow;
        }

        .status-green {
            background-color: green;
        }
    </style>
    <script>
        function toggleFilterPanel() {
            const filterPanel = document.querySelector('.filter-panel');
            filterPanel.classList.toggle('open');
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

        <!-- Filter Button -->
        <button class="filter-button" onclick="toggleFilterPanel()">Filter</button>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <form action="" method="GET">
                <!-- Filter by Status -->
                <label for="status">Filter by Status:</label>
                <select name="status" id="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="gray" <?php echo $status_filter === 'gray' ? 'selected' : ''; ?>>Not Read</option>
                    <option value="red" <?php echo $status_filter === 'red' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="yellow" <?php echo $status_filter === 'yellow' ? 'selected' : ''; ?>>Work in Progress</option>
                    <option value="green" <?php echo $status_filter === 'green' ? 'selected' : ''; ?>>Resolved</option>
                </select>

                <!-- Filter by Venue -->
                <label for="venue">Filter by Venue:</label>
                <select name="venue" id="venue">
                    <option value="all" <?php echo $venue_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($venues as $venue): ?>
                        <option value="<?php echo $venue['id']; ?>" <?php echo $venue_filter == $venue['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($venue['room']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Filter by Date Range -->
                <label for="date_from">Date From:</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">

                <label for="date_to">Date To:</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">

                <!-- Sort by -->
                <label for="order_by">Sort By:</label>
                <select name="order_by" id="order_by">
                    <option value="created_at" <?php echo $order_by === 'created_at' ? 'selected' : ''; ?>>Time</option>
                    <option value="status" <?php echo $order_by === 'status' ? 'selected' : ''; ?>>Status</option>
                </select>

                <!-- Sort direction -->
                <label for="order_dir">Order:</label>
                <select name="order_dir" id="order_dir">
                    <option value="asc" <?php echo $order_dir === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="desc" <?php echo $order_dir === 'desc' ? 'selected' : ''; ?>>Descending</option>
                </select>

                <!-- Apply and Reset Buttons -->
                <button type="submit" class="apply-button">Apply Filters</button>
                <a href="dashboard.php" class="reset-button" style="text-decoration: none;">Reset</a>
            </form>
        </div>

        <!-- Display defect records -->
        <h2>Defect Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Created At</th>
                    <th>Created By</th>
                    <th>Title</th>
                    <th>Venue</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($defects as $defect): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($defect['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($defect['username']); ?></td>
                        <td>
                            <a href="view_defect.php?id=<?php echo $defect['id']; ?>">
                                <?php echo htmlspecialchars($defect['title']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($defect['venue']); ?></td>
                        <td>
                            <?php
                            // Determine the status class based on the status value
                            $statusClass = '';
                            switch ($defect['status']) {
                                case 'gray':
                                    $statusClass = 'status-gray';
                                    break;
                                case 'red':
                                    $statusClass = 'status-red';
                                    break;
                                case 'yellow':
                                    $statusClass = 'status-yellow';
                                    break;
                                case 'green':
                                    $statusClass = 'status-green';
                                    break;
                                default:
                                    $statusClass = 'status-gray'; // Default to gray if status is unknown
                                    break;
                            }
                            ?>
                            <span class="status-ball <?php echo $statusClass; ?>"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>