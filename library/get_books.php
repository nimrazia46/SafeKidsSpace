<?php
// get_books.php - This file fetches data from your database
header('Content-Type: application/json');

try {
    // 1. Connect to the database using the same credentials as your save file
    $pdo = new PDO('mysql:host=localhost;dbname=safekidsspace', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Select all books from your table
    $stmt = $pdo->query("SELECT * FROM books");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Return the data as a JSON array
    echo json_encode($books ?: []);
} catch (Exception $e) {
    // If something goes wrong, return an empty array to prevent JS errors
    echo json_encode([]);
}
?>