<?php

header('Content-Type: application/json');

require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$name     = trim(strip_tags($_POST['name']     ?? ''));
$email    = trim(strip_tags($_POST['email']    ?? ''));
$category = trim(strip_tags($_POST['category'] ?? ''));
$rating   = (int)($_POST['rating']             ?? 0);
$message  = trim(strip_tags($_POST['message']  ?? ''));

$errors = [];

if (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Name must be between 2 and 100 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

$allowed_categories = ['general', 'product', 'support', 'billing', 'other'];
if (!in_array($category, $allowed_categories, true)) {
    $errors[] = 'Please select a valid category.';
}

if ($rating < 1 || $rating > 5) {
    $errors[] = 'Rating must be between 1 and 5.';
}

if (strlen($message) < 10 || strlen($message) > 2000) {
    $errors[] = 'Message must be between 10 and 2000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}


$stmt = $conn->prepare(
    'INSERT INTO feedback (name, email, category, rating, message) VALUES (?, ?, ?, ?, ?)'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('sssds', $name, $email, $category, $rating, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your feedback has been submitted.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save feedback: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
