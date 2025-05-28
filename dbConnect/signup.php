<?php
/**
 * NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
 * signup.php - Handles user registration and account creation.
 */

header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "db.php";

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$rawPassword = $data['password'] ?? '';
$isAdmin = $data['isAdmin'] ?? false;

// Validate username
if (empty($username)) {
    http_response_code(400);
    echo json_encode(["message" => "Username is required"]);
    exit;
}

if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(["message" => "Username must be between 3 and 50 characters"]);
    exit;
}

// Validate email
if (empty($email)) {
    http_response_code(400);
    echo json_encode(["message" => "Email is required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email format"]);
    exit;
}

// Validate phone (optional)
if (!empty($phone)) {
    if (!preg_match("/^\+?[\d\s-]{10,}$/", $phone)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid phone number format"]);
        exit;
    }
}

// Validate password
function validatePassword($password) {
    if (strlen($password) < 8) {
        return ["valid" => false, "message" => "Password must be at least 8 characters long"];
    }

    if (!preg_match("/[A-Z]/", $password)) {
        return ["valid" => false, "message" => "Password must contain at least one uppercase letter"];
    }

    if (!preg_match("/[a-z]/", $password)) {
        return ["valid" => false, "message" => "Password must contain at least one lowercase letter"];
    }

    if (!preg_match("/[0-9]/", $password)) {
        return ["valid" => false, "message" => "Password must contain at least one number"];
    }

    if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        return ["valid" => false, "message" => "Password must contain at least one special character"];
    }

    return ["valid" => true, "message" => "Password is valid"];
}

$passwordValidation = validatePassword($rawPassword);

if (!$passwordValidation["valid"]) {
    http_response_code(400);
    echo json_encode(["message" => $passwordValidation["message"]]);
    exit;
}

$password = password_hash($rawPassword, PASSWORD_BCRYPT);

// Set role based on isAdmin flag
$role = $isAdmin ? 'admin' : 'user';

$sql = "INSERT INTO users (username, email, phone, password, role, created_at) 
        VALUES (:username, :email, :phone, :password, :role, NOW())";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $username,
        ":email" => $email,
        ":phone" => $phone,
        ":password" => $password,
        ":role" => $role
    ]);

    echo json_encode(["message" => "success"]);

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(["message" => "Username, email, or phone already exists"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}
?>