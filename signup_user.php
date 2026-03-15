<?php
$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
    '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
    '新潟県','富山県','石川県','福井県','山梨県','長野県',
    '岐阜県','静岡県','愛知県','三重県',
    '滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県',
    '鳥取県','島根県','岡山県','広島県','山口県',
    '徳島県','香川県','愛媛県','高知県',
    '福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];
?>
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
        input[type="text"], input[type="password"], select {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
            background: #fff;
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

            <div class="form-group">
                <label>お住まいの都道府県 <span style="color:#e91e63; font-size:0.8rem;">必須</span></label>
                <select name="area" required>
                    <option value="">-- 選択してください --</option>
                    <?php foreach ($prefectures as $p): ?>
                    <option value="<?= htmlspecialchars($p, ENT_QUOTES) ?>"><?= htmlspecialchars($p, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="user_type" value="user">

            <div class="form-group" style="margin-bottom:24px;">
                <label style="display:flex; align-items:flex-start; gap:10px; font-weight:normal; cursor:pointer;">
                    <input type="checkbox" name="agree_terms" value="1" required
                           style="width:18px; height:18px; margin-top:2px; flex-shrink:0; cursor:pointer;">
                    <span style="font-size:0.88rem; color:#555; line-height:1.6;">
                        <a href="terms.php" target="_blank" style="color:#004e92; font-weight:bold;">利用規約</a>
                        および
                        <a href="privacy.php" target="_blank" style="color:#004e92; font-weight:bold;">プライバシーポリシー</a>
                        に同意する（必須）
                    </span>
                </label>
            </div>

            <input type="submit" value="登録してプロを探す" class="btn-submit">
        </form>

        <div class="link-area">
            すでにアカウントをお持ちの方は<br>
            <a href="login_user.php" style="color:#004e92; font-weight:bold;">こちらからログイン</a>
        </div>
    </div>

</body>
</html>