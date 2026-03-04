<?php
require_once 'config.php';
require_once 'includes/db_connect.php';

try {
    // 1. Add `category` to products
    $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'Uncategorized'");
    echo "Added category column to products.\n";
} catch (PDOException $e) {
    echo "Warning (category): " . $e->getMessage() . "\n";
}

try {
    // 2. Make `product_id` NULLABLE in sale_items
    // Need to drop foreign key first, then re-add it if needed or just drop it.
    // The foreign key is sale_items_ibfk_2 on product_id.
    // Let's drop the foreign key because if product_id is NULL, it doesn't reference products.
    // But wait, ON DELETE CASCADE or RESTRICT? If it's nullable, we can keep the FK, it just allows NULL.
    // Let's first try to modify the column. If it fails due to FK, we drop FK.
    // In MySQL, you can modify column to NULL even with FK.
    $pdo->exec("ALTER TABLE sale_items MODIFY COLUMN product_id INT(11) NULL");
    echo "Made product_id nullable in sale_items.\n";
} catch (PDOException $e) {
    echo "Warning (product_id nullable): " . $e->getMessage() . "\n";
}

try {
    // 3. Add `custom_name` to sale_items
    $pdo->exec("ALTER TABLE sale_items ADD COLUMN custom_name VARCHAR(255) NULL");
    echo "Added custom_name column to sale_items.\n";
} catch (PDOException $e) {
    echo "Warning (custom_name): " . $e->getMessage() . "\n";
}

echo "Migration completed.\n";
?>
