<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者登録 - ERAPRO</title>
    <style>
        body { font-family: sans-serif; background-color: #333; color: #fff; padding: 50px; text-align: center; }
        .box { background: #fff; color: #333; padding: 20px; width: 300px; margin: 0 auto; border-radius: 5px; }
        input { width: 90%; padding: 10px; margin: 10px 0; }
        button { background: #333; color: #fff; padding: 10px 20px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>管理者アカウント作成</h2>
    <div class="box">
        <form action="admin_register_act.php" method="post">
            <input type="text" name="name" placeholder="名前 (例: 運営太郎)" required>
            <input type="text" name="lid" placeholder="ログインID" required>
            <input type="text" name="lpw" placeholder="パスワード" required>
            <button type="submit">登録する</button>
        </form>
        <p style="margin-top:20px;"><a href="login_admin.php">ログイン画面へ</a></p>
    </div>
</body>
</html>