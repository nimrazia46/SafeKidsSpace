<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';

try {
    if ($category === 'all') {
        $stmt = $pdo->prepare("SELECT p.*, c.slug AS category_slug 
                               FROM store_products p 
                               JOIN store_categories c ON p.category_id = c.id 
                               WHERE p.is_active = 1 
                               ORDER BY p.id DESC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.slug AS category_slug 
                               FROM store_products p 
                               JOIN store_categories c ON p.category_id = c.id 
                               WHERE p.is_active = 1 AND c.slug = :category
                               ORDER BY p.id DESC");
        $stmt->execute(['category' => $category]);
    }
    
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to read product storage logs.']);
}
?>