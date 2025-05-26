<?php
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';
require_once 'jwt.php';

try {
    // Verify that the user is an admin
    $user = requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = $_GET['type'] ?? '';
        
        switch ($type) {
            case 'users':
                // Get all users except the current admin
                $stmt = $conn->prepare("
                    SELECT id, username, email, phone, role, 
                           DATE_FORMAT(last_login, '%Y-%m-%d %H:%i:%s') as last_login,
                           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at
                    FROM users 
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "users" => $users]);
                break;

            case 'tasks':
                // Get all tasks with user information
                $stmt = $conn->prepare("
                    SELECT t.*, u.username as assigned_to
                    FROM tasks t
                    JOIN users u ON t.user_id = u.id
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute();
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "tasks" => $tasks]);
                break;

            case 'resources':
                // Get all resources with user information
                $stmt = $conn->prepare("
                    SELECT r.*, u.username as uploaded_by, t.title as task_title
                    FROM resources r
                    JOIN users u ON r.user_id = u.id
                    LEFT JOIN tasks t ON r.task_id = t.id
                    ORDER BY r.upload_date DESC
                ");
                $stmt->execute();
                $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success" => true, "resources" => $resources]);
                break;

            default:
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid type specified"]);
                break;
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'update_user_role':
                if (!isset($data['user_id']) || !isset($data['role'])) {
                    throw new Exception("User ID and role are required");
                }

                // Prevent admin from changing their own role
                if ($data['user_id'] == $user->user_id) {
                    throw new Exception("Cannot change your own role");
                }

                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$data['role'], $data['user_id']]);
                echo json_encode(["success" => true]);
                break;

            case 'delete_user':
                if (!isset($data['user_id'])) {
                    throw new Exception("User ID is required");
                }

                // Prevent admin from deleting themselves
                if ($data['user_id'] == $user->user_id) {
                    throw new Exception("Cannot delete your own account");
                }

                // Start transaction
                $conn->beginTransaction();

                try {
                    // Delete user's resources
                    $stmt = $conn->prepare("DELETE FROM resources WHERE user_id = ?");
                    $stmt->execute([$data['user_id']]);

                    // Delete user's tasks
                    $stmt = $conn->prepare("DELETE FROM tasks WHERE user_id = ?");
                    $stmt->execute([$data['user_id']]);

                    // Finally, delete the user
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$data['user_id']]);

                    $conn->commit();
                    echo json_encode(["success" => true]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
                break;

            case 'delete_task':
                if (!isset($data['task_id'])) {
                    throw new Exception("Task ID is required");
                }

                // Delete task and its resource associations
                $conn->beginTransaction();
                try {
                    // Remove task associations from resources
                    $stmt = $conn->prepare("UPDATE resources SET task_id = NULL WHERE task_id = ?");
                    $stmt->execute([$data['task_id']]);

                    // Delete the task
                    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
                    $stmt->execute([$data['task_id']]);

                    $conn->commit();
                    echo json_encode(["success" => true]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
                break;

            case 'delete_resource':
                if (!isset($data['resource_id'])) {
                    throw new Exception("Resource ID is required");
                }

                // Get resource info before deletion
                $stmt = $conn->prepare("SELECT filename FROM resources WHERE id = ?");
                $stmt->execute([$data['resource_id']]);
                $resource = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($resource) {
                    // Delete the physical file
                    $filepath = __DIR__ . '/../Finals/public/uploads/' . $resource['filename'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }

                    // Delete from database
                    $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
                    $stmt->execute([$data['resource_id']]);
                    echo json_encode(["success" => true]);
                } else {
                    throw new Exception("Resource not found");
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid action specified"]);
                break;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?> 