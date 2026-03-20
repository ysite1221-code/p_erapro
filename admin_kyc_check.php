<?php
session_start();
include("function.php");

// 1. ログインチェック
if (!isset($_SESSION["chk_ssid"]) || $_SESSION["chk_ssid"] != session_id() || $_SESSION["user_type"] != 'admin') {
    redirect("login_admin.php");
}

// 2. 対象のAgentID取得
$agent_id = (int)($_GET["id"] ?? 0);
if ($agent_id <= 0) {
    redirect("admin_dashboard.php");
}

// 3. データ取得
$pdo = db_conn();

$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();

if (!$agent) {
    redirect("admin_dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC審査 - ERAPRO Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", sans-serif;
            background: #f4f6f9;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        /* サイドバー */
        .sidebar {
            width: 240px;
            background: #1e2a3a;
            color: #c2c7d0;
            min-height: 100vh;
            padding: 28px 0;
            flex-shrink: 0;
        }
        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            text-align: center;
            padding: 0 20px 24px;
            letter-spacing: 1px;
            border-bottom: 1px solid #2e3f52;
            margin-bottom: 16px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #c2c7d0;
            padding: 12px 24px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar a:hover { background: #2e3f52; color: #fff; }
        .sidebar .material-icons-outlined { font-size: 1.1rem; }

        /* メインコンテンツ */
        .content { flex: 1; padding: 36px 40px; }

        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .page-subtitle {
            font-size: 0.88rem;
            color: #888;
            margin-bottom: 32px;
        }

        /* カードレイアウト */
        .kyc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .kyc-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 28px 28px;
        }
        .kyc-card h3 {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* 申請者情報テーブル */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table th,
        .info-table td { padding: 11px 6px; border-bottom: 1px solid #f0f0f0; font-size: 0.88rem; vertical-align: top; }
        .info-table th { width: 38%; color: #888; font-weight: 600; }
        .info-table td { color: #333; font-weight: 500; word-break: break-all; }
        .info-table tr:last-child th,
        .info-table tr:last-child td { border-bottom: none; }

        /* ステータスバッジ */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .badge-0 { background: #e9ecef; color: #495057; }
        .badge-1 { background: #fff3cd; color: #856404; }
        .badge-2 { background: #d4edda; color: #155724; }
        .badge-9 { background: #f8d7da; color: #721c24; }

        /* URL確認エリア */
        .url-box {
            background: #f8f9ff;
            border: 1.5px solid #dce4f5;
            border-radius: 8px;
            padding: 20px;
        }
        .url-box .url-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .url-box .url-value {
            font-size: 0.88rem;
            word-break: break-all;
            line-height: 1.6;
        }
        .url-box .url-value a {
            color: #004e92;
            text-decoration: none;
            font-weight: 600;
        }
        .url-box .url-value a:hover { text-decoration: underline; }
        .url-box .url-open-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            padding: 9px 18px;
            background: #004e92;
            color: #fff;
            border-radius: 6px;
            font-size: 0.84rem;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s;
        }
        .url-box .url-open-btn:hover { background: #003a70; }
        .url-empty {
            text-align: center;
            padding: 40px 20px;
            color: #aaa;
            font-size: 0.9rem;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1.5px dashed #ddd;
        }
        .url-empty .url-empty-icon { font-size: 2rem; display: block; margin-bottom: 8px; }

        /* アクションエリア */
        .action-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .action-card .action-note {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.6;
        }
        .action-btns {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        .btn {
            padding: 11px 24px;
            border: none;
            border-radius: 7px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            color: #fff;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-approve { background: #28a745; }
        .btn-reject  { background: #dc3545; }
        .btn-back    {
            background: transparent;
            color: #666;
            border: 1.5px solid #ddd;
            padding: 11px 20px;
            border-radius: 7px;
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 600;
            transition: border-color 0.15s, color 0.15s;
        }
        .btn-back:hover { border-color: #999; color: #333; }

        @media (max-width: 900px) {
            .kyc-grid { grid-template-columns: 1fr; }
            .content { padding: 24px 20px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-title">ERAPRO ADMIN</div>
        <a href="admin_dashboard.php">
            <span class="material-icons-outlined">dashboard</span>ダッシュボード
        </a>
    </div>

    <div class="content">
        <div class="page-title">本人確認の審査</div>
        <div class="page-subtitle">Agent ID: <?= h($agent['id']) ?> &mdash; <?= h($agent['name']) ?></div>

        <div class="kyc-grid">

            <!-- 申請者情報 -->
            <div class="kyc-card">
                <h3>申請者情報</h3>
                <table class="info-table">
                    <tr>
                        <th>ID</th>
                        <td><?= h($agent['id']) ?></td>
                    </tr>
                    <tr>
                        <th>氏名</th>
                        <td><?= h($agent['name']) ?></td>
                    </tr>
                    <tr>
                        <th>ログインID</th>
                        <td><?= h($agent['lid']) ?></td>
                    </tr>
                    <tr>
                        <th>活動エリア</th>
                        <td><?= h($agent['area'] ?: '未設定') ?></td>
                    </tr>
                    <tr>
                        <th>現在のステータス</th>
                        <td>
                            <?php
                            $status_map = [
                                0 => ['label' => '未提出',   'class' => 'badge-0'],
                                1 => ['label' => '審査待ち', 'class' => 'badge-1'],
                                2 => ['label' => '承認済み', 'class' => 'badge-2'],
                                9 => ['label' => '否認',     'class' => 'badge-9'],
                            ];
                            $vs = (int)$agent['verification_status'];
                            $sm = $status_map[$vs] ?? ['label' => '不明', 'class' => 'badge-0'];
                            ?>
                            <span class="badge <?= $sm['class'] ?>"><?= $sm['label'] ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 所属先URL確認 -->
            <div class="kyc-card">
                <h3>提出された所属先URL</h3>
                <?php if (!empty($agent['affiliation_url'])): ?>
                    <div class="url-box">
                        <div class="url-label">所属先・登録情報 URL</div>
                        <div class="url-value">
                            <a href="<?= h($agent['affiliation_url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= h($agent['affiliation_url']) ?>
                            </a>
                        </div>
                        <a href="<?= h($agent['affiliation_url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="url-open-btn">
                            <span class="material-icons-outlined" style="font-size:1rem;">open_in_new</span>
                            別タブで開いて確認する
                        </a>
                    </div>
                <?php else: ?>
                    <div class="url-empty">
                        <span class="url-empty-icon">🔗</span>
                        URLが提出されていません
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- 審査アクション -->
        <div class="action-card">
            <div class="action-note">
                URLを確認の上、問題なければ「承認」、確認できない場合は「否認」してください。<br>
                否認するとAgentに再提出を促すメールが送信されます。
            </div>
            <div class="action-btns">
                <a href="admin_dashboard.php" class="btn-back">戻る</a>
                <form action="admin_kyc_act.php" method="post" style="display:contents;">
                    <input type="hidden" name="agent_id" value="<?= h($agent['id']) ?>">
                    <button type="submit" name="status" value="9"
                            class="btn btn-reject"
                            onclick="return confirm('本当に否認しますか？');">否認する</button>
                    <button type="submit" name="status" value="2"
                            class="btn btn-approve"
                            onclick="return confirm('承認してよろしいですか？');">承認する</button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>
