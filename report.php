<?php
session_start();
include("function.php");
loginCheck('agent');
check_agent_approval();

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

// ── 属性分析（user_id カラムが未追加の場合は空で続行） ──
$area_labels         = [];
$area_data           = [];
$diag_labels         = [];
$diag_data           = [];
$logged_viewer_count = 0;

try {
    // エリア別閲覧割合（過去30日・ログインユーザーのみ）
    $stmt = $pdo->prepare(
        "SELECT COALESCE(NULLIF(TRIM(u.area),''), '未設定') AS label, COUNT(*) AS cnt
         FROM profile_views pv
         LEFT JOIN users u ON pv.user_id = u.id
         WHERE pv.agent_id = :id
           AND pv.user_id IS NOT NULL
           AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY u.area
         ORDER BY cnt DESC
         LIMIT 8"
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $area_labels[] = $r['label'];
        $area_data[]   = (int)$r['cnt'];
    }

    // 診断タイプ別閲覧割合（過去30日・ログインユーザーのみ）
    $stmt = $pdo->prepare(
        "SELECT COALESCE(NULLIF(TRIM(u.diagnosis_type),''), '未設定') AS label, COUNT(*) AS cnt
         FROM profile_views pv
         LEFT JOIN users u ON pv.user_id = u.id
         WHERE pv.agent_id = :id
           AND pv.user_id IS NOT NULL
           AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY u.diagnosis_type
         ORDER BY cnt DESC
         LIMIT 8"
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $diag_labels[] = $r['label'];
        $diag_data[]   = (int)$r['cnt'];
    }

    // ログインユーザー閲覧者数（ユニーク、過去30日）
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM profile_views
         WHERE agent_id=:id AND user_id IS NOT NULL
           AND viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $logged_viewer_count = (int)$stmt->fetchColumn();

} catch (PDOException $e) {
    // user_id カラム未追加の環境では空のまま続行
}

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
        /* ── 属性分析セクション ── */
        .section-sub {
            font-size: 0.82rem;
            color: #999;
            margin: -2px 0 20px;
            line-height: 1.5;
        }
        .doughnut-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
        }
        .doughnut-wrap h4 {
            font-size: 0.86rem;
            font-weight: 700;
            color: #555;
            text-align: center;
            margin-bottom: 10px;
        }
        .doughnut-container {
            position: relative;
            height: 230px;
        }
        .no-attr-data {
            grid-column: 1 / -1;
            text-align: center;
            padding: 48px 20px;
            color: #ccc;
            font-size: 0.9rem;
            background: #fafafa;
            border-radius: 8px;
            border: 1.5px dashed #e8e8e8;
        }
        .no-attr-data .no-attr-icon {
            display: block;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* ── プレミアムティーザー ── */
        .teaser-card {
            background: linear-gradient(135deg, #0d1f3c 0%, #004e92 60%, #0077cc 100%);
            border-radius: 16px;
            padding: 52px 40px 44px;
            text-align: center;
            color: #fff;
            margin-top: 24px;
            position: relative;
            overflow: hidden;
        }
        .teaser-card::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 260px; height: 260px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            pointer-events: none;
        }
        .teaser-card::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            pointer-events: none;
        }
        .teaser-lock {
            font-size: 2.8rem;
            display: block;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .teaser-card h3 {
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1.7;
            color: #fff;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .teaser-card h3 strong {
            color: #f4c430;
            font-size: 1.45rem;
        }
        .teaser-card .teaser-desc {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.72);
            line-height: 1.8;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .teaser-features {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .teaser-feature {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 0.82rem;
            color: #fff;
            min-width: 130px;
            backdrop-filter: blur(4px);
        }
        .teaser-feature .feat-icon {
            display: block;
            font-size: 1.4rem;
            margin-bottom: 6px;
        }
        .btn-upgrade {
            display: inline-block;
            padding: 16px 44px;
            background: #f4c430;
            color: #1a2a4a;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
            position: relative;
            z-index: 1;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(244,196,48,0.35);
        }
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(244,196,48,0.5);
        }
        .teaser-note {
            font-size: 0.74rem;
            color: rgba(255,255,255,0.38);
            margin-top: 16px;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 640px) {
            .doughnut-grid { grid-template-columns: 1fr; }
            .teaser-card { padding: 36px 20px 32px; }
            .teaser-features { gap: 10px; }
            .teaser-feature { min-width: 110px; padding: 12px 14px; }
            .btn-upgrade { padding: 14px 28px; font-size: 0.93rem; }
        }
    </style>
</head>
<body>

    <?php include("header_agent.php"); ?>

    <div class="dashboard">
        <aside class="sidebar">
            <img src="<?= h($img) ?>" class="sidebar-avatar" alt="プロフィール">
            <ul>
                <li><a href="mypage.php" class="sidebar-link">
                    <span class="material-icons-outlined sidebar-icon">dashboard</span>ダッシュボード
                </a></li>
                <li><a href="edit.php" class="sidebar-link">
                    <span class="material-icons-outlined sidebar-icon">person</span>プロフィール編集
                </a></li>
                <li><a href="messages_list.php" class="sidebar-link">
                    <span class="material-icons-outlined sidebar-icon">chat</span>メッセージ
                </a></li>
                <li><a href="customer_list.php" class="sidebar-link">
                    <span class="material-icons-outlined sidebar-icon">people</span>顧客リスト
                </a></li>
                <li><a href="report.php" class="sidebar-link active">
                    <span class="material-icons-outlined sidebar-icon">analytics</span>レポート
                </a></li>
            </ul>
            <a href="profile.php?id=<?= $id ?>" target="_blank" class="sidebar-public-btn">自分の公開ページを見る</a>
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

            <!-- 閲覧ユーザーの属性分析 -->
            <div class="chart-card">
                <h3>
                    <span class="material-icons-outlined" style="color:#004e92;">group</span>
                    👤 閲覧ユーザーの属性分析
                </h3>
                <p class="section-sub">過去30日間にプロフィールを閲覧したログイン済みユーザーの属性（<?= number_format($logged_viewer_count) ?>人）</p>

                <div class="doughnut-grid">
                    <?php if (empty($area_data) && empty($diag_data)): ?>
                        <div class="no-attr-data">
                            <span class="no-attr-icon">📊</span>
                            まだデータが蓄積されていません。<br>
                            ユーザーがログイン状態でプロフィールを閲覧すると属性データが表示されます。
                        </div>
                    <?php else: ?>
                        <div class="doughnut-wrap">
                            <h4>エリア別 閲覧割合</h4>
                            <?php if (!empty($area_data)): ?>
                            <div class="doughnut-container">
                                <canvas id="areaChart"></canvas>
                            </div>
                            <?php else: ?>
                            <div class="no-attr-data" style="padding:28px 16px;">データなし</div>
                            <?php endif; ?>
                        </div>
                        <div class="doughnut-wrap">
                            <h4>診断タイプ別 閲覧割合</h4>
                            <?php if (!empty($diag_data)): ?>
                            <div class="doughnut-container">
                                <canvas id="diagChart"></canvas>
                            </div>
                            <?php else: ?>
                            <div class="no-attr-data" style="padding:28px 16px;">データなし</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php /* TODO: プレミアムプラン導入時にロックUIを復活させる
            <!-- プレミアムプラン ティーザー -->
            <div class="teaser-card">
                <span class="teaser-lock">🔒</span>
                <h3>
                    過去30日間にあなたに興味を持ったユーザー<br>
                    <strong><?= number_format($logged_viewer_count) ?>人</strong>の詳細プロフィールを閲覧できます
                </h3>
                <p class="teaser-desc">
                    ダイレクトスカウト機能で気になるユーザーに直接メッセージを送り、<br>
                    待ちの営業から、攻めの営業へ。成約率を大幅に改善しましょう。
                </p>
                <div class="teaser-features">
                    <div class="teaser-feature">
                        <span class="feat-icon">📋</span>
                        閲覧ユーザーの<br>詳細プロフィール
                    </div>
                    <div class="teaser-feature">
                        <span class="feat-icon">✉️</span>
                        ダイレクト<br>スカウト送信
                    </div>
                    <div class="teaser-feature">
                        <span class="feat-icon">📈</span>
                        詳細な<br>アクセス解析
                    </div>
                    <div class="teaser-feature">
                        <span class="feat-icon">🏅</span>
                        プレミアム<br>バッジ表示
                    </div>
                </div>
                <a href="#" class="btn-upgrade">
                    プレミアムプランにアップグレード（月額 ¥3,980）
                </a>
                <p class="teaser-note">※ 現在準備中のモックアップです。正式リリース時にご案内いたします。</p>
            </div>
            */ ?>

        </main>
    </div>

    <script>
    const CHART_COLORS = [
        '#004e92','#f4c430','#2ecc71','#e74c3c',
        '#9b59b6','#1abc9c','#e67e22','#95a5a6',
        '#3498db','#e91e63'
    ];

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

    // ── エリア別ドーナツチャート ──
    const areaLabels = <?= json_encode($area_labels) ?>;
    const areaData   = <?= json_encode($area_data) ?>;
    if (areaLabels.length > 0 && document.getElementById('areaChart')) {
        new Chart(document.getElementById('areaChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: areaLabels,
                datasets: [{
                    data: areaData,
                    backgroundColor: CHART_COLORS.slice(0, areaLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, color: '#666', boxWidth: 12, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                const total = c.dataset.data.reduce((a,b) => a+b, 0);
                                const pct   = total > 0 ? Math.round(c.parsed / total * 100) : 0;
                                return c.label + ': ' + c.parsed + '件 (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ── 診断タイプ別ドーナツチャート ──
    const diagLabels = <?= json_encode($diag_labels) ?>;
    const diagData   = <?= json_encode($diag_data) ?>;
    if (diagLabels.length > 0 && document.getElementById('diagChart')) {
        new Chart(document.getElementById('diagChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: diagLabels,
                datasets: [{
                    data: diagData,
                    backgroundColor: CHART_COLORS.slice(0, diagLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, color: '#666', boxWidth: 12, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                const total = c.dataset.data.reduce((a,b) => a+b, 0);
                                const pct   = total > 0 ? Math.round(c.parsed / total * 100) : 0;
                                return c.label + ': ' + c.parsed + '件 (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    </script>

</body>
</html>
