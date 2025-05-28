<?php
/**
 * NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
 * profile.php - Handles user profile data retrieval and updates.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'jwt.php';
require_once 'db.php';

// CORS is now handled by the handle_cors() function in jwt.php
handle_cors();

// Constants
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', __DIR__ . '/../Finals/public/pfp/');

// Error handling function
function sendResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

// Validate file
function validateFile($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'No file uploaded or upload error occurred';
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File size exceeds limit of 5MB';
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
    }

    return null;
}

try {
    // Verify user using auth_middleware
    $user = verifyToken();
    if (!$user) {
        sendResponse(false, 'Unauthorized access');
    }
    $user_id = $user->user_id;

    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            sendResponse(false, 'Failed to create upload directory');
        }
    }

    // Handle requests
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Validate file
            $error = validateFile($_FILES['profile_image'] ?? null);
            if ($error) {
                sendResponse(false, $error);
            }

            $file = $_FILES['profile_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Generate unique filename
            $new_filename = 'pfp_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = UPLOAD_DIR . $new_filename;

            // Delete old profile picture if exists
            $stmt = $conn->prepare("SELECT img_link FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['img_link']) {
                $old_file = UPLOAD_DIR . basename($row['img_link']);
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }

            // Upload new file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $img_link = '/pfp/' . $new_filename;
                
                // Update database
                $stmt = $conn->prepare("UPDATE users SET img_link = ? WHERE user_id = ?");
                if ($stmt->execute([$img_link, $user_id])) {
                    sendResponse(true, 'Profile picture updated successfully', ['img_link' => $img_link]);
                } else {
                    unlink($upload_path);
                    sendResponse(false, 'Failed to update database');
                }
            } else {
                sendResponse(false, 'Failed to upload file');
            }
            break;

        case 'GET':
            // Get user's profile image
            $stmt = $conn->prepare("SELECT img_link FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['img_link']) {
                sendResponse(true, 'Profile image retrieved', ['img_link' => $row['img_link']]);
            } else {
                sendResponse(false, 'No profile image found');
            }
            break;

        case 'DELETE':
            // Delete profile picture
            $stmt = $conn->prepare("SELECT img_link FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['img_link']) {
                $file_path = UPLOAD_DIR . basename($row['img_link']);
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Update database
            $stmt = $conn->prepare("UPDATE users SET img_link = NULL WHERE user_id = ?");
            if ($stmt->execute([$user_id])) {
                sendResponse(true, 'Profile picture deleted successfully');
            } else {
                sendResponse(false, 'Failed to update database');
            }
            break;

        default:
            sendResponse(false, 'Invalid request method');
            break;
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while processing your request');
}
?> 