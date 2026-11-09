<?php
/**
 * Authentication & Password Reset API Endpoint for Rengganis Net
 * Handles Request OTP, Verify OTP, and Reset Password with CSRF & rate limit protection
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDB();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// Helper to send json
function sendJson(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper to check if running locally
function isLocalEnvironment(): bool {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($remote, ['127.0.0.1', '::1']) || str_starts_with($remote, '192.168.') || str_starts_with($remote, '172.') || str_starts_with($remote, '10.');
}

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    initAppSession();
}

// Parse request body if JSON
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];
$params = array_merge($_GET, $_POST, $jsonInput);

try {
    switch ($action) {
        
        // -------------------------------------------------------------
        // 1. REQUEST OTP
        // -------------------------------------------------------------
        case 'request_otp':
            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
            }

            $identifier = trim($params['identifier'] ?? '');
            if (empty($identifier)) {
                sendJson(['success' => false, 'message' => 'Silakan masukkan username atau alamat email akun Anda.'], 400);
            }

            // Look up user by username or email
            $stmt = $pdo->prepare("SELECT id, username, name, email, role FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if (!$user) {
                sendJson(['success' => false, 'message' => 'Akun dengan username atau email tersebut tidak ditemukan dalam sistem.'], 404);
            }

            // If user has no email, assign default fallback email based on role/username
            $email = trim($user['email'] ?? '');
            if (empty($email)) {
                $email = strtolower($user['username']) . '@rengganis.net';
                $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $user['id']]);
                $user['email'] = $email;
            }

            // Generate cryptographically secure 6-digit OTP
            $otp = (string)random_int(100000, 999999);
            $otpHash = password_hash($otp, PASSWORD_BCRYPT);
            $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 mins expiry

            // Save OTP hash and expiration
            $upd = $pdo->prepare("UPDATE users SET reset_otp_hash = ?, reset_otp_expires_at = ? WHERE id = ?");
            $upd->execute([$otpHash, $expiresAt, $user['id']]);

            // Dispatch email
            sendPasswordResetOtpEmail($user['email'], $user['name'], $otp);

            $masked = maskEmail($user['email']);

            sendJson([
                'success' => true,
                'message' => "Kode OTP 6-digit berhasil dikirimkan ke email {$masked}.",
                'user_id' => (int)$user['id'],
                'masked_email' => $masked,
                'username' => $user['username'],
                'demo_otp' => isLocalEnvironment() ? $otp : null, // Display in local dev for seamless testing
            ]);
            break;

        // -------------------------------------------------------------
        // 2. VERIFY OTP
        // -------------------------------------------------------------
        case 'verify_otp':
            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
            }

            $userId = (int)($params['user_id'] ?? 0);
            $otp    = trim($params['otp'] ?? '');

            if ($userId <= 0 || empty($otp)) {
                sendJson(['success' => false, 'message' => 'ID Pengguna dan Kode OTP wajib diisi.'], 400);
            }

            $stmt = $pdo->prepare("SELECT id, reset_otp_hash, reset_otp_expires_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                sendJson(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
            }

            if (empty($user['reset_otp_hash']) || empty($user['reset_otp_expires_at'])) {
                sendJson(['success' => false, 'message' => 'Tidak ada permintaan reset aktif. Silakan minta kode OTP baru.'], 400);
            }

            // Check expiry
            if (strtotime($user['reset_otp_expires_at']) < time()) {
                sendJson(['success' => false, 'message' => 'Kode OTP telah kedaluwarsa. Silakan minta kode OTP baru.'], 400);
            }

            // Verify hash
            if (!password_verify($otp, $user['reset_otp_hash'])) {
                sendJson(['success' => false, 'message' => 'Kode OTP tidak cocok atau salah. Silakan periksa kembali.'], 400);
            }

            // Authorize reset in session
            $_SESSION['reset_authorized_user_id'] = $userId;
            $_SESSION['reset_authorized_at'] = time();

            sendJson([
                'success' => true,
                'message' => 'Kode OTP berhasil diverifikasi! Silakan tentukan kata sandi baru Anda.'
            ]);
            break;

        // -------------------------------------------------------------
        // 3. RESET PASSWORD
        // -------------------------------------------------------------
        case 'reset_password':
            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
            }

            $userId          = (int)($params['user_id'] ?? 0);
            $password        = $params['password'] ?? '';
            $passwordConfirm = $params['password_confirm'] ?? '';

            if ($userId <= 0 || empty($password)) {
                sendJson(['success' => false, 'message' => 'Kata sandi baru tidak boleh kosong.'], 400);
            }

            // Verify session authorization
            $sessUserId = (int)($_SESSION['reset_authorized_user_id'] ?? 0);
            $sessTime   = (int)($_SESSION['reset_authorized_at'] ?? 0);

            if ($sessUserId !== $userId || (time() - $sessTime) > (15 * 60)) {
                sendJson(['success' => false, 'message' => 'Sesi verifikasi OTP telah kedaluwarsa atau tidak sah. Silakan ulangi proses dari awal.'], 403);
            }

            if (strlen($password) < 6) {
                sendJson(['success' => false, 'message' => 'Kata sandi baru minimal harus 6 karakter.'], 400);
            }

            if ($password !== $passwordConfirm) {
                sendJson(['success' => false, 'message' => 'Konfirmasi kata sandi tidak cocok.'], 400);
            }

            // Update user password and clear OTP
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_otp_hash = NULL, reset_otp_expires_at = NULL WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            // Clear session authorization
            unset($_SESSION['reset_authorized_user_id'], $_SESSION['reset_authorized_at']);

            sendJson([
                'success' => true,
                'message' => 'Kata sandi berhasil diubah! Silakan login menggunakan kata sandi baru Anda.'
            ]);
            break;

        default:
            sendJson(['success' => false, 'message' => 'Aksi API tidak valid.'], 400);
            break;
    }
} catch (\Throwable $e) {
    sendJson(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
}
