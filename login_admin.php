<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理画面ログイン - ERAPRO Admin</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #2c3e50; /* 濃いネイビー */
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: #fff;
            color: #333;
            padding: 40px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        h2 { margin-top: 0; color: #2c3e50; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background: #e74c3c; /* 管理者っぽい赤系アクセント */
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn-submit:hover { background: #c0392b; }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>ERAPRO Admin</h2>
        <p style="font-size:0.8rem; color:#666;">運営管理パネルへログイン</p>
        
        <form action="login_admin_act.php" method="post">
            <input type="text" name="lid" placeholder="Admin ID" required>
            <input type="password" name="lpw" placeholder="Password" required>
            <input type="submit" value="LOGIN" class="btn-submit">
        </form>
    </div>

</body>
</html>