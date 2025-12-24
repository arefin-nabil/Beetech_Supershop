<?php
require_once 'config.php';
require_once 'includes/db_connect.php';

echo "Updating Database Schema...<br>";

try {
    // Check if column is already decimal (simple check or just run alter)
    // Running ALTER twice on same type is usually fine in MySQL/MariaDB
    $sql = "ALTER TABLE sales MODIFY COLUMN points_earned DECIMAL(10,2) NOT NULL DEFAULT 0.00";
    $pdo->exec($sql);
    echo "SUCCESS: 'sales.points_earned' colum converted to DECIMAL(10,2).<br>";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "Done.";
?>
