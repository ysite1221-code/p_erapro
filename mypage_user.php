<?php
session_start();
include("function.php");
loginCheck(); // ログインチェック

$user_name = $_SESSION["name"];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイページ - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f9f9f9; }
        .mypage-container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .welcome-box { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; margin-bottom: 30px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .menu-card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; transition: 0.3s; color: #333; display: block; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); color: #004e92; }
        .menu-icon { font-size: 2rem; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
            <a href="logout.php" style="font-size:0.9rem; color:#666;">ログアウト</a>
        </div>
    </header>

    <div class="mypage-container">
        
        <div class="welcome-box">
            <h2>ようこそ、<?= h($user_name) ?> さん</h2>
            <p>今日はどんなプロフェッショナルを探しますか？</p>
        </div>

        <div class="menu-grid">
            
            <a href="search.php" class="menu-card">
                <span class="menu-icon">🔍</span>
                <h3>プロを探す</h3>
                <p>条件やタグで検索する</p>
            </a>

            <a href="diagnosis.php" class="menu-card">
                <span class="menu-icon">📋</span>
                <h3>ぴったり診断</h3>
                <p>あなたに合う人を診断する</p>
            </a>

            <div class="menu-card" style="opacity:0.6; cursor:not-allowed;">
                <span class="menu-icon">💬</span>
                <h3>メッセージ</h3>
                <p>準備中...</p>
            </div>
            
            <div class="menu-card" style="opacity:0.6; cursor:not-allowed;">
                <span class="menu-icon">❤️</span>
                <h3>お気に入り</h3>
                <p>準備中...</p>
            </div>

        </div>
    </div>

</body>
</html>