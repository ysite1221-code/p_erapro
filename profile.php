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

// クチコミ取得
$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    agent_id   INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_agent (user_id, agent_id),
    INDEX idx_agent (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// テーブルスキーマの自動アップデート
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
} catch (PDOException $e) {
    // カラムが既に存在する場合のエラーは無視する
}

$stmt = $pdo->prepare(
    "SELECT r.id, r.user_id, r.rating, r.comment, r.updated_at, u.name AS user_name
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.agent_id = :aid
     ORDER BY r.updated_at DESC"
);
$stmt->bindValue(':aid', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$review_count = count($reviews);
$avg_rating   = $review_count > 0
    ? round(array_sum(array_column($reviews, 'rating')) / $review_count, 1)
    : 0;

// ログイン中Userが既にレビュー済みか
$user_reviewed = false;
if ($is_user) {
    $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=:uid AND agent_id=:aid");
    $chk->bindValue(':uid', (int)$_SESSION['id'], PDO::PARAM_INT);
    $chk->bindValue(':aid', $agent_id,             PDO::PARAM_INT);
    $chk->execute();
    $user_reviewed = (bool)$chk->fetchColumn();
}

// 投稿完了メッセージ
$review_posted = isset($_GET['review']) && $_GET['review'] === '1';

// 画像処理
$img = $row['profile_img']
    ? 'uploads/' . $row['profile_img']
    : 'https://picsum.photos/seed/agent' . $agent_id . '/1200/480';

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
        .profile-wrap { max-width: 800px; margin: 0 auto; padding-bottom: 100px; }

        /* カバー画像 */
        .cover-img {
            width: 100%;
            height: 340px;
            object-fit: cover;
            display: block;
        }

        /* カード本体 */
        .profile-card {
            background: #fff;
            margin: -56px 24px 0;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 40px 48px 48px;
            position: relative;
        }

        /* ヘッダー情報 */
        .profile-head { display: flex; gap: 24px; align-items: flex-start; margin-bottom: 28px; }
        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.14);
            flex-shrink: 0;
            margin-top: -72px;
            background: #eee;
        }
        .profile-head-info { flex: 1; padding-top: 4px; }
        .area-chip {
            display: inline-block;
            background: #f0f4ff;
            color: #004e92;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 3px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            letter-spacing: 0.02em;
        }
        .profile-name {
            font-size: 1.9rem;
            font-weight: 900;
            color: #111;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .profile-catch {
            font-size: 1rem;
            color: #004e92;
            font-weight: 700;
            line-height: 1.6;
        }

        /* タグ */
        .tag-area { margin: 20px 0 32px; }
        .tag {
            font-size: 0.75rem;
            background: #f0f4ff;
            color: #004e92;
            padding: 4px 12px;
            border-radius: 4px;
            margin-right: 6px;
            margin-bottom: 6px;
            display: inline-block;
            font-weight: 500;
        }

        /* セクション */
        .sec-title {
            font-size: 1.25rem;
            font-weight: 900;
            color: #111;
            margin: 48px 0 16px;
            letter-spacing: -0.01em;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        .narrative-text {
            font-size: 1rem;
            line-height: 2.2;
            color: #444;
            white-space: pre-wrap;
        }

        /* アクションボタンエリア */
        .action-area {
            display: flex;
            gap: 12px;
            margin-top: 48px;
            flex-wrap: wrap;
        }
        .btn-consult {
            flex: 2;
            padding: 16px 20px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background 0.2s, box-shadow 0.2s;
            min-width: 160px;
            letter-spacing: 0.03em;
        }
        .btn-consult:hover {
            background: #003a70;
            color: #fff;
            box-shadow: 0 4px 16px rgba(0,78,146,0.25);
        }

        /* お気に入りボタン */
        .btn-fav {
            flex: 1;
            padding: 16px 14px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            border: 2px solid;
            transition: all 0.2s;
            background: #fff;
            min-width: 110px;
        }
        .btn-fav-heart { border-color: #e91e63; color: #e91e63; }
        .btn-fav-heart.active { background: #e91e63; color: #fff; }
        .btn-fav-star { border-color: #004e92; color: #004e92; }
        .btn-fav-star.active { background: #004e92; color: #fff; }

        /* ログイン促進 */
        .login-hint {
            font-size: 0.8rem;
            color: #aaa;
            text-align: center;
            margin-top: 12px;
        }
        .login-hint a { color: #004e92; }

        /* クチコミセクション */
        .review-summary {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #f8f9ff;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 12px 0 24px;
        }
        .review-score {
            font-size: 2.8rem;
            font-weight: 900;
            color: #004e92;
            line-height: 1;
            letter-spacing: -0.03em;
        }
        .review-stars-avg { font-size: 1.3rem; color: #f4c430; letter-spacing: 3px; }
        .review-count { font-size: 0.82rem; color: #999; margin-top: 4px; }
        .review-list { list-style: none; padding: 0; margin: 0; }
        .review-item {
            border-top: 1px solid #f0f0f0;
            padding: 20px 0;
        }
        .review-item:first-child { border-top: none; }
        .review-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .review-item-stars { font-size: 1rem; color: #f4c430; }
        .review-item-date { font-size: 0.78rem; color: #ccc; }
        .review-item-comment { font-size: 0.92rem; color: #555; line-height: 1.8; }
        .review-empty { color: #bbb; font-size: 0.9rem; padding: 16px 0; }
        .btn-review-post {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            background: #004e92;
            color: #fff;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-review-post:hover { background: #003a70; color: #fff; }
        .btn-review-edit {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 16px;
            background: #f5f7ff;
            color: #004e92;
            border: 1px solid #c5d3f0;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-review-edit:hover { background: #e8eeff; color: #003a70; }

        /* 戻るリンク */
        .back-link {
            display: inline-block;
            padding: 20px 0 16px 4px;
            font-size: 0.85rem;
            color: #999;
        }
        .back-link:hover { color: #004e92; }

        /* トースト */
        #toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(16px);
            background: #111;
            color: #fff;
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 0.875rem;
            opacity: 0;
            transition: opacity 0.25s, transform 0.25s;
            z-index: 1000;
            pointer-events: none;
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="profile-wrap">

    <!-- 戻るリンク -->
    <div style="padding: 0 28px;">
        <a href="search.php" class="back-link">← プロ一覧に戻る</a>
    </div>

    <!-- カバー画像 -->
    <img src="<?= h($img) ?>" class="cover-img" alt="<?= h($row['name']) ?>">

    <!-- プロフィールカード -->
    <div class="profile-card">

        <div class="profile-head">
            <img src="<?= h($img) ?>" class="profile-avatar" alt="<?= h($row['name']) ?>">
            <div class="profile-head-info">
                <?php
                    $area_display = $row['area'] ?: '未設定';
                    if (!empty($row['area_detail'])) {
                        $area_display .= '　' . mb_substr($row['area_detail'], 0, 25);
                    }
                ?>
                <span class="area-chip">📍 <?= h($area_display) ?></span>
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

        <!-- クチコミセクション -->
        <div class="sec-title">クチコミ・評価</div>

        <?php if ($review_posted): ?>
        <p style="color:#004e92; font-weight:600; margin-bottom:16px;">✅ クチコミを投稿しました。ありがとうございます！</p>
        <?php endif; ?>

        <?php if ($review_count > 0): ?>
        <div class="review-summary">
            <div class="review-score"><?= number_format($avg_rating, 1) ?></div>
            <div>
                <div class="review-stars-avg">
                    <?php
                    $full  = floor($avg_rating);
                    $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                    $empty = 5 - $full - $half;
                    echo str_repeat('★', $full);
                    echo $half  ? '½' : '';
                    echo str_repeat('☆', $empty);
                    ?>
                </div>
                <div class="review-count"><?= $review_count ?> 件の評価</div>
            </div>
        </div>

        <ul class="review-list">
            <?php foreach ($reviews as $rv): ?>
            <li class="review-item">
                <div class="review-item-header">
                    <span class="review-item-stars">
                        <?= str_repeat('★', (int)$rv['rating']) ?><?= str_repeat('☆', 5 - (int)$rv['rating']) ?>
                    </span>
                    <span class="review-item-date"><?= h(date('Y年n月', strtotime($rv['updated_at']))) ?></span>
                </div>
                <?php if (!empty($rv['comment'])): ?>
                <p class="review-item-comment"><?= h($rv['comment']) ?></p>
                <?php endif; ?>
                <?php if ($is_user && (int)$rv['user_id'] === (int)$_SESSION['id']): ?>
                <a href="review_post.php?agent_id=<?= $agent_id ?>" class="btn-review-edit">✏️ 編集する</a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="review-empty">まだクチコミはありません。</p>
        <?php endif; ?>

        <?php if ($is_user && !$user_reviewed): ?>
        <a href="review_post.php?agent_id=<?= $agent_id ?>" class="btn-review-post">
            ★ クチコミを投稿する
        </a>
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
