<?php
session_start();
include("function.php");
loginCheck();

$id = $_SESSION["id"];
$pdo = db_conn();

// 1. ユーザー情報の取得
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status == false) {
    sql_error($stmt);
} else {
    $row = $stmt->fetch();
}

// 画像パス
$img = !empty($row['profile_img']) ? 'uploads/' . $row['profile_img'] : 'https://placehold.co/150x150/e0e0e0/888?text=No+Img';

// --- ダミーデータ（本来はDBからCOUNTなどで取得しますが、まずは雰囲気を作るため） ---
$access_count = 128;      // 今月のプロフィール閲覧数
$message_count = 3;       // 未読メッセージ数
$rating_score = 4.8;      // ユーザー評価平均
$contract_count = 12;     // 成約数
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ダッシュボード - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* ダッシュボード固有の追加スタイル */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .kpi-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        .kpi-label { font-size: 0.9rem; color: #666; margin-bottom: 10px; }
        .kpi-value { font-size: 2rem; font-weight: bold; color: #333; }
        .kpi-unit { font-size: 1rem; color: #999; }
        
        .alert-box {
            background-color: #e3f2fd;
            border-left: 5px solid #004e92;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <div class="admin-header">
        <div class="logo">ERAPRO Agent <span style="font-size:0.8rem; font-weight:normal;">Dashboard</span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <span style="font-size:0.9rem;">こんにちは、<?= h($row["name"]) ?> さん</span>
            <a href="logout.php" style="color:#fff; text-decoration:underline; font-size:0.8rem;">ログアウト</a>
        </div>
    </div>

    <div class="dashboard">
        <aside class="sidebar">
            <div style="text-align:center; margin-bottom:20px;">
                <img src="<?= $img ?>" style="width:80px; height:80px; object-fit:cover; border-radius:50%; border:2px solid #eee;">
            </div>
            <ul>
                <li><a href="mypage.php" class="active"><span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">dashboard</span>ダッシュボード</a></li>
                <li><a href="edit.php"><span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">person</span>プロフィール編集</a></li>
                <li><a href="#" style="color:#aaa;"><span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">chat</span>メッセージ (準備中)</a></li>
                <li><a href="#" style="color:#aaa;"><span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">people</span>顧客リスト (準備中)</a></li>
                <li><a href="#" style="color:#aaa;"><span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">analytics</span>レポート (準備中)</a></li>
            </ul>
            <div style="margin-top:30px; text-align:center;">
                <a href="profile.php?id=<?= $id ?>" target="_blank" class="btn-edit" style="width:100%; box-sizing:border-box; background:#555;">自分の公開ページを見る</a>
            </div>
        </aside>

        <main class="main-content">
            
            <h2>活動サマリー</h2>

            <?php if(empty($row['profile_img']) || empty($row['story'])): ?>
                <div class="alert-box">
                    <strong>⚠️ プロフィールが未完成です</strong><br>
                    写真やストーリーを充実させることで、マッチング率が大幅に向上します。<br>
                    <a href="edit.php" style="color:#004e92; font-weight:bold;">→ 編集する</a>
                </div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">今月のプロフィール閲覧</div>
                    <div class="kpi-value"><?= $access_count ?><span class="kpi-unit"> PV</span></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">新着メッセージ</div>
                    <div class="kpi-value" style="color:#d32f2f;"><?= $message_count ?><span class="kpi-unit"> 件</span></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">ユーザー評価</div>
                    <div class="kpi-value" style="color:#fbc02d;">★ <?= $rating_score ?></div>
                </div>
            </div>

            <div class="profile-card" style="display:block;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0;">登録情報の確認</h3>
                    <a href="edit.php" style="color:#004e92; font-size:0.9rem;">編集する</a>
                </div>
                <hr style="border:0; border-top:1px solid #eee; margin-bottom:15px;">
                
                <p><strong>活動名:</strong> <?= h($row["name"]) ?></p>
                <p><strong>エリア:</strong> <?= h($row["area"]) ?></p>
                <p><strong>キャッチコピー:</strong> <?= h($row["title"]) ?></p>
                <p><strong>タグ:</strong> <?= h($row["tags"]) ?></p>
                <p style="color:#666; font-size:0.9rem; margin-top:10px;">
                    <?= h(mb_substr($row["story"] ?? '', 0, 80)) ?>...
                </p>
            </div>

        </main>
    </div>

</body>
</html>