<?php
// --- START SESSION ---
session_start();

// --- DATABASE CONNECTION ---
// Fixed relative path
require_once __DIR__ . '../../includes/db.php';

// --- HEADERS FOR JSON RESPONSE ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// --- GET RAW POST DATA ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['error' => 'No token provided.']);
    exit;
}

// --- VERIFY GOOGLE/FIREBASE ID TOKEN ---
// Recommended: Use Google_Client to verify token properly
// Composer required: composer require google/apiclient
// require_once __DIR__ . '/../../vendor/autoload.php';
// $client = new Google_Client(['client_id' => 'YOUR_FIREBASE_CLIENT_ID']);
// $payload = $client->verifyIdToken($token);
// if (!$payload) {
//     echo json_encode(['error' => 'Invalid token.']);
//     exit;
// }

// --- TEMPORARY DEMO DECODING (INSECURE FOR PRODUCTION) ---
$token_parts = explode('.', $token);
if (count($token_parts) !== 3) {
    echo json_encode(['error' => 'Invalid JWT format.']);
    exit;
}

$payload = base64_decode(strtr($token_parts[1], '-_', '+/'));
$payload_data = json_decode($payload, true);

if (!$payload_data) {
    echo json_encode(['error' => 'Could not decode JWT payload.']);
    exit;
}

$email = $payload_data['email'] ?? null;
$name = $payload_data['name'] ?? null;
$photoURL = $payload_data['picture'] ?? null;

if (!$email || !$name) {
    echo json_encode(['error' => 'Invalid user data.']);
    exit;
}

// --- CHECK IF USER EXISTS ---
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// --- DEFAULT REDIRECT ---
$redirect_page = 'dashboard';

if ($user) {
    // User exists, log them in
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['is_logged_in'] = true;

    $redirect_page = 'dashboard';
} else {
    // New user, register them
    $role = 'student'; // Default role for new Google sign-ups
    $stmt_insert = $conn->prepare("INSERT INTO users (name, email, role, avatar) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("ssss", $name, $email, $role, $photoURL);

    if ($stmt_insert->execute()) {
        $_SESSION['user_id'] = $stmt_insert->insert_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['is_logged_in'] = true;

        $redirect_page = $role . '_dashboard';
    } else {
        echo json_encode(['error' => 'Database error during registration.']);
        exit;
    }
    $stmt_insert->close();
}

// --- RETURN JSON RESPONSE ---
echo json_encode(['redirect' => 'dashboard']);
exit;
