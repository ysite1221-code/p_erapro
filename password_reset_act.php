<?php
session_start();
include('function.php');

// POSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('password_reset.php');
}

$email     = trim($_POST['email'] ?? '');
$user_type = $_POST['user_type'] ?? '';

// バリデーション
if ($email === '' || !in_array($user_type, ['user', 'agent'])) {
    redirect('password_reset.php?error=' . urlencode('入力内容を確認してください'));
}

$pdo = db_conn();

// password_resetsテーブルがなければ作成
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    user_type  ENUM('user','agent') NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// メールアドレスがDB上に存在するか確認
$table = ($user_type === 'agent') ? 'agents' : 'users';
$stmt  = $pdo->prepare("SELECT id, name FROM {$table} WHERE lid=:email AND life_flg=0");
$stmt->bindValue(':email', $email, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// セキュリティ上、存在有無に関わらず同じ画面を表示する
if ($user) {
    // トークン生成（64文字の16進数）
    $token      = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 既存レコードがあれば削除してから挿入（同一メールの重複防止）
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email=:email AND user_type=:utype");
    $stmt->bindValue(':email', $email,     PDO::PARAM_STR);
    $stmt->bindValue(':utype', $user_type, PDO::PARAM_STR);
    $stmt->execute();

    $stmt = $pdo->prepare(
        "INSERT INTO password_resets (email, user_type, token, expires_at)
         VALUES (:email, :utype, :token, :expires_at)"
    );
    $stmt->bindValue(':email',      $email,      PDO::PARAM_STR);
    $stmt->bindValue(':utype',      $user_type,  PDO::PARAM_STR);
    $stmt->bindValue(':token',      $token,      PDO::PARAM_STR);
    $stmt->bindValue(':expires_at', $expires_at, PDO::PARAM_STR);
    $stmt->execute();

    // リセットURL
    $reset_url = 'http://localhost/sotsu/password_reset_form.php?token=' . $token;

    // メール送信
    $mail_subject = '【ERAPRO】パスワード再設定のご案内';
    $mail_body  = $user['name'] . " 様\n\n";
    $mail_body .= "ERAPROのパスワード再設定のリクエストを受け付けました。\n\n";
    $mail_body .= "下記のURLにアクセスして、新しいパスワードを設定してください。\n";
    $mail_body .= "※このURLの有効期限は1時間です。\n\n";
    $mail_body .= $reset_url . "\n\n";
    $mail_body .= "このメールに心当たりがない場合は、無視していただいて構いません。\n\n";
    $mail_body .= "--\nEKAPRO運営事務局\n";

    send_mail($email, $mail_subject, $mail_body);
}

// 成否に関わらず完了画面へ（メール存在有無を外部に漏らさない）
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール送信完了 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f4f7f6; }
        .auth-box {
            max-width: 480px;
            margin: 80px auto;
            padding: 48px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h2 { color: #004e92; margin-bottom: 16px; }
        p { font-size: 0.92rem; color: #666; line-height: 1.8; margin-bottom: 8px; }
        .btn-back {
            display: inline-block;
            margin-top: 28px;
            padding: 12px 32px;
            background: #004e92;
            color: #fff;
            border-radius: 30px;
            font-size: 0.92rem;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-back:hover { background: #003366; }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <div class="icon">📧</div>
        <h2>メールを送信しました</h2>
        <p>ご登録のメールアドレス宛に<br>パスワード再設定用のURLをお送りしました。</p>
        <p>メールが届かない場合は、迷惑メールフォルダもご確認ください。</p>
        <p><small style="color:#aaa;">URLの有効期限は1時間です。</small></p>
        <a href="login_user.php" class="btn-back">ログイン画面へ戻る</a>
    </div>
</body>
</html>
