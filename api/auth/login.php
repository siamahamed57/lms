<?php


// --- DATABASE CONNECTION ---
require_once __DIR__ . '/../../includes/db.php';



// Initialize messages
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- REGISTER ---
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'student'; // Default to student

        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required for registration.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
            $message_type = 'error';
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $message = 'An account with this email already exists.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into users
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $stmt->close();

                    // Insert into model_has_roles
                    $role_id = ($role === 'admin') ? 1 : 2;
                    $stmt2 = $conn->prepare("INSERT INTO model_has_roles (role_id, model_id) VALUES (?, ?)");
                    $stmt2->bind_param("ii", $role_id, $user_id);
                    $stmt2->execute();
                    $stmt2->close();

                    $message = 'Registration successful! You can now log in.';
                    $message_type = 'success';
                } else {
                    $message = 'Registration failed. Please try again.';
                    $message_type = 'error';
                }
            }
        }

    // --- LOGIN ---
    } elseif (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $message = 'Both email and password are required.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                // Get role
                $stmt = $conn->prepare("SELECT role_id FROM model_has_roles WHERE model_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $role_row = $result->fetch_assoc();
                $stmt->close();

                $_SESSION['user_role'] = $role_row ? $role_row['role_id'] : 2;

                // Redirect based on role
                if ($_SESSION['user_role'] == 1) {
                    header('Location:home');
                } else {
                    header('Location:home');
                }
                exit;
            } else {
                $message = 'Invalid email or password.';
                $message_type = 'error';
            }
        }
    }
}
?>
