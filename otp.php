<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Preflight request for CORS
}

// Define the correct constant!
define('a328763fe27bba', true);


require_once 'app_init.php'; // must include DB connection and config

$data = $_GET['data'] ?? '';

function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

function generateToken($length = 64) {
    return bin2hex(random_bytes($length/2));
}

// --- REQUEST OTP ---
// checks honeypot, validate request limit, generate OTP + expiry, sends brevo API, updates DB
if ($data === 'request_otp') {
    $username = trim($_POST['username'] ?? '');
    $honeypot = $_POST['honeypot'] ?? '';

    if (!empty($honeypot)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bot detected']);
        exit;
    }

    if (!$username) {
        http_response_code(400);
        echo json_encode(['error' => 'Username is required']);
        exit;
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Username not found']);
        exit;
    }

    $now = new DateTime();
    $lastRequest = $user['last_otp_request'] ? new DateTime($user['last_otp_request']) : null;

    // 30 second cooldown
    if ($lastRequest && $now->getTimestamp() - $lastRequest->getTimestamp() < 30) {
        echo json_encode(['error' => 'You can request a new code in a few seconds']);
        exit;
    }

    // Max 4/hour, 10/day (spec says 4/hour and 10/day, adjust if needed)
    if ($user['otp_requests_hour'] >= 4) {
        echo json_encode(['error' => 'Hourly limit reached']);
        exit;
    }
    if ($user['otp_requests_day'] >= 10) {
        echo json_encode(['error' => 'Daily limit reached']);
        exit;
    }

    // Generate OTP
    $otp = generateOTP(6);
    $expiry = (clone $now)->modify('+10 minutes');

    // Update DB
    $stmt = $pdo->prepare("UPDATE users SET otp_code=?, otp_expiry=?, otp_requests_hour=otp_requests_hour+1, otp_requests_day=otp_requests_day+1, last_otp_request=? WHERE id=?");
    $stmt->execute([$otp, $expiry->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'), $user['id']]);

    // Send email via Brevo API
    $brevoKey = 'YOUR_BREVO_API_KEY';
    $email = 'you@example.com'; // fetch from DB if email exists

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "api-key: $brevoKey",
        "content-type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "sender" => ["name" => "AssafMedia", "email" => "no-reply@assafmedia.com"],
        "to" => [["email" => $email]],
        "subject" => "Your OTP Code",
        "htmlContent" => "<p>Your OTP is: <strong>$otp</strong></p>"
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    echo json_encode(['status' => 'otp_sent']);
    exit;
}

// --- VERIFY OTP ---
// check useraname + OTP, validates OTP + expiry, generate token, saves token to table, clear OTP
if ($data === 'verify_otp') {
    $username = trim($_POST['username'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if (!$username || !$otp) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and OTP are required']);
        exit;
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['otp_code'] || !$user['otp_expiry']) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $now = new DateTime();
    $expiry = new DateTime($user['otp_expiry']);

    if ($otp !== $user['otp_code'] || $now > $expiry) {
        echo json_encode(['error' => 'Invalid or expired OTP']);
        exit;
    }

    // Generate token
    $token = generateToken();
    $tokenExpiry = (clone $now)->modify('+24 hours');

    $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expiry) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $tokenExpiry->format('Y-m-d H:i:s')]);

    // Clear OTP (optional)
    $pdo->prepare("UPDATE users SET otp_code=NULL, otp_expiry=NULL WHERE id=?")->execute([$user['id']]);

    echo json_encode(['status' => 'success', 'token' => $token]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);

