<?php
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "db.php";
require_once "jwt.php";

class User {
    public $user_id;
    public $role;
    
    public function __construct($id, $role) {
        $this->user_id = $id;
        $this->role = $role;
    }
}

function authenticateUser() {
    global $conn;
    
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return null;
    }

    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    $token = str_replace('Bearer ', '', $auth_header);

    if (empty($token)) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, role FROM users WHERE id IN (SELECT user_id FROM sessions WHERE token = ?)");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    return new User($user['id'], $user['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verify user is authenticated and is admin
    $user = requireRole('admin');
    if (!$user) {
        throw new Exception('Unauthorized access');
    }

    // Get current month's date range
    $endDate = date('Y-m-t'); // Last day of current month
    $startDate = date('Y-m-01'); // First day of current month
    $status = $_GET['status'] ?? 'all';

    // Base query for tasks
    $query = "
        SELECT 
            t.id,
            t.title,
            t.description,
            t.due_date,
            t.completed,
            t.created_at,
            t.priority,
            COUNT(r.id) as resource_count,
            u.username
        FROM tasks t
        LEFT JOIN resources r ON t.id = r.task_id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
    ";

    $params = [$startDate, $endDate];

    // Add status condition if provided
    if ($status === 'completed') {
        $query .= " AND t.completed = 1";
    } elseif ($status === 'pending') {
        $query .= " AND t.completed = 0";
    }

    // Group by task
    $query .= " GROUP BY t.id";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current month name and year
    $currentMonth = date('F Y');

    // Calculate summary statistics
    $totalTasks = count($tasks);
    $completedTasks = 0;
    $pendingTasks = 0;
    $totalResources = 0;
    $priorityCounts = ['low' => 0, 'medium' => 0, 'high' => 0];
    $tasksByDate = [];

    foreach ($tasks as $task) {
        // Count by status
        if ($task['completed']) {
            $completedTasks++;
        } else {
            $pendingTasks++;
        }

        // Count resources
        $totalResources += $task['resource_count'];

        // Count by priority
        $priorityCounts[$task['priority']]++;

        // Group by date
        $date = date('Y-m-d', strtotime($task['created_at']));
        if (!isset($tasksByDate[$date])) {
            $tasksByDate[$date] = 0;
        }
        $tasksByDate[$date]++;
    }

    // Sort tasks by date
    ksort($tasksByDate);

    // Prepare the response
    $response = [
        'success' => true,
        'period' => $currentMonth,
        'summary' => [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'total_resources' => $totalResources,
            'priority_distribution' => $priorityCounts,
            'tasks_by_date' => $tasksByDate
        ],
        'tasks' => $tasks
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 