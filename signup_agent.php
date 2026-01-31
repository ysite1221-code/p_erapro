<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフェッショナル登録 - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 募集人向け：ダークヘッダーに合わせて少しリッチに */
        body { background-color: #f4f4f4; }
        header { background: #222; border-bottom: none; }
        .logo { color: #fff; }
        
        .auth-box { 
            max-width: 500px; 
            margin: 60px auto; 
            padding: 50px; 
            background: #fff; 
            border-radius: 8px; 
            border-top: 5px solid #d4af37; /* ゴールドのアクセント */
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; margin-bottom: 10px; }
        .sub-title { text-align: center; font-size: 0.85rem; color: #666; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 0.9rem; }
        input[type="text"], input[type="password"] { 
            width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1rem;
        }
        
        .btn-submit { 
            width: 100%; padding: 15px; background: #333; color: #fff; 
            border: none; border-radius: 4px; font-size: 1.1rem; font-weight: bold; 
            cursor: pointer; margin-top: 10px; transition: 0.3s;
        }
        .btn-submit:hover { background: #555; }
        
        .link-area { text-align: center; margin-top: 30px; font-size: 0.9rem; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <a href="agent_lp.php" class="logo">ERAPRO <span style="font-size:0.8rem; font-weight:normal;">for Agent</span></a>
        </div>
    </header>

    <div class="auth-box">
        <h2>パートナーアカウント作成</h2>
        <p class="sub-title">あなたの経験と想いを、求めている人へ。</p>
        
        <form action="insert.php" method="post">
            <div class="form-group">
                <label>お名前 (活動名)</label>
                <input type="text" name="name" required placeholder="例: 佐藤 健太">
            </div>

            <div class="form-group">
                <label>メールアドレス (ログインID)</label>
                <input type="text" name="lid" required placeholder="sato@erapro.com">
            </div>

            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="lpw" required placeholder="半角英数字">
            </div>

            <input type="hidden" name="user_type" value="agent">

            <input type="submit" value="アカウントを作成する" class="btn-submit">
        </form>

        <div class="link-area">
            すでにアカウントをお持ちの方は<br>
            <a href="login_agent.php" style="color:#d4af37; font-weight:bold;">パートナーログイン</a>
        </div>
    </div>

</body>
</html>