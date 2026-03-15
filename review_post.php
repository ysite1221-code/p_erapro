<?php
session_start();
include("function.php");
loginCheck('user');

$user_id  = (int)$_SESSION['id'];
$agent_id = (int)($_GET['agent_id'] ?? 0);
if ($agent_id <= 0) {
    redirect('mypage_user.php');
}

$pdo = db_conn();

// Agent存在チェック
$stmt = $pdo->prepare("SELECT id, name FROM agents WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();
if (!$agent) {
    redirect('mypage_user.php');
}

// 既存のクチコミを取得（編集用）
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
    "SELECT rating, comment FROM reviews WHERE user_id=:uid AND agent_id=:aid"
);
$stmt->bindValue(':uid', $user_id,  PDO::PARAM_INT);
$stmt->bindValue(':aid', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$existing = $stmt->fetch();
$cur_rating  = $existing ? (int)$existing['rating']  : 0;
$cur_comment = $existing ? $existing['comment'] : '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($agent['name']) ?> へのクチコミ - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f6f9; }
        .review-wrap {
            max-width: 560px;
            margin: 40px auto 80px;
            padding: 0 20px;
        }
        .review-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            padding: 36px 40px;
        }
        .review-card h1 {
            font-size: 1.3rem;
            color: #004e92;
            margin-bottom: 6px;
        }
        .review-card .agent-name {
            font-size: 1rem;
            color: #555;
            margin-bottom: 28px;
        }

        /* 星選択 */
        .star-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 10px;
            display: block;
        }
        .star-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 6px;
            margin-bottom: 28px;
        }
        .star-group input[type="radio"] { display: none; }
        .star-group label {
            font-size: 2.2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.15s, transform 0.1s;
        }
        .star-group input:checked ~ label,
        .star-group label:hover,
        .star-group label:hover ~ label {
            color: #f4c430;
        }
        .star-group label:active { transform: scale(1.2); }

        /* コメント */
        .comment-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
            display: block;
        }
        textarea {
            width: 100%;
            min-height: 120px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
            transition: border-color 0.2s;
            margin-bottom: 28px;
        }
        textarea:focus { outline: none; border-color: #004e92; }

        /* ボタン */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #003a70; }
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 14px;
            color: #999;
            font-size: 0.875rem;
        }
        .btn-cancel:hover { color: #555; }
        .update-notice {
            font-size: 0.8rem;
            color: #888;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="img/logo_blue.png" alt="ERAPRO" onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
            <span style="display:none; font-weight:800; color:#004e92; font-size:1.2rem;">ERAPRO</span>
        </a>
        <nav class="header-nav">
            <a href="mypage_user.php" class="btn-mypage">マイページ</a>
            <a href="logout.php" class="btn-login">ログアウト</a>
        </nav>
    </div>
</header>

<div class="review-wrap">
    <div class="review-card">
        <h1>クチコミを投稿</h1>
        <p class="agent-name"><?= h($agent['name']) ?> さんへの評価</p>

        <?php if ($existing): ?>
        <p class="update-notice">※ 既存のクチコミを上書き更新します</p>
        <?php endif; ?>

        <form action="review_act.php" method="post">
            <input type="hidden" name="agent_id" value="<?= $agent_id ?>">

            <span class="star-label">評価（星1〜5）</span>
            <div class="star-group">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input
                    type="radio"
                    name="rating"
                    id="star<?= $i ?>"
                    value="<?= $i ?>"
                    <?= ($cur_rating === $i) ? 'checked' : '' ?>
                    required
                >
                <label for="star<?= $i ?>" title="<?= $i ?>">★</label>
                <?php endfor; ?>
            </div>

            <span class="comment-label">コメント（任意）</span>
            <textarea
                name="comment"
                placeholder="この方と関わった体験や率直な感想をお書きください"
                maxlength="1000"
            ><?= h($cur_comment) ?></textarea>

            <button type="submit" class="btn-submit">
                <?= $existing ? 'クチコミを更新する' : 'クチコミを投稿する' ?>
            </button>
        </form>

        <a href="profile.php?id=<?= $agent_id ?>" class="btn-cancel">← キャンセル</a>
    </div>
</div>

</body>
</html>
