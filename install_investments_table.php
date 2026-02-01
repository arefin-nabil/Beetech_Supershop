<?php
require_once 'includes/db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `investments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `investor_name` varchar(255) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `purpose` text DEFAULT NULL,
      `invest_date` date NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `invest_date` (`invest_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'investments' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
