<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

require_once 'db.php';
require_once 'jwt.php';

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

// Get user data from token
$user = getUserFromToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get database connection
    $pdo = getConnection();
    
    // Use user_id from token
    $userId = $user->user_id;

    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get task_id from query parameters if it exists
            $taskId = isset($_GET['task_id']) ? $_GET['task_id'] : null;
            
            $query = "
                SELECT r.*, u.username as owner_username, t.title as task_title 
                FROM resources r 
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN tasks t ON r.task_id = t.id
            ";
            
            if ($taskId) {
                $query .= " WHERE r.task_id = :task_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['task_id' => $taskId]);
            } else {
                $stmt = $pdo->prepare($query);
                $stmt->execute();
            }
            
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'resources' => $resources]);
            break;

        case 'POST':
            debug_log("Starting file upload process");
            debug_log("FILES array:", $_FILES);
            debug_log("POST array:", $_POST);

            // Handle file upload
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            $description = $_POST['description'] ?? '';
            $taskId = $_POST['task_id'] ?? null;
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $errorMessage = isset($uploadErrors[$file['error']]) 
                    ? $uploadErrors[$file['error']] 
                    : 'Unknown upload error';
                throw new Exception('Upload error: ' . $errorMessage);
            }
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type: ' . $file['type']);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('resource_') . '.' . $extension;
            
            // Ensure upload directory exists and is writable
            $uploadDir = __DIR__ . '/../public/uploads';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    debug_log("Failed to create upload directory", [
                        'dir' => $uploadDir,
                        'error' => error_get_last()
                    ]);
                    throw new Exception('Failed to create upload directory');
                }
                chmod($uploadDir, 0777);
            }

            if (!is_writable($uploadDir)) {
                debug_log("Upload directory is not writable", [
                    'dir' => $uploadDir,
                    'perms' => substr(sprintf('%o', fileperms($uploadDir)), -4)
                ]);
                throw new Exception('Upload directory is not writable');
            }
            
            $uploadPath = $uploadDir . '/' . $filename;
            debug_log("Attempting to move uploaded file", [
                'from' => $file['tmp_name'],
                'to' => $uploadPath
            ]);
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $moveError = error_get_last();
                debug_log("Failed to move uploaded file", [
                    'error' => $moveError,
                    'tmp_name' => $file['tmp_name'],
                    'destination' => $uploadPath
                ]);
                throw new Exception('Failed to move uploaded file: ' . ($moveError['message'] ?? 'Unknown error'));
            }
            
            debug_log("File moved successfully", ['path' => $uploadPath]);
            
            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO resources (user_id, task_id, filename, original_filename, file_type, file_size, description, upload_date)
                VALUES (:user_id, :task_id, :filename, :original_filename, :file_type, :file_size, :description, NOW())
            ");
            
            $params = [
                'user_id' => $userId,
                'task_id' => $taskId,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'description' => $description
            ];
            
            debug_log("Inserting into database", $params);
            
            if (!$stmt->execute($params)) {
                $error = $stmt->errorInfo();
                debug_log("Database error", $error);
                throw new Exception('Database error: ' . $error[2]);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Resource uploaded successfully',
                'resource' => [
                    'filename' => $filename,
                    'original_filename' => $file['name'],
                    'file_type' => $file['type'],
                    'file_size' => $file['size']
                ]
            ]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $resourceId = $data['resource_id'] ?? null;
            
            if (!$resourceId) {
                throw new Exception('Resource ID is required');
            }
            
            // Check if user owns the resource or is admin
            $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
            $stmt->execute(['id' => $resourceId]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resource) {
                throw new Exception('Resource not found');
            }
            
            // Check if user is owner or admin
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resource['user_id'] !== $userId && $user['role'] !== 'admin') {
                throw new Exception('Unauthorized to delete this resource');
            }
            
            // Delete file
            $filePath = __DIR__ . '/../public/uploads/' . $resource['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM resources WHERE id = :id");
            $stmt->execute(['id' => $resourceId]);
            
            echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    debug_log("Error occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
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
            'error' => $e->getMessage(),
            'file_error' => isset($_FILES['file']) ? $_FILES['file']['error'] : null
        ]
    ]);
}
?> 