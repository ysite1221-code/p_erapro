<?php include("function.php"); ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>募集人掲載 - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 募集人用：信頼感とプロフェッショナル感（ダーク系） */
        body { background-color: #fff; }
        header { border-bottom: none; background: #222; color: #fff; }
        .logo { color: #fff; }
        .hero-agent { 
            background: #222; color: #fff; text-align: center; padding: 100px 20px; 
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        .btn-register { 
            background: #d4af37; /* ゴールド系 */
            color: #fff; padding: 15px 50px; border-radius: 30px; 
            font-weight: bold; font-size: 1.2rem; display: inline-block;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }
        .btn-agent-login-header {
            color: #fff; border: 1px solid #fff; padding: 5px 15px; border-radius: 4px; font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <div class="logo">ERAPRO <span style="font-size:0.8rem; font-weight:normal;">for Agent</span></div>
            <div>
                <a href="login_agent.php" class="btn-agent-login-header">ログイン</a>
            </div>
        </div>
    </header>

    <div class="hero-agent">
        <h1 style="font-size:2.5rem; margin-bottom:20px;">あなたの「物語」が、<br>最強の集客ツールになる。</h1>
        <p style="opacity:0.8; margin-bottom:40px;">
            ERAPROは、スペックではなく「人柄」で選ばれる<br>
            新しい保険プラットフォームです。
        </p>
        <a href="signup_agent.php" class="btn-register">無料で掲載をはじめる</a>
    </div>

    <div class="container" style="text-align:center; margin-top:60px;">
        <h2 style="margin-bottom:40px; color:#333;">ERAPROが選ばれる理由</h2>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:30px;">
            <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                <h3 style="color:#d4af37;">1. 質の高いリード</h3>
                <p>価格比較ではなく、あなたの考えに共感したユーザーからの問い合わせなので、成約率が高いのが特徴です。</p>
            </div>
            <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                <h3 style="color:#d4af37;">2. セルフブランディング</h3>
                <p>専用のプロフィールページで、原体験や哲学をリッチに表現。名刺代わりとしても活用できます。</p>
            </div>
            <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                <h3 style="color:#d4af37;">3. 簡単な顧客管理</h3>
                <p>問い合わせ対応から面談調整まで、管理画面ひとつで完結。営業活動に集中できます。</p>
            </div>
        </div>

        <div style="background:#f9f9f9; padding:60px 20px; margin-top:80px; border-radius:10px;">
            <h2>まずは無料プランからスタート</h2>
            <p>初期費用0円。リスクなく始められます。</p>
            <div style="margin-top:30px;">
                <a href="signup_agent.php" class="btn-register">今すぐアカウント作成</a>
            </div>
        </div>
    </div>

    <footer style="background:#222; color:#666; padding:20px; text-align:center; margin-top:50px;">
        <p>&copy; 2024 ERAPRO Agent</p>
        <a href="index.php" style="color:#666; font-size:0.8rem;">一般ユーザー向けトップへ</a>
    </footer>

</body>
</html>