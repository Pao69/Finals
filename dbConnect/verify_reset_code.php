<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit();
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['email']) || !isset($data['code'])) {
    http_response_code(400);
    echo json_encode(["message" => "Email and code are required"]);
    exit;
}

$email = trim($data['email']);
$code = trim($data['code']);

try {
    // Check if code exists and is valid
    $stmt = $conn->prepare("
        SELECT id FROM password_resets 
        WHERE email = ? 
        AND code = ? 
        AND expiry > NOW() 
        AND used = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email, $code]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid or expired reset code"]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Code verified successfully"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error verifying reset code: " . $e->getMessage()
    ]);
}
?> 