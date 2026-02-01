<?php
require_once 'includes/db_connect.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM investments");
    echo "Table verified. Row count: " . $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "Verification failed: " . $e->getMessage();
}
?>
