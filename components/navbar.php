<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current page for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../global.css">
    <title>Document</title>
</head>
<body>
    <nav class="vertical-nav">
    <ul>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Logged-in links -->
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="index.php">Dashboard</a>
            </li>
            <li class="<?php echo ($current_page == 'invoices.php') ? 'active' : ''; ?>">
                <a href="invoices.php">Invoices</a>
            </li>
            <li class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <a href="profile.php">Profile</a>
            </li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <!-- Guest links -->
            <li class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                <a href="login.php">Login</a>
            </li>
            <li class="<?php echo ($current_page == 'signup.php') ? 'active' : ''; ?>">
                <a href="signup.php">Sign Up</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
</body>
</html>