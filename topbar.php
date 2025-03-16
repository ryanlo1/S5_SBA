<!-- topbar.php -->
<div class="top-bar">
    
    <div class="top-bar-center">
        <a href="dashboard.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>">Dashboard</a>
        <a href="submission.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'about.php') echo 'active'; ?>">Submit</a>
    </div>
    <div class="top-bar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'login.php') echo 'active'; ?>">Login</a>
        <?php endif; ?>
    </div>
</div>
<link rel="stylesheet" href="topbar.css">