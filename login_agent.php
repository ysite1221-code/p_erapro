<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>募集人ログイン - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #333; } /* 管理者っぽく背景を暗く */
        .auth-box { 
            max-width: 400px; margin: 100px auto; padding: 40px; 
            background: #fff; border-radius: 5px; text-align: center; 
        }
        .btn-login { background: #004e92; color: #fff; width: 100%; padding: 10px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <header style="background:#fff; padding:10px;">
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="auth-box">
        <h2 style="color:#004e92;">Professional Login</h2>
        <p style="font-size:0.8rem; margin-bottom:20px;">募集人・管理画面へログイン</p>
        
        <form action="login_act.php" method="post">
            <div style="margin-bottom:15px; text-align:left;">
                <label>Login ID</label>
                <input type="text" name="lid" style="width:100%; padding:10px; box-sizing:border-box;" required>
            </div>
            
            <div style="margin-bottom:20px; text-align:left;">
                <label>Password</label>
                <input type="password" name="lpw" style="width:100%; padding:10px; box-sizing:border-box;" required>
            </div>

            <input type="hidden" name="user_type" value="agent">

            <input type="submit" value="ログイン" class="btn-login">
        </form>

        <div style="margin-top:20px;">
            <a href="signup.php" style="font-size:0.9rem;">アカウント登録はこちら</a>
        </div>
    </div>
</body>
</html>