<?php
session_start();
include("function.php");

$lid       = $_POST["lid"];
$lpw       = $_POST["lpw"];
$user_type = $_POST["user_type"];

$pdo = db_conn();

// 1. SQL実行
if ($user_type === 'agent') {
    $sql = "SELECT * FROM agents WHERE lid=:lid AND life_flg=0";
} else {
    $sql = "SELECT * FROM users WHERE lid=:lid AND life_flg=0";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$status = $stmt->execute();

if ($status == false) {
    sql_error($stmt);
}

$val = $stmt->fetch();

// 2. データがあるか確認
if (!$val) {
    exit("Login Error: IDが見つかりません。登録されていますか？");
}

// 3. メール認証チェック
if (empty($val['email_verified_at'])) {
    $login_page = ($user_type === 'agent') ? 'login_agent.php' : 'login_user.php';
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メール認証未完了 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f7f6; }
        .err-wrap { max-width: 480px; margin: 80px auto; padding: 0 20px; }
        .err-box {
            background: #fff; border-radius: 12px; padding: 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07); text-align: center;
        }
        .err-icon {
            width: 64px; height: 64px; background: #fff8e1; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin: 0 auto 20px;
        }
        h2 { font-size: 1.2rem; color: #e65100; margin-bottom: 14px; }
        p  { font-size: 0.9rem; color: #555; line-height: 1.8; margin-bottom: 8px; }
        .btn-back {
            display: inline-block; margin-top: 24px; padding: 12px 36px;
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
<div class="err-wrap">
    <div class="err-box">
        <div class="err-icon">📧</div>
        <h2>メール認証が完了していません</h2>
        <p>ご登録のメールアドレス宛に認証メールをお送りしています。</p>
        <p>メール内の認証リンクをクリックしてから、ログインしてください。</p>
        <p style="font-size:0.82rem; color:#aaa;">メールが届かない場合は迷惑メールフォルダもご確認ください。</p>
        <a href="<?= htmlspecialchars($login_page, ENT_QUOTES) ?>" class="btn-back">ログイン画面へ戻る</a>
    </div>
</div>
</body>
</html>
    <?php
    exit();
}

// 4. パスワード確認
if (password_verify($lpw, $val['lpw'])) {
    // 成功
    $_SESSION["chk_ssid"]  = session_id();
    $_SESSION["name"]      = $val['name'];
    $_SESSION["id"]        = $val['id'];
    $_SESSION["user_type"] = $user_type;

    if ($user_type === 'agent') {
        redirect("mypage.php");
    } else {
        redirect("mypage_user.php"); // ユーザー用マイページへ！
    }
} else {
    // 失敗
    exit("Login Error: パスワードが違います。");
}
