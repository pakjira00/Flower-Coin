<?php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function walletOut(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readWalletInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

if (empty($_SESSION['user_id'])) {
    walletOut([
        'ok' => false,
        'message' => 'กรุณาเข้าสู่ระบบก่อนเชื่อม Wallet'
    ], 401);
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    walletOut(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$data = readWalletInput();

if ($action === 'nonce') {
    $address = $data['address'] ?? '';
    $chainId = filter_var(
        $data['chainId'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($address) ||
        !preg_match('/^0x[a-fA-F0-9]{40}$/', $address) ||
        $chainId === false ||
        $chainId === null ||
        $chainId < 1
    ) {
        walletOut([
            'ok' => false,
            'message' => 'Wallet Address หรือ Chain ID ไม่ถูกต้อง'
        ], 400);
    }

    // สร้างข้อความใหม่ทุกครั้ง เพื่อป้องกันการนำลายเซ็นเดิมมาใช้ซ้ำ
    $nonce = bin2hex(random_bytes(32));
    $issuedAt = gmdate('Y-m-d\TH:i:s\Z');

    $message =
        "Flower Coin Wallet Verification\n" .
        "User ID: {$userId}\n" .
        "Wallet: " . strtolower($address) . "\n" .
        "Chain ID: {$chainId}\n" .
        "Nonce: {$nonce}\n" .
        "Issued At: {$issuedAt}";

    $_SESSION['wallet_challenge'] = [
        'user_id' => $userId,
        'address' => strtolower($address),
        'chain_id' => (string) $chainId,
        'message' => $message,
        'created_at' => time()
    ];

    walletOut([
        'ok' => true,
        'message' => $message
    ]);
}

if ($action === 'save') {
    $signature = $data['signature'] ?? '';
    $challenge = $_SESSION['wallet_challenge'] ?? null;

    // ใช้ challenge ได้ครั้งเดียว แม้การตรวจสอบจะไม่สำเร็จ
    unset($_SESSION['wallet_challenge']);

    if (
        !is_array($challenge) ||
        (int) ($challenge['user_id'] ?? 0) !== $userId ||
        time() - (int) ($challenge['created_at'] ?? 0) > 300
    ) {
        walletOut([
            'ok' => false,
            'message' => 'ข้อความยืนยันหมดอายุ กรุณาลองใหม่'
        ], 400);
    }

    if (
        !is_string($signature) ||
        !preg_match('/^0x[a-fA-F0-9]{130}$/', $signature)
    ) {
        walletOut([
            'ok' => false,
            'message' => 'ลายเซ็นไม่ถูกต้อง'
        ], 400);
    }

    if (!is_file(__DIR__ . '/vendor/autoload.php')) {
        walletOut([
            'ok' => false,
            'message' => 'ยังไม่ได้ติดตั้ง Composer dependencies'
        ], 500);
    }

    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/vendor/wmh/php-ecrecover/ecrecover_helper.php';

    try {
        $recoveredAddress = personal_ecRecover(
            $challenge['message'],
            $signature  
        );

        if (
            !is_string($recoveredAddress) ||
            strtolower($recoveredAddress) !== $challenge['address']
        ) {
            walletOut([
                'ok' => false,
                'message' => 'ยืนยันเจ้าของ Wallet ไม่สำเร็จ'
            ], 403);
        }

        $pdo->beginTransaction();

        // ไม่อนุญาตให้บัญชีหนึ่งเปลี่ยน Wallet เดิมโดยอัตโนมัติ
        $stmt = $pdo->prepare(
            'SELECT wallet_address
             FROM user_wallets
             WHERE user_id = ? AND chain_id = ?
             FOR UPDATE'
        );
        $stmt->execute([
            $userId,
            $challenge['chain_id']
        ]);

        $existing = $stmt->fetchColumn();

        if ($existing !== false) {
            $pdo->rollBack();

            if (strtolower($existing) === $challenge['address']) {
                walletOut([
                    'ok' => true,
                    'message' => 'Wallet นี้เชื่อมกับบัญชีอยู่แล้ว'
                ]);
            }

            walletOut([
                'ok' => false,
                'message' => 'บัญชีนี้มี Wallet ในเครือข่ายนี้แล้ว'
            ], 409);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO user_wallets
             (user_id, wallet_address, chain_id)
             VALUES (?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $challenge['address'],
            $challenge['chain_id']
        ]);

        $pdo->commit();

        walletOut([
            'ok' => true,
            'message' => 'ยืนยันและบันทึก Wallet สำเร็จ',
            'wallet' => $challenge['address'],
            'chainId' => $challenge['chain_id']
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // บันทึกรายละเอียดไว้ใน PHP error log เท่านั้น
        error_log('Wallet verification error: ' . $e->getMessage());

        walletOut([
            'ok' => false,
            'message' => 'ตรวจสอบ Wallet ไม่สำเร็จ กรุณาตรวจสอบการตั้งค่า PHP'
        ], 500);
    }
}

walletOut([
    'ok' => false,
    'message' => 'Invalid action'
], 400);