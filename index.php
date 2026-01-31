<?php include("function.php"); ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ERAPRO - 人で選ぶ保険</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 一般ユーザー用：清潔感と安心感 */
        .header-inner { justify-content: space-between; }
        .link-for-agent { font-size: 0.8rem; color: #999; text-decoration: underline; margin-left: 20px; }
        .hero { background: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%); color: #333; } /* 明るく優しい色味に変更 */
        .hero h1 { color: #004e92; }
        .hero p { color: #555; }
        .btn-search { background: #004e92; color: #fff; box-shadow: 0 5px 15px rgba(0,78,146,0.3); }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <div style="display:flex; align-items:center;">
                <div class="logo">ERAPRO</div>
            </div>
            
            <nav>
                <a href="login_user.php" class="btn-login" style="border:1px solid #004e92; color:#004e92;">ログイン</a>
                
                <a href="agent_lp.php" class="link-for-agent">募集人の方はこちら</a>
            </nav>
        </div>
    </header>

    <div class="hero">
        <h1>保険選びは、<br>「商品」から「人」へ。</h1>
        <p>あなたの価値観に合うプロフェッショナルが、<br>きっと見つかります。</p>
        
        <div style="margin-top:30px;">
            <a href="search.php" class="btn-search">プロフェッショナルを探す</a>
        </div>
    </div>

    <div class="container" style="text-align:center; margin-top:60px;">
        <h2 style="color:#004e92;">ERAPROの3つの特徴</h2>
        <div style="display:flex; justify-content:center; gap:30px; margin-top:40px; flex-wrap:wrap;">
            <div style="width:280px;">
                <h3>1. 想いを知る</h3>
                <p>経歴や原体験を知ることで、信頼できるパートナーを選べます。</p>
            </div>
            <div style="width:280px;">
                <h3>2. 相性で選ぶ</h3>
                <p>「子育て」「経営者」など、タグ検索であなたに合う人を探せます。</p>
            </div>
            <div style="width:280px;">
                <h3>3. 納得の相談</h3>
                <p>無理な勧誘はありません。まずはチャットから気軽に相談できます。</p>
            </div>
        </div>
    </div>

    <footer style="background:#f4f4f4; padding:40px 0; margin-top:80px; text-align:center; font-size:0.8rem;">
        <p>&copy; 2026 ERAPRO</p>
        <div style="margin-top:10px;">
            <a href="agent_lp.php" style="color:#666;">保険募集人の掲載登録について</a>
        </div>
    </footer>

</body>
</html>