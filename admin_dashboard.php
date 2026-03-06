<?php
session_start();
include("function.php");

// 1. ログインチェック (管理者かどうか)
if(!isset($_SESSION["chk_ssid"]) || $_SESSION["chk_ssid"]!=session_id() || $_SESSION["user_type"]!='admin'){
    redirect("login_admin.php");
}

$pdo = db_conn();

// 2. データ取得
// A. 未承認（審査待ち）のAgent件数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE verification_status = 1");
$stmt->execute();
$count_pending = $stmt->fetchColumn();

// B. Agent総数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agents");
$stmt->execute();
$count_agents = $stmt->fetchColumn();

// C. User総数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$count_users = $stmt->fetchColumn();

// D. 審査待ちリスト（最新10件）
$sql = "SELECT * FROM agents WHERE verification_status = 1 ORDER BY id DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pending_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ERAPRO 管理画面</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Noto Sans JP', sans-serif; background: #f4f6f9; margin: 0; display: flex; min-height: 100vh; font-size: 14px; color: #333; }

        /* サイドバー */
        .sidebar { width: 240px; background: #1e2330; color: #fff; min-height: 100vh; flex-shrink: 0; }
        .sidebar-brand { padding: 22px 24px; font-size: 0.95rem; font-weight: 900; letter-spacing: 0.08em; border-bottom: 1px solid rgba(255,255,255,0.07); }
        .sidebar-brand span { font-size: 0.65rem; font-weight: 400; color: #888; display: block; margin-top: 2px; }
        .menu a {
            display: flex; align-items: center; gap: 10px;
            color: #9aa0b0; padding: 14px 24px;
            font-size: 0.875rem; font-weight: 500;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s;
        }
        .menu a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .menu a.active { background: rgba(0,123,255,0.15); color: #4da6ff; border-left-color: #007bff; font-weight: 700; }
        .menu .icon { font-size: 1.1rem; }

        /* メインコンテンツ */
        .content { flex: 1; padding: 36px 40px 80px; min-width: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; }
        .header h2 { font-size: 1.6rem; font-weight: 900; color: #111; margin: 0; letter-spacing: -0.02em; }
        .btn-logout { background: #dc3545; color: #fff; padding: 9px 20px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; transition: background 0.2s; }
        .btn-logout:hover { background: #b02a37; color: #fff; }

        /* KPIカード */
        .kpi-container { display: flex; gap: 20px; margin-bottom: 36px; }
        .card {
            flex: 1; background: #fff; padding: 24px 20px; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card h3 { margin: 0 0 8px; font-size: 0.78rem; color: #999; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; }
        .card .value { font-size: 2.4rem; font-weight: 900; color: #111; line-height: 1; letter-spacing: -0.04em; }
        .card.alert { border-left: 4px solid #ffc107; }
        .card.alert .value { color: #d39e00; }

        /* テーブル */
        .table-container {
            background: #fff; border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;
        }
        .table-container > h3 { padding: 18px 24px; margin: 0; font-size: 1rem; font-weight: 700; border-bottom: 1px solid #f0f0f0; }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #fff; color: #888; font-size: 0.73rem; font-weight: 700;
            padding: 12px 20px; text-align: left;
            letter-spacing: 0.06em; text-transform: uppercase; border-bottom: 2px solid #f0f0f0;
        }
        td { padding: 14px 20px; font-size: 0.875rem; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafbff; }
        .btn-check {
            background: #007bff; color: #fff; padding: 6px 14px;
            border-radius: 5px; font-size: 0.78rem; font-weight: 700;
            transition: background 0.2s;
        }
        .btn-check:hover { background: #0062cc; color: #fff; }
        .status-badge {
            background: #fff8e1; color: #d39e00; padding: 3px 10px;
            border-radius: 4px; font-size: 0.72rem; font-weight: 700;
            border: 1px solid #fde68a;
        }

        /* フラッシュメッセージ */
        .flash { padding: 14px 20px; border-radius: 6px; margin-bottom: 24px; font-size: 0.9rem; font-weight: 500; }
        .flash-success { background: #f0faf2; border-left: 4px solid #2e7d32; color: #2e7d32; }
        .flash-danger  { background: #fff0f0; border-left: 4px solid #c62828; color: #c62828; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            ERAPRO ADMIN
            <span>管理パネル</span>
        </div>
        <div class="menu">
            <a href="admin_dashboard.php" class="active"><span class="material-icons-outlined icon">dashboard</span>ダッシュボード</a>
            <a href="admin_agent_list.php"><span class="material-icons-outlined icon">people</span>Agent一覧</a>
            <a href="admin_user_list.php"><span class="material-icons-outlined icon">face</span>User一覧</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>ダッシュボード</h2>
            <div style="display:flex; align-items:center; gap:16px;">
                <span style="font-size:0.82rem; color:#aaa;">管理者: <?= h($_SESSION["name"]) ?></span>
                <a href="logout.php" class="btn-logout">ログアウト</a>
            </div>
        </div>

        <?php if (isset($_GET['result'])): ?>
            <?php if ($_GET['result'] === 'approved'): ?>
                <div class="flash flash-success">✅ Agentを承認しました。メールで通知しました。</div>
            <?php elseif ($_GET['result'] === 'rejected'): ?>
                <div class="flash flash-danger">⚠️ 否認しました。Agentへ再提出依頼メールを送信しました。</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="kpi-container">
            <div class="card alert">
                <div>
                    <h3>KYC審査待ち</h3>
                    <div class="value" style="color:#d39e00;"><?= $count_pending ?></div>
                </div>
                <span class="material-icons-outlined" style="font-size:3rem; color:#ffeeba;">warning</span>
            </div>

            <div class="card">
                <div>
                    <h3>登録Agent数</h3>
                    <div class="value"><?= $count_agents ?></div>
                </div>
                <span class="material-icons-outlined" style="font-size:3rem; color:#e9ecef;">people</span>
            </div>

            <div class="card">
                <div>
                    <h3>登録User数</h3>
                    <div class="value"><?= $count_users ?></div>
                </div>
                <span class="material-icons-outlined" style="font-size:3rem; color:#e9ecef;">face</span>
            </div>
        </div>

        <div class="table-container">
            <h3>📝 承認待ちのAgentリスト</h3>
            <?php if(count($pending_agents) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名前</th>
                            <th>活動エリア</th>
                            <th>ステータス</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_agents as $agent): ?>
                        <tr>
                            <td><?= h($agent['id']) ?></td>
                            <td><?= h($agent['name']) ?></td>
                            <td><?= h($agent['area']) ?></td>
                            <td><span class="status-badge">審査待ち</span></td>
                            <td>
                                <a href="admin_kyc_check.php?id=<?= h($agent['id']) ?>" class="btn-check">確認・承認</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#999; margin-top:20px; text-align:center;">現在、審査待ちのAgentはいません。</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>