<?php
// Start the session at the very top, no whitespace or output before this
session_start();

// Include the database connection script
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to the login page
    exit;
}

// Check if the user has any defects with new remarks
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$new_remark_query = "
    SELECT id, title 
    FROM defect_table 
    WHERE username = ? AND has_new_remark = TRUE
";
$new_remark_stmt = $db->prepare($new_remark_query);
$new_remark_stmt->execute([$username]);
$new_remarks = $new_remark_stmt->fetchAll(PDO::FETCH_ASSOC);

// If there are new remarks, show an alert
if (!empty($new_remarks)) {
    echo "<script>alert('A remark has been added by the admin for one of your defects.');</script>";

    // Reset has_new_remark to FALSE for all defects of the user
    $reset_query = "UPDATE defect_table SET has_new_remark = FALSE WHERE username = ?";
    $reset_stmt = $db->prepare($reset_query);
    $reset_stmt->execute([$username]);
}

// Include the topbar after session_start and login check
include 'topbar.php';

// Default filter and sorting options
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$venue_filter = isset($_GET['venue']) ? $_GET['venue'] : 'all';
$item_filter = isset($_GET['item']) ? $_GET['item'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$vote_order = isset($_GET['vote_order']) ? $_GET['vote_order'] : 'none'; // New vote order filter
$search_term = isset($_GET['search']) ? $_GET['search'] : ''; // Search term
$user_filter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all'; // User filter

$order_by = $vote_order === 'none' ? 'created_at' : 'votes'; // Default to created_at if none
$order_dir = $vote_order === 'none' ? 'desc' : $vote_order; // Default to desc if none

// Fetch venues for dropdown
$venue_query = $db->query("SELECT id, room FROM venue");
$venues = $venue_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch items with their counts, casting i.id to text
$item_query = $db->query("
    SELECT i.name, COUNT(d.id) AS item_count 
    FROM items i 
    LEFT JOIN defect_table d ON d.item = i.id::text 
    WHERE i.name IS NOT NULL 
    GROUP BY i.name 
    ORDER BY i.name
");
$items = $item_query->fetchAll(PDO::FETCH_ASSOC);

// Build the SQL query dynamically based on filters
$query = "
    SELECT 
        d.id, d.created_at, d.title, d.username, d.status, v.room AS venue, i.name AS item, d.votes 
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
    WHERE 1=1
";

// Add filters dynamically
if ($status_filter !== 'all') {
    $query .= " AND d.status = :status_filter";
}
if ($venue_filter !== 'all') {
    $query .= " AND d.venue = :venue_filter";
}
if ($item_filter !== 'all') {
    $query .= " AND i.name = :item_filter";
}
if (!empty($date_from)) {
    $query .= " AND d.created_at >= :date_from";
}
if (!empty($date_to)) {
    $query .= " AND d.created_at <= :date_to";
}
if (!empty($search_term)) {
    $query .= " AND (d.title LIKE :search_term OR d.username LIKE :search_term OR i.name LIKE :search_term)";
}
if ($user_filter === 'me') {
    $query .= " AND d.username = :username";
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
if ($item_filter !== 'all') {
    $stmt->bindParam(':item_filter', $item_filter, PDO::PARAM_STR);
}
if (!empty($date_from)) {
    $stmt->bindParam(':date_from', $date_from, PDO::PARAM_STR);
}
if (!empty($date_to)) {
    $stmt->bindParam(':date_to', $date_to, PDO::PARAM_STR);
}
if (!empty($search_term)) {
    $search_term_like = "%$search_term%";
    $stmt->bindParam(':search_term', $search_term_like, PDO::PARAM_STR);
}
if ($user_filter === 'me') {
    $stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
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
    <link rel="stylesheet" href="styles3.css"> <!-- Link to the centralized CSS file -->
    <script>
        function clearFilter(filterName) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(filterName);
            window.location.search = urlParams.toString();
        }
    </script>
</head>
<body>
    <div class="container">
        <br></br><br></br>
        <p></p><h1>Dashboard</h1></p>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <br></br>

        <!-- Filters Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h3>Filters</h3>
            </div>

            <!-- Active Filter Tags -->
            <div class="filter-tags">
                <?php if (!empty($search_term)): ?>
                    <span class="filter-tag">
                        Search: <?php echo htmlspecialchars($search_term); ?>
                        <button class="remove-btn" onclick="clearFilter('search')">×</button>
                    </span>
                <?php endif; ?>
                <?php if ($status_filter !== 'all'): ?>
                    <span class="filter-tag">
                        Status: <?php echo htmlspecialchars($status_filter); ?>
                        <button class="remove-btn" onclick="clearFilter('status')">×</button>
                    </span>
                <?php endif; ?>
                <?php if ($venue_filter !== 'all'): ?>
                    <span class="filter-tag">
                        Venue: <?php echo htmlspecialchars($venues[array_search($venue_filter, array_column($venues, 'id'))]['room']); ?>
                        <button class="remove-btn" onclick="clearFilter('venue')">×</button>
                    </span>
                <?php endif; ?>
                <?php if ($item_filter !== 'all'): ?>
                    <span class="filter-tag">
                        Item: <?php echo htmlspecialchars($item_filter); ?>
                        <button class="remove-btn" onclick="clearFilter('item')">×</button>
                    </span>
                <?php endif; ?>
                <?php if (!empty($date_from)): ?>
                    <span class="filter-tag">
                        From: <?php echo htmlspecialchars($date_from); ?>
                        <button class="remove-btn" onclick="clearFilter('date_from')">×</button>
                    </span>
                <?php endif; ?>
                <?php if (!empty($date_to)): ?>
                    <span class="filter-tag">
                        To: <?php echo htmlspecialchars($date_to); ?>
                        <button class="remove-btn" onclick="clearFilter('date_to')">×</button>
                    </span>
                <?php endif; ?>
                <?php if ($vote_order !== 'none'): ?>
                    <span class="filter-tag">
                        Sort by Votes: <?php echo $vote_order === 'asc' ? 'Ascending' : 'Descending'; ?>
                        <button class="remove-btn" onclick="clearFilter('vote_order')">×</button>
                    </span>
                <?php endif; ?>
                <?php if ($user_filter !== 'all'): ?>
                    <span class="filter-tag">
                        Submitted By: <?php echo $user_filter === 'me' ? 'Me' : 'All Users'; ?>
                        <button class="remove-btn" onclick="clearFilter('user_filter')">×</button>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Filter Form -->
            <form action="" method="GET" class="filter-form">
                <div class="filter-group search-group">
                    <label for="search">Search</label>
                    <div class="search-container">
                        <input type="text" name="search" id="search" placeholder="Search" value="<?php echo htmlspecialchars($search_term); ?>">
                       
                    </div>
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="gray" <?php echo $status_filter === 'gray' ? 'selected' : ''; ?>>Not Read</option>
                        <option value="red" <?php echo $status_filter === 'red' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="yellow" <?php echo $status_filter === 'yellow' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="green" <?php echo $status_filter === 'green' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="venue">Venue</label>
                    <select name="venue" id="venue">
                        <option value="all" <?php echo $venue_filter === 'all' ? 'selected' : ''; ?>>All Venues</option>
                        <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo $venue['id']; ?>" <?php echo $venue_filter == $venue['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($venue['room']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="item">Item</label>
                    <select name="item" id="item">
                        <option value="all" <?php echo $item_filter === 'all' ? 'selected' : ''; ?>>All Items</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['name']); ?>" <?php echo $item_filter === $item['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['name']) . " (" . $item['item_count'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_from">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="filter-group">
                    <label for="vote_order">Sort by Votes</label>
                    <select name="vote_order" id="vote_order">
                        <option value="none" <?php echo $vote_order === 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="asc" <?php echo $vote_order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="desc" <?php echo $vote_order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="user_filter">Submitted By</label>
                    <select name="user_filter" id="user_filter">
                        <option value="all" <?php echo $user_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="me" <?php echo $user_filter === 'me' ? 'selected' : ''; ?>>Me</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="apply-button">Apply Filters</button>
                    <a href="dashboard.php" class="reset-button">Reset</a>
                </div>
            </form>
        </div>

        <!-- Defect Records -->
        <h2>Defect Records</h2>
        <table>
            <thead>
                <tr>
                    <th><a href="?order_by=created_at&order_dir=<?php echo $order_by === 'created_at' && $order_dir === 'asc' ? 'desc' : 'asc'; ?>&status=<?php echo urlencode($status_filter); ?>&venue=<?php echo urlencode($venue_filter); ?>&item=<?php echo urlencode($item_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&vote_order=<?php echo urlencode($vote_order); ?>">Created At</a></th>
                    <th>Created By</th>
                    <th>Item</th>
                    <th>Title</th>
                    <th>Venue</th>
                    <th>Votes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($defects as $defect): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($defect['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($defect['username']); ?></td>
                        <td><?php echo htmlspecialchars($defect['item']); ?></td>
                        <td>
                            <a href="view_defect.php?id=<?php echo $defect['id']; ?>">
                                <?php echo htmlspecialchars($defect['title']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($defect['venue']); ?></td>
                        <td><?php echo htmlspecialchars($defect['votes']); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($defect['status']) {
                                case 'gray': $statusClass = 'status-gray'; break;
                                case 'red': $statusClass = 'status-red'; break;
                                case 'yellow': $statusClass = 'status-yellow'; break;
                                case 'green': $statusClass = 'status-green'; break;
                                default: $statusClass = 'status-gray'; break;
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