<?php
session_start();
include("function.php");

// 1. トークン取得
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    redirect('index.php');
}

// 2. DB接続
$pdo = db_conn();

// ───────────────────────────────────────────────────
// 3. agents テーブルでトークン照合
// ───────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT * FROM agents WHERE email_token=:token AND email_verified_at IS NULL AND life_flg=0"
);
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$agent = $stmt->fetch(PDO::FETCH_ASSOC);

if ($agent) {
    // 認証済みに更新
    $stmt = $pdo->prepare("UPDATE agents SET email_verified_at=NOW() WHERE id=:id");
    $stmt->bindValue(':id', $agent['id'], PDO::PARAM_INT);
    $stmt->execute();

    // 自動ログイン → KYC画面へ（既存のエージェントフロー維持）
    $_SESSION['chk_ssid']  = session_id();
    $_SESSION['name']      = $agent['name'];
    $_SESSION['id']        = $agent['id'];
    $_SESSION['user_type'] = 'agent';

    redirect('agent_kyc.php');
}

// ───────────────────────────────────────────────────
// 4. users テーブルでトークン照合
// ───────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE email_token=:token AND email_verified_at IS NULL AND life_flg=0"
);
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // 認証済みに更新
    $stmt = $pdo->prepare("UPDATE users SET email_verified_at=NOW() WHERE id=:id");
    $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
    $stmt->execute();

    // 本登録完了画面を表示
    $verified_name = $user['name'];
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本登録完了 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f7f6; }
        .verify-wrap {
            max-width: 520px;
            margin: 80px auto 80px;
            padding: 0 20px;
        }
        .verify-box {
            background: #fff;
            border-radius: 12px;
            padding: 56px 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            text-align: center;
        }
        .verify-icon {
            width: 72px;
            height: 72px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 24px;
        }
        .verify-box h2 { font-size: 1.4rem; color: #1a1a1a; margin-bottom: 16px; }
        .verify-box p { font-size: 0.92rem; color: #555; line-height: 1.8; margin-bottom: 8px; }
        .btn-login {
            display: inline-block;
            margin-top: 32px;
            padding: 14px 44px;
            background: #004e92;
            color: #fff;
            border-radius: 30px;
            font-size: 0.95rem;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.3s;
            box-shadow: 0 4px 10px rgba(0,78,146,0.2);
        }
        .btn-login:hover { background: #003366; color: #fff; }
    </style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="img/logo_blue.png" alt="ERAPRO"
                 onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
            <span style="display:none; font-weight:800; color:#004e92; font-size:1.2rem;">ERAPRO</span>
        </a>
    </div>
</header>

<div class="verify-wrap">
    <div class="verify-box">
        <div class="verify-icon">✅</div>
        <h2>本登録が完了しました</h2>
        <p><strong><?= h($verified_name) ?> さん</strong>、ようこそERAPROへ！</p>
        <p>メールアドレスの認証が完了しました。<br>下のボタンからログインしてご利用ください。</p>
        <a href="login_user.php" class="btn-login">ログイン画面へ</a>
    </div>
</div>

</body>
</html>
    <?php
    exit();
}

// ───────────────────────────────────────────────────
// 5. どちらにも該当しない（無効・使用済みトークン）
// ───────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>認証エラー - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f7f6; }
        .verify-wrap { max-width: 520px; margin: 80px auto; padding: 0 20px; }
        .verify-box {
            background: #fff; border-radius: 12px; padding: 56px 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07); text-align: center;
        }
        .verify-icon {
            width: 72px; height: 72px; background: #fff3f3; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 24px;
        }
        .verify-box h2 { font-size: 1.3rem; color: #dc3545; margin-bottom: 16px; }
        .verify-box p { font-size: 0.92rem; color: #555; line-height: 1.8; }
        .btn-back {
            display: inline-block; margin-top: 28px; padding: 12px 36px;
            background: #004e92; color: #fff; border-radius: 30px;
            font-size: 0.92rem; font-weight: bold; text-decoration: none;
        }
        .btn-back:hover { background: #003366; color: #fff; }
    </style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="img/logo_blue.png" alt="ERAPRO"
                 onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
            <span style="display:none; font-weight:800; color:#004e92; font-size:1.2rem;">ERAPRO</span>
        </a>
    </div>
</header>

<div class="verify-wrap">
    <div class="verify-box">
        <div class="verify-icon">❌</div>
        <h2>認証リンクが無効です</h2>
        <p>このリンクは無効か、既に使用済みです。<br>
           既に認証済みの場合はそのままログインできます。</p>
        <a href="login_user.php" class="btn-back">ログイン画面へ</a>
    </div>
</div>

</body>
</html>
