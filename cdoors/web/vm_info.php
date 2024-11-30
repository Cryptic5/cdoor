<?php
// Start the session to get the logged-in user info
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedInUser'])) {
    // Redirect to login if not logged in
    header('Location: login.php');
    exit();
}

// Get the logged-in user's information
$loggedInUser = $_SESSION['loggedInUser'];

// Connect to the database to retrieve VM details
$DB_HOST = "10.0.1.236";
$DB_NAME = "testdb";
$DB_USER = "user";
$DB_PASSWORD = "pass";

function get_db_connection() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASSWORD;
    try {
        $conn = new PDO("pgsql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
        return $conn;
    } catch (PDOException $e) {
        die("Could not connect to the database: " . $e->getMessage());
    }
}

// Fetch VM details for the logged-in user
function get_vm_details($user_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM vm_details WHERE ownerid = :userid ORDER BY ownerid DESC LIMIT 1");
    $stmt->bindParam(':userid', $user_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Retrieve the logged-in user's `personid` from the session
$user_id = $loggedInUser['personid'];  // Replace with the actual logged-in user ID (personid)
$vm_details = get_vm_details($user_id);  // Fetch VM details for this user
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VM Information</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="navbar">
    <a href="index.php" class="logo-btn">cdoors</a>
    <div id="nav-options" class="nav-options"></div>
  </div>
</header>

<div class="content">
  <h1 class="title">VM Information</h1>
  <p>Here is the SSH login information for your VM:</p>
  <div class="info-box">
    <?php if ($vm_details): ?>
      <label for="ssh-login">SSH Login</label>
      <input type="text" id="ssh-login" name="ssh-login" value="<?php echo htmlspecialchars($vm_details['user_login']); ?>" readonly>

      <label for="private-ip">Private IP Address</label>
      <input type="text" id="private-ip" name="private-ip" value="<?php echo htmlspecialchars($vm_details['private_ip']); ?>" readonly>

      <label for="pass">Password</label>
      <input type="text" id="pass" name="pass" value="<?php echo htmlspecialchars($vm_details['vm_ssh_password']); ?>" readonly>
    <?php else: ?>
      <p>No VM details available.</p>
    <?php endif; ?>
  </div>
</div>

<script>
  function updateUI() {
    const navOptions = document.getElementById('nav-options');
    const loggedInUser = <?php echo json_encode($loggedInUser); ?>;

    navOptions.innerHTML = '<a href="index.php">Home</a>';

    if (loggedInUser) {
      navOptions.innerHTML += `
        <a href="logout.php">Logout</a>
      `;
    } else {
      navOptions.innerHTML += `
        <a href="login.php">Log In</a>
        <a href="register.php">Register</a>
      `;
    }
  }

  updateUI();
</script>

</body>
</html>
