<?php
// Start a session to store login state
session_start();

// Initialize variables
$username = $password = '';
$errorMessage = '';

// Database connection settings
$DB_HOST = '10.0.1.236';  // Replace with the private IP of the database VM
$DB_NAME = 'testdb';
$DB_USER = 'user';
$DB_PASSWORD = 'pass';

// Connect to PostgreSQL database
function getDbConnection() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASSWORD;
    try {
        $conn = new PDO("pgsql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and sanitize
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $errorMessage = 'Please fill in both fields.';
    } else {
        // Check if user exists and password matches
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Store logged-in user data in the session
                $_SESSION['loggedInUser'] = $user;
                header('Location: services.php');  // Redirect to the services page
                exit();
            } else {
                $errorMessage = 'Invalid username or password.';
            }
        } else {
            $errorMessage = 'Failed to connect to the database.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="navbar">
        <a href="index.html" class="logo-btn">cdoors</a>
        <div class="nav-options">
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </div>
</header>

<div class="content">
    <h1 class="title">Log In</h1>

    <!-- Show error message -->
    <?php if ($errorMessage): ?>
        <div class="error"><?= $errorMessage ?></div>
    <?php endif; ?>

    <!-- Log In Form -->
    <form method="POST" action="">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn">Log In</button>
    </form>
</div>
</body>
</html>
