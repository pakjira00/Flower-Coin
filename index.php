
<?php
session_start();

// ถ้าเข้าสู่ระบบอยู่แล้ว ให้ไปหน้าเกม
if (isset($_SESSION['user_id'])) {
    header('Location: game.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Flower Coin - เข้าสู่ระบบด้วย MetaMask</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            font-family: sans-serif;
        }

        .auth-card {
            width: 100%;
            max-width: 380px;
            padding: 32px 28px;
            box-sizing: border-box;
            text-align: center;
            border-radius: 16px;
        }

        .auth-card h1 {
            margin: 0 0 10px;
        }

        .auth-subtitle {
            margin: 0 0 28px;
        }

        #metamaskLoginButton {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        #metamaskLoginButton:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        #walletLoginMsg {
            margin-top: 16px;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body class="login-page">

    <main class="auth-card">
        <h1>🌸 Flower Coin</h1>

        <p class="auth-subtitle">
            เกมปลูกดอกไม้สะสม Coin
        </p>

        <button
            type="button"
            id="metamaskLoginButton"
        >
            เข้าสู่ระบบด้วย MetaMask
        </button>

        <div
            id="walletLoginMsg"
            class="msg"
            aria-live="polite"
        ></div>
    </main>

    <script src="assets/auth.js?v=2"></script>

</body>
</html>
