<?php
include_once '../authcheck.php';
include '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$nurse_id = isset($_SESSION['nurse_id']) ? (int)$_SESSION['nurse_id'] : 0;

if (!$patient_id || !$nurse_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid patient or nurse ID']);
    exit();
}

if (!isset($_FILES['lab_image']) || $_FILES['lab_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['lab_image'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
    exit();
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = '../uploads/lab_results/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_filename = 'lab_' . $patient_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
$upload_path = $upload_dir . $unique_filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

// Store path relative to root (for displaying in views)
$stored_path = 'uploads/lab_results/' . $unique_filename;

// Insert into database
$stmt = $con->prepare("
    INSERT INTO lab_results (patient_id, nurse_id, image_path, image_name, notes, upload_date)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$original_filename = $file['name'];
$stmt->bind_param("iisss", $patient_id, $nurse_id, $stored_path, $original_filename, $notes);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Lab result uploaded successfully',
        'lab_result_id' => $stmt->insert_id
    ]);
} else {
    // Delete uploaded file if database insert fails
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $con->error]);
}

$stmt->close();
$con->close();
?>