<?php
// htdocs/admin/view_document.php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
if (!hasRole('ADMIN') && !hasRole('SUPER_ADMIN')) die("Access Denied.");
if (!isset($_GET['file']) || empty($_GET['file'])) die("No file specified.");

$file_param = $_GET['file'];
$type = $_GET['type'] ?? 'doc'; // Default is document, but can be 'profile'

// Determine which folder to look in safely
$base_dir = ($type === 'profile') ? '../uploads/profile_pics/' : '../uploads/documents/';

// Sanitize path to prevent hackers from leaving the folder
$safe_path = str_replace(['../', '..\\'], '', $file_param);
$filepath = $base_dir . ltrim($safe_path, '/');

// Display placeholder if profile pic is missing, or error if document is missing
if (!file_exists($filepath)) {
    if ($type === 'profile') {
        // Fallback to a blank avatar if they didn't upload a profile pic
        $filepath = '../assets/images/default_avatar.png'; 
        if(!file_exists($filepath)) die("No Image.");
    } else {
        die("File Not Found on Server.");
    }
}

// Get the correct file type
$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext == 'pdf') $mime = 'application/pdf';
elseif (in_array($ext, ['jpg', 'jpeg'])) $mime = 'image/jpeg';
elseif ($ext == 'png') $mime = 'image/png';

// Stream the file directly to the browser (BYPASSES 403 ERROR!)
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
?>