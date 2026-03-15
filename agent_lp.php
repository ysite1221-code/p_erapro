<?php session_start(); include("function.php"); ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>募集人掲載 - ERAPRO Agent</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Agent LP専用スタイル (Wantedly風ダークテーマ) */
        body {
            font-family: "Noto Sans JP", sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
        }
        
        /* ヘッダー */
        header {
            background: #111; /* 完全な黒に近いグレー */
            padding: 20px 0;  /* ゆとりを持たせる */
            border-bottom: 1px solid #333;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .btn-header-login {
            color: #fff;
            border: 1px solid #fff;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: 0.3s;
            text-decoration: none;
        }
        .btn-header-login:hover {
            background: #fff;
            color: #111;
        }

        /* ヒーローエリア */
        .hero-agent {
            background: #111;
            color: #fff;
            padding: 180px 20px 100px;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 30px;
            letter-spacing: 0.05em;
        }
        .hero-sub {
            font-size: 1.1rem;
            color: #ccc;
            margin-bottom: 50px;
            line-height: 1.8;
        }
        .btn-cta {
            background: linear-gradient(135deg, #d4af37 0%, #b49328 100%); /* ゴールド */
            color: #fff;
            padding: 18px 60px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            display: inline-block;
            transition: transform 0.3s;
            text-decoration: none;
        }
        .btn-cta:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.6);
        }

        /* コンテンツエリア */
        .section {
            padding: 100px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .section-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 60px;
            font-weight: 700;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        .feature-item {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
        .feature-item h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #111;
        }
        .feature-item p {
            color: #666;
            line-height: 1.8;
            font-size: 0.95rem;
        }

        /* 想いのセクション (Wantedly風) */
        .narrative-section {
            background: #fff;
            padding: 100px 20px;
        }
        .narrative-box {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .narrative-text {
            font-size: 1.2rem;
            line-height: 2.2;
            color: #333;
            font-weight: 500;
        }

        /* フッター */
        footer {
            background: #111;
            color: #666;
            padding: 40px 20px;
            text-align: center;
            font-size: 0.8rem;
        }
        footer a { color: #888; text-decoration: underline; }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">
                <img src="img/logo_white.png" alt="ERAPRO Agent" style="height:60px; vertical-align:middle;">
            </a>
            
            <nav>
                <a href="login_agent.php" class="btn-header-login">ログイン</a>
            </nav>
        </div>
    </header>

    <div class="hero-agent">
        <h1 class="hero-title">
            あなたの「物語」が、<br>
            最強の集客ツールになる。
        </h1>
        <p class="hero-sub">
            ERAPROは、スペック比較ではなく「人柄」で選ばれる<br>
            新しい保険プラットフォームです。<br>
            <br>
            営業工数ゼロで、あなたの価値観に共感する顧客と出会いませんか？
        </p>
        <a href="signup_agent.php" class="btn-cta">無料で掲載をはじめる</a>
        <p style="margin-top:20px; font-size:0.9rem; color:#666;">※審査があります</p>
    </div>

    <div class="narrative-section">
        <div class="narrative-box">
            <h2 class="section-title" style="font-size:1.5rem; color:#d4af37;">WHY ERAPRO?</h2>
            <p class="narrative-text">
                「保険は誰から入っても同じ」<br>
                そんな時代は終わりました。<br>
                <br>
                AIが台頭する今だからこそ、<br>
                あなたの「原体験」「哲学」「顧客への想い」といった<br>
                人間味が、最大の差別化要因になります。<br>
                <br>
                ERAPROは、あなたのプロフェッショナルとしての価値を<br>
                必要としている人に、正しく届けるための場所です。
            </p>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">DXパートナーとしての3つの機能</h2>
        <div class="feature-grid">
            <div class="feature-item">
                <span class="feature-icon">💎</span>
                <h3>ブランディング</h3>
                <p>
                    質問に答えるだけで、あなたの魅力を伝えるリッチなプロフィールページが完成。<br>
                    「原体験」や「哲学」を可視化し、名刺代わりのWebページとして活用できます。
                </p>
            </div>
            <div class="feature-item">
                <span class="feature-icon">🧩</span>
                <h3>相性マッチング</h3>
                <p>
                    独自の「タイプ診断」により、あなたの提案スタイル（論理派・感情派など）と相性の良い顧客を自動でマッチング。<br>
                    ミスマッチを減らし、成約率を高めます。
                </p>
            </div>
            <div class="feature-item">
                <span class="feature-icon">💬</span>
                <h3>スマート顧客管理</h3>
                <p>
                    問い合わせ対応からチャット相談、ステータス管理までを一元化。<br>
                    「未対応」「見込み」「成約」を可視化し、営業活動を効率化します。
                </p>
            </div>
        </div>
    </div>

    <div style="background:#f0f0f0; padding:80px 20px; text-align:center;">
        <h2 style="margin-bottom:20px;">まずは無料プランからスタート</h2>
        <p style="margin-bottom:40px; color:#666;">初期費用0円。リスクなく始められます。</p>
        <a href="signup_agent.php" class="btn-cta">今すぐアカウント作成</a>
    </div>

    <footer>
        <p>&copy; 2026 ERAPRO Agent</p>
        <div style="margin-top:20px;">
            <a href="index.php">一般ユーザー向けトップへ</a>
        </div>
    </footer>

</body>
</html>