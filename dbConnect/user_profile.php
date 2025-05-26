<?php
require_once('jwt.php');
require_once('db.php');

try {
    // Verify user token and get user data
    $user = verifyToken();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Get user ID from token
    $userId = $user->user_id;

    // Fetch user data from database
    $stmt = $conn->prepare("SELECT id, username, email, phone, role, pfp FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        // Return user data
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'role' => $userData['role'] ?? 'user',
                'pfp' => $userData['pfp']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 