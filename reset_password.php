<?php
// reset_password.php
require_once 'config.php';
require_once 'includes/db_connect.php';

// New password
$new_password = 'admin';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update the admin user's password
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
    $stmt->execute(['password' => $hashed_password]);

    echo "<h3>Password Reset Successful!</h3>";
    echo "<p>Your new login credentials are:</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin</p>";
    echo "<p><a href='login.php'>Click here to login</a></p>";
    echo "<p style='color:red;'><b>SECURITY WARNING:</b> Please delete this file (reset_password.php) immediately after logging in.</p>";
} catch (PDOException $e) {
    echo "Error updating password: " . $e->getMessage();
}
?>
