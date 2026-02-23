<?php
session_start();
include("function.php");

// セッション検証
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    !in_array($_SESSION['user_type'], ['user', 'agent'])
) {
    redirect('login_user.php');
}

$user_type = $_SESSION['user_type'];
$my_id     = (int)$_SESSION['id'];
$pdo       = db_conn();

$conversations = [];

if ($user_type === 'agent') {
    // Agent inbox: 会話したユーザー一覧（最新メッセージ・未読数つき）
    $stmt = $pdo->prepare(
        "SELECT u.id AS user_id, u.name AS user_name,
            (SELECT message FROM messages
             WHERE (sender_type=2 AND sender_id=:my_id1 AND receiver_id=u.id)
                OR (sender_type=1 AND sender_id=u.id AND receiver_id=:my_id2)
             ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM messages
             WHERE (sender_type=2 AND sender_id=:my_id3 AND receiver_id=u.id)
                OR (sender_type=1 AND sender_id=u.id AND receiver_id=:my_id4)
             ORDER BY created_at DESC LIMIT 1) AS last_at,
            (SELECT COUNT(*) FROM messages
             WHERE sender_type=1 AND sender_id=u.id AND receiver_id=:my_id5 AND is_read=0) AS unread_count
         FROM users u
         WHERE u.id IN (
             SELECT DISTINCT CASE WHEN sender_type=2 THEN receiver_id ELSE sender_id END
             FROM messages
             WHERE (sender_type=2 AND sender_id=:my_id6)
                OR (sender_type=1 AND receiver_id=:my_id7)
         ) AND u.life_flg=0
         ORDER BY last_at DESC"
    );
    $stmt->bindValue(':my_id1', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id2', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id3', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id4', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id5', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id6', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id7', $my_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $conversations[] = [
            'room_url'    => 'message_room.php?user_id=' . (int)$r['user_id'],
            'name'        => $r['user_name'],
            'img'         => 'https://placehold.co/48x48/e0e0e0/888?text=U',
            'last_message'=> $r['last_message'] ?? '',
            'last_at'     => $r['last_at'] ?? '',
            'unread'      => (int)$r['unread_count'],
        ];
    }

} else {
    // User inbox: 会話したエージェント一覧
    $stmt = $pdo->prepare(
        "SELECT a.id AS agent_id, a.name AS agent_name, a.profile_img,
            (SELECT message FROM messages
             WHERE (sender_type=1 AND sender_id=:my_id1 AND receiver_id=a.id)
                OR (sender_type=2 AND sender_id=a.id AND receiver_id=:my_id2)
             ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM messages
             WHERE (sender_type=1 AND sender_id=:my_id3 AND receiver_id=a.id)
                OR (sender_type=2 AND sender_id=a.id AND receiver_id=:my_id4)
             ORDER BY created_at DESC LIMIT 1) AS last_at,
            (SELECT COUNT(*) FROM messages
             WHERE sender_type=2 AND sender_id=a.id AND receiver_id=:my_id5 AND is_read=0) AS unread_count
         FROM agents a
         WHERE a.id IN (
             SELECT DISTINCT CASE WHEN sender_type=1 THEN receiver_id ELSE sender_id END
             FROM messages
             WHERE (sender_type=1 AND sender_id=:my_id6)
                OR (sender_type=2 AND receiver_id=:my_id7)
         ) AND a.life_flg=0
         ORDER BY last_at DESC"
    );
    $stmt->bindValue(':my_id1', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id2', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id3', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id4', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id5', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id6', $my_id, PDO::PARAM_INT);
    $stmt->bindValue(':my_id7', $my_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $conversations[] = [
            'room_url'    => 'message_room.php?agent_id=' . (int)$r['agent_id'],
            'name'        => $r['agent_name'],
            'img'         => $r['profile_img']
                ? 'uploads/' . $r['profile_img']
                : 'https://placehold.co/48x48/e0e0e0/888?text=A',
            'last_message'=> $r['last_message'] ?? '',
            'last_at'     => $r['last_at'] ?? '',
            'unread'      => (int)$r['unread_count'],
        ];
    }
}

// 日時フォーマット
function format_time($dt) {
    if (!$dt) return '';
    $ts  = strtotime($dt);
    $now = time();
    if (date('Y-m-d', $ts) === date('Y-m-d', $now)) {
        return date('H:i', $ts);
    }
    return date('m/d', $ts);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メッセージ一覧 - ERAPRO</title>
    <?php if ($user_type === 'user'): ?>
    <link rel="stylesheet" href="css/style.css">
    <?php else: ?>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* ===== メッセージ一覧共通 ===== */
        .msg-list-wrap {
            max-width: 680px;
            margin: 0 auto;
            padding: 24px 16px 60px;
        }
        .msg-list-wrap h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        /* 会話アイテム */
        .conv-list { list-style: none; padding: 0; margin: 0; }
        .conv-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            margin-bottom: 8px;
            text-decoration: none;
            color: #333;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .conv-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .conv-item.unread { background: #f0f6ff; }

        .conv-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            background: #eee;
        }
        .conv-body { flex: 1; min-width: 0; }
        .conv-name {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        .conv-preview {
            font-size: 0.82rem;
            color: #777;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conv-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
            flex-shrink: 0;
        }
        .conv-time { font-size: 0.75rem; color: #aaa; }
        .unread-badge {
            background: #004e92;
            color: #fff;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
        }

        /* 空状態 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 16px; display: block; }
        .empty-state p { font-size: 0.95rem; margin-bottom: 20px; }
        .empty-state a {
            display: inline-block;
            padding: 12px 28px;
            background: #004e92;
            color: #fff;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php if ($user_type === 'user'): ?>
    <?php include("header.php"); ?>
    <div class="msg-list-wrap">
        <h2>💬 メッセージ</h2>

<?php else: ?>
    <div class="admin-header">
        <div class="logo">ERAPRO Agent <span style="font-size:0.8rem; font-weight:normal;">Messages</span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <a href="mypage.php" style="color:#fff; font-size:0.85rem;">← ダッシュボード</a>
            <a href="logout.php" style="color:#fff; text-decoration:underline; font-size:0.8rem;">ログアウト</a>
        </div>
    </div>
    <div class="dashboard">
        <aside class="sidebar">
            <ul>
                <li><a href="mypage.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">dashboard</span>ダッシュボード
                </a></li>
                <li><a href="edit.php">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">person</span>プロフィール編集
                </a></li>
                <li><a href="messages_list.php" class="active">
                    <span class="material-icons-outlined" style="vertical-align:middle; font-size:1.2rem; margin-right:5px;">chat</span>メッセージ
                </a></li>
            </ul>
        </aside>
        <main class="main-content" style="flex:1;">
        <div class="msg-list-wrap" style="padding-top:0;">
            <h2>💬 メッセージ</h2>
<?php endif; ?>

        <?php if (empty($conversations)): ?>
        <div class="empty-state">
            <span class="empty-icon">💬</span>
            <p>まだメッセージのやり取りがありません。</p>
            <?php if ($user_type === 'user'): ?>
            <a href="search.php">プロを探してメッセージする</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <ul class="conv-list">
            <?php foreach ($conversations as $conv): ?>
            <a href="<?= h($conv['room_url']) ?>" class="conv-item <?= $conv['unread'] > 0 ? 'unread' : '' ?>">
                <img src="<?= h($conv['img']) ?>" class="conv-avatar" alt="<?= h($conv['name']) ?>">
                <div class="conv-body">
                    <div class="conv-name"><?= h($conv['name']) ?></div>
                    <div class="conv-preview"><?= h(mb_substr($conv['last_message'], 0, 50)) ?></div>
                </div>
                <div class="conv-meta">
                    <span class="conv-time"><?= h(format_time($conv['last_at'])) ?></span>
                    <?php if ($conv['unread'] > 0): ?>
                    <span class="unread-badge"><?= (int)$conv['unread'] ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

<?php if ($user_type === 'user'): ?>
    </div><!-- .msg-list-wrap -->
<?php else: ?>
        </div><!-- .msg-list-wrap -->
        </main>
    </div><!-- .dashboard -->
<?php endif; ?>

</body>
</html>
