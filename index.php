<?php session_start(); include("function.php"); ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERAPRO - 人で選ぶ保険</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== ヒーロー上書き（index専用） ===== */
        .hero { padding: 140px 24px 120px; }
        .hero h1 { font-size: 3.4rem; }
        .hero-sub {
            font-size: 1.05rem;
            opacity: 0.75;
            margin-top: 10px;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* ===== 特徴セクション ===== */
        .features {
            max-width: 1040px;
            margin: 96px auto 0;
            padding: 0 28px;
        }
        .features-heading {
            text-align: center;
            font-size: 1.9rem;
            font-weight: 900;
            color: #111;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        .features-lead {
            text-align: center;
            font-size: 0.95rem;
            color: #888;
            margin-bottom: 56px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 28px;
        }
        .feature-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 40px 32px 36px;
        }
        .feature-num {
            font-size: 0.75rem;
            font-weight: 700;
            color: #004e92;
            letter-spacing: 0.12em;
            margin-bottom: 16px;
            display: block;
        }
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 900;
            color: #111;
            margin: 0 0 14px;
            letter-spacing: -0.01em;
        }
        .feature-card p {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.85;
            margin: 0;
        }

        /* ===== CTAバナー ===== */
        .cta-banner {
            max-width: 1040px;
            margin: 96px auto 0;
            padding: 0 28px;
        }
        .cta-inner {
            background: linear-gradient(135deg, #004e92, #000e2c);
            border-radius: 8px;
            padding: 64px 48px;
            text-align: center;
            color: #fff;
        }
        .cta-inner h2 {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        .cta-inner p {
            font-size: 0.95rem;
            opacity: 0.75;
            margin-bottom: 36px;
        }
        .btn-cta {
            display: inline-block;
            background: #fff;
            color: #004e92;
            font-weight: 700;
            font-size: 1rem;
            padding: 16px 52px;
            border-radius: 6px;
            letter-spacing: 0.03em;
            transition: all 0.2s;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }
        .btn-cta:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(0,0,0,0.2);
        }

        /* ===== フッター ===== */
        footer {
            margin-top: 112px;
            padding: 48px 28px;
            border-top: 1px solid #ebebeb;
            background: #fff;
            text-align: center;
        }
        .footer-inner {
            max-width: 1040px;
            margin: 0 auto;
        }
        footer p {
            font-size: 0.82rem;
            color: #aaa;
            margin: 0 0 10px;
        }
        footer a { font-size: 0.82rem; color: #999; }
        footer a:hover { color: #004e92; }
    </style>
</head>
<body>

    <?php include("header.php"); ?>

    <!-- ヒーロー -->
    <div class="hero">
        <p class="hero-sub">INSURANCE × REPUTATION</p>
        <h1>保険選びは、<br>「商品」から「人」へ。</h1>
        <p>あなたの価値観に合うプロフェッショナルが、<br>きっと見つかります。</p>
        <a href="search.php" class="btn-search">プロフェッショナルを探す</a>
    </div>

    <!-- 特徴セクション -->
    <div class="features">
        <h2 class="features-heading">ERAPROの3つの特徴</h2>
        <p class="features-lead">実力はユーザーが決める。それが、ERAPROのREPUTATION。</p>
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-num">01 / STORY</span>
                <h3>想いを知る</h3>
                <p>経歴や原体験を読むことで、資格や実績だけでは見えない、信頼できるパートナーを選べます。</p>
            </div>
            <div class="feature-card">
                <span class="feature-num">02 / MATCH</span>
                <h3>相性で選ぶ</h3>
                <p>「子育て」「経営者」「相続」など、タグ検索とAI診断で、あなたにフィットする人を探せます。</p>
            </div>
            <div class="feature-card">
                <span class="feature-num">03 / TRUST</span>
                <h3>クチコミで確かめる</h3>
                <p>実際に相談したユーザーの評価とコメントが積み上がります。無理な勧誘はありません。</p>
            </div>
        </div>
    </div>

    <!-- CTAバナー -->
    <div class="cta-banner">
        <div class="cta-inner">
            <h2>まず、探してみよう。</h2>
            <p>全国のERAPROメンバーが、あなたの相談を待っています。</p>
            <a href="search.php" class="btn-cta">プロを探す</a>
        </div>
    </div>

    <!-- フッター -->
    <footer>
        <div class="footer-inner">
            <p>&copy; 2026 ERAPRO</p>
            <a href="agent_lp.php">保険募集人の掲載登録について</a>
        </div>
    </footer>

</body>
</html>
