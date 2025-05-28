<?php
/**
 * NOTE: The following comments are for educational/debugging purposes and may not cover all edge cases.
 * login.php - Handles user login, authentication, and JWT issuance.
 */

// Add headers for CORS
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

//access jwt and db before execution
require("jwt.php");
require("db.php");

// Debug log file
$logFile = 'login_debug.log';

//Encode logs into debug file
function debugLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if there's an existing token in the Authorization header
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = decode_jwt($token);
    
    if ($decoded) {
        // Token is valid, auto-login the user
        echo json_encode([
            "message" => "success",
            "token" => $token,
            "user" => [
                "id" => $decoded->user_id,
                "username" => $decoded->username,
                "email" => $decoded->email
            ]
        ]);
        exit;
    }
}

debugLog('Login attempt started');

$data = json_decode(file_get_contents("php://input"), true);
debugLog('Received data: ' . json_encode($data));

if (!$data || !isset($data["username"]) || !isset($data["password"])) {
    debugLog('Invalid input data');
    http_response_code(400);
    echo json_encode(["message" => "Username and password are required"]);
    exit;
}

$loginInput = trim($data["username"]);
$password = $data["password"];
debugLog('Login attempt for username: ' . $loginInput);


// Check if the username/email/phone is empty
if (empty($loginInput) || empty($password)) {
    debugLog('Empty username or password');
    http_response_code(400);
    echo json_encode(["message" => "Username/email/phone and password are required"]);
    exit;
}

$sql = "SELECT * FROM users WHERE username = :username OR email = :email OR phone = :phone";
debugLog('Executing SQL query');

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":username" => $loginInput,
        ":email" => $loginInput,
        ":phone" => $loginInput
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    debugLog('User found: ' . ($user ? 'yes' : 'no'));

    if ($user) {
        debugLog('Attempting password verification');
        $passwordValid = password_verify($password, $user["password"]);
        debugLog('Password valid: ' . ($passwordValid ? 'yes' : 'no'));

        if ($passwordValid) {
            debugLog('Login successful, generating token');
            
            // Generate JWT token with enhanced payload
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'last_login' => date('Y-m-d H:i:s')
            ];
            
            $token = generate_jwt($payload);
            debugLog('Token generated successfully');

            // Update last login time
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([":id" => $user['id']]);

            echo json_encode([
                "message" => "success",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "username" => $user['username'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "role" => $user['role'] ?? 'user'
                ]
            ]);
        } else {
            debugLog('Invalid password');
            http_response_code(401);
            echo json_encode(["message" => "Invalid credentials"]);
        }
    } else {
        debugLog('User not found');
        http_response_code(401);
        echo json_encode(["message" => "Invalid credentials"]);
    }
} catch (PDOException $e) {
    debugLog('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Internal server error", "error" => $e->getMessage()]);
}
?>