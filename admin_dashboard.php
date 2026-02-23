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
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; margin: 0; display: flex; }
        
        /* サイドバー */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            min-height: 100vh;
            padding: 20px 0;
        }
        .sidebar h2 { text-align: center; font-size: 1.2rem; margin-bottom: 30px; letter-spacing: 1px; }
        .menu a {
            display: block;
            color: #c2c7d0;
            padding: 15px 20px;
            text-decoration: none;
            border-bottom: 1px solid #4b545c;
            transition: 0.3s;
        }
        .menu a:hover, .menu a.active { background-color: #007bff; color: #fff; }
        .menu .icon { margin-right: 10px; vertical-align: bottom; }

        /* メインコンテンツ */
        .content { flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-logout { background: #dc3545; color: #fff; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }

        /* KPIカード */
        .kpi-container { display: flex; gap: 20px; margin-bottom: 40px; }
        .card {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card h3 { margin: 0; font-size: 0.9rem; color: #666; }
        .card .value { font-size: 2rem; font-weight: bold; margin-top: 5px; }
        .card.alert { border-left: 5px solid #ffc107; } /* 注意色 */
        
        /* テーブル */
        .table-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-size: 0.9rem; }
        .btn-check {
            background-color: #007bff; color: #fff; padding: 6px 12px;
            text-decoration: none; border-radius: 4px; font-size: 0.8rem;
        }
        .status-badge {
            background: #ffc107; color: #333; padding: 4px 8px;
            border-radius: 12px; font-size: 0.75rem; font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ERAPRO ADMIN</h2>
        <div class="menu">
            <a href="admin_dashboard.php" class="active"><span class="material-icons-outlined icon">dashboard</span>ダッシュボード</a>
            <a href="#"><span class="material-icons-outlined icon">people</span>Agent一覧</a>
            <a href="#"><span class="material-icons-outlined icon">face</span>User一覧</a>
            <a href="#"><span class="material-icons-outlined icon">settings</span>設定</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>ダッシュボード</h2>
            <div style="text-align:right;">
                <span style="margin-right:15px; font-size:0.9rem;">管理者: <?= h($_SESSION["name"]) ?></span>
                <a href="logout.php" class="btn-logout">ログアウト</a>
            </div>
        </div>

        <?php if (isset($_GET['result'])): ?>
            <?php if ($_GET['result'] === 'approved'): ?>
                <div style="background:#d4edda; border-left:4px solid #28a745; padding:12px 16px; border-radius:4px; margin-bottom:20px; color:#155724;">
                    ✅ Agentを承認しました。メールで通知しました。
                </div>
            <?php elseif ($_GET['result'] === 'rejected'): ?>
                <div style="background:#f8d7da; border-left:4px solid #dc3545; padding:12px 16px; border-radius:4px; margin-bottom:20px; color:#721c24;">
                    ⚠️ 否認しました。Agentへ再提出依頼メールを送信しました。
                </div>
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