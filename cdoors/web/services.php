<?php
// Start the session to check if the user is logged in
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedInUser'])) {
    header('Location: login.php');
    exit();
}

$loggedInUser = $_SESSION['loggedInUser'];

// Initialize response variables
$responseMessage = "";

// Database connection settings
$DB_HOST = '10.0.1.236';  // Replace with your DB host IP
$DB_NAME = 'testdb';
$DB_USER = 'user';
$DB_PASSWORD = 'pass';

// Function to get database connection
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

// Function to get owner_id from the database
function getOwnerId($username) {
    $conn = getDbConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT personid FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user['personid'];
        }
    }
    return null;
}

// Handle VM creation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vm-name'])) {
    $vmName = trim($_POST['vm-name']); // Trim spaces from the input

    // Check if the VM name is not empty
    if (empty($vmName)) {
        $responseMessage = "VM name cannot be empty.";
    } else {
        // Retrieve the logged-in user's owner_id (user_id) from the database
        $ownerId = getOwnerId($loggedInUser['username']);

        // Check if owner_id was found
        if (!$ownerId) {
            $responseMessage = "Failed to retrieve owner ID.";
        } else {
            // Build the command to call the Python script
            $command = "sudo -u www-data python3 /usr/lib/cgi-bin/vm.py --vm-name " . escapeshellarg($vmName) . " --owner-id " . escapeshellarg($ownerId);

            // Execute the Python script and capture both output and exit status
            $output = shell_exec($command . " 2>&1");

            // Decode the JSON response from the Python script
            $response = json_decode($output, true);

            // Check if the response status is success
            if ($response && isset($response['status']) && $response['status'] == 'success') {
                // Redirect to vm_info.php after successful VM creation
                header('Location: vm_info.php');
                exit();
            } else {
                // If the response indicates failure, show an error message
                $responseMessage = "VM creation failed: " . ($response['message'] ?? 'Unknown error');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="navbar">
    <a href="index.php" class="logo-btn">cdoors</a>
    <div class="nav-options" id="nav-options">
      <a href="index.php">Home</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
</header>

<div class="content">
  <h1 class="title">Hello, <?php echo htmlspecialchars($loggedInUser['username']); ?>!</h1>
  <p>Order a Virtual Machine below:</p>

  <!-- Display response message -->
  <?php if (!empty($responseMessage)): ?>
    <div class="response"><?= $responseMessage ?></div>
  <?php endif; ?>

  <!-- VM Creation Form -->
  <form method="POST" action="services.php">
    <label for="vm-name">Enter Virtual Machine Name</label>
    <input type="text" id="vm-name" name="vm-name" placeholder="Enter VM name" required>
    <button class="btn" type="submit">Order a Virtual Machine</button>
  </form>
</div>
</body>
</html>
