<?php
session_start();
include("function.php");
loginCheck('agent');

$id  = (int)$_SESSION["id"];
$pdo = db_conn();

// Agent情報（サイドバー用画像）
$stmt = $pdo->prepare("SELECT profile_img, name FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
$img = !empty($agent['profile_img'])
    ? 'uploads/' . $agent['profile_img']
    : 'https://placehold.co/150x150/e0e0e0/888?text=No+Img';

// ── favorites 経由のユーザー ──
$stmt = $pdo->prepare(
    "SELECT u.id AS user_id, u.name,
            f.status AS fav_status,
            f.updated_at AS contact_at
     FROM favorites f
     JOIN users u ON f.user_id = u.id
     WHERE f.agent_id = :agent_id AND u.life_flg = 0"
);
$stmt->bindValue(':agent_id', $id, PDO::PARAM_INT);
$stmt->execute();
$fav_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── messages 経由のユーザー（distinct） ──
$stmt = $pdo->prepare(
    "SELECT DISTINCT u.id AS user_id, u.name,
            0 AS fav_status,
            (SELECT MAX(created_at) FROM messages
             WHERE (sender_type=1 AND sender_id=u.id AND receiver_id=:aid1)
                OR (sender_type=2 AND sender_id=:aid2 AND receiver_id=u.id)
            ) AS contact_at
     FROM messages m
     JOIN users u ON (
         (m.sender_type=1 AND m.sender_id=u.id AND m.receiver_id=:aid3)
      OR (m.sender_type=2 AND m.sender_id=:aid4 AND m.receiver_id=u.id)
     )
     WHERE u.life_flg = 0"
);
$stmt->bindValue(':aid1', $id, PDO::PARAM_INT);
$stmt->bindValue(':aid2', $id, PDO::PARAM_INT);
$stmt->bindValue(':aid3', $id, PDO::PARAM_INT);
$stmt->bindValue(':aid4', $id, PDO::PARAM_INT);
$stmt->execute();
$msg_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── PHP 側でマージ・デデュープ ──
// favorites を user_id をキーとして格納
$customers = [];
foreach ($fav_rows as $r) {
    $uid = (int)$r['user_id'];
    $customers[$uid] = [
        'user_id'    => $uid,
        'name'       => $r['name'],
        'fav_status' => (int)$r['fav_status'],
        'has_msg'    => false,
        'contact_at' => $r['contact_at'],
    ];
}

// messages 経由を追加・既存エントリとマージ
foreach ($msg_rows as $r) {
    $uid = (int)$r['user_id'];
    if (isset($customers[$uid])) {
        // favorites が優先、contact_at は新しい方を採用
        $customers[$uid]['has_msg'] = true;
        if ($r['contact_at'] > $customers[$uid]['contact_at']) {
            $customers[$uid]['contact_at'] = $r['contact_at'];
        }
    } else {
        $customers[$uid] = [
            'user_id'    => $uid,
            'name'       => $r['name'],
            'fav_status' => 0,
            'has_msg'    => true,
            'contact_at' => $r['contact_at'],
        ];
    }
}

// 最終接触日時 降順ソート
usort($customers, function($a, $b) {
    return strcmp($b['contact_at'], $a['contact_at']);
});
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>顧客リスト - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* 検索バー */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
        }
        .search-bar input:focus { border-color: #004e92; }

        /* テーブル */
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .customer-table th {
            background: #004e92;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 12px 16px;
            text-align: left;
        }
        .customer-table td {
            padding: 12px 16px;
            font-size: 0.9rem;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            vertical-align: middle;
        }
        .customer-table tr:last-child td { border-bottom: none; }
        .customer-table tr:hover td { background: #f8fbff; }

        /* バッジ */
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 9px;
            border-radius: 12px;
            font-weight: 600;
            margin-right: 4px;
            white-space: nowrap;
        }
        .badge-myagent { background: #fff8e1; color: #f59e0b; border: 1px solid #f59e0b; }
        .badge-fav     { background: #fce4ec; color: #e91e63; border: 1px solid #e91e63; }
        .badge-msg     { background: #e3f2fd; color: #004e92; border: 1px solid #004e92; }

        /* メッセージボタン */
        .btn-msg {
            display: inline-block;
            padding: 6px 14px;
            background: #004e92;
            color: #fff;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-msg:hover { background: #003d72; }

        /* 空状態 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 16px; display: block; }
        .empty-state p { font-size: 0.95rem; }

        /* カウント表示 */
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .list-header h2 { font-size: 1.3rem; font-weight: 700; color: #333; margin: 0; }
        .list-count { font-size: 0.85rem; color: #888; }
    </style>
</head>
<body>

    <div class="admin-header">
        <div class="logo">ERAPRO Agent <span style="font-size:0.8rem; font-weight:normal;">Customers</span></div>
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
                <li><a href="customer_list.php" class="active">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">people</span>顧客リスト
                </a></li>
                <li><a href="report.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">analytics</span>レポート
                </a></li>
            </ul>
            <div style="margin-top:30px; text-align:center;">
                <a href="profile.php?id=<?= $id ?>" target="_blank" class="btn-edit" style="width:100%; box-sizing:border-box; background:#555;">自分の公開ページを見る</a>
            </div>
        </aside>

        <main class="main-content">

            <div class="list-header">
                <h2>👥 顧客リスト</h2>
                <span class="list-count" id="visible-count"><?= count($customers) ?> 件</span>
            </div>

            <!-- 検索バー -->
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="名前で絞り込み..." oninput="filterCustomers()">
            </div>

            <?php if (empty($customers)): ?>
            <div class="empty-state">
                <span class="empty-icon">👥</span>
                <p>まだ接触したユーザーがいません。</p>
                <p style="font-size:0.85rem; margin-top:8px;">お気に入り登録やメッセージのやり取りがあるユーザーがここに表示されます。</p>
            </div>
            <?php else: ?>
            <table class="customer-table" id="customer-table">
                <thead>
                    <tr>
                        <th>ユーザー名</th>
                        <th>接触タイプ</th>
                        <th>最終接触日時</th>
                        <th>アクション</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr class="customer-row" data-name="<?= h(mb_strtolower($c['name'])) ?>">
                        <td>
                            <strong><?= h($c['name']) ?></strong>
                        </td>
                        <td>
                            <?php if ($c['fav_status'] === 2): ?>
                                <span class="badge badge-myagent">⭐ My Agent</span>
                            <?php elseif ($c['fav_status'] === 1): ?>
                                <span class="badge badge-fav">❤️ お気に入り</span>
                            <?php endif; ?>
                            <?php if ($c['has_msg']): ?>
                                <span class="badge badge-msg">💬 メッセージ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $c['contact_at'] ? h(date('Y/m/d H:i', strtotime($c['contact_at']))) : '—' ?>
                        </td>
                        <td>
                            <a href="message_room.php?user_id=<?= (int)$c['user_id'] ?>" class="btn-msg">💬 メッセージする</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

        </main>
    </div>

    <script>
    function filterCustomers() {
        const q = document.getElementById('search-input').value.toLowerCase();
        const rows = document.querySelectorAll('.customer-row');
        let visible = 0;
        rows.forEach(function(row) {
            const name = row.dataset.name || '';
            if (name.includes(q)) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });
        document.getElementById('visible-count').textContent = visible + ' 件';
    }
    </script>

</body>
</html>
