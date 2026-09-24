<?php
// htdocs/student/ajax_upload.php
session_start();
require_once '../config/database.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Check for upload errors from the free host
    if ($file['error'] !== 0) {
        echo json_encode(['success' => false, 'message' => 'Server rejected file. Try a smaller file. (Error: '.$file['error'].')']);
        exit;
    }

    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only PDF, JPG, PNG allowed.']);
        exit;
    }

    // Generate unique name
    $new_filename = $_SESSION['user_id'] . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $destination = '../uploads/documents/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        
        // FORCE LINUX FILE PERMISSIONS SO ADMIN CAN VIEW IT (Fixes 403 Error)
        chmod($destination, 0644); 
        
        // Success! Send the filename back to the browser
        echo json_encode(['success' => true, 'filename' => $new_filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save to folder. Check folder permissions.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
}
?>