<?php
// api/messaging/handler.php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_initial_data':
            // --- 1. Get or Generate Keys for the current user ---
            $user_keys_raw = db_select("SELECT public_key, private_key FROM users WHERE id = ?", 's', [$user_id]);
            $user_keys = !empty($user_keys_raw) ? $user_keys_raw[0] : ['public_key' => null, 'private_key' => null];
            if (empty($user_keys['public_key']) || empty($user_keys['private_key'])) {
            // Generate new keys if they don't exist
            $config = array(
                "digest_alg" => "sha512",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            );
            $res = openssl_pkey_new($config);
                if ($res === false) {
                    throw new Exception("Failed to generate new private key. OpenSSL might be configured incorrectly.");
                }
            openssl_pkey_export($res, $private_key);
            $public_key_details = openssl_pkey_get_details($res);
            $public_key = $public_key_details["key"];

            // Save UNENCRYPTED keys to the database
            db_execute("UPDATE users SET public_key = ?, private_key = ? WHERE id = ?", 'sss', [$public_key, $private_key, $user_id]);
            
            $user_keys['public_key'] = $public_key;
            $user_keys['private_key'] = $private_key;
            }


            // --- 2. Get Contacts ---
            // Students see instructors of their enrolled courses.
            // Instructors see students who have enrolled in their courses.
            if ($user_role === 'student') {
            $sql = "SELECT DISTINCT u.id, u.name, u.avatar, u.public_key
                    FROM users u
                    JOIN courses c ON u.id = c.instructor_id
                    JOIN enrollments e ON c.id = e.course_id
                    WHERE e.student_id = ?";
            $contacts = db_select($sql, 's', [$user_id]);
            } elseif ($user_role === 'instructor') {
            $sql = "SELECT DISTINCT u.id, u.name, u.avatar, u.public_key
                    FROM users u
                    JOIN enrollments e ON u.id = e.student_id
                    JOIN courses c ON e.course_id = c.id
                    WHERE c.instructor_id = ?";
            $contacts = db_select($sql, 's', [$user_id]);
            } else {
            $contacts = [];
            }
            echo json_encode([
                'success' => true, 
                'contacts' => $contacts,
                'keys' => $user_keys
            ]);
            break;

        case 'get_messages':
            $contact_id = intval($_GET['contact_id'] ?? 0);
            if ($contact_id === 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid contact ID']);
            exit;
        }

        // Find or create conversation
        $user1 = min($user_id, $contact_id);
        $user2 = max($user_id, $contact_id);
        
        $convo = db_select("SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?", 'ss', [$user1, $user2]);
        if (empty($convo)) {
            $convo_id = db_execute("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)", 'ss', [$user1, $user2]);
        } else {
            $convo_id = $convo[0]['id'];
        }

        // Fetch messages
        $messages = db_select(
            "SELECT id, sender_id, message_content_for_receiver, message_content_for_sender, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC",
            's',
            [$convo_id]
        );

        // Mark messages as read
        db_execute("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?", 'ss', [$convo_id, $user_id]);

        echo json_encode(['success' => true, 'messages' => $messages, 'conversation_id' => $convo_id]);
        break;

        case 'send_message':
            $conversation_id = intval($_POST['conversation_id'] ?? 0);
            $receiver_id = intval($_POST['receiver_id'] ?? 0);
            $message_for_receiver = $_POST['message_for_receiver'] ?? '';
            $message_for_sender = $_POST['message_for_sender'] ?? '';

            if ($conversation_id === 0 || $receiver_id === 0 || empty($message_for_receiver) || empty($message_for_sender)) {
            echo json_encode(['success' => false, 'error' => 'Missing required encrypted message fields.']);
            exit;
        }

        // Verify user is part of the conversation
        $convo_check = db_select("SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)", 'sss', [$conversation_id, $user_id, $user_id]);
        if (empty($convo_check)) {
            echo json_encode(['success' => false, 'error' => 'Not part of this conversation.']);
            exit;
        }

        $message_id = db_execute(
            "INSERT INTO messages (conversation_id, sender_id, receiver_id, message_content_for_receiver, message_content_for_sender) VALUES (?, ?, ?, ?, ?)",
            'sssss',
            [$conversation_id, $user_id, $receiver_id, $message_for_receiver, $message_for_sender]
        );

        if ($message_id) {
            echo json_encode(['success' => true, 'message_id' => $message_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message.']);
        }
        break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    // Log the actual error to the server's error log for debugging
    error_log("Messaging API Error: " . $e->getMessage());
    // Send a generic, user-friendly error response
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'A server error occurred. Please contact support.']);
}
?>