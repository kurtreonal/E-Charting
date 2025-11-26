<?php
session_start();
include 'connection.php';

// require patient session
$patient_id = $_SESSION['patient_id'] ?? null;
$nurse_id = $_SESSION['nurse_id'] ?? null; // optional

if (!$patient_id) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['lab_file'])) {
    $uploadDir = __DIR__ . '/uploads/labs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $file = $_FILES['lab_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_error'] = "Upload error code: " . $file['error'];
        header("Location: patient-profile.php");
        exit();
    }

    // validate type & size if you want (example allow pdf, png, jpg)
    $allowed = ['application/pdf','image/png','image/jpeg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        $_SESSION['upload_error'] = "Unsupported file type.";
        header("Location: patient-profile.php");
        exit();
    }

    // unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = 'lab_' . $patient_id . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $stmt = $con->prepare("INSERT INTO lab_results (patient_id, nurse_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
        $file_path_db = 'uploads/labs/' . $newName; // web path
        $file_type = $mime;
        $stmt->bind_param("iisss", $patient_id, $nurse_id, $file['name'], $file_path_db, $file_type);
        $stmt->execute();
        $stmt->close();

        $_SESSION['upload_success'] = "File uploaded.";
    } else {
        $_SESSION['upload_error'] = "Could not move uploaded file.";
    }

    header("Location: patient-profile.php");
    exit();
}
?>
