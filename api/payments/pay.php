<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';

// --- Authorization & Course ID Check ---
// If a referral link is used by a logged-out user, save the destination and redirect to login.
if (isset($_GET['ref']) && !isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: /lms/account');
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /lms/account');
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

// --- Handle Referral Code & Clear Stale Session Data ---
$referral_discount = 0;
$referral_message = '';
$error_message = '';
$final_price = $course['price'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Clear session data if no relevant params are in the URL
    unset($_SESSION['applied_coupon']);
    if (isset($_GET['ref'])) {
        $ref_code = trim($_GET['ref']);
        
        // 1. Validate the referral code
        $referral_sql = "SELECT r.id as referral_id, r.referrer_id, r.course_id, rs.reward_type, rs.reward_value, c.price
                           FROM referrals r
                           JOIN referral_settings rs ON r.course_id = rs.course_id
                           JOIN courses c ON r.course_id = c.id
                           WHERE r.referral_code = ? AND r.expires_at > NOW() AND rs.is_enabled = 1";
        $referral_data = db_select($referral_sql, 's', [$ref_code]);

        if (!empty($referral_data)) {
            $referral = $referral_data[0];
            
            if ($referral['course_id'] == $course_id && $referral['referrer_id'] != $user_id) {
                $is_enrolled = db_select("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", 'ii', [$user_id, $course_id]);
                if (empty($is_enrolled)) {
                    // All checks passed, calculate discount
                    if ($referral['reward_type'] === 'fixed') {
                        $referral_discount = $referral['reward_value'];
                    } else { // percentage
                        $referral_discount = ($referral['reward_value'] / 100) * $referral['price'];
                    }
                    $referral_discount = round($referral_discount, 2);
                    $final_price = max(0, $course['price'] - $referral_discount);

                    // Store in session to apply on POST
                    $_SESSION['applied_referral'] = [
                        'referral_id' => $referral['referral_id'],
                        'referrer_id' => $referral['referrer_id'],
                        'code' => $ref_code,
                        'discount_amount' => $referral_discount,
                        'reward_amount' => $referral_discount // Reward is same as discount
                    ];
                    $referral_message = "Referral discount of $" . number_format($referral_discount, 2) . " applied!";

                    // A referral code takes precedence, so clear any potentially stale coupon data.
                    unset($_SESSION['applied_coupon']);
                }
            }
        } else {
            // Check why the referral failed to give a helpful message
            $check_code_exists = db_select("SELECT course_id FROM referrals WHERE referral_code = ?", 's', [$ref_code]);
            if (empty($check_code_exists)) {
                $error_message = "The referral code you used is invalid.";
            } else {
                // The code exists, so the program is likely disabled or expired.
                $error_message = "This referral is for a program that is not currently active.";
            }
        }
    } else {
        unset($_SESSION['applied_referral']);
    }
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    $original_price = $course['price'];
    $final_price = $original_price;
    $applied_coupon_id = null;
    $discount = 0;

    // Check for referral first, as it comes from the URL and has priority.
    if (isset($_SESSION['applied_referral']) && isset($_SESSION['applied_referral']['code'])) {
        $discount = (float) ($_SESSION['applied_referral']['discount_amount'] ?? 0);
        $final_price = max(0, $original_price - $discount);
    }
    // Re-validate coupon from session on server-side before processing
    elseif (isset($_SESSION['applied_coupon']) && $_SESSION['applied_coupon']['course_id'] == $course_id) {
        $coupon_session = $_SESSION['applied_coupon'];
        $coupon_data = db_select("SELECT * FROM coupons WHERE id = ? AND status = 'active'", 'i', [$coupon_session['id']]);
        
        if ($coupon_data) {
            $coupon_db = $coupon_data[0];
            $is_valid = true;
            if ($coupon_db['expires_at'] && strtotime($coupon_db['expires_at']) < time()) $is_valid = false;
            if ($coupon_db['usage_limit'] !== null && $coupon_db['times_used'] >= $coupon_db['usage_limit']) $is_valid = false;

            if ($is_valid) {
                if ($coupon_db['type'] === 'fixed') {
                    $discount = $coupon_db['value'];
                } else { // percentage
                    $discount = ($coupon_db['value'] / 100) * $original_price;
                }
                $final_price = max(0, $original_price - $discount);
                $applied_coupon_id = $coupon_db['id'];
            }
        }
    }

    $payment_method = $_POST['payment_method'] ?? '';

    if ($payment_method === 'ssl') {
        // Redirect to the SSLCommerz page (as requested, this file is not created here)
        header("Location: ssl.php?course_id=" . $course_id);
        exit;
    } elseif ($payment_method === 'offline') {
        $is_enrolled = db_select("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", 'ii', [$user_id, $course_id]);

        if (empty($is_enrolled)) {
            $conn->begin_transaction();
            try {
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
                $enrollment_id = db_execute("INSERT INTO enrollments (student_id, course_id, expires_at) VALUES (?, ?, ?)", 'iis', [$user_id, $course_id, $expires_at]);
                
                if ($applied_coupon_id) {
                    db_execute("UPDATE coupons SET times_used = times_used + 1 WHERE id = ?", 'i', [$applied_coupon_id]);
                }

                // --- Handle Referral Usage ---
                if (isset($_SESSION['applied_referral'])) {
                    $ref_info = $_SESSION['applied_referral'];
                    $referrer_id = $ref_info['referrer_id'];
                    $referral_id = $ref_info['referral_id'];
                    $reward_amount = $ref_info['reward_amount'];

                    // 1. Credit the referrer's wallet
                    $referrer_wallet = db_select("SELECT id FROM student_wallets WHERE student_id = ?", 'i', [$referrer_id]);
                    if (empty($referrer_wallet)) {
                        db_execute("INSERT INTO student_wallets (student_id, balance) VALUES (?, 0.00)", 'i', [$referrer_id]);
                        $referrer_wallet = db_select("SELECT id FROM student_wallets WHERE student_id = ?", 'i', [$referrer_id]);
                    }
                    $referrer_wallet_id = $referrer_wallet[0]['id'];

                    db_execute("UPDATE student_wallets SET balance = balance + ? WHERE id = ?", 'di', [$reward_amount, $referrer_wallet_id]);

                    // 2. Log the transaction for the referrer
                    $invitee_info = db_select("SELECT name FROM users WHERE id = ?", 'i', [$user_id])[0];
                    $transaction_desc = "Referral reward for inviting " . $invitee_info['name'];
                    db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description, related_id) VALUES (?, ?, 'referral_credit', ?, ?)", 'idsi', [$referrer_wallet_id, $reward_amount, $transaction_desc, $enrollment_id]);

                    // 3. Log the referral usage
                    db_execute("INSERT INTO referral_usages (referral_id, invitee_id, enrollment_id, reward_earned) VALUES (?, ?, ?, ?)", 'iiid', [$referral_id, $user_id, $enrollment_id, $reward_amount]);
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                die("An error occurred during enrollment. Please try again.");
            }
        }
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['applied_referral']);
        $_SESSION['show_enroll_popup'] = true;
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
            <?php if ($error_message): ?>
                <div class="md:col-span-2 p-4 m-8 mb-0 rounded-lg bg-red-900/50 border border-red-700 text-red-300 text-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            
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
                        <span>$<?= htmlspecialchars(number_format($course['price'], 2)) ?></span>
                    </div>
                    <div id="referral-discount-row" class="hidden justify-between text-green-400">
                        <span>Referral Discount</span>
                        <span id="referral-discount-amount">- $0.00</span>
                    </div>
                    <div id="discount-row" class="hidden justify-between text-green-400">
                        <span>Discount</span>
                        <span id="discount-amount">- $0.00</span>
                    </div>
                    <div class="flex justify-between text-white font-bold text-lg">
                        <span>Total</span>
                        <span id="total-price">$<?= htmlspecialchars(number_format($final_price, 2)) ?></span>
                    </div>
                </div>
                <div class="border-t border-gray-700 pt-4 mt-4" id="coupon-form-container">
                    <label for="coupon-code" class="text-sm font-medium text-gray-300">Have a coupon?</label>
                    <div class="flex space-x-2 mt-2">
                        <input type="text" id="coupon-code" name="coupon_code_field" class="block w-full bg-gray-900/50 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter coupon code" style="text-transform:uppercase">
                        <button type="button" id="apply-coupon-btn" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-md hover:bg-purple-700 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500">Apply</button>
                    </div>
                    <p id="coupon-message" class="text-sm mt-2"></p>
                </div>

            </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // This script handles coupon application and success popups
            const applyBtn = document.getElementById('apply-coupon-btn');
            const couponCodeInput = document.getElementById('coupon-code');
            const couponMessage = document.getElementById('coupon-message');
            const originalPrice = <?= $course['price'] ?>;
            const referralMessage = "<?= addslashes($referral_message ?? '') ?>";
            const referralDiscount = <?= $referral_discount ?? 0 ?>;

            // Handle applied referral on page load
            if (referralDiscount > 0 && referralMessage) {
                const couponContainer = document.getElementById('coupon-form-container');
                const referralInfoDiv = document.createElement('div');
                referralInfoDiv.className = 'p-3 rounded-md bg-green-900/50 border border-green-700 text-green-300 text-sm';
                referralInfoDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${referralMessage}`;
                
                // Replace coupon form with referral message
                couponContainer.innerHTML = ''; 
                couponContainer.appendChild(referralInfoDiv);

                document.getElementById('referral-discount-row').classList.remove('hidden');
                document.getElementById('referral-discount-row').classList.add('flex');
                document.getElementById('referral-discount-amount').textContent = `- $${referralDiscount.toFixed(2)}`;
                const newTotal = Math.max(0, originalPrice - referralDiscount);
                document.getElementById('total-price').textContent = `$${newTotal.toFixed(2)}`;
            }

            // Only add the event listener if the button exists (i.e., no referral was applied)
            if(applyBtn) {
                applyBtn.addEventListener('click', function() {
                    const code = couponCodeInput.value.trim();
                    if (!code) {
                        couponMessage.textContent = 'Please enter a coupon code.';
                        couponMessage.className = 'text-sm mt-2 text-red-400';
                        return;
                    }

                    applyBtn.disabled = true;
                    applyBtn.textContent = 'Applying...';

                    fetch('./pages/apply_coupon.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            coupon_code: code,
                            course_id: <?= $course_id ?>
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            couponMessage.textContent = data.message;
                            couponMessage.className = 'text-sm mt-2 text-green-400';
                            
                            document.getElementById('discount-row').classList.remove('hidden');
                            document.getElementById('discount-row').classList.add('flex');
                            document.getElementById('discount-amount').textContent = `- $${data.discount_amount.toFixed(2)}`;
                            document.getElementById('total-price').textContent = `$${data.new_price.toFixed(2)}`;

                            couponCodeInput.disabled = true;
                            applyBtn.textContent = 'Applied';
                        } else {
                            couponMessage.textContent = data.message;
                            couponMessage.className = 'text-sm mt-2 text-red-400';
                            applyBtn.disabled = false;
                            applyBtn.textContent = 'Apply';
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        couponMessage.textContent = 'An error occurred. Please try again.';
                        couponMessage.className = 'text-sm mt-2 text-red-400';
                        applyBtn.disabled = false;
                        applyBtn.textContent = 'Apply';
                    });
                });
            }

            <?php if ($show_popup): ?>
                alert('you successfully enroll this course');
                window.location.href = 'dashboard';
            <?php endif; ?>
        });
        </script>
</body>
</html>