<?php
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$action = $_GET['action'] ?? '';
$pdo = db();
ensurePendingCoinsColumn($pdo);

// ----------------------------------------------------
// 1. ระบบยืนยันตัวตนด้วย MetaMask (Nonce & Login)
// ----------------------------------------------------
if (in_array($action, ['wallet_nonce', 'wallet_login'], true)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $data = is_array($data) ? $data : [];

    if ($action === 'wallet_nonce') {
        $address = $data['address'] ?? '';
        
        $chainId = filter_var(
            $data['chain_id'] ?? $data['chainId'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !is_string($address) ||
            !preg_match('/^0x[a-fA-F0-9]{40}$/', $address) ||
            $chainId === false ||
            $chainId === null ||
            $chainId < 1
        ) {
            out([
                'ok' => false,
                'message' => 'Wallet Address หรือ Chain ID ไม่ถูกต้อง'
            ], 400);
        }

        $address = strtolower($address);
        $nonce = bin2hex(random_bytes(32));
        $issuedAt = gmdate('Y-m-d\TH:i:s\Z');

        $message =
            "Flower Coin Login\n" .
            "Wallet: {$address}\n" .
            "Chain ID: {$chainId}\n" .
            "Nonce: {$nonce}\n" .
            "Issued At: {$issuedAt}";

        $_SESSION['wallet_login_challenge'] = [
            'address' => $address,
            'chain_id' => (string) $chainId,
            'message' => $message,
            'created_at' => time()
        ];

        out([
            'ok' => true,
            'message' => $message
        ]);
    }

    if ($action === 'wallet_login') {
        $signature = $data['signature'] ?? '';
        $challenge = $_SESSION['wallet_login_challenge'] ?? null;

        unset($_SESSION['wallet_login_challenge']);

        if (
            !is_array($challenge) ||
            !isset(
                $challenge['address'],
                $challenge['chain_id'],
                $challenge['message'],
                $challenge['created_at']
            ) ||
            time() - (int) $challenge['created_at'] > 300 ||
            time() < (int) $challenge['created_at']
        ) {
            out([
                'ok' => false,
                'message' => 'ข้อความยืนยันหมดอายุ กรุณาลองใหม่'
            ], 400);
        }

        if (
            !is_string($signature) ||
            !preg_match('/^0x[a-fA-F0-9]{130}$/', $signature)
        ) {
            out([
                'ok' => false,
                'message' => 'ลายเซ็นไม่ถูกต้อง'
            ], 400);
        }

        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }

        if (file_exists(__DIR__ . '/vendor/wmh/php-ecrecover/ecrecover_helper.php')) {
            require_once __DIR__ . '/vendor/wmh/php-ecrecover/ecrecover_helper.php';
        }

        if (!function_exists('personal_ecRecover')) {
            out([
                'ok' => false,
                'message' => 'ไม่พบไลบรารี personal_ecRecover กรุณาตรวจสอบ vendor/wmh/php-ecrecover'
            ], 500);
        }

        try {
            $recoveredAddress = personal_ecRecover(
                $challenge['message'],
                $signature
            );

            if (
                !is_string($recoveredAddress) ||
                strtolower($recoveredAddress) !== $challenge['address']
            ) {
                out([
                    'ok' => false,
                    'message' => 'ยืนยันเจ้าของ Wallet ไม่สำเร็จ'
                ], 403);
            }

            // ตรวจสอบว่า Wallet เคยผูกกับบัญชีใดหรือไม่
            $stmt = $pdo->prepare(
                'SELECT u.id
                 FROM user_wallets w
                 INNER JOIN users u ON u.id = w.user_id
                 WHERE LOWER(w.wallet_address) = ?
                   AND w.chain_id = ?
                 LIMIT 1'
            );

            $stmt->execute([
                $challenge['address'],
                $challenge['chain_id']
            ]);

            $userId = $stmt->fetchColumn();

            // หากยังไม่เคยผูกกระเป๋า ให้สร้างบัญชีใหม่ให้อัตโนมัติ (Auto-Register)
            if ($userId === false) {
                $pdo->beginTransaction();

                $shortAddr = substr($challenge['address'], 2, 6);
                $autoUsername = 'Player_' . $shortAddr;
                $randomPass = bin2hex(random_bytes(16));

                // 1. เพิ่มผู้ใช้ใหม่
                $s = $pdo->prepare("INSERT INTO users(username, password_hash, coins, mode) VALUES(?, ?, 500, 'night')");
                $s->execute([$autoUsername, password_hash($randomPass, PASSWORD_DEFAULT)]);
                $newUserId = (int)$pdo->lastInsertId();

                // 2. ผูกกระเป๋าลง user_wallets
                $s = $pdo->prepare("INSERT INTO user_wallets(user_id, wallet_address, chain_id) VALUES(?, ?, ?)");
                $s->execute([$newUserId, $challenge['address'], $challenge['chain_id']]);

                // 3. สร้างแปลงสวน 9 ช่อง
                $s = $pdo->prepare("INSERT INTO gardens(user_id, slot) VALUES(?, ?)");
                for ($i = 0; $i < 9; $i++) {
                    $s->execute([$newUserId, $i]);
                }

                // 4. มอบดอกไม้เริ่มต้นในคลัง
                $s = $pdo->prepare("INSERT INTO inventory(user_id, flower_tier, quantity) VALUES(?, ?, ?)");
                $s->execute([$newUserId, 0, 5]);
                for ($i = 1; $i < 5; $i++) {
                    $s->execute([$newUserId, $i, 0]);
                }

                $pdo->commit();
                $userId = $newUserId;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $userId;

            out([
                'ok' => true,
                'message' => 'เข้าสู่ระบบด้วย MetaMask สำเร็จ'
            ]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('MetaMask login error: ' . $e->getMessage());

            out([
                'ok' => false,
                'message' => 'ตรวจสอบ Wallet ไม่สำเร็จ: ' . $e->getMessage()
            ], 500);
        }
    }
}

// ----------------------------------------------------
// 2. ระบบลงทะเบียน / เข้าสู่ระบบแบบ Username & Password
// ----------------------------------------------------
if ($action === 'register' || $action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

    $d = json_decode(file_get_contents('php://input'), true);
    $d = is_array($d) ? $d : $_POST;

    $u = trim($d['username'] ?? '');
    $p = $d['password'] ?? '';

    if ($action === 'register') {
        if (
            !preg_match('/^[A-Za-z0-9_\x{0E00}-\x{0E7F}]{3,30}$/u', $u) ||
            strlen($p) < 6 ||
            $p !== ($d['confirm_password'] ?? '')
        ) {
            out([
                'ok' => false,
                'message' => 'ข้อมูลสมัครสมาชิกไม่ถูกต้อง (ชื่อ 3-30 ตัวอักษร, รหัสผ่าน 6 ตัวขึ้นไป)'
            ], 400);
        }

        try {
            $pdo->beginTransaction();

            $s = $pdo->prepare(
                "INSERT INTO users(username,password_hash,coins,mode)
                 VALUES(?,?,500,'night')"
            );
            $s->execute([
                $u,
                password_hash($p, PASSWORD_DEFAULT)
            ]);

            $id = (int) $pdo->lastInsertId();

            $s = $pdo->prepare('INSERT INTO gardens(user_id,slot) VALUES(?,?)');
            for ($i = 0; $i < 9; $i++) {
                $s->execute([$id, $i]);
            }

            $s = $pdo->prepare('INSERT INTO inventory(user_id,flower_tier,quantity) VALUES(?,?,?)');
            $s->execute([$id, 0, 5]);

            for ($i = 1; $i < 5; $i++) {
                $s->execute([$id, $i, 0]);
            }

            $pdo->commit();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;

            out(['ok' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Register error: ' . $e->getMessage());

            out([
                'ok' => false,
                'message' => 'ชื่อผู้ใช้นี้ถูกใช้แล้วหรือสมัครสมาชิกไม่สำเร็จ'
            ], 400);
        }
    }

    $s = $pdo->prepare('SELECT id,password_hash FROM users WHERE username=?');
    $s->execute([$u]);
    $r = $s->fetch();

    if (!$r || !password_verify($p, $r['password_hash'])) {
        out([
            'ok' => false,
            'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
        ], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $r['id'];

    out(['ok' => true]);
}

if ($action === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    out(['ok' => true]);
}

// ยืนยันการเข้าสู่ระบบ
$id = uid();

// ----------------------------------------------------
// 3. ตั้งชื่อ / แก้ไขชื่อผู้ใช้ (Update Username)
// ----------------------------------------------------
if ($action === 'update_username') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

    $d = json_decode(file_get_contents('php://input'), true);
    $d = is_array($d) ? $d : $_POST;

    $newUsername = trim($d['username'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_\x{0E00}-\x{0E7F}]{3,30}$/u', $newUsername)) {
        out([
            'ok' => false, 
            'message' => 'ชื่อต้องยาว 3-30 ตัวอักษร ห้ามมีอักขระพิเศษ'
        ], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $stmt->execute([$newUsername, $id]);
    if ($stmt->fetch()) {
        out(['ok' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่ออื่น'], 400);
    }

    $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
    $stmt->execute([$newUsername, $id]);

    out([
        'ok' => true,
        'username' => $newUsername,
        'message' => 'เปลี่ยนชื่อผู้ใช้สำเร็จ'
    ]);
}

// ----------------------------------------------------
// 4. ผูกกระเป๋า Wallet
// ----------------------------------------------------
if ($action === 'link_wallet') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

    $d = json_decode(file_get_contents('php://input'), true);
    $d = is_array($d) ? $d : $_POST;

    $address = strtolower(trim($d['address'] ?? ''));
    $chainId = filter_var($d['chain_id'] ?? $d['chainId'] ?? null, FILTER_VALIDATE_INT);

    if (!preg_match('/^0x[a-f0-9]{40}$/', $address) || !$chainId || $chainId < 1) {
        out(['ok' => false, 'message' => 'Wallet Address หรือ Chain ID ไม่ถูกต้อง'], 400);
    }

    $stmt = $pdo->prepare('SELECT user_id FROM user_wallets WHERE LOWER(wallet_address) = ? AND chain_id = ? LIMIT 1');
    $stmt->execute([$address, $chainId]);
    $owner = $stmt->fetchColumn();

    if ($owner && (int)$owner !== $id) {
        out(['ok' => false, 'message' => 'Wallet นี้ถูกผูกกับบัญชีอื่นไปแล้ว'], 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO user_wallets (user_id, wallet_address, chain_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE wallet_address = VALUES(wallet_address)'
    );
    $stmt->execute([$id, $address, $chainId]);

    out(['ok' => true, 'message' => 'เชื่อมต่อ Wallet สำเร็จ']);
}

// ----------------------------------------------------
// 5. ข้อมูลสถานะเกมของผู้เล่น
// ----------------------------------------------------
if ($action === 'state') {
    $pdo->beginTransaction();

    $gain = settleDay($pdo, $id);

    $s = $pdo->prepare(
        'SELECT id,username,coins,pending_coins,mode,last_tick,day_started_at
         FROM users WHERE id=?'
    );
    $s->execute([$id]);
    $user = $s->fetch();

    $s = $pdo->prepare('SELECT slot,flower_tier FROM gardens WHERE user_id=? ORDER BY slot');
    $s->execute([$id]);
    $garden = $s->fetchAll();

    $s = $pdo->prepare('SELECT flower_tier,quantity FROM inventory WHERE user_id=? ORDER BY flower_tier');
    $s->execute([$id]);
    $inv = $s->fetchAll();

    $s = $pdo->prepare('SELECT wallet_address, chain_id FROM user_wallets WHERE user_id=? LIMIT 1');
    $s->execute([$id]);
    $wallet = $s->fetch();

    $pdo->commit();

    out([
        'ok' => true,
        'user' => $user,
        'wallet' => $wallet ?: null,
        'garden' => $garden,
        'inventory' => $inv,
        'flowers' => $FLOWERS,
        'settled' => $gain
    ]);
}

// ----------------------------------------------------
// 6. สลับโหมด Day / Night (แยกเหรียญส่งเข้า token_rewards)
// ----------------------------------------------------
if ($action === 'mode') {
    $to = ($_POST['mode'] ?? '') === 'day' ? 'day' : 'night';

    $pdo->beginTransaction();

    $s = $pdo->prepare('SELECT mode,pending_coins FROM users WHERE id=? FOR UPDATE');
    $s->execute([$id]);
    $u = $s->fetch();

    if (!$u) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ไม่พบผู้เล่น'], 404);
    }

    $rewardAmount = 0;

    if ($u['mode'] === 'day' && $to === 'night') {
        settleDay($pdo, $id);

        $s = $pdo->prepare('SELECT pending_coins FROM users WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $pending = (float) $s->fetchColumn();

        if ($pending > 0) {
            $stmt = $pdo->prepare('SELECT wallet_address, chain_id FROM user_wallets WHERE user_id=? LIMIT 1');
            $stmt->execute([$id]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $pdo->rollBack();
                out([
                    'ok' => false, 
                    'message' => 'ไม่พบกระเป๋า MetaMask กรุณาเชื่อมต่อ Wallet เพื่อรับ FLOWER Token'
                ], 400);
            }

            // ล้าง pending_coins = 0 ในตาราง users
            $pdo->prepare('UPDATE users SET pending_coins=0 WHERE id=?')->execute([$id]);

            // บันทึกลงตาราง token_rewards
            $pdo->prepare(
                'INSERT INTO token_rewards(user_id, wallet_address, chain_id, amount, status)
                 VALUES(?, ?, ?, ?, ?)'
            )->execute([
                $id,
                strtolower($wallet['wallet_address']),
                $wallet['chain_id'],
                $pending,
                'pending'
            ]);

            $rewardAmount = $pending;
        }
    }

    $now = date('Y-m-d H:i:s');

    if ($to === 'day') {
        $pdo->prepare("UPDATE users SET mode='day',last_tick=?,day_started_at=? WHERE id=?")->execute([$now, $now, $id]);
    } else {
        $pdo->prepare("UPDATE users SET mode='night',last_tick=?,day_started_at=NULL WHERE id=?")->execute([$now, $id]);
    }

    $pdo->commit();

    out([
        'ok' => true, 
        'reward_flower' => $rewardAmount,
        'message' => $rewardAmount > 0 
            ? "สะสม {$rewardAmount} FLOWER Token สำเร็จ (บันทึกเข้าระบบบล็อกเชน)" 
            : null
    ]);
}

// ----------------------------------------------------
// 7. ซื้อเหรียญในเกมด้วย FLOWER Token (Game Coin)
// ----------------------------------------------------
if ($action === 'buy_coins') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out(['ok' => false, 'message' => 'Method not allowed'], 405);
    }

    $d = json_decode(file_get_contents('php://input'), true);
    $d = is_array($d) ? $d : $_POST;

    $txHash = trim($d['tx_hash'] ?? '');
    $tokenAmount = (float)($d['token_amount'] ?? 0);
    $chainId = filter_var($d['chain_id'] ?? $d['chainId'] ?? null, FILTER_VALIDATE_INT);

    if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash) || $tokenAmount <= 0 || !$chainId) {
        out(['ok' => false, 'message' => 'ข้อมูลธุรกรรมไม่ถูกต้อง'], 400);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id FROM coin_purchases WHERE tx_hash = ? LIMIT 1');
    $stmt->execute([$txHash]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ธุรกรรมนี้ถูกนำไปใช้แลก Coin แล้ว'], 400);
    }

    $stmt = $pdo->prepare('SELECT wallet_address FROM user_wallets WHERE user_id = ? AND chain_id = ? LIMIT 1');
    $stmt->execute([$id, $chainId]);
    $walletAddress = $stmt->fetchColumn();

    if (!$walletAddress) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ไม่พบข้อมูล Wallet ของคุณในระบบ'], 400);
    }

    $rate = 100; // 1 FLOWER Token = 100 Game Coins
    $coinsReceived = $tokenAmount * $rate;

    $stmt = $pdo->prepare(
        'INSERT INTO coin_purchases (user_id, wallet_address, chain_id, token_amount, coins_received, tx_hash, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $id,
        strtolower($walletAddress),
        $chainId,
        $tokenAmount,
        $coinsReceived,
        $txHash,
        'verified'
    ]);

    $pdo->prepare('UPDATE users SET coins = coins + ? WHERE id = ?')->execute([$coinsReceived, $id]);

    $pdo->prepare(
        'INSERT INTO coin_transactions (user_id, amount, type, description)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $id,
        $coinsReceived,
        'purchase',
        "ซื้อ {$coinsReceived} Coins ด้วย FLOWER ({$tokenAmount} Token)"
    ]);

    $pdo->commit();

    out([
        'ok' => true,
        'coins_received' => $coinsReceived,
        'message' => "ซื้อสำเร็จ! คุณได้รับ {$coinsReceived} Game Coins"
    ]);
}

// ----------------------------------------------------
// 8. ปลูกและขุดดอกไม้
// ----------------------------------------------------
if ($action === 'plant') {
    $slot = (int) ($_POST['slot'] ?? -1);
    $tier = (int) ($_POST['tier'] ?? -1);

    if ($slot < 0 || $slot > 8 || $tier < 0 || $tier > 4) {
        out(['ok' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }

    $pdo->beginTransaction();

    $s = $pdo->prepare('SELECT mode FROM users WHERE id=? FOR UPDATE');
    $s->execute([$id]);

    if ($s->fetchColumn() !== 'night') {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ปลูกได้เฉพาะช่วงเวลากลางคืน (Night)'], 400);
    }

    $s = $pdo->prepare('SELECT flower_tier FROM gardens WHERE user_id=? AND slot=? FOR UPDATE');
    $s->execute([$id, $slot]);
    $r = $s->fetch();

    if (!$r || $r['flower_tier'] !== null) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ช่องนี้มีดอกไม้อยู่แล้ว'], 400);
    }

    $s = $pdo->prepare('SELECT quantity FROM inventory WHERE user_id=? AND flower_tier=? FOR UPDATE');
    $s->execute([$id, $tier]);
    $q = (int) $s->fetchColumn();

    if ($q < 1) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'คุณไม่มีดอกไม้นี้ในคลัง'], 400);
    }

    $pdo->prepare('UPDATE inventory SET quantity=quantity-1 WHERE user_id=? AND flower_tier=?')->execute([$id, $tier]);
    $pdo->prepare('UPDATE gardens SET flower_tier=?,planted_at=NOW() WHERE user_id=? AND slot=?')->execute([$tier, $id, $slot]);

    $pdo->commit();
    out(['ok' => true]);
}

if ($action === 'dig') {
    $slot = (int) ($_POST['slot'] ?? -1);

    $pdo->beginTransaction();

    $s = $pdo->prepare('SELECT mode FROM users WHERE id=? FOR UPDATE');
    $s->execute([$id]);

    if ($s->fetchColumn() !== 'night') {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ขุดได้เฉพาะช่วงเวลากลางคืน (Night)'], 400);
    }

    $s = $pdo->prepare('SELECT flower_tier FROM gardens WHERE user_id=? AND slot=? FOR UPDATE');
    $s->execute([$id, $slot]);
    $r = $s->fetch();

    if (!$r || $r['flower_tier'] === null) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ช่องนี้ว่าง'], 400);
    }

    $t = (int) $r['flower_tier'];

    $pdo->prepare('UPDATE gardens SET flower_tier=NULL,planted_at=NULL WHERE user_id=? AND slot=?')->execute([$id, $slot]);
    $pdo->prepare('UPDATE inventory SET quantity=quantity+1 WHERE user_id=? AND flower_tier=?')->execute([$id, $t]);

    $pdo->commit();
    out(['ok' => true]);
}

// ----------------------------------------------------
// 9. กล่องสุ่ม Mystery Box (ใช้ Game Coin)
// ----------------------------------------------------
if ($action === 'box') {
    $pdo->beginTransaction();

    $s = $pdo->prepare('SELECT mode,coins FROM users WHERE id=? FOR UPDATE');
    $s->execute([$id]);
    $u = $s->fetch();

    if (!$u || $u['mode'] !== 'night' || (float) $u['coins'] < 100) {
        $pdo->rollBack();
        out([
            'ok' => false,
            'message' => !$u
                ? 'ไม่พบผู้เล่น'
                : ($u['mode'] !== 'night'
                    ? 'เปิดกล่องได้เฉพาะช่วงเวลากลางคืน (Night)'
                    : 'Game Coin ไม่พอ (ต้องการ 100 Coins)')
        ], 400);
    }

    $t = rollFlower();

    $pdo->prepare('UPDATE users SET coins=coins-100 WHERE id=?')->execute([$id]);
    $pdo->prepare('UPDATE inventory SET quantity=quantity+1 WHERE user_id=? AND flower_tier=?')->execute([$id, $t]);
    $pdo->prepare('INSERT INTO box_history(user_id,flower_tier) VALUES(?,?)')->execute([$id, $t]);
    $pdo->prepare('INSERT INTO coin_transactions(user_id,amount,type,description) VALUES(?,?,?,?)')->execute([$id, -100, 'box', 'เปิด Mystery Box']);

    $pdo->commit();

    out([
        'ok' => true,
        'tier' => $t,
        'flower' => $FLOWERS[$t]
    ]);
}

// ----------------------------------------------------
// 10. ผสมดอกไม้ (Merge)
// ----------------------------------------------------
if ($action === 'merge') {
    $tier = (int) ($_POST['tier'] ?? -1);

    if ($tier < 0 || $tier > 3) {
        out(['ok' => false, 'message' => 'Tier ไม่ถูกต้อง'], 400);
    }

    $pdo->beginTransaction();

    $s = $pdo->prepare('SELECT mode FROM users WHERE id=? FOR UPDATE');
    $s->execute([$id]);

    if ($s->fetchColumn() !== 'night') {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'Merge ได้เฉพาะช่วงเวลากลางคืน (Night)'], 400);
    }

    $s = $pdo->prepare('SELECT quantity FROM inventory WHERE user_id=? AND flower_tier=? FOR UPDATE');
    $s->execute([$id, $tier]);
    $q = (int) $s->fetchColumn();

    if ($q < 4) {
        $pdo->rollBack();
        out(['ok' => false, 'message' => 'ต้องมีดอกไม้ระดับเดียวกันอย่างน้อย 4 ดอก'], 400);
    }

    $success = random_int(1, 100) <= $FLOWERS[$tier]['merge'];

    $pdo->prepare('UPDATE inventory SET quantity=quantity-4 WHERE user_id=? AND flower_tier=?')->execute([$id, $tier]);
    $result = $success ? $tier + 1 : $tier;
    $pdo->prepare('UPDATE inventory SET quantity=quantity+1 WHERE user_id=? AND flower_tier=?')->execute([$id, $result]);

    $pdo->prepare('INSERT INTO merge_history(user_id,from_tier,to_tier,success) VALUES(?,?,?,?)')->execute([$id, $tier, $tier + 1, $success ? 1 : 0]);

    $pdo->commit();

    out([
        'ok' => true,
        'success' => $success,
        'tier' => $result,
        'flower' => $FLOWERS[$result]
    ]);
}

// ----------------------------------------------------
// 11. ประวัติเหรียญในเกม
// ----------------------------------------------------
if ($action === 'history') {
    $s = $pdo->prepare(
        'SELECT amount,description,created_at
         FROM coin_transactions
         WHERE user_id=? ORDER BY id DESC LIMIT 20'
    );
    $s->execute([$id]);

    out(['ok' => true, 'items' => $s->fetchAll()]);
}

out(['ok' => false, 'message' => 'Invalid action'], 400);