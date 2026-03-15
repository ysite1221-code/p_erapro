<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仮登録完了 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f7f6; }
        .success-wrap {
            max-width: 560px;
            margin: 80px auto 80px;
            padding: 0 20px;
        }
        .success-box {
            background: #fff;
            border-radius: 12px;
            padding: 56px 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            text-align: center;
        }
        .success-icon {
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
        .success-box h2 {
            font-size: 1.4rem;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        .success-box .lead {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: 28px;
        }
        .email-highlight {
            display: inline-block;
            background: #f0f4ff;
            color: #004e92;
            font-weight: bold;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 0.95rem;
            margin-bottom: 28px;
            word-break: break-all;
        }
        .steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px 24px;
            text-align: left;
            margin-bottom: 32px;
        }
        .steps p {
            font-size: 0.85rem;
            font-weight: bold;
            color: #888;
            margin-bottom: 10px;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            font-size: 0.88rem;
            color: #555;
            line-height: 1.8;
        }
        .btn-login {
            display: inline-block;
            padding: 14px 40px;
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
        .notice {
            margin-top: 24px;
            font-size: 0.8rem;
            color: #aaa;
            line-height: 1.6;
        }
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

<div class="success-wrap">
    <div class="success-box">

        <div class="success-icon">📧</div>

        <h2>仮登録が完了しました</h2>

        <?php if (!empty($_GET['email'])): ?>
        <div class="email-highlight">
            <?= htmlspecialchars($_GET['email'], ENT_QUOTES) ?>
        </div>
        <?php endif; ?>

        <p class="lead">
            入力したメールアドレス宛に<strong>認証メール</strong>を送信しました。<br>
            メール内のリンクをクリックして、<br>
            <strong>本登録を完了してください。</strong>
        </p>

        <div class="steps">
            <p>📋 本登録の手順</p>
            <ol>
                <li>届いたメールを開く</li>
                <li>メール内の「メールアドレスを認証する」リンクをクリック</li>
                <li>認証完了後、ログイン画面からログイン</li>
            </ol>
        </div>

        <?php
        $user_type  = $_GET['user_type'] ?? 'user';
        $login_url  = ($user_type === 'agent') ? 'login_agent.php' : 'login_user.php';
        ?>
        <a href="<?= htmlspecialchars($login_url, ENT_QUOTES) ?>" class="btn-login">
            ログイン画面へ
        </a>

        <p class="notice">
            メールが届かない場合は、迷惑メールフォルダをご確認ください。<br>
            それでも届かない場合は、再度登録手続きをお試しください。
        </p>

    </div>
</div>

</body>
</html>
