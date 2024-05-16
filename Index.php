<?php
// Establish database connection (replace DB_HOST, DB_USER, DB_PASS, and DB_NAME with your actual database credentials)
$mysqli = new mysqli("DB_HOST", "DB_USER", "DB_PASS", "DB_NAME");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Function to calculate affiliate commissions
function calculateCommission($saleAmount, $level)
{
    switch ($level) {
        case 1:
            return $saleAmount * 0.1; // 10% commission for Level 1
        case 2:
            return $saleAmount * 0.05; // 5% commission for Level 2
        case 3:
            return $saleAmount * 0.03; // 3% commission for Level 3
        case 4:
            return $saleAmount * 0.02; // 2% commission for Level 4
        case 5:
            return $saleAmount * 0.01; // 1% commission for Level 5
        default:
            return 0; // No commission for levels beyond 5
    }
}

// Function to record a sale and calculate affiliate payouts
function recordSale($userId, $saleAmount)
{
    global $mysqli;
    $commission = 0;

    // Get user hierarchy levels
    $userLevels = [];
    $parent = $userId;
    for ($i = 1; $i <= 5; $i++) {
        $query = "SELECT parent_id FROM users WHERE user_id = $parent";
        $result = $mysqli->query($query);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userLevels[$i] = $row['parent_id'];
            $parent = $row['parent_id'];
        } else {
            break;
        }
    }

    // Calculate commissions and update balances
    foreach ($userLevels as $level => $parentId) {
        $commission += calculateCommission($saleAmount, $level);
        $updateQuery = "UPDATE users SET balance = balance + $commission WHERE user_id = $parentId";
        $mysqli->query($updateQuery);
    }

    // Record the sale in the database
    $insertQuery = "INSERT INTO sales (user_id, sale_amount, commission) VALUES ($userId, $saleAmount, $commission)";
    $mysqli->query($insertQuery);
}

// Function to add a new user to the database
function addUser($username, $parentId)
{
    global $mysqli;
    $query = "INSERT INTO users (username, parent_id, level) VALUES ('$username', $parentId, (
        SELECT level+1 FROM users WHERE user_id = $parentId
    ))";
    if ($mysqli->query($query) === TRUE) {
        echo "New user added successfully.";
    } else {
        echo "Error adding user: " . $mysqli->error;
    }
}

// Handle user registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = $_POST["username"];
    $parentId = $_POST["parent_id"];
    addUser($username, $parentId);
}

// Handle sales form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["record_sale"])) {
    $userId = $_POST["user_id"];
    $saleAmount = $_POST["sale_amount"];
    recordSale($userId, $saleAmount);
}

// Close database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration & Sales</title>
</head>
<body>
    <h1>User Registration Form</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="parent_id">Parent User ID:</label>
        <input type="number" id="parent_id" name="parent_id" required><br><br>
        <input type="submit" name="register" value="Register">
    </form>

    <h1>Record Sale Form</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="user_id">User ID:</label>
        <input type="number" id="user_id" name="user_id" required><br><br>
        <label for="sale_amount">Sale Amount:</label>
        <input type="number" id="sale_amount" name="sale_amount" step="0.01" required><br><br>
        <input type="submit" name="record_sale" value="Record Sale">
    </form>
</body>
</html>
