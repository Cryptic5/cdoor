<?php
// Start the session to check login status
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Website</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="navbar">
        <a href="index.php" class="logo-btn">cdoors</a>
        <div class="nav-options" id="nav-options">
            <?php
                // Check if user is logged in
                if (isset($_SESSION['loggedInUser'])) {
                    echo '
                        <a href="logout.php">Logout</a>
                        <a href="services.php">Services</a>
                        <a href="vm_info.php">VM\'s</a>
                    ';
                } else {
                    echo '
                        <a href="login.php">Log In</a>
                        <a href="register.php">Register</a>
                    ';
                }
            ?>
        </div>
    </div>
</header>

<div class="content">
    <h1 class="title">CDOORS</h1>
    <div id="welcome-message">
        <?php
            if (isset($_SESSION['loggedInUser'])) {
                echo '<p>Welcome, ' . htmlspecialchars($_SESSION['loggedInUser']['username']) . '!</p>';
            } else {
                echo '<p>Welcome, guest! Please log in to access more features.</p>';
            }
        ?>
    </div>
    <div class="text-boxes">
        <div class="box">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</div>
        <div class="box">Pellentesque habitant morbi tristique senectus et netus.</div>
        <div class="box">Vivamus lacinia odio vitae vestibulum vestibulum.</div>
    </div>
</div>

</body>
</html>
