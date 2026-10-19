<?php
// ซ่อน Warning/Notice ไม่ให้พิมพ์ HTML แทรกออกมาปนใน JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

date_default_timezone_set('Asia/Bangkok');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'flower_coin');
define('DB_USER', 'root');
define('DB_PASS', '');

function db() {
    static $p;
    if (!$p) {
        $p = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    return $p;
}

function ensurePendingCoinsColumn($pdo) {
    static $done = false;
    if ($done) return;

    $check = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='pending_coins'")->fetchColumn();
    if (!(int)$check) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_coins DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER coins");
    }

    $check = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='day_started_at'")->fetchColumn();
    if (!(int)$check) {
        $pdo->exec("ALTER TABLE users ADD COLUMN day_started_at DATETIME NULL AFTER last_tick");
    }

    $pdo->exec("UPDATE users SET day_started_at=NOW() WHERE mode='day' AND day_started_at IS NULL");
    $done = true;
}

function out($x, $code = 200) {
    // ล้างข้อความ PHP Warning/Notice ตกค้างที่อาจหลุดออกมา
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($x, JSON_UNESCAPED_UNICODE);
    exit;
}

function uid() {
    // เช็คก่อนว่ามี session เปิดอยู่หรือยัง เพื่อไม่ให้เกิด Notice ซ้ำซ้อน
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        out(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
    }
    return (int)$_SESSION['user_id'];
}

$FLOWERS = [
    0 => ['name' => 'Sunflower', 'thai' => 'ทานตะวัน', 'emoji' => '🌻', 'rate' => 0.06, 'drop' => 60, 'merge' => 80],
    1 => ['name' => 'Lavender', 'thai' => 'ลาเวนเดอร์', 'emoji' => '💜', 'rate' => 0.12, 'drop' => 25, 'merge' => 60],
    2 => ['name' => 'Blue Rose', 'thai' => 'กุหลาบสีน้ำเงิน', 'emoji' => '🔵', 'rate' => 0.25, 'drop' => 10, 'merge' => 40],
    3 => ['name' => 'Tulip', 'thai' => 'ทิวลิป', 'emoji' => '🌷', 'rate' => 0.50, 'drop' => 4, 'merge' => 20],
    4 => ['name' => 'Glowing Fantasy Flower', 'thai' => 'ดอกไม้แฟนตาซีเรืองแสง', 'emoji' => '✨', 'rate' => 1.00, 'drop' => 1, 'merge' => null]
];

function rollFlower() {
    global $FLOWERS;
    $r = random_int(1, 100);
    $s = 0;
    foreach ($FLOWERS as $t => $f) {
        $s += $f['drop'];
        if ($r <= $s) return $t;
    }
    return 0;
}

function settleDay($pdo, $id) {
    ensurePendingCoinsColumn($pdo);

    $st = $pdo->prepare("SELECT mode,last_tick,day_started_at FROM users WHERE id=? FOR UPDATE");
    $st->execute([$id]);
    $u = $st->fetch();

    if (!$u || $u['mode'] !== 'day') return 0;

    $lastTs = $u['last_tick'] ? strtotime($u['last_tick']) : false;
    if ($lastTs === false) {
        $lastTs = $u['day_started_at'] ? strtotime($u['day_started_at']) : time();
        $pdo->prepare("UPDATE users SET last_tick=FROM_UNIXTIME(?) WHERE id=?")->execute([$lastTs, $id]);
    }

    $nowTs = time();
    $ticks = max(0, (int)floor(($nowTs - $lastTs) / 10));
    if ($ticks <= 0) return 0;

    global $FLOWERS;
    $st = $pdo->prepare("SELECT flower_tier FROM gardens WHERE user_id=? AND flower_tier IS NOT NULL");
    $st->execute([$id]);
    $rate = 0;
    foreach ($st as $r) {
        $rate += $FLOWERS[(int)$r['flower_tier']]['rate'];
    }

    $gain = $rate * $ticks;
    $newLastTs = $lastTs + ($ticks * 10);

    $pdo->prepare("UPDATE users SET pending_coins=pending_coins+?,last_tick=FROM_UNIXTIME(?) WHERE id=?")->execute([$gain, $newLastTs, $id]);
    return $gain;
}