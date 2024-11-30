<?php
// Start a session to store login state
session_start();

// Initialize variables
$Fname = $Lname = $dob = $username = $password = $confirm_password = $email = '';
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
    $Fname = trim($_POST['Fname']);
    $Lname = trim($_POST['Lname']);
    $dob = trim($_POST['dob']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);

    if (empty($Fname) || empty($Lname) || empty($dob) || empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $errorMessage = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $errorMessage = 'Passwords do not match.';
    } else {
        // Check if username already exists in the database
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $errorMessage = 'Username already taken.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into the database
                $stmt = $conn->prepare("INSERT INTO users (Fname, Lname, dob, username, email, password)
                                        VALUES (:Fname, :Lname, :dob, :username, :email, :password)");
                $stmt->bindParam(':Fname', $Fname);
                $stmt->bindParam(':Lname', $Lname);
                $stmt->bindParam(':dob', $dob);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                try {
                    $stmt->execute();
                    header('Location: login.php');  // Redirect to login page after successful registration
                    exit();
                } catch (Exception $e) {
                    $errorMessage = 'Error registering user: ' . $e->getMessage();
                }
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
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="navbar">
        <a href="index.html" class="logo-btn">cdoors</a>
        <div class="nav-options">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </div>
</header>

<div class="content">
    <h1 class="title">Register</h1>

    <!-- Show error message -->
    <?php if ($errorMessage): ?>
        <div class="error"><?= $errorMessage ?></div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="POST" action="">
        <label for="Fname">First Name</label>
        <input type="text" id="Fname" name="Fname" value="<?= htmlspecialchars($Fname) ?>" required>

        <label for="Lname">Last Name</label>
        <input type="text" id="Lname" name="Lname" value="<?= htmlspecialchars($Lname) ?>" required>

        <label for="dob">Date of Birth (yyyy-mm-dd)</label>
        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($dob) ?>" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit" class="btn">Register</button>
    </form>
</div>
</body>
</html>
