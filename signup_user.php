<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>一般会員登録 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 一般向け：明るい白ベース */
        body { background-color: #f9f9f9; }
        .auth-box { 
            max-width: 480px; 
            margin: 60px auto; 
            padding: 40px; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        h2 { text-align: center; color: #004e92; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; font-size: 0.9rem; }
        input[type="text"], input[type="password"] { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        .btn-submit { 
            width: 100%; padding: 15px; background: #004e92; color: #fff; 
            border: none; border-radius: 30px; font-size: 1.1rem; font-weight: bold; 
            cursor: pointer; margin-top: 10px; transition: 0.3s;
            box-shadow: 0 4px 10px rgba(0,78,146,0.2);
        }
        .btn-submit:hover { background: #003366; transform: translateY(-2px); }
        .link-area { text-align: center; margin-top: 25px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <h2>無料会員登録</h2>
        <p style="text-align:center; font-size:0.9rem; color:#666; margin-bottom:30px;">
            自分に合うプロフェッショナルを見つけよう。
        </p>
        
        <form action="insert.php" method="post">
            <div class="form-group">
                <label>お名前</label>
                <input type="text" name="name" required placeholder="例: 山田 太郎">
            </div>

            <div class="form-group">
                <label>メールアドレス (ログインID)</label>
                <input type="text" name="lid" required placeholder="example@email.com">
            </div>

            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="lpw" required placeholder="半角英数字8文字以上推奨">
            </div>

            <input type="hidden" name="user_type" value="user">

            <input type="submit" value="登録してプロを探す" class="btn-submit">
        </form>

        <div class="link-area">
            すでにアカウントをお持ちの方は<br>
            <a href="login_user.php" style="color:#004e92; font-weight:bold;">こちらからログイン</a>
        </div>
    </div>

</body>
</html>