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

if ($user_type === 'user') {
    // ユーザー側: agent_id を指定
    $agent_id = (int)($_GET['agent_id'] ?? 0);
    if ($agent_id <= 0) redirect('mypage_user.php');

    $stmt = $pdo->prepare("SELECT id, name, profile_img FROM agents WHERE id=:id AND life_flg=0");
    $stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
    $stmt->execute();
    $partner = $stmt->fetch();
    if (!$partner) redirect('search.php');

    $partner_name = $partner['name'];
    $partner_img  = $partner['profile_img']
        ? 'uploads/' . $partner['profile_img']
        : 'https://placehold.co/40x40/e0e0e0/888?text=A';

    $user_id_q  = $my_id;
    $agent_id_q = $agent_id;
    $receiver_id = $agent_id;

} else {
    // エージェント側: user_id を指定
    $target_user_id = (int)($_GET['user_id'] ?? 0);
    if ($target_user_id <= 0) redirect('mypage.php');

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id=:id AND life_flg=0");
    $stmt->bindValue(':id', $target_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $partner = $stmt->fetch();
    if (!$partner) redirect('messages_list.php');

    $partner_name = $partner['name'];
    $partner_img  = 'https://placehold.co/40x40/e0e0e0/888?text=U';

    $user_id_q  = $target_user_id;
    $agent_id_q = $my_id;
    $receiver_id = $target_user_id;
}

// 未読を既読に更新（相手から来たメッセージを開封）
if ($user_type === 'user') {
    // エージェントから来た未読メッセージを既読にする
    $stmt = $pdo->prepare(
        "UPDATE messages SET is_read=1
         WHERE sender_type=2 AND sender_id=:agent_id AND receiver_id=:user_id AND is_read=0"
    );
    $stmt->bindValue(':agent_id', $agent_id_q, PDO::PARAM_INT);
    $stmt->bindValue(':user_id',  $user_id_q,  PDO::PARAM_INT);
} else {
    // ユーザーから来た未読メッセージを既読にする
    $stmt = $pdo->prepare(
        "UPDATE messages SET is_read=1
         WHERE sender_type=1 AND sender_id=:user_id AND receiver_id=:agent_id AND is_read=0"
    );
    $stmt->bindValue(':user_id',  $user_id_q,  PDO::PARAM_INT);
    $stmt->bindValue(':agent_id', $agent_id_q, PDO::PARAM_INT);
}
$stmt->execute();

// メッセージ取得
$stmt = $pdo->prepare(
    "SELECT m.id, m.sender_id, m.sender_type, m.message, m.created_at
     FROM messages m
     WHERE (m.sender_type=1 AND m.sender_id=:uid1 AND m.receiver_id=:aid1)
        OR (m.sender_type=2 AND m.sender_id=:aid2 AND m.receiver_id=:uid2)
     ORDER BY m.created_at ASC"
);
$stmt->bindValue(':uid1', $user_id_q,  PDO::PARAM_INT);
$stmt->bindValue(':aid1', $agent_id_q, PDO::PARAM_INT);
$stmt->bindValue(':aid2', $agent_id_q, PDO::PARAM_INT);
$stmt->bindValue(':uid2', $user_id_q,  PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 自分のメッセージ判定用
// user: sender_type=1 AND sender_id=my_id
// agent: sender_type=2 AND sender_id=my_id
function is_mine($msg, $user_type, $my_id) {
    if ($user_type === 'user') {
        return (int)$msg['sender_type'] === 1 && (int)$msg['sender_id'] === $my_id;
    } else {
        return (int)$msg['sender_type'] === 2 && (int)$msg['sender_id'] === $my_id;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($partner_name) ?> とのメッセージ - ERAPRO</title>
    <?php if ($user_type === 'user'): ?>
    <link rel="stylesheet" href="css/style.css">
    <?php else: ?>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* ===== チャット画面共通 ===== */
        .chat-wrap {
            max-width: 760px;
            margin: 0 auto;
            padding-bottom: 90px;
        }

        /* チャットヘッダー */
        .chat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .chat-header a { font-size: 0.875rem; color: #666; text-decoration: none; }
        .chat-partner-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #eee;
        }
        .chat-partner-name { font-size: 1rem; font-weight: 700; color: #333; }

        /* メッセージリスト */
        .chat-messages {
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 300px;
        }

        /* バブル */
        .bubble-wrap {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        .bubble-wrap.mine { flex-direction: row-reverse; }

        .bubble-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            background: #eee;
        }

        .bubble {
            max-width: 68%;
            padding: 10px 14px;
            border-radius: 16px;
            font-size: 0.92rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .bubble.mine {
            background: #004e92;
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .bubble.theirs {
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px;
        }
        .bubble-time {
            font-size: 0.72rem;
            color: #aaa;
            align-self: flex-end;
            flex-shrink: 0;
        }
        .bubble-wrap.mine .bubble-time { text-align: right; }

        /* 固定フッター入力エリア */
        .chat-input-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            padding: 10px 16px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
            z-index: 200;
        }
        .chat-input-bar textarea {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 20px;
            font-size: 0.95rem;
            resize: none;
            outline: none;
            max-height: 120px;
            line-height: 1.5;
            font-family: inherit;
        }
        .chat-input-bar textarea:focus { border-color: #004e92; }
        .chat-send-btn {
            width: 44px;
            height: 44px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .chat-send-btn:hover { background: #003a70; }
        .chat-send-btn:disabled { background: #ccc; cursor: not-allowed; }

        /* エージェントレイアウト調整 */
        <?php if ($user_type === 'agent'): ?>
        .chat-wrap { max-width: 680px; margin: 0 auto; }
        <?php endif; ?>

        /* 日付区切り */
        .date-divider {
            text-align: center;
            font-size: 0.78rem;
            color: #aaa;
            margin: 8px 0;
        }
    </style>
</head>
<body>

<?php if ($user_type === 'user'): ?>
    <?php include("header.php"); ?>
    <div class="chat-wrap">

<?php else: ?>
    <div class="admin-header">
        <div class="logo">ERAPRO Agent <span style="font-size:0.8rem; font-weight:normal;">Message</span></div>
        <div style="display:flex; gap:15px; align-items:center;">
            <a href="messages_list.php" style="color:#fff; font-size:0.85rem;">← メッセージ一覧</a>
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
        <div class="chat-wrap">
<?php endif; ?>

        <!-- チャットヘッダー -->
        <div class="chat-header">
            <?php if ($user_type === 'user'): ?>
            <a href="mypage_user.php">← 戻る</a>
            <?php else: ?>
            <a href="messages_list.php">← 一覧</a>
            <?php endif; ?>
            <img src="<?= h($partner_img) ?>" class="chat-partner-img" alt="<?= h($partner_name) ?>">
            <span class="chat-partner-name"><?= h($partner_name) ?></span>
        </div>

        <!-- メッセージリスト -->
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
            <p style="text-align:center; color:#aaa; margin-top:40px; font-size:0.9rem;">
                まだメッセージがありません。<br>最初のメッセージを送ってみましょう。
            </p>
            <?php endif; ?>

            <?php
            $last_date = '';
            foreach ($messages as $msg):
                $msg_date = date('Y年m月d日', strtotime($msg['created_at']));
                $mine = is_mine($msg, $user_type, $my_id);
            ?>
                <?php if ($msg_date !== $last_date): ?>
                <div class="date-divider"><?= h($msg_date) ?></div>
                <?php $last_date = $msg_date; ?>
                <?php endif; ?>

                <div class="bubble-wrap <?= $mine ? 'mine' : 'theirs' ?>">
                    <?php if (!$mine): ?>
                    <img src="<?= h($partner_img) ?>" class="bubble-avatar" alt="<?= h($partner_name) ?>">
                    <?php endif; ?>
                    <div class="bubble <?= $mine ? 'mine' : 'theirs' ?>"><?= h($msg['message']) ?></div>
                    <span class="bubble-time"><?= h(date('H:i', strtotime($msg['created_at']))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

<?php if ($user_type === 'user'): ?>
    </div><!-- .chat-wrap -->
<?php else: ?>
        </div><!-- .chat-wrap -->
        </main>
    </div><!-- .dashboard -->
<?php endif; ?>

<!-- 固定フッター入力エリア（.dashboard の外に配置） -->
<div class="chat-input-bar">
    <textarea
        id="msgInput"
        rows="1"
        placeholder="メッセージを入力... (Ctrl+Enter で送信)"
        maxlength="2000"
    ></textarea>
    <button class="chat-send-btn" id="sendBtn" title="送信">&#9658;</button>
</div>

<script>
const receiverId  = <?= (int)$receiver_id ?>;
const partnerName = <?= json_encode($partner_name) ?>;
const partnerImg  = <?= json_encode($partner_img) ?>;
const myType      = <?= json_encode($user_type) ?>;

// テキストエリア自動リサイズ
const msgInput = document.getElementById('msgInput');
msgInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Ctrl+Enter / Cmd+Enter で送信
msgInput.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});

document.getElementById('sendBtn').addEventListener('click', sendMessage);

function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;

    const body = new URLSearchParams({
        receiver_id: receiverId,
        message: text
    });

    fetch('message_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.result === 'ok') {
            appendBubble(data.message.message, data.message.created_at, true);
            msgInput.value = '';
            msgInput.style.height = 'auto';
        } else {
            alert(data.error || '送信に失敗しました');
        }
    })
    .catch(() => alert('通信エラーが発生しました。再度お試しください。'))
    .finally(() => { btn.disabled = false; });
}

function appendBubble(text, createdAt, mine) {
    const chatMessages = document.getElementById('chatMessages');

    // 空状態テキストを削除
    const empty = chatMessages.querySelector('p');
    if (empty) empty.remove();

    const time = new Date(createdAt);
    const timeStr = time.getHours().toString().padStart(2,'0') + ':' + time.getMinutes().toString().padStart(2,'0');

    const wrap = document.createElement('div');
    wrap.className = 'bubble-wrap ' + (mine ? 'mine' : 'theirs');

    if (!mine) {
        const img = document.createElement('img');
        img.src = partnerImg;
        img.className = 'bubble-avatar';
        img.alt = partnerName;
        wrap.appendChild(img);
    }

    const bubble = document.createElement('div');
    bubble.className = 'bubble ' + (mine ? 'mine' : 'theirs');
    bubble.textContent = text;
    wrap.appendChild(bubble);

    const timeEl = document.createElement('span');
    timeEl.className = 'bubble-time';
    timeEl.textContent = timeStr;
    wrap.appendChild(timeEl);

    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// 初期スクロール
window.addEventListener('load', function () {
    const cm = document.getElementById('chatMessages');
    cm.scrollTop = cm.scrollHeight;
});
</script>

</body>
</html>
