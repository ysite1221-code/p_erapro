<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワードをお忘れの方 - ERAPRO</title>
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
        .sub { text-align: center; font-size: 0.88rem; color: #777; margin-bottom: 32px; line-height: 1.6; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; font-size: 0.9rem; }
        input[type="email"], input[type="text"] {
            width: 100%; padding: 12px; border: 1px solid #ddd;
            border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        .btn-submit {
            width: 100%; padding: 14px; background: #004e92; color: #fff;
            border: none; border-radius: 30px; font-size: 1rem; font-weight: bold;
            cursor: pointer; transition: 0.3s;
        }
        .btn-submit:hover { background: #003366; }
        .link-area { text-align: center; margin-top: 24px; font-size: 0.88rem; color: #999; }
        .link-area a { color: #004e92; }
        .type-select {
            display: flex; gap: 12px; margin-bottom: 24px;
        }
        .type-select label {
            flex: 1; display: flex; align-items: center; gap: 8px;
            padding: 12px; border: 2px solid #eee; border-radius: 8px;
            cursor: pointer; font-weight: normal; transition: border-color 0.2s;
        }
        .type-select input[type="radio"] { width: auto; }
        .type-select label:has(input:checked) { border-color: #004e92; color: #004e92; }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <h2>パスワードのリセット</h2>
        <p class="sub">
            ご登録のメールアドレスを入力してください。<br>
            パスワード再設定用のURLをお送りします。
        </p>

        <?php if (!empty($_GET['error'])): ?>
        <p style="background:#fff3f3; color:#dc3545; border:1px solid #f5c6cb;
                  padding:12px 16px; border-radius:6px; font-size:0.88rem; margin-bottom:20px;">
            <?= htmlspecialchars($_GET['error'], ENT_QUOTES) ?>
        </p>
        <?php endif; ?>

        <form action="password_reset_act.php" method="post">
            <div class="form-group">
                <label>アカウントの種類</label>
                <div class="type-select">
                    <label>
                        <input type="radio" name="user_type" value="user" checked>
                        一般ユーザー
                    </label>
                    <label>
                        <input type="radio" name="user_type" value="agent">
                        募集人 (Agent)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>登録メールアドレス</label>
                <input type="text" name="email" required placeholder="example@email.com"
                       value="<?= htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES) ?>">
            </div>

            <button type="submit" class="btn-submit">リセットメールを送信</button>
        </form>

        <div class="link-area">
            <a href="login_user.php">← ログイン画面に戻る</a>
        </div>
    </div>
</body>
</html>
