<?php
session_start();
include('function.php');

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    redirect('password_reset.php?error=' . urlencode('無効なURLです'));
}

$pdo = db_conn();

// トークンの有効性チェック
$stmt = $pdo->prepare(
    "SELECT * FROM password_resets WHERE token=:token AND expires_at > NOW()"
);
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    redirect('password_reset.php?error=' . urlencode('このURLは無効か、有効期限が切れています。再度お手続きください。'));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しいパスワードの設定 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f4f7f6; }
        .auth-box {
            max-width: 440px;
            margin: 80px auto;
            padding: 44px 48px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        h2 { text-align: center; color: #004e92; margin-bottom: 10px; font-size: 1.3rem; }
        .sub { text-align: center; font-size: 0.88rem; color: #777; margin-bottom: 32px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; font-size: 0.9rem; }
        input[type="password"] {
            width: 100%; padding: 12px; border: 1px solid #ddd;
            border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        .btn-submit {
            width: 100%; padding: 14px; background: #004e92; color: #fff;
            border: none; border-radius: 30px; font-size: 1rem; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .btn-submit:hover { background: #003366; }
        .error-msg {
            background: #fff3f3; color: #dc3545; border: 1px solid #f5c6cb;
            padding: 12px 16px; border-radius: 6px; font-size: 0.88rem; margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <h2>新しいパスワードの設定</h2>
        <p class="sub">新しいパスワードを入力してください。</p>

        <?php if (!empty($_GET['error'])): ?>
        <p class="error-msg"><?= htmlspecialchars($_GET['error'], ENT_QUOTES) ?></p>
        <?php endif; ?>

        <form action="password_reset_confirm_act.php" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

            <div class="form-group">
                <label>新しいパスワード</label>
                <input type="password" name="new_password" required
                       placeholder="半角英数字8文字以上推奨" minlength="8">
            </div>

            <div class="form-group">
                <label>新しいパスワード（確認）</label>
                <input type="password" name="new_password_confirm" required
                       placeholder="もう一度入力してください" minlength="8">
            </div>

            <button type="submit" class="btn-submit">パスワードを変更する</button>
        </form>
    </div>
</body>
</html>
