<?php
require_once __DIR__ . '/../../includes/db.php';

// --- Authorization & Course ID Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: account');
    exit;
}
$user_id = $_SESSION['user_id'];

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
if ($course_id <= 0) {
    die("Invalid course specified.");
}

// --- Fetch Course Details ---
$course_data = db_select("SELECT title, thumbnail, price FROM courses WHERE id = ?", 'i', [$course_id]);
if (empty($course_data)) {
    die("Course not found.");
}
$course = $course_data[0];

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';

    if ($payment_method === 'ssl') {
        // Redirect to the SSLCommerz page (as requested, this file is not created here)
        header("Location: ssl.php?course_id=" . $course_id);
        exit;
    } elseif ($payment_method === 'offline') {
        // Check if the user is already enrolled to prevent duplicates
        $is_enrolled = db_select("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", 'ii', [$user_id, $course_id]);

        if (empty($is_enrolled)) {
            // Set an expiration date (e.g., 1 year from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
            // Enroll the user in the database. The 'status' column will use its default 'active' value.
            db_execute("INSERT INTO enrollments (student_id, course_id, progress, expires_at) VALUES (?, ?, ?, ?)", 'iids', [$user_id, $course_id, 0.00, $expires_at]);
        }

        // Set a session flag to trigger the success popup on the next page load
        $_SESSION['show_enroll_popup'] = true;
        
        // Redirect back to this page with a success status to trigger the JS
        header("Location: ?_page=pay&course_id=" . $course_id . "&status=success");
        exit;
    }
}

// --- Check if we need to show the success popup ---
$show_popup = false;
if (isset($_GET['status']) && $_GET['status'] === 'success' && isset($_SESSION['show_enroll_popup'])) {
    $show_popup = true;
    unset($_SESSION['show_enroll_popup']);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0c0c0e;
            --bg-secondary: #111115;
            --glass-bg: rgba(17, 17, 21, 0.7);
            --glass-border: rgba(255, 255, 255, 0.15);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --primary: #6366f1;
            --secondary: #a855f7;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .dot-grid-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background-image: radial-gradient(var(--glass-border) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        .payment-option {
            transition: all 0.3s ease;
            border: 2px solid var(--glass-border);
        }
        .payment-option:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
        }
        input[type="radio"]:checked + .payment-option {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px var(--secondary);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(168, 85, 247, 0.3);
        }
    </style>
</head>
<body>
    <div class="dot-grid-bg"></div>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl mx-auto grid md:grid-cols-2 gap-8 glass rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Left Side: Course Info -->
            <div class="p-8 space-y-6 bg-black/20">
                <h2 class="text-2xl font-bold text-gray-200">Order Summary</h2>
                <div class="space-y-4">
                    <img src="<?= htmlspecialchars($course['thumbnail']) ?>" 
                         alt="<?= htmlspecialchars($course['title']) ?>" 
                         class="w-full h-48 object-cover rounded-lg shadow-lg"
                         onerror="this.onerror=null;this.src='https://placehold.co/400x250/0c0c0e/a1a1aa?text=Course';">
                    
                    <h3 class="text-xl font-semibold text-white"><?= htmlspecialchars($course['title']) ?></h3>
                </div>
                <div class="border-t border-gray-700 pt-4 space-y-2">
                    <div class="flex justify-between text-gray-300">
                        <span>Subtotal</span>
                        <span>৳<?= htmlspecialchars(number_format($course['price'], 2)) ?></span>
                    </div>
                    <div class="flex justify-between text-white font-bold text-lg">
                        <span>Total</span>
                        <span>৳<?= htmlspecialchars(number_format($course['price'], 2)) ?></span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Payment Method -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-200 mb-6">Select Payment Method</h2>
                <form method="POST" action="?_page=pay&course_id=<?= $course_id ?>">
                    <div class="space-y-4">
                        
                        <label for="offline_payment" class="cursor-pointer">
                            <input type="radio" id="offline_payment" name="payment_method" value="offline" class="hidden" checked>
                            <div class="payment-option p-4 rounded-lg flex items-center space-x-4">
                                <i class="fas fa-money-bill-wave text-2xl text-green-400"></i>
                                <div>
                                    <h4 class="font-semibold text-white">Offline Payment</h4>
                                    <p class="text-sm text-gray-400">Pay via bKash/Nagad and get instant access.</p>
                                </div>
                            </div>
                        </label>

                        <label for="ssl_payment" class="cursor-pointer">
                            <input type="radio" id="ssl_payment" name="payment_method" value="ssl" class="hidden">
                            <div class="payment-option p-4 rounded-lg flex items-center space-x-4">
                                <i class="fas fa-credit-card text-2xl text-blue-400"></i>
                                <div>
                                    <h4 class="font-semibold text-white">SSLCommerz</h4>
                                    <p class="text-sm text-gray-400">Pay with Card, MFS, or Net Banking.</p>
                                </div>
                            </div>
                        </label>

                    </div>

                    <div class="mt-8">
                        <button type="submit" class="btn-primary w-full py-3 rounded-lg font-semibold text-white">
                            Pay Now
                        </button>
                    </div>
                </form>
                 <div class="text-center mt-4">
                    <a href="dashboard" class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Back to Dashboard</a>
                </div>
            </div>

        </div>
    </div>

    <?php if ($show_popup): ?>
    <script>
        // This script runs only after a successful offline enrollment
        document.addEventListener('DOMContentLoaded', function() {
            alert('you successfully enroll this course');
            window.location.href = 'dashboard';
        });
    </script>
    <?php endif; ?>

</body>
</html>