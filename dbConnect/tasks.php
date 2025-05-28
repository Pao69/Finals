<?php
/**
 * NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
 * tasks.php - Handles CRUD operations for tasks (GET, POST, DELETE, etc.)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add headers for CORS
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// NOTE: Handle preflight CORS requests for browsers.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';
require_once 'jwt.php';

// NOTE: Utility function for logging errors to a file.
function logError($message, $error = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($error) {
        $logMessage .= " - " . $error;
    }
    error_log($logMessage . "\n", 3, "tasks_error.log");
}

// NOTE: Validate incoming task data for required fields and formats.
function validateTaskData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = "Title is required";
    }
    
    if (isset($data['due_date']) && !empty($data['due_date'])) {
        // Validate date format MM/DD/YYYY HH:MM AM/PM
        $date = DateTime::createFromFormat('m/d/Y h:i A', $data['due_date']);
        if (!$date) {
            $errors[] = "Invalid due date format. Use MM/DD/YYYY HH:MM AM/PM";
        }
    }
    
    return $errors;
}

// CORS is now handled by the handle_cors() function in jwt.php
handle_cors();

// NOTE: Get user data from JWT token for authentication.
$user = getUserFromToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get database connection
    $pdo = getConnection();
    
    // NOTE: Main switch to handle different HTTP request methods (GET, POST, DELETE, etc.)
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // NOTE: Handle fetching tasks (all or by ID).
            // Get task ID from query parameters if it exists
            $taskId = isset($_GET['taskId']) ? $_GET['taskId'] : null;
            
            if ($taskId) {
                // Fetch specific task
                $stmt = $pdo->prepare("
                    SELECT t.*, u.username as owner_username,
                           DATE_FORMAT(t.due_date, '%m/%d/%Y %h:%i %p') as due_date
                    FROM tasks t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.id = :taskId AND (t.user_id = :userId OR :isAdmin = true)
                ");
                $stmt->execute([
                    'taskId' => $taskId,
                    'userId' => $user->user_id,
                    'isAdmin' => $user->role === 'admin'
                ]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($task) {
                    echo json_encode(['success' => true, 'task' => $task]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Task not found or access denied']);
                }
            } else {
                // Fetch all tasks for the user
                $stmt = $pdo->prepare("
                    SELECT t.*, u.username as owner_username,
                           DATE_FORMAT(t.due_date, '%m/%d/%Y %h:%i %p') as due_date
                    FROM tasks t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.user_id = :userId OR :isAdmin = true
                    ORDER BY t.due_date ASC
                ");
                $stmt->execute([
                    'userId' => $user->user_id,
                    'isAdmin' => $user->role === 'admin'
                ]);
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'tasks' => $tasks]);
            }
            break;

        case 'POST':
            // NOTE: Handle task creation or update.
            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if this is just a completion status update
            if (isset($data['id']) && isset($data['completed']) && count($data) === 2) {
                // Update only the completion status
                $sql = "UPDATE tasks 
                        SET completed = :completed
                        WHERE id = :id AND (user_id = :user_id OR :isAdmin = true)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
                $stmt->bindValue(':completed', $data['completed'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user->user_id, PDO::PARAM_INT);
                $stmt->bindValue(':isAdmin', $user->role === 'admin', PDO::PARAM_BOOL);
                
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Task completion status updated successfully'
                    ]);
                    break;
                } else {
                    throw new Exception('Failed to update task completion status');
                }
            }

            // For full task updates, validate required fields
            if (empty($data['title']) || empty($data['due_date'])) {
                throw new Exception('Title and due date are required');
            }

            // Validate and convert date
            $errors = validateTaskData($data);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit();
            }

            // Convert date from MM/DD/YYYY HH:MM AM/PM to MySQL format
            $date = DateTime::createFromFormat('m/d/Y h:i A', $data['due_date']);
            $mysqlDate = $date->format('Y-m-d H:i:s');
            
            // Prepare the SQL statement for task creation
            if (isset($data['id'])) {
                // Update existing task
                $sql = "UPDATE tasks 
                        SET title = :title, 
                            description = :description, 
                            due_date = :due_date, 
                            completed = :completed,
                            priority = :priority
                        WHERE id = :id AND (user_id = :user_id OR :isAdmin = true)";
                
                $stmt = $pdo->prepare($sql);
                
                // Bind parameters for update
                $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user->user_id, PDO::PARAM_INT);
                $stmt->bindValue(':isAdmin', $user->role === 'admin', PDO::PARAM_BOOL);
            } else {
                // Create new task
                $sql = "INSERT INTO tasks (user_id, title, description, due_date, completed, priority) 
                        VALUES (:user_id, :title, :description, :due_date, :completed, :priority)";
                
                $stmt = $pdo->prepare($sql);
            }

            // Bind common parameters
            $stmt->bindValue(':user_id', $user->user_id, PDO::PARAM_INT);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':due_date', $mysqlDate, PDO::PARAM_STR);
            $stmt->bindValue(':completed', $data['completed'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':priority', $data['priority'] ?? 'medium', PDO::PARAM_STR);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => isset($data['id']) ? 'Task updated successfully' : 'Task created successfully',
                    'task_id' => isset($data['id']) ? $data['id'] : $pdo->lastInsertId()
                ]);
            } else {
                throw new Exception(isset($data['id']) ? 'Failed to update task' : 'Failed to create task');
            }
            break;

        case 'DELETE':
            // NOTE: Handle task deletion.
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['taskId'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                exit();
            }
            
            // Delete task
            $stmt = $pdo->prepare("
                DELETE FROM tasks 
                WHERE id = :taskId AND (user_id = :userId OR :isAdmin = true)
            ");
            
            $stmt->execute([
                'taskId' => $data['taskId'],
                'userId' => $user->user_id,
                'isAdmin' => $user->role === 'admin'
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found or unauthorized']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    // NOTE: Catch and log any exceptions that occur during request handling.
    logError('Exception in tasks.php', $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>