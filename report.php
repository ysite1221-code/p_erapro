<?php
session_start();
include("function.php");
loginCheck('agent');

$id  = (int)$_SESSION["id"];
$pdo = db_conn();

// Agent情報（サイドバー用）
$stmt = $pdo->prepare("SELECT profile_img, name FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
$img = !empty($agent['profile_img'])
    ? 'uploads/' . $agent['profile_img']
    : 'https://placehold.co/150x150/e0e0e0/888?text=No+Img';

// ── KPI: 今月の閲覧数 ──
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM profile_views
     WHERE agent_id=:id AND viewed_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"
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

// ── 過去30日間のプロフィール閲覧数グラフ ──
$stmt = $pdo->prepare(
    "SELECT DATE(viewed_at) AS view_date, COUNT(*) AS cnt
     FROM profile_views
     WHERE agent_id = :id
       AND viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(viewed_at)
     ORDER BY view_date ASC"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$view_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 日付配列を生成して0件の日も埋める
$view_map = [];
foreach ($view_rows as $r) {
    $view_map[$r['view_date']] = (int)$r['cnt'];
}
$chart_labels = [];
$chart_data   = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('m/d', strtotime($d));
    $chart_data[]   = $view_map[$d] ?? 0;
}

// ── お気に入り・My Agent 推移（先月 vs 今月） ──
$this_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end   = date('Y-m-t', strtotime('-1 month'));

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM favorites
     WHERE agent_id=:id AND status=1
       AND updated_at >= :start AND updated_at <= :end"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':start', $last_month_start);
$stmt->bindValue(':end', $last_month_end . ' 23:59:59');
$stmt->execute();
$last_fav = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM favorites
     WHERE agent_id=:id AND status=1
       AND updated_at >= :start"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':start', $this_month_start);
$stmt->execute();
$this_fav = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM favorites
     WHERE agent_id=:id AND status=2
       AND updated_at >= :start AND updated_at <= :end"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':start', $last_month_start);
$stmt->bindValue(':end', $last_month_end . ' 23:59:59');
$stmt->execute();
$last_myagent = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM favorites
     WHERE agent_id=:id AND status=2
       AND updated_at >= :start"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':start', $this_month_start);
$stmt->execute();
$this_myagent = (int)$stmt->fetchColumn();

// ── メッセージ統計 ──
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM messages
     WHERE sender_type=1 AND receiver_id=:id"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$total_received = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM messages
     WHERE sender_type=1 AND receiver_id=:id AND is_read=0"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$unread_count = (int)$stmt->fetchColumn();

// 増減計算
$fav_diff     = $this_fav - $last_fav;
$myagent_diff = $this_myagent - $last_myagent;

function diff_html($diff) {
    if ($diff > 0) return '<span style="color:#2e7d32; font-weight:bold;">+' . $diff . '</span>';
    if ($diff < 0) return '<span style="color:#c62828; font-weight:bold;">' . $diff . '</span>';
    return '<span style="color:#999;">±0</span>';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レポート - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* KPIグリッド（mypage.php流用） */
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
        .kpi-card.today   { border-top-color: #2e7d32; }
        .kpi-card.fav     { border-top-color: #e91e63; }
        .kpi-card.myagent { border-top-color: #f59e0b; }
        .kpi-label { font-size: 0.82rem; color: #666; margin-bottom: 10px; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: #333; line-height: 1; }
        .kpi-unit  { font-size: 0.9rem; color: #999; font-weight: normal; }

        /* チャートカード */
        .chart-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 24px;
            margin-bottom: 24px;
        }
        .chart-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* 比較カードグリッド */
        .compare-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .compare-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px 24px;
        }
        .compare-card h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 14px;
        }
        .compare-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.88rem;
        }
        .compare-row:last-child { border-bottom: none; }
        .compare-label { color: #666; }
        .compare-value { font-weight: 700; font-size: 1.1rem; color: #333; }

        /* メッセージ統計 */
        .msg-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .msg-stat-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
            text-align: center;
            border-top: 4px solid #004e92;
        }
        .msg-stat-card.unread-card { border-top-color: #e91e63; }
    </style>
</head>
<body>

    <div class="admin-header">
        <div class="logo">ERAPRO Agent <span style="font-size:0.8rem; font-weight:normal;">Report</span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <span style="font-size:0.9rem;">こんにちは、<?= h($agent["name"]) ?> さん</span>
            <a href="logout.php" style="color:#fff; text-decoration:underline; font-size:0.8rem;">ログアウト</a>
        </div>
    </div>

    <div class="dashboard">
        <aside class="sidebar">
            <div style="text-align:center; margin-bottom:20px;">
                <img src="<?= h($img) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:50%; border:2px solid #eee;">
            </div>
            <ul>
                <li><a href="mypage.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">dashboard</span>ダッシュボード
                </a></li>
                <li><a href="edit.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">person</span>プロフィール編集
                </a></li>
                <li><a href="messages_list.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">chat</span>メッセージ
                </a></li>
                <li><a href="customer_list.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">people</span>顧客リスト
                </a></li>
                <li><a href="report.php" class="active">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">analytics</span>レポート
                </a></li>
            </ul>
            <div style="margin-top:30px; text-align:center;">
                <a href="profile.php?id=<?= $id ?>" target="_blank" class="btn-edit" style="width:100%; box-sizing:border-box; background:#555;">自分の公開ページを見る</a>
            </div>
        </aside>

        <main class="main-content">

            <h2>📈 アクティビティレポート</h2>

            <!-- KPIサマリーカード -->
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

            <!-- 過去30日間の閲覧数グラフ -->
            <div class="chart-card">
                <h3>
                    <span class="material-icons-outlined" style="color:#004e92;">bar_chart</span>
                    過去30日間のプロフィール閲覧数
                </h3>
                <canvas id="viewsChart" height="100"></canvas>
            </div>

            <!-- お気に入り・My Agent 推移比較 -->
            <div class="compare-grid">
                <div class="compare-card">
                    <h4>❤️ お気に入り推移</h4>
                    <div class="compare-row">
                        <span class="compare-label">先月の新規登録</span>
                        <span class="compare-value"><?= $last_fav ?> 人</span>
                    </div>
                    <div class="compare-row">
                        <span class="compare-label">今月の新規登録</span>
                        <span class="compare-value"><?= $this_fav ?> 人</span>
                    </div>
                    <div class="compare-row">
                        <span class="compare-label">増減</span>
                        <span class="compare-value"><?= diff_html($fav_diff) ?></span>
                    </div>
                </div>
                <div class="compare-card">
                    <h4>⭐ My Agent 推移</h4>
                    <div class="compare-row">
                        <span class="compare-label">先月の新規登録</span>
                        <span class="compare-value"><?= $last_myagent ?> 人</span>
                    </div>
                    <div class="compare-row">
                        <span class="compare-label">今月の新規登録</span>
                        <span class="compare-value"><?= $this_myagent ?> 人</span>
                    </div>
                    <div class="compare-row">
                        <span class="compare-label">増減</span>
                        <span class="compare-value"><?= diff_html($myagent_diff) ?></span>
                    </div>
                </div>
            </div>

            <!-- メッセージ統計 -->
            <div class="chart-card">
                <h3>
                    <span class="material-icons-outlined" style="color:#004e92;">chat</span>
                    メッセージ統計
                </h3>
                <div class="msg-stat-grid">
                    <div class="msg-stat-card">
                        <div class="kpi-label">📨 総受信メッセージ数</div>
                        <div class="kpi-value"><?= number_format($total_received) ?><span class="kpi-unit"> 件</span></div>
                    </div>
                    <div class="msg-stat-card unread-card">
                        <div class="kpi-label">🔴 未読メッセージ数</div>
                        <div class="kpi-value"><?= number_format($unread_count) ?><span class="kpi-unit"> 件</span></div>
                        <?php if ($unread_count > 0): ?>
                        <div style="margin-top:10px;">
                            <a href="messages_list.php" style="font-size:0.82rem; color:#004e92; font-weight:bold;">→ 確認する</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
    const labels = <?= json_encode($chart_labels) ?>;
    const data   = <?= json_encode($chart_data) ?>;

    const ctx = document.getElementById('viewsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'プロフィール閲覧数',
                data: data,
                backgroundColor: 'rgba(0, 78, 146, 0.18)',
                borderColor: '#004e92',
                borderWidth: 2,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.y + ' PV';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#888',
                        font: { size: 11 }
                    },
                    grid: { color: '#f0f0f0' }
                },
                x: {
                    ticks: {
                        color: '#888',
                        font: { size: 10 },
                        maxTicksLimit: 10
                    },
                    grid: { display: false }
                }
            }
        }
    });
    </script>

</body>
</html>
