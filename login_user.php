<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 一般向けは明るい雰囲気で */
        body { background-color: #f4f7f6; }
        .auth-box { 
            max-width: 400px; margin: 100px auto; padding: 40px; 
            background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
            text-align: center;
        }
        .btn-login { background: #ff9a9e; color: #fff; width: 100%; padding: 10px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <h2>ログイン</h2>
        <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">保存したプロフェッショナルを確認しよう</p>
        
        <form action="login_act.php" method="post">
            <div style="margin-bottom:15px; text-align:left;">
                <label>メールアドレス</label>
                <input type="text" name="lid" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;" required>
            </div>
            
            <div style="margin-bottom:20px; text-align:left;">
                <label>パスワード</label>
                <input type="password" name="lpw" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;" required>
            </div>

            <input type="hidden" name="user_type" value="user">

            <input type="submit" value="ログインする" class="btn-login">
        </form>
        
        <?php if (!empty($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <p style="background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7;
                  padding:12px 16px; border-radius:6px; font-size:0.88rem; margin-top:16px;">
            パスワードを変更しました。新しいパスワードでログインしてください。
        </p>
        <?php endif; ?>

        <div style="margin-top:20px;">
            <a href="signup_user.php" style="color:#004e92; font-size:0.9rem;">新規登録はこちら</a>
            <span style="color:#ccc; margin:0 8px;">|</span>
            <a href="password_reset.php?user_type=user" style="color:#999; font-size:0.85rem;">パスワードを忘れた方</a>
        </div>
    </div>
</body>
</html>