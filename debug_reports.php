<?php
require 'includes/db_connect.php';

// Simulate the query used in reports.php
$report_type = 'daily';
$sql = "SELECT s.*, DATE_FORMAT(s.created_at, '" . ($report_type == 'monthly' ? '%Y-%m-01' : '%Y-%m-%d') . "') as date_group FROM sales s ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Check if PDO::FETCH_GROUP works as expected with the *first* column implicitly? 
// No, FETCH_GROUP groups by the first column in the result set.
// In the query above: "SELECT s.*, DATE_FORMAT(...) as date_group"
// s.id is likely the first column. So it might be grouping by ID, not date_group!

echo "Query: " . $sql . "\n";
$first_row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "First Column Name: " . array_keys($first_row)[0] . "\n";
echo "First Column Value: " . array_values($first_row)[0] . "\n";

// To fix, we must select date_group AS THE FIRST COLUMN.
$sql_fix = "SELECT DATE_FORMAT(s.created_at, '" . ($report_type == 'monthly' ? '%Y-%m-01' : '%Y-%m-%d') . "') as date_group, s.* FROM sales s ORDER BY s.created_at DESC";
$stmt_fix = $pdo->prepare($sql_fix);
$stmt_fix->execute();
$grouped = $stmt_fix->fetchAll(PDO::FETCH_GROUP);

echo "Grouped Keys (Corrected Query): \n";
print_r(array_keys($grouped));
?>
