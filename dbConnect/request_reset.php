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

if (!$data || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(["message" => "Email is required"]);
    exit;
}

$email = trim($data['email']);

try {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Email not found"]);
        exit;
    }

    // Generate 6-digit code
    $resetCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiryTime = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store reset code
    $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expiry) VALUES (?, ?, ?)");
    $stmt->execute([$email, $resetCode, $expiryTime]);

    // In a real application, you would send this code via email
    // For development, we'll return it in the response
    echo json_encode([
        "success" => true,
        "message" => "Reset code sent successfully",
        "debug_code" => $resetCode // Remove this in production
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error requesting password reset: " . $e->getMessage()
    ]);
}
?> 