<?php
session_start();
include("function.php");

$agent_id = (int)($_GET["id"] ?? 0);
if ($agent_id <= 0) {
    redirect("search.php");
}

$pdo = db_conn();

// Agent情報
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();
if (!$row) {
    redirect("search.php");
}

// ログイン済みUserのお気に入り状態を取得
$fav_status = 0; // 0=未登録, 1=お気に入り, 2=My Agent
$is_user    = (
    isset($_SESSION['chk_ssid']) &&
    $_SESSION['chk_ssid'] === session_id() &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'user'
);

if ($is_user) {
    // favoritesテーブルをなければ作成
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        agent_id   INT NOT NULL,
        status     TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_agent (user_id, agent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare(
        "SELECT status FROM favorites WHERE user_id=:uid AND agent_id=:aid"
    );
    $stmt->bindValue(':uid', (int)$_SESSION['id'], PDO::PARAM_INT);
    $stmt->bindValue(':aid', $agent_id,             PDO::PARAM_INT);
    $stmt->execute();
    $fav_row = $stmt->fetch();
    if ($fav_row) {
        $fav_status = (int)$fav_row['status'];
    }
}

// プロフィール閲覧トラッキング（自分自身は除外）
$viewer_is_agent = (
    isset($_SESSION['chk_ssid']) &&
    $_SESSION['chk_ssid'] === session_id() &&
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'agent' &&
    (int)$_SESSION['id'] === $agent_id
);
if (!$viewer_is_agent) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile_views (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        agent_id  INT NOT NULL,
        viewer_ip VARCHAR(45) DEFAULT NULL,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_agent_date (agent_id, viewed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $sv = $pdo->prepare("INSERT INTO profile_views (agent_id, viewer_ip, viewed_at) VALUES (:aid, :ip, NOW())");
    $sv->bindValue(':aid', $agent_id, PDO::PARAM_INT);
    $sv->bindValue(':ip',  $ip,       PDO::PARAM_STR);
    $sv->execute();
}

// 画像処理
$img = $row['profile_img']
    ? 'uploads/' . $row['profile_img']
    : 'https://placehold.co/800x300/e0e0e0/888?text=No+Image';

// タグ処理
$tags = array_filter(array_map('trim', explode(',', $row['tags'] ?? '')));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($row["name"]) ?> - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== プロフィールページ固有スタイル ===== */
        .profile-wrap { max-width: 760px; margin: 0 auto; padding-bottom: 80px; }

        /* カバー画像 */
        .cover-img { width: 100%; height: 280px; object-fit: cover; }

        /* カード本体 */
        .profile-card {
            background: #fff;
            margin: -48px 20px 0;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            padding: 36px 40px 40px;
            position: relative;
        }

        /* ヘッダー情報 */
        .profile-head { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 24px; }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            flex-shrink: 0;
            margin-top: -60px;
            background: #eee;
        }
        .profile-head-info { flex: 1; }
        .area-chip {
            display: inline-block;
            background: #e8f0fe;
            color: #004e92;
            font-size: 0.78rem;
            padding: 3px 12px;
            border-radius: 14px;
            margin-bottom: 8px;
        }
        .profile-name { font-size: 1.6rem; font-weight: 800; color: #222; margin-bottom: 4px; }
        .profile-catch { font-size: 1rem; color: #004e92; font-weight: 600; }

        /* タグ */
        .tag-area { margin: 16px 0 24px; }
        .tag { font-size: 0.78rem; background: #f0f4ff; color: #004e92; padding: 4px 10px; border-radius: 12px; margin-right: 6px; margin-bottom: 6px; display: inline-block; }

        /* セクション */
        .sec-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #004e92;
            border-left: 4px solid #004e92;
            padding-left: 12px;
            margin: 32px 0 14px;
        }
        .narrative-text { font-size: 1rem; line-height: 2; color: #444; white-space: pre-wrap; }

        /* アクションボタンエリア */
        .action-area {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .btn-consult {
            flex: 2;
            padding: 16px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background 0.2s;
            min-width: 160px;
        }
        .btn-consult:hover { background: #003a70; color: #fff; }

        /* お気に入りボタン */
        .btn-fav {
            flex: 1;
            padding: 16px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            border: 2px solid;
            transition: all 0.2s;
            background: #fff;
            min-width: 110px;
        }
        .btn-fav-heart {
            border-color: #e91e63;
            color: #e91e63;
        }
        .btn-fav-heart.active {
            background: #e91e63;
            color: #fff;
        }
        .btn-fav-star {
            border-color: #004e92;
            color: #004e92;
        }
        .btn-fav-star.active {
            background: #004e92;
            color: #fff;
        }

        /* ログイン促進 */
        .login-hint {
            font-size: 0.8rem;
            color: #999;
            text-align: center;
            margin-top: 10px;
        }
        .login-hint a { color: #004e92; }

        /* トースト */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #333;
            color: #fff;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
            pointer-events: none;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="profile-wrap">

    <!-- 戻るリンク -->
    <div style="padding: 14px 24px;">
        <a href="search.php" style="font-size:0.875rem; color:#666;">← プロ一覧に戻る</a>
    </div>

    <!-- カバー画像 -->
    <img src="<?= h($img) ?>" class="cover-img" alt="<?= h($row['name']) ?>">

    <!-- プロフィールカード -->
    <div class="profile-card">

        <div class="profile-head">
            <img src="<?= h($img) ?>" class="profile-avatar" alt="<?= h($row['name']) ?>">
            <div class="profile-head-info">
                <span class="area-chip">📍 <?= h($row['area'] ?: '未設定') ?></span>
                <div class="profile-name"><?= h($row['name']) ?></div>
                <div class="profile-catch"><?= h($row['title']) ?></div>
            </div>
        </div>

        <!-- タグ -->
        <?php if (!empty($tags)): ?>
        <div class="tag-area">
            <?php foreach ($tags as $t): ?>
            <span class="tag"># <?= h($t) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ストーリー -->
        <?php if (!empty($row['story'])): ?>
        <div class="sec-title">My Story（原体験）</div>
        <div class="narrative-text"><?= h($row['story']) ?></div>
        <?php endif; ?>

        <!-- 哲学 -->
        <?php if (!empty($row['philosophy'])): ?>
        <div class="sec-title">Philosophy（哲学）</div>
        <div class="narrative-text"><?= h($row['philosophy']) ?></div>
        <?php endif; ?>

        <!-- アクションボタン -->
        <div class="action-area">
            <?php if ($is_user): ?>

                <a href="message_room.php?agent_id=<?= $agent_id ?>" class="btn-consult">💬 この人に相談する</a>

                <button
                    class="btn-fav btn-fav-heart <?= ($fav_status === 1) ? 'active' : '' ?>"
                    id="btnFav"
                    onclick="toggleAction('favorite')"
                >
                    <?= ($fav_status === 1) ? '❤️ お気に入り済み' : '♡ お気に入り' ?>
                </button>

                <button
                    class="btn-fav btn-fav-star <?= ($fav_status === 2) ? 'active' : '' ?>"
                    id="btnMyAgent"
                    onclick="toggleAction('my_agent')"
                >
                    <?= ($fav_status === 2) ? '⭐ My Agent' : '☆ My Agentに登録' ?>
                </button>

            <?php else: ?>
                <a href="#" class="btn-consult" style="opacity:0.5; cursor:not-allowed;">💬 この人に相談する</a>
                <a href="#" class="btn-fav btn-fav-heart" style="opacity:0.5; cursor:not-allowed;">♡ お気に入り</a>
            <?php endif; ?>
        </div>

        <?php if (!$is_user): ?>
        <p class="login-hint">
            <a href="login_user.php">ログイン</a> または
            <a href="signup_user.php">新規登録</a> するとお気に入り・相談機能が使えます
        </p>
        <?php endif; ?>

    </div>
</div>

<div id="toast"></div>

<?php if ($is_user): ?>
<script>
const agentId  = <?= $agent_id ?>;
let   favStatus = <?= $fav_status ?>; // 0=none, 1=fav, 2=myagent

function toggleAction(action) {
    fetch('favorite_act.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'agent_id=' + agentId + '&action=' + action
    })
    .then(r => r.json())
    .then(data => {
        if (action === 'favorite') {
            const btn = document.getElementById('btnFav');
            if (data.result === 'removed') {
                btn.classList.remove('active');
                btn.textContent = '♡ お気に入り';
                favStatus = 0;
                showToast('お気に入りを解除しました');
            } else {
                btn.classList.add('active');
                btn.textContent = '❤️ お気に入り済み';
                // My Agentが有効だった場合は解除
                if (favStatus === 2) {
                    const btnM = document.getElementById('btnMyAgent');
                    btnM.classList.remove('active');
                    btnM.textContent = '☆ My Agentに登録';
                }
                favStatus = 1;
                showToast('❤️ お気に入りに追加しました');
            }
        } else {
            const btn = document.getElementById('btnMyAgent');
            if (data.result === 'removed') {
                btn.classList.remove('active');
                btn.textContent = '☆ My Agentに登録';
                favStatus = 0;
                showToast('My Agentの登録を解除しました');
            } else {
                btn.classList.add('active');
                btn.textContent = '⭐ My Agent';
                // お気に入りが有効だった場合は解除
                if (favStatus === 1) {
                    const btnF = document.getElementById('btnFav');
                    btnF.classList.remove('active');
                    btnF.textContent = '♡ お気に入り';
                }
                favStatus = 2;
                showToast('⭐ My Agentに登録しました！');
            }
        }
    })
    .catch(() => showToast('エラーが発生しました。再度お試しください。'));
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}
</script>
<?php endif; ?>

</body>
</html>
