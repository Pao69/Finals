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

// Debug log file
$logFile = 'reset_password_debug.log';
function debugLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

debugLog('Reset password attempt started');
debugLog('Received data: ' . json_encode($data));

if (!$data || !isset($data['email']) || !isset($data['code']) || !isset($data['newPassword'])) {
    debugLog('Missing required fields');
    http_response_code(400);
    echo json_encode(["message" => "All fields are required"]);
    exit;
}

$email = trim($data['email']);
$code = trim($data['code']);
$newPassword = $data['newPassword'];

debugLog("Email: $email, Code: $code");

// Validate password requirements
if (strlen($newPassword) < 11) {
    debugLog('Password too short');
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must be at least 11 characters long"]);
    exit;
}
if (!preg_match('/[A-Za-z]/', $newPassword)) {
    debugLog('Password missing letter');
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must contain at least one letter"]);
    exit;
}
if (!preg_match('/[0-9]/', $newPassword)) {
    debugLog('Password missing number');
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must contain at least one number"]);
    exit;
}
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
    debugLog('Password missing special char');
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Password must contain at least one special character"]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Verify reset code
    $stmt = $conn->prepare("
        SELECT id, expiry, used FROM password_resets 
        WHERE email = ? 
        AND code = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email, $code]);
    $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resetRow) {
        debugLog('Reset code not found for email');
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Reset code not found for this email. Please request a new one."]);
        exit;
    }
    if ($resetRow['used'] == 1) {
        debugLog('Reset code already used');
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Reset code already used. Please request a new one."]);
        exit;
    }
    if (strtotime($resetRow['expiry']) < time()) {
        debugLog('Reset code expired');
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Reset code expired. Please request a new one."]);
        exit;
    }

    $resetId = $resetRow['id'];

    // Get user info
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        debugLog('User not found for email');
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found for this email"]);
        exit;
    }

    // Check if new password is same as current
    if (password_verify($newPassword, $user['password'])) {
        debugLog('New password same as current');
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "New password must be different from current password"]);
        exit;
    }

    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$newPasswordHash, $email]);

    if (!$result) {
        debugLog('Failed to update password');
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update password"]);
        exit;
    }

    // Mark reset code as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->execute([$resetId]);

    // Commit transaction
    $conn->commit();
    debugLog('Password reset successful');
    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully"
    ]);

} catch (Exception $e) {
    debugLog('Exception: ' . $e->getMessage());
    $conn->rollBack();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error resetting password: " . $e->getMessage()
    ]);
}
?> 