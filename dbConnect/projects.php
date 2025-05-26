<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db.php';
require 'jwt.php';

function validateProjectData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = "Title is required";
    }
    
    if (isset($data['start_date']) && !empty($data['start_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['start_date']);
        if (!$date || $date->format('Y-m-d') !== $data['start_date']) {
            $errors[] = "Invalid start date format. Use YYYY-MM-DD";
        }
    }
    
    if (isset($data['end_date']) && !empty($data['end_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['end_date']);
        if (!$date || $date->format('Y-m-d') !== $data['end_date']) {
            $errors[] = "Invalid end date format. Use YYYY-MM-DD";
        }
    }
    
    return $errors;
}

try {
    // Verify user authentication for all routes
    $user = verifyToken();
    $userId = $user->user_id;

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare("SELECT id, user_id, title, description, 
                               DATE_FORMAT(start_date, '%Y-%m-%d') as start_date,
                               DATE_FORMAT(end_date, '%Y-%m-%d') as end_date,
                               status,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                               DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                               FROM projects 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "projects" => $projects]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $errors = validateProjectData($data);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => implode(", ", $errors)]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, start_date, end_date, status) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $userId,
            $data['title'],
            $data['description'] ?? '',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'In Progress'
        ]);

        if ($success) {
            $projectId = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT id, user_id, title, description, 
                                  DATE_FORMAT(start_date, '%Y-%m-%d') as start_date,
                                  DATE_FORMAT(end_date, '%Y-%m-%d') as end_date,
                                  status,
                                  DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                                  DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                                  FROM projects 
                                  WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'project' => $project]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to create project"]);
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['projectId'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Project ID is required"]);
            exit;
        }
        
        $errors = validateProjectData($data);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => implode(", ", $errors)]);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE projects 
                               SET title = ?, 
                                   description = ?, 
                                   start_date = ?, 
                                   end_date = ?,
                                   status = ?,
                                   updated_at = CURRENT_TIMESTAMP
                               WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'In Progress',
            $data['projectId'],
            $userId
        ]);
        
        if ($success && $stmt->rowCount() > 0) {
            $stmt = $conn->prepare("SELECT id, user_id, title, description, 
                                  DATE_FORMAT(start_date, '%Y-%m-%d') as start_date,
                                  DATE_FORMAT(end_date, '%Y-%m-%d') as end_date,
                                  status,
                                  DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                                  DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                                  FROM projects 
                                  WHERE id = ?");
            $stmt->execute([$data['projectId']]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'project' => $project]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Project not found or not owned by user"]);
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['projectId'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Project ID is required"]);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$data['projectId'], $userId]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Project not found or not owned by user"]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error", "error" => $e->getMessage()]);
}
?>
