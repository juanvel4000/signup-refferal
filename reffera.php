<?php
// Include the configuration file and check login status
require_once 'config.php';
checkLogin();

/**
 * Generates a unique key of a specified length.
 *
 * @param int $length The length of the key (default: 16)
 * @return string A unique key
 */


function generateUniqueKey(int $length = 16): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Checks if a referral key is unique in the database.
 *
 * @param string $key The referral key to check
 * @param mysqli $mysqli The database connection
 * @return bool True if the key is unique, false otherwise
 */
function isKeyUnique(string $key, mysqli $mysqli): bool
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM refferal WHERE referral_key = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $mysqli->error);
    }
    
    $stmt->bind_param('s', $key);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $stmt->bind_result($count);
    $stmt->fetch();
    
    $stmt->close();
    return $count === 0;
}

/**
 * Inserts a referral key into the database.
 *
 * @param int $userId The user ID
 * @param string $referralKey The referral key
 * @param mysqli $mysqli The database connection
 * @return bool True if the insertion was successful, false otherwise
 */
function insertReferralKey(int $userId, string $referralKey, mysqli $mysqli): bool
{
    $stmt = $mysqli->prepare("INSERT INTO refferal (user_id, referral_key) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $mysqli->error);
    }
    
    $stmt->bind_param('is', $userId, $referralKey);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

/**
 * Updates the user's referral key status.
 *
 * @param int $userId The user ID
 * @param mysqli $mysqli The database connection
 * @return bool True if the update was successful, false otherwise
 */
function updateUser(int $userId, mysqli $mysqli): bool
{
    $stmt = $mysqli->prepare("UPDATE users SET ork = true WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $mysqli->error);
    }
    
    $stmt->bind_param('i', $userId);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

// Database connection
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Generate a unique referral key
do {
    $referralKey = generateUniqueKey();
} while (!isKeyUnique($referralKey, $mysqli));

// Get the logged-in user's ID
$userId = $_SESSION['id']; // Correct session variable name

// Insert the referral key into the database and update the user's column
try {
    if (insertReferralKey($userId, $referralKey, $mysqli) && updateUser($userId, $mysqli)) {
        echo "<div class='container mt-3'>
                <div class='alert alert-success'>
                    Referral key generated and saved successfully: <strong>$referralKey</strong>
                </div>
              </div>";
    } else {
        echo "<div class='container mt-3'>
                <div class='alert alert-danger'>
                    An error occurred while generating the referral key.
                </div>
              </div>";
    }
} catch (Exception $e) {
    echo "";
}

?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generate a Referral Key</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 360px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
       <h3>You can only generate one Referral Key</h3>
<p>Your Generated Referral key is: 
<?php
// Prepare the SQL query
$sql = "SELECT referral_key FROM refferal WHERE user_id = ?";

// Create a prepared statement
$stmt = $mysqli->prepare($sql);

// Check if the preparation was successful
if (!$stmt) {
    echo "Failed to prepare statement: " . $mysqli->error;
    exit;
}

// Bind the user ID parameter
$stmt->bind_param("i", $user_id);

// Set the user ID value
$user_id = $_SESSION['id']; // or however you get the user ID

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Check if there is a result
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['referral_key'];
} else {
    echo "No referral key found for this user.";
}

// Close the statement
$stmt->close();
?>
</p>
<a href="welcome.php" type="button" class="btn btn-success">Return to Dashboard</a>
    </div>
</body>
</html>
