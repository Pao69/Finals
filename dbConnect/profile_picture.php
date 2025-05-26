<?php
// Enable error reporting and display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
error_log("=== Profile Picture Upload Started ===");

require_once 'jwt.php';
require_once 'db.php';

// CORS is now handled by the handle_cors() function in jwt.php
handle_cors();

// Debug logging function
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\n" . print_r($data, true);
    }
    error_log($log . "\n", 3, __DIR__ . '/debug.log');
}

debug_log("Request received", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'files' => isset($_FILES) ? $_FILES : 'No files',
    'headers' => getallheaders()
]);

try {
    // Get user data from token
    $user = getUserFromToken();
    if (!$user) {
        debug_log("Unauthorized access attempt");
        throw new Exception('Unauthorized access');
    }
    $userId = $user->user_id;
    debug_log("User authenticated", ['id' => $userId]);

    // Constants
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
    define('UPLOAD_DIR', __DIR__ . '/../public/pfp/');
    define('DEFAULT_AVATAR', 'https://ionicframework.com/docs/img/demos/avatar.svg');

    debug_log("Constants defined", [
        'UPLOAD_DIR' => UPLOAD_DIR,
        'exists' => file_exists(UPLOAD_DIR),
        'writable' => is_writable(UPLOAD_DIR)
    ]);

    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        debug_log("Created upload directory");
    }

    // Verify directory is writable
    if (!is_writable(UPLOAD_DIR)) {
        chmod(UPLOAD_DIR, 0777);
        if (!is_writable(UPLOAD_DIR)) {
            throw new Exception('Upload directory is not writable');
        }
        debug_log("Made directory writable");
    }

    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            debug_log("Processing POST request");
            if (!isset($_FILES['profile_image'])) {
                debug_log("No file uploaded", $_FILES);
                sendErrorResponse('No file uploaded', [
                    'files_present' => array_keys($_FILES),
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_filesize' => ini_get('upload_max_filesize')
                ]);
            }

            $file = $_FILES['profile_image'];
            debug_log("File details", $file);

            // Add more detailed error checking
            if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
                debug_log("Temporary file not found", [
                    'tmp_name' => $file['tmp_name'] ?? null,
                    'exists' => isset($file['tmp_name']) ? file_exists($file['tmp_name']) : false
                ]);
                sendErrorResponse('Temporary file not found', [
                    'tmp_name' => $file['tmp_name'] ?? null,
                    'exists' => isset($file['tmp_name']) ? file_exists($file['tmp_name']) : false
                ]);
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $errorMessage = isset($errorMessages[$file['error']]) 
                    ? $errorMessages[$file['error']] 
                    : 'Unknown upload error';
                debug_log("File upload error", [
                    'error_code' => $file['error'],
                    'message' => $errorMessage
                ]);
                throw new Exception('Upload error: ' . $errorMessage);
            }

            if ($file['size'] > MAX_FILE_SIZE) {
                debug_log("File too large", [
                    'size' => $file['size'],
                    'max_size' => MAX_FILE_SIZE
                ]);
                throw new Exception('File is too large (max 5MB)');
            }

            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            debug_log("Checking file extension", [
                'extension' => $fileExt,
                'allowed' => ALLOWED_EXTENSIONS
            ]);

            if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
                throw new Exception('Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS));
            }

            // Generate unique filename
            $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
            $uploadPath = UPLOAD_DIR . $newFileName;

            debug_log("Attempting to move uploaded file", [
                'from' => $file['tmp_name'],
                'to' => $uploadPath,
                'exists' => file_exists($file['tmp_name']),
                'writable' => is_writable(dirname($uploadPath))
            ]);

            // Delete old image if exists
            $stmt = $conn->prepare("SELECT pfp FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $oldImage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($oldImage && $oldImage['pfp']) {
                $oldPath = UPLOAD_DIR . basename($oldImage['pfp']);
                if (file_exists($oldPath)) {
                    debug_log("Deleting old file", ['path' => $oldPath]);
                    if (!unlink($oldPath)) {
                        debug_log("Failed to delete old file", [
                            'path' => $oldPath,
                            'error' => error_get_last()
                        ]);
                    }
                }
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $moveError = error_get_last();
                debug_log("Failed to move uploaded file", [
                    'from' => $file['tmp_name'],
                    'to' => $uploadPath,
                    'error' => $moveError,
                    'tmp_file_exists' => file_exists($file['tmp_name']),
                    'destination_writable' => is_writable(dirname($uploadPath)),
                    'php_error' => $moveError
                ]);
                throw new Exception('Failed to save uploaded file: ' . ($moveError['message'] ?? 'Unknown error'));
            }

            debug_log("File moved successfully", ['path' => $uploadPath]);

            // Update database
            $imageLink = '/pfp/' . $newFileName;
            $updateStmt = $conn->prepare("UPDATE users SET pfp = ? WHERE id = ?");
            if (!$updateStmt) {
                debug_log("Failed to prepare update statement", ['error' => $conn->errorInfo()]);
                throw new Exception('Database error: Failed to prepare update statement');
            }

            $updateStmt->execute([$imageLink, $userId]);
            debug_log("Updating database", [
                'image_link' => $imageLink,
                'user_id' => $userId
            ]);

            if ($updateStmt->rowCount() > 0) {
                debug_log("Database updated successfully");
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile picture updated successfully',
                    'image_link' => $imageLink
                ]);
            } else {
                debug_log("Failed to update database", ['error' => $updateStmt->errorInfo()]);
                // If database update fails, delete the uploaded file
                unlink($uploadPath);
                throw new Exception('Failed to update database: ' . ($updateStmt->errorInfo()['0'] ?? 'Unknown error'));
            }
            break;

        case 'GET':
            debug_log("Processing GET request");
            try {
                // User already verified above, just return the profile picture
                $stmt = $conn->prepare("SELECT pfp FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result && $result['pfp']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Profile picture retrieved',
                        'image_link' => $result['pfp']
                    ]);
                } else {
                    debug_log("No image link found, using default avatar");
                    echo json_encode([
                        'success' => true,
                        'message' => 'No profile picture found',
                        'image_link' => DEFAULT_AVATAR
                    ]);
                }
            } catch (Exception $e) {
                debug_log("Error in GET request", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_details' => [
                        'error_type' => 'GET request error',
                        'error_message' => $e->getMessage(),
                        'error_line' => $e->getLine(),
                        'error_file' => basename($e->getFile())
                    ]
                ]);
            }
            break;

        case 'DELETE':
            // Delete profile picture
            $stmt = $conn->prepare("SELECT pfp FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['pfp']) {
                $filePath = UPLOAD_DIR . basename($result['pfp']);
                if (file_exists($filePath)) {
                    if (!unlink($filePath)) {
                        throw new Exception('Failed to delete file');
                    }
                }
            }

            // Update database
            $updateStmt = $conn->prepare("UPDATE users SET pfp = NULL WHERE id = ?");
            if (!$updateStmt->execute([$userId])) {
                throw new Exception('Failed to update database');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile picture deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    debug_log("Error occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => [
            'upload_dir_exists' => defined('UPLOAD_DIR') ? file_exists(UPLOAD_DIR) : false,
            'upload_dir_writable' => defined('UPLOAD_DIR') ? is_writable(UPLOAD_DIR) : false,
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'files_present' => isset($_FILES) ? array_keys($_FILES) : [],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'file_error' => isset($_FILES['profile_image']) ? $_FILES['profile_image']['error'] : null,
            'file_tmp_name' => isset($_FILES['profile_image']) ? $_FILES['profile_image']['tmp_name'] : null,
            'file_size' => isset($_FILES['profile_image']) ? $_FILES['profile_image']['size'] : null,
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile())
        ]
    ]);
    exit();
}

// After error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// Add detailed error information to the response
function sendErrorResponse($message, $debug = []) {
    $response = [
        'success' => false,
        'message' => $message,
        'error_details' => array_merge($debug, [
            'upload_dir_exists' => defined('UPLOAD_DIR') ? file_exists(UPLOAD_DIR) : false,
            'upload_dir_writable' => defined('UPLOAD_DIR') ? is_writable(UPLOAD_DIR) : false,
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'files' => isset($_FILES) ? array_keys($_FILES) : [],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ])
    ];
    error_log("Error response: " . json_encode($response));
    http_response_code(500);
    echo json_encode($response);
    exit();
}
?> 