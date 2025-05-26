<?php
// Define constants

// define('JWT_SECRET_KEY', '4yLZT8uRn2xVfJ7vKpMwNcBgQhWjXkAe'); This is just an example, use your own secure key
define('JWT_SECRET_KEY', 'niggapenis69'); 
define('JWT_EXPIRATION_TIME', 7 * 24 * 60 * 60); // 7 days in seconds
define('APP_NAME', 'APP_NAME');
// ====================================
// Core JWT Functions
// ====================================

function generate_jwt($payload, $secret = JWT_SECRET_KEY) {
    // Add standard JWT claims
    $payload['iat'] = time(); // Issued at
    $payload['exp'] = time() + JWT_EXPIRATION_TIME; // Expiration time
    $payload['iss'] = 'APP_NAME'; // Issuer
    
    $headers = ['alg' => 'HS256', 'typ' => 'JWT'];
    $headers_encoded = base64url_encode(json_encode($headers));
    $payload_encoded = base64url_encode(json_encode($payload));
    
    $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
    $signature_encoded = base64url_encode($signature);
    
    return "$headers_encoded.$payload_encoded.$signature_encoded";
}

function base64url_encode($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}

function base64url_decode($str) {
    return base64_decode(strtr($str, '-_', '+/'));
}

function decode_jwt($jwt, $secret = JWT_SECRET_KEY) {
        if (!$jwt) {
            return null;
        }

        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            return null;
        }

    list($header_encoded, $payload_encoded, $signature_provided) = $tokenParts;

    // Verify signature
    $signature = hash_hmac('SHA256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = base64url_encode($signature);

        if (!hash_equals($signature_encoded, $signature_provided)) {
            return null;
        }

    $payload = json_decode(base64url_decode($payload_encoded));
    
        if (!$payload) {
            return null;
        }

        // Verify required claims
        if (!isset($payload->exp) || !isset($payload->iat) || !isset($payload->iss)) {
            return null;
        }

        // Check expiration
        if (time() >= $payload->exp) {
            return null;
        }

        // Check issuer
        if ($payload->iss !== 'APP_NAME') {
            return null;
        }

        return $payload;
}

function refresh_token($jwt) {
    $payload = decode_jwt($jwt);
    if (!$payload) {
        return null;
    }
    
    // Create new payload with updated expiration
    $new_payload = [
        'user_id' => $payload->user_id,
        'username' => $payload->username,
        'email' => $payload->email,
        'role' => $payload->role ?? 'user'
    ];
    
    return generate_jwt($new_payload);
}

// ====================================
// Authentication Middleware Functions
// ====================================

function handle_cors() {
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: http://localhost:8100");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        http_response_code(200);
        exit();
    }

    // Set CORS headers for all other requests
    header("Access-Control-Allow-Origin: http://localhost:8100");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json");
}

function get_token_from_headers() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }
    return str_replace('Bearer ', '', $headers['Authorization']);
}

function verifyToken() {
    handle_cors();

    $token = get_token_from_headers();
    if (!$token) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "No authorization token provided"]);
        exit();
    }
    
    $decoded = decode_jwt($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid or expired token"]);
        exit();
    }

    return $decoded;
}

function requireRole($requiredRole) {
    $user = verifyToken();
    if (!isset($user->role) || $user->role !== $requiredRole) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Insufficient permissions"]);
        exit();
    }
    return $user;
}

function getUserFromToken() {
    try {
        return verifyToken();
    } catch (Exception $e) {
        return null;
    }
}

function isAuthenticated() {
    return getUserFromToken() !== null;
}

function hasRole($role) {
    $user = getUserFromToken();
    return $user && isset($user->role) && $user->role === $role;
}

// ====================================
// Helper Functions
// ====================================

function get_token_expiration_time($jwt) {
    $payload = decode_jwt($jwt);
    return $payload ? $payload->exp : null;
}

function is_jwt_valid($jwt, $secret = JWT_SECRET_KEY) {
    return decode_jwt($jwt, $secret) !== null;
}

function getTokenData($token) {
    $decoded = decode_jwt($token);
    if (!$decoded) {
        throw new Exception('Invalid or expired token');
    }
    
    return [
        'id' => $decoded->user_id,
        'username' => $decoded->username,
        'email' => $decoded->email,
        'role' => $decoded->role ?? 'user'
    ];
}

// Example usage (commented out):
/*
// Generate a token
$payload = [
    'user_id' => 123,
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'role' => 'user'
];
$token = generate_jwt($payload);

// Use in protected routes
$user = verifyToken(); // For basic authentication
$admin = requireRole('admin'); // For admin-only routes

// Check roles
if (hasRole('admin')) {
    // Do admin stuff
}
*/
?>