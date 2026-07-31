<?php
// Set header to JSON for the frontend to read
header('Content-Type: application/json');

// 1. Read the raw POST data sent from your JavaScript
$json_data = file_get_contents('php://input');
$books = json_decode($json_data, true);

if (!$books) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

// 2. Connect to Database
try {
    // Replace 'username' and 'password' with your actual database credentials
    $pdo = new PDO('mysql:host=localhost;dbname=safekidsspace', 'root', '');
    // Set error mode to exception to catch connection issues
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// 3. Prepare statement
// We prepare once outside the loop for efficiency
$stmt = $pdo->prepare("INSERT INTO books (title, author, img_url, pdf_url, section) 
                       VALUES (:title, :author, :img_url, :pdf_url, :section)");

// 4. Execute inside a transaction
try {
    $pdo->beginTransaction();
    
    foreach ($books as $book) {
        $stmt->execute([
            ':title'    => $book['title'],
            ':author'   => $book['author'],
            ':img_url'  => $book['img_url'] ?? '',
            ':pdf_url'  => $book['pdf_url'] ?? '',
          ':section'  => $book['section']
        ]);
    }
    
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Books saved successfully!']);

} catch (Exception $e) {
    // If anything fails, rollback changes
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>