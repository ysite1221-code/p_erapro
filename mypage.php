<?php
session_start();
include("function.php");
loginCheck('agent');

$id  = (int)$_SESSION["id"];
$pdo = db_conn();

// Agent情報
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

// 画像パス
$img = !empty($row['profile_img'])
    ? 'uploads/' . $row['profile_img']
    : 'https://placehold.co/150x150/e0e0e0/888?text=No+Img';

// ── profile_views テーブルがなければ作成 ──
$pdo->exec("CREATE TABLE IF NOT EXISTS profile_views (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    agent_id  INT NOT NULL,
    viewer_ip VARCHAR(45) DEFAULT NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agent_date (agent_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── KPI: 今月の閲覧数 ──
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM profile_views
     WHERE agent_id=:id
       AND viewed_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$monthly_views = (int)$stmt->fetchColumn();

// ── KPI: 本日の閲覧数 ──
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM profile_views
     WHERE agent_id=:id AND DATE(viewed_at) = CURDATE()"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$today_views = (int)$stmt->fetchColumn();

// ── favorites テーブルがなければ作成 ──
$pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    agent_id   INT NOT NULL,
    status     TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_agent (user_id, agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── KPI: お気に入り登録数（status=1） ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE agent_id=:id AND status=1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$fav_count = (int)$stmt->fetchColumn();

// ── KPI: My Agent 登録数（status=2） ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE agent_id=:id AND status=2");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$myagent_count = (int)$stmt->fetchColumn();

// ── 最近の閲覧履歴（直近10件、時刻のみ） ──
$stmt = $pdo->prepare(
    "SELECT viewed_at, viewer_ip
     FROM profile_views
     WHERE agent_id=:id
     ORDER BY viewed_at DESC LIMIT 10"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$recent_views = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── プロフィール完成度スコア ──
$completion_items = [
    'profile_img' => 'プロフィール写真',
    'title'       => 'キャッチコピー',
    'area'        => '活動エリア',
    'tags'        => 'タグ',
    'story'       => 'My Story',
    'philosophy'  => 'Philosophy',
];
$filled = 0;
foreach ($completion_items as $field => $_) {
    if (!empty($row[$field])) $filled++;
}
$completion_pct = (int)round($filled / count($completion_items) * 100);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ダッシュボード - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .kpi-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
            border-top: 4px solid #004e92;
        }
        .kpi-card.today  { border-top-color: #2e7d32; }
        .kpi-card.fav    { border-top-color: #e91e63; }
        .kpi-card.myagent{ border-top-color: #f59e0b; }
        .kpi-label { font-size: 0.82rem; color: #666; margin-bottom: 10px; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: #333; line-height: 1; }
        .kpi-unit  { font-size: 0.9rem; color: #999; font-weight: normal; }

        .alert-box {
            background: #e3f2fd;
            border-left: 5px solid #004e92;
            padding: 14px 18px;
            margin-bottom: 24px;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        /* プロフィール完成度バー */
        .completion-wrap {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .completion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .completion-label { font-size: 0.9rem; font-weight: 700; color: #333; }
        .completion-pct   { font-size: 1.4rem; font-weight: 800; color: #004e92; }
        .progress-bar-bg  { background: #eee; border-radius: 8px; height: 10px; }
        .progress-bar-fill{
            height: 10px;
            border-radius: 8px;
            background: linear-gradient(90deg, #004e92, #4db6ff);
            transition: width 0.6s ease;
        }
        .completion-items {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .ci-chip {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 12px;
        }
        .ci-done { background: #e8f5e9; color: #2e7d32; }
        .ci-miss { background: #fce4ec; color: #c62828; }

        /* 最近の閲覧 */
        .activity-wrap {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .activity-wrap h3 { font-size: 1rem; color: #333; margin: 0 0 14px; }
        .activity-list { list-style: none; padding: 0; margin: 0; }
        .activity-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.88rem;
            color: #555;
        }
        .activity-list li:last-child { border-bottom: none; }
        .act-icon { font-size: 1.1rem; }
        .act-time { margin-left: auto; color: #999; font-size: 0.82rem; }

        /* プロフィールカード */
        .info-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 24px;
        }
        .info-card h3 {
            margin: 0 0 14px;
            font-size: 1rem;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
                <img src="<?= h($img) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:50%; border:2px solid #eee;">
            </div>
            <ul>
                <li><a href="mypage.php" class="active">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">dashboard</span>ダッシュボード
                </a></li>
                <li><a href="edit.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">person</span>プロフィール編集
                </a></li>
                <li><a href="messages_list.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">chat</span>メッセージ
                </a></li>
                <li><a href="#" style="color:#aaa;">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">people</span>顧客リスト (準備中)
                </a></li>
                <li><a href="#" style="color:#aaa;">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">analytics</span>レポート (準備中)
                </a></li>
            </ul>
            <div style="margin-top:30px; text-align:center;">
                <a href="profile.php?id=<?= $id ?>" target="_blank" class="btn-edit" style="width:100%; box-sizing:border-box; background:#555;">自分の公開ページを見る</a>
            </div>
        </aside>

        <main class="main-content">

            <h2>活動サマリー</h2>

            <?php if ($completion_pct < 100): ?>
            <div class="alert-box">
                <strong>⚠️ プロフィールが未完成です（<?= $completion_pct ?>%）</strong><br>
                写真やストーリーを充実させることで、マッチング率が大幅に向上します。<br>
                <a href="edit.php" style="color:#004e92; font-weight:bold;">→ 今すぐ編集する</a>
            </div>
            <?php endif; ?>

            <!-- KPIグリッド -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">📊 今月の閲覧数</div>
                    <div class="kpi-value"><?= number_format($monthly_views) ?><span class="kpi-unit"> PV</span></div>
                </div>
                <div class="kpi-card today">
                    <div class="kpi-label">🔍 本日の閲覧数</div>
                    <div class="kpi-value"><?= number_format($today_views) ?><span class="kpi-unit"> PV</span></div>
                </div>
                <div class="kpi-card fav">
                    <div class="kpi-label">❤️ お気に入り登録</div>
                    <div class="kpi-value"><?= number_format($fav_count) ?><span class="kpi-unit"> 人</span></div>
                </div>
                <div class="kpi-card myagent">
                    <div class="kpi-label">⭐ My Agent 登録</div>
                    <div class="kpi-value"><?= number_format($myagent_count) ?><span class="kpi-unit"> 人</span></div>
                </div>
            </div>

            <!-- プロフィール完成度 -->
            <div class="completion-wrap">
                <div class="completion-header">
                    <span class="completion-label">プロフィール完成度</span>
                    <span class="completion-pct"><?= $completion_pct ?>%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width:<?= $completion_pct ?>%;"></div>
                </div>
                <div class="completion-items">
                    <?php foreach ($completion_items as $field => $label): ?>
                    <span class="ci-chip <?= !empty($row[$field]) ? 'ci-done' : 'ci-miss' ?>">
                        <?= !empty($row[$field]) ? '✓' : '✗' ?> <?= $label ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 最近の閲覧アクティビティ -->
            <div class="activity-wrap">
                <h3>最近のプロフィール閲覧（直近10件）</h3>
                <?php if (empty($recent_views)): ?>
                    <p style="color:#999; font-size:0.9rem;">まだ閲覧されていません。プロフィールを充実させてシェアしましょう！</p>
                <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recent_views as $v): ?>
                    <li>
                        <span class="act-icon">👤</span>
                        <span>プロフィールが閲覧されました</span>
                        <span class="act-time"><?= h(date('m/d H:i', strtotime($v['viewed_at']))) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- 登録情報確認 -->
            <div class="info-card">
                <h3>登録情報の確認 <a href="edit.php" style="font-size:0.85rem; font-weight:normal; color:#004e92;">編集する</a></h3>
                <hr style="border:0; border-top:1px solid #eee; margin-bottom:14px;">
                <p><strong>活動名:</strong> <?= h($row["name"]) ?></p>
                <p><strong>エリア:</strong> <?= h($row["area"] ?: '未設定') ?></p>
                <p><strong>キャッチコピー:</strong> <?= h($row["title"] ?: '未設定') ?></p>
                <p><strong>タグ:</strong> <?= h($row["tags"] ?: '未設定') ?></p>
                <?php if (!empty($row['story'])): ?>
                <p style="color:#666; font-size:0.9rem; margin-top:10px; line-height:1.6;">
                    <?= h(mb_substr($row["story"], 0, 100)) ?>...
                </p>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>
</html>
