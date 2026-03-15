<?php
session_start();
include("function.php");
loginCheck('user');

$user_id   = (int)$_SESSION['id'];
$user_name = $_SESSION['name'];
$pdo       = db_conn();

// スキーマ自動更新
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN interests VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN area VARCHAR(50) DEFAULT NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE agents ADD COLUMN area_detail VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}

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

// お気に入り一覧（status=1）
$stmt = $pdo->prepare(
    "SELECT a.id, a.name, a.title, a.area, a.tags, a.profile_img, a.diagnosis_score, f.created_at AS fav_at
     FROM favorites f
     JOIN agents a ON f.agent_id = a.id
     WHERE f.user_id=:uid AND f.status=1 AND a.life_flg=0
     ORDER BY f.updated_at DESC"
);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// My Agent一覧（status=2）
$stmt = $pdo->prepare(
    "SELECT a.id, a.name, a.title, a.area, a.tags, a.profile_img, a.diagnosis_score, f.updated_at AS reg_at
     FROM favorites f
     JOIN agents a ON f.agent_id = a.id
     WHERE f.user_id=:uid AND f.status=2 AND a.life_flg=0
     ORDER BY f.updated_at DESC"
);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$my_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 未読メッセージ数
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM messages
     WHERE sender_type=2 AND receiver_id=:uid AND is_read=0"
);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$unread_msg_count = (int)$stmt->fetchColumn();

// 診断タイプ・スコア・関心事・エリア：DBを正として取得し、セッションに同期
$stmt_diag = $pdo->prepare("SELECT diagnosis_type, diagnosis_score, interests, area FROM users WHERE id=:uid AND life_flg=0");
$stmt_diag->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt_diag->execute();
$diag_row = $stmt_diag->fetch(PDO::FETCH_ASSOC);

$db_diag_type  = $diag_row ? $diag_row['diagnosis_type'] : null;
$db_diag_score = ($diag_row && $diag_row['diagnosis_score'] !== null) ? (int)$diag_row['diagnosis_score'] : null;

if (!empty($db_diag_type)) {
    $diag_type = $db_diag_type;
    $_SESSION['diagnosis_type'] = $db_diag_type;
} else {
    $diag_type = $_SESSION['diagnosis_type'] ?? null;
}
$user_score = $db_diag_score;

// 関心事パース
$interests_str  = ($diag_row && !empty($diag_row['interests'])) ? $diag_row['interests'] : '';
$user_interests = (!empty($interests_str))
    ? array_values(array_filter(array_map('trim', explode(',', $interests_str))))
    : [];

// ユーザーエリア
$user_area = ($diag_row && !empty($diag_row['area'])) ? $diag_row['area'] : null;

// 近隣Agent取得（ユーザーのareaと一致するAgent、スコア相性順）
$nearby_agents = [];
if (!empty($user_area)) {
    $near_sql = "SELECT a.id, a.name, a.title, a.area, a.area_detail, a.tags, a.profile_img, a.diagnosis_score
                 FROM agents a
                 WHERE a.life_flg = 0 AND a.area = :uarea";
    if ($user_score !== null) {
        $near_sql .= " ORDER BY ABS(COALESCE(a.diagnosis_score, 50) - :near_score) ASC, a.id DESC";
    } else {
        $near_sql .= " ORDER BY a.id DESC";
    }
    $near_sql .= " LIMIT 6";

    $near_stmt = $pdo->prepare($near_sql);
    $near_stmt->bindValue(':uarea', $user_area, PDO::PARAM_STR);
    if ($user_score !== null) {
        $near_stmt->bindValue(':near_score', $user_score, PDO::PARAM_INT);
    }
    $near_stmt->execute();
    $nearby_agents = $near_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$type_labels = [
    'logic_seeker'   => ['論理・データ重視タイプ', '📊'],
    'empathy_seeker' => ['バランス重視タイプ',     '🤝'],
    'support_seeker' => ['感情・寄り添い重視タイプ','💛'],
];

// 関心事選択肢
$all_interests = ['結婚', '妊娠・出産', '住宅購入', '教育資金', '資産運用', '老後の備え', '自動車購入'];

// おすすめAgent取得（関心事が設定されている場合）
$recommended = [];
if (!empty($user_interests)) {
    $int_conditions = [];
    $int_params     = [];
    foreach ($user_interests as $i => $kw) {
        $key = ':ikw' . $i;
        $int_conditions[] = "a.tags LIKE $key";
        $int_params[$key]  = '%' . $kw . '%';
    }
    $rec_sql = "SELECT a.id, a.name, a.title, a.area, a.tags, a.profile_img, a.diagnosis_score
                FROM agents a
                WHERE a.life_flg = 0
                  AND (" . implode(' OR ', $int_conditions) . ")";
    if ($user_score !== null) {
        $rec_sql .= " ORDER BY ABS(COALESCE(a.diagnosis_score, 50) - :rec_score) ASC, a.id DESC";
    } else {
        $rec_sql .= " ORDER BY a.id DESC";
    }
    $rec_sql .= " LIMIT 5";

    $rec_stmt = $pdo->prepare($rec_sql);
    foreach ($int_params as $k => $v) {
        $rec_stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    if ($user_score !== null) {
        $rec_stmt->bindValue(':rec_score', $user_score, PDO::PARAM_INT);
    }
    $rec_stmt->execute();
    $recommended = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f4f6f9; }

        /* ===== ページ全体レイアウト ===== */
        .mypage-wrap { max-width: 900px; margin: 0 auto; padding: 32px 20px 80px; }

        /* ===== ウェルカムバナー ===== */
        .welcome-banner {
            background: linear-gradient(135deg, #004e92, #000428);
            color: #fff;
            border-radius: 14px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .welcome-avatar {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }
        .welcome-text h2 { font-size: 1.3rem; margin-bottom: 4px; }
        .welcome-text p  { font-size: 0.9rem; opacity: 0.8; }
        .diag-badge {
            margin-left: auto;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .diag-badge .emoji { font-size: 1.4rem; display: block; }

        /* ===== クイックアクション ===== */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 32px;
        }
        .qa-card {
            background: #fff;
            border-radius: 10px;
            padding: 18px 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            color: #333;
            text-decoration: none;
            display: block;
        }
        .qa-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); color: #004e92; }
        .qa-card .qa-icon { font-size: 1.8rem; margin-bottom: 8px; display: block; }
        .qa-card h3 { font-size: 0.95rem; margin-bottom: 4px; }
        .qa-card p  { font-size: 0.78rem; color: #999; margin: 0; }

        /* ===== タブ ===== */
        .tab-nav {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 24px;
        }
        .tab-btn {
            padding: 12px 22px;
            border: none;
            background: none;
            font-size: 0.95rem;
            font-weight: 600;
            color: #999;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
            position: relative;
        }
        .tab-btn.active { color: #004e92; border-color: #004e92; }
        .tab-btn .count {
            display: inline-block;
            background: #004e92;
            color: #fff;
            font-size: 0.7rem;
            padding: 1px 7px;
            border-radius: 12px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .tab-btn:not(.active) .count { background: #ccc; }

        /* ===== タブコンテンツ ===== */
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ===== Agentカード ===== */
        .agent-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .agent-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: transform 0.2s;
            position: relative;
        }
        .agent-card:hover { transform: translateY(-3px); }
        .agent-card-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: #eee;
        }
        .agent-card-body { padding: 14px 16px; }
        .agent-card-body h3 { font-size: 1rem; color: #004e92; margin-bottom: 4px; }
        .agent-card-body .catch { font-size: 0.82rem; color: #555; margin-bottom: 8px; }
        .agent-card-body .area-tag {
            display: inline-block;
            font-size: 0.75rem;
            background: #e8f0fe;
            color: #004e92;
            padding: 2px 8px;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        .agent-card-actions {
            display: flex;
            gap: 8px;
            padding: 0 16px 14px;
        }
        .btn-profile {
            flex: 1;
            padding: 8px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background 0.2s;
        }
        .btn-profile:hover { background: #003a70; color: #fff; }
        .btn-remove {
            padding: 8px 12px;
            background: #fff;
            color: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-remove:hover { background: #dc3545; color: #fff; }
        .btn-review {
            padding: 8px 12px;
            background: #fff;
            color: #f4c430;
            border: 1px solid #f4c430;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: all 0.2s;
        }
        .btn-review:hover { background: #f4c430; color: #fff; }
        .my-agent-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #004e92;
            color: #fff;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
        }

        /* ===== 関心事セクション ===== */
        .interest-section {
            background: #fff;
            border-radius: 12px;
            padding: 22px 28px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .interest-section-header { margin-bottom: 14px; }
        .interest-section-header h3 { font-size: 0.97rem; font-weight: 700; color: #111; margin: 0 0 3px; }
        .interest-section-header p  { font-size: 0.8rem; color: #999; margin: 0; }
        .interest-chips { display: flex; flex-wrap: wrap; gap: 9px; }
        .interest-chip {
            padding: 7px 16px;
            border: 1.5px solid #d8d8d8;
            border-radius: 24px;
            background: #fafafa;
            color: #555;
            font-size: 0.84rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.17s;
            line-height: 1;
        }
        .interest-chip:hover { border-color: #004e92; color: #004e92; background: #f0f4ff; }
        .interest-chip.active {
            border-color: #004e92;
            background: #004e92;
            color: #fff;
            font-weight: 600;
        }
        .interest-chip.saving { opacity: 0.5; pointer-events: none; }

        /* ===== おすすめセクション ===== */
        .recommend-section { margin-bottom: 28px; }
        .recommend-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 14px;
        }
        .recommend-header h3 { font-size: 1rem; font-weight: 700; color: #111; margin: 0; }
        .recommend-header p  { font-size: 0.8rem; color: #999; margin: 0; }
        .recommend-scroll {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: thin;
            scrollbar-color: #e0e0e0 transparent;
        }
        .recommend-scroll::-webkit-scrollbar { height: 4px; }
        .recommend-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
        .rec-card {
            flex-shrink: 0;
            width: 196px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
        }
        .rec-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.11); }
        .rec-card-img-wrap { width: 100%; height: 108px; overflow: hidden; }
        .rec-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; display: block; }
        .rec-card:hover .rec-card-img { transform: scale(1.05); }
        .rec-card-body { padding: 11px 13px 13px; }
        .rec-card-catch { font-size: 0.79rem; font-weight: 700; color: #111; margin: 0 0 3px; line-height: 1.4; }
        .rec-card-name  { font-size: 0.76rem; color: #888; margin: 0 0 6px; }
        .rec-card-area  { font-size: 0.71rem; color: #bbb; }
        .rec-card-more {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
            border: 1.5px dashed #d0d0d0;
            color: #aaa;
            font-size: 0.82rem;
            text-align: center;
        }
        .rec-card-more:hover { border-color: #004e92; color: #004e92; background: #f0f4ff; }
        .rec-more-icon { font-size: 1.4rem; display: block; margin-bottom: 6px; }

        /* ===== 関心事バッジ（カード内） ===== */
        .interest-badge {
            display: inline-block;
            font-size: 0.71rem;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            padding: 2px 8px;
            border-radius: 10px;
            margin-bottom: 5px;
        }

        /* ===== 相性バッジ ===== */
        .compat-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f4c430, #e8961c);
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            margin-bottom: 6px;
        }

        /* ===== 近隣セクション ===== */
        .nearby-section { margin-bottom: 28px; }
        .nearby-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 14px;
        }
        .nearby-header h3 { font-size: 1rem; font-weight: 700; color: #111; margin: 0; }
        .nearby-header p  { font-size: 0.8rem; color: #999; margin: 0; }
        .nearby-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }
        .nearby-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
        }
        .nearby-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
        .nearby-card-img { width: 100%; height: 100px; object-fit: cover; display: block; }
        .nearby-card-body { padding: 11px 14px 13px; }
        .nearby-card-area {
            font-size: 0.72rem;
            color: #004e92;
            background: #f0f4ff;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .nearby-card-catch { font-size: 0.8rem; font-weight: 700; color: #111; margin: 0 0 3px; line-height: 1.4; }
        .nearby-card-name  { font-size: 0.76rem; color: #888; margin: 0; }

        /* ===== 空状態 ===== */
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
            font-size: 0.9rem;
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
            <span style="font-size:0.875rem; color:#333; font-weight:500;"><?= h($user_name) ?> さん</span>
            <a href="logout.php" class="btn-login">ログアウト</a>
        </nav>
    </div>
</header>

<div class="mypage-wrap">

    <!-- ウェルカムバナー -->
    <div class="welcome-banner">
        <div class="welcome-avatar">👤</div>
        <div class="welcome-text">
            <h2>こんにちは、<?= h($user_name) ?> さん</h2>
            <p>今日はどんなプロを探しますか？</p>
        </div>
        <?php if ($diag_type && isset($type_labels[$diag_type])): ?>
        <div class="diag-badge">
            <span class="emoji"><?= $type_labels[$diag_type][1] ?></span>
            <?= h($type_labels[$diag_type][0]) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 関心事選択 -->
    <div class="interest-section">
        <div class="interest-section-header">
            <h3>今のあなたに当てはまる関心事は？</h3>
            <p>選択した関心事に強いプロをおすすめします（複数選択可）</p>
        </div>
        <div class="interest-chips" id="interest-chips">
            <?php foreach ($all_interests as $kw): ?>
            <button
                class="interest-chip <?= in_array($kw, $user_interests) ? 'active' : '' ?>"
                data-interest="<?= h($kw) ?>"
                onclick="toggleInterest(this)">
                <?= in_array($kw, $user_interests) ? '✓ ' : '' ?><?= h($kw) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- クイックアクション -->
    <div class="quick-actions">
        <a href="search.php" class="qa-card">
            <span class="qa-icon">🔍</span>
            <h3>プロを探す</h3>
            <p>エリア・タグで検索</p>
        </a>
        <a href="diagnosis.php" class="qa-card">
            <span class="qa-icon">📋</span>
            <h3>ぴったり診断</h3>
            <p><?= $diag_type ? '再診断する' : '診断してみる' ?></p>
        </a>
        <?php if ($diag_type): ?>
        <a href="search.php?type=<?= h($diag_type) ?>" class="qa-card">
            <span class="qa-icon">✨</span>
            <h3>相性のいいプロ</h3>
            <p>診断結果でマッチング</p>
        </a>
        <?php endif; ?>
        <a href="messages_list.php" class="qa-card" style="position:relative;">
            <span class="qa-icon">💬</span>
            <h3>メッセージ</h3>
            <p>プロとやり取り</p>
            <?php if ($unread_msg_count > 0): ?>
            <span style="position:absolute; top:10px; right:10px; background:#e91e63; color:#fff; font-size:0.7rem; font-weight:bold; padding:2px 8px; border-radius:12px;"><?= $unread_msg_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- あなたへのおすすめのプロ -->
    <?php if (!empty($recommended)): ?>
    <div class="recommend-section">
        <div class="recommend-header">
            <h3>✨ あなたへのおすすめのプロ</h3>
            <p>選択した関心事と診断タイプからピックアップ</p>
        </div>
        <div class="recommend-scroll">
            <?php foreach ($recommended as $r): ?>
            <?php
                $rec_img = $r['profile_img']
                    ? 'uploads/' . $r['profile_img']
                    : 'https://picsum.photos/seed/agent' . $r['id'] . '/300/200';
                // 合致した関心事キーワードを探す
                $matched_kw = '';
                foreach ($user_interests as $kw) {
                    if (!empty($r['tags']) && mb_strpos(mb_strtolower($r['tags']), mb_strtolower($kw)) !== false) {
                        $matched_kw = $kw;
                        break;
                    }
                }
                // 相性
                $rec_compat = '';
                if ($user_score !== null && isset($r['diagnosis_score']) && $r['diagnosis_score'] !== null) {
                    $c = 100 - abs($user_score - (int)$r['diagnosis_score']);
                    $rec_compat = '<span class="compat-badge" style="display:inline-block;font-size:0.7rem;margin-bottom:4px;">✨ 相性 ' . $c . '%</span>';
                }
            ?>
            <a href="profile.php?id=<?= $r['id'] ?>" class="rec-card">
                <div class="rec-card-img-wrap">
                    <img src="<?= h($rec_img) ?>" class="rec-card-img" alt="<?= h($r['name']) ?>">
                </div>
                <div class="rec-card-body">
                    <?php if ($matched_kw): ?>
                    <span class="interest-badge">💡 <?= h($matched_kw) ?>に強いプロ</span><br>
                    <?php endif; ?>
                    <?= $rec_compat ?>
                    <p class="rec-card-catch"><?= h(mb_substr($r['title'] ?? '', 0, 28)) ?><?= mb_strlen($r['title'] ?? '') > 28 ? '…' : '' ?></p>
                    <p class="rec-card-name"><?= h($r['name']) ?></p>
                    <span class="rec-card-area">📍 <?= h($r['area'] ?: '未設定') ?></span>
                </div>
            </a>
            <?php endforeach; ?>
            <a href="search.php" class="rec-card rec-card-more">
                <div>
                    <span class="rec-more-icon">→</span>
                    <span>もっと見る</span>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 近隣のおすすめプロ -->
    <?php if (!empty($nearby_agents)): ?>
    <div class="nearby-section">
        <div class="nearby-header">
            <h3>📍 近隣のおすすめプロ（<?= h($user_area) ?>）</h3>
            <p>あなたのエリアで活動しているプロフェッショナル</p>
        </div>
        <div class="nearby-grid">
            <?php foreach ($nearby_agents as $na): ?>
            <?php
                $na_img = $na['profile_img']
                    ? 'uploads/' . $na['profile_img']
                    : 'https://picsum.photos/seed/agent' . $na['id'] . '/400/200';
                // エリア表示
                $na_area = $na['area'] ?? '';
                if (!empty($na['area_detail'])) {
                    $na_parts = array_map('trim', explode(',', $na['area_detail']));
                    if (!empty($na_parts[0])) { $na_area .= ' ' . $na_parts[0]; }
                }
                // 相性バッジ
                $na_compat = '';
                if ($user_score !== null && isset($na['diagnosis_score']) && $na['diagnosis_score'] !== null) {
                    $c = 100 - abs($user_score - (int)$na['diagnosis_score']);
                    $na_compat = '<span class="compat-badge" style="display:inline-block;font-size:0.7rem;margin-bottom:5px;">✨ 相性 ' . $c . '%</span><br>';
                }
            ?>
            <a href="profile.php?id=<?= $na['id'] ?>" class="nearby-card">
                <img src="<?= h($na_img) ?>" class="nearby-card-img" alt="<?= h($na['name']) ?>">
                <div class="nearby-card-body">
                    <?= $na_compat ?>
                    <span class="nearby-card-area">📍 <?= h($na_area) ?></span>
                    <p class="nearby-card-catch"><?= h(mb_substr($na['title'] ?? '', 0, 28)) ?><?= mb_strlen($na['title'] ?? '') > 28 ? '…' : '' ?></p>
                    <p class="nearby-card-name"><?= h($na['name']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- タブナビ -->
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="favorites">
            ❤️ お気に入り
            <span class="count"><?= count($favorites) ?></span>
        </button>
        <button class="tab-btn" data-tab="my_agents">
            ⭐ My Agent
            <span class="count"><?= count($my_agents) ?></span>
        </button>
    </div>

    <!-- お気に入りタブ -->
    <div class="tab-content active" id="tab-favorites">
        <?php if (count($favorites) > 0): ?>
        <div class="agent-list">
            <?php foreach ($favorites as $a): ?>
            <?php
                $img = $a['profile_img']
                    ? 'uploads/' . $a['profile_img']
                    : 'https://picsum.photos/seed/agent' . $a['id'] . '/600/360';
                $fav_compat = '';
                if ($user_score !== null && isset($a['diagnosis_score']) && $a['diagnosis_score'] !== null) {
                    $c = 100 - abs($user_score - (int)$a['diagnosis_score']);
                    $fav_compat = '<span class="compat-badge">✨ 相性 ' . $c . '%</span>';
                }
            ?>
            <div class="agent-card" id="fav-card-<?= $a['id'] ?>">
                <img src="<?= h($img) ?>" class="agent-card-img" alt="<?= h($a['name']) ?>">
                <div class="agent-card-body">
                    <?= $fav_compat ?>
                    <span class="area-tag">📍 <?= h($a['area'] ?: '未設定') ?></span>
                    <h3><?= h($a['name']) ?></h3>
                    <p class="catch"><?= h(mb_substr($a['title'] ?? '', 0, 40)) ?></p>
                </div>
                <div class="agent-card-actions">
                    <a href="profile.php?id=<?= $a['id'] ?>" class="btn-profile">プロフィールを見る</a>
                    <button class="btn-remove" onclick="removeFavorite(<?= $a['id'] ?>, 'fav-card-<?= $a['id'] ?>')">削除</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">❤️</span>
            <p>まだお気に入りに追加していません。<br>気になるプロを♡でお気に入り登録しましょう。</p>
            <a href="search.php">プロを探す</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- My Agentタブ -->
    <div class="tab-content" id="tab-my_agents">
        <?php if (count($my_agents) > 0): ?>
        <div class="agent-list">
            <?php foreach ($my_agents as $a): ?>
            <?php
                $img = $a['profile_img']
                    ? 'uploads/' . $a['profile_img']
                    : 'https://picsum.photos/seed/agent' . $a['id'] . '/600/360';
                $ma_compat = '';
                if ($user_score !== null && isset($a['diagnosis_score']) && $a['diagnosis_score'] !== null) {
                    $c = 100 - abs($user_score - (int)$a['diagnosis_score']);
                    $ma_compat = '<span class="compat-badge">✨ 相性 ' . $c . '%</span>';
                }
            ?>
            <div class="agent-card" id="myagent-card-<?= $a['id'] ?>">
                <span class="my-agent-badge">⭐ My Agent</span>
                <img src="<?= h($img) ?>" class="agent-card-img" alt="<?= h($a['name']) ?>">
                <div class="agent-card-body">
                    <?= $ma_compat ?>
                    <span class="area-tag">📍 <?= h($a['area'] ?: '未設定') ?></span>
                    <h3><?= h($a['name']) ?></h3>
                    <p class="catch"><?= h(mb_substr($a['title'] ?? '', 0, 40)) ?></p>
                </div>
                <div class="agent-card-actions">
                    <a href="profile.php?id=<?= $a['id'] ?>" class="btn-profile">プロフィールを見る</a>
                    <a href="review_post.php?agent_id=<?= $a['id'] ?>" class="btn-review">★ クチコミ</a>
                    <button class="btn-remove" onclick="removeMyAgent(<?= $a['id'] ?>, 'myagent-card-<?= $a['id'] ?>')">解除</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">⭐</span>
            <p>まだMy Agentが登録されていません。<br>プロフィールページから「My Agentに登録」してみましょう。</p>
            <a href="search.php">プロを探す</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- 退会セクション -->
    <div style="margin-top:48px; padding-top:32px; border-top:1px solid #e0e0e0; text-align:center;">
        <p style="font-size:0.85rem; color:#aaa; margin-bottom:12px;">アカウントを削除する場合はこちら</p>
        <form action="withdraw_act.php" method="post"
              onsubmit="return confirm('本当に退会しますか？\n退会するとアカウント情報が削除され、元に戻せません。');">
            <button type="submit"
                    style="padding:10px 28px; background:#fff; color:#dc3545; border:1px solid #dc3545;
                           border-radius:6px; font-size:0.88rem; cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.background='#dc3545';this.style.color='#fff';"
                    onmouseout="this.style.background='#fff';this.style.color='#dc3545';">
                退会する
            </button>
        </form>
    </div>

</div>

<script>
// 関心事チップのトグル
function toggleInterest(el) {
    const interest = el.dataset.interest;
    el.classList.add('saving');

    fetch('update_interest.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'interest=' + encodeURIComponent(interest)
    })
    .then(r => r.json())
    .then(data => {
        el.classList.remove('saving');
        if (data.result === 'added') {
            el.classList.add('active');
            el.textContent = '✓ ' + interest;
        } else {
            el.classList.remove('active');
            el.textContent = interest;
        }
        // おすすめ枠を最新状態に同期
        setTimeout(() => location.reload(), 350);
    })
    .catch(() => el.classList.remove('saving'));
}

// タブ切り替え
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

// お気に入り削除
function removeFavorite(agentId, cardId) {
    if (!confirm('お気に入りから削除しますか？')) return;
    fetch('favorite_act.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'agent_id=' + agentId + '&action=favorite'
    })
    .then(r => r.json())
    .then(data => {
        if (data.result === 'removed') {
            const card = document.getElementById(cardId);
            card.style.opacity = '0';
            card.style.transition = 'opacity 0.3s';
            setTimeout(() => card.remove(), 300);
        }
    });
}

// My Agent解除
function removeMyAgent(agentId, cardId) {
    if (!confirm('My Agentの登録を解除しますか？')) return;
    fetch('favorite_act.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'agent_id=' + agentId + '&action=my_agent'
    })
    .then(r => r.json())
    .then(data => {
        if (data.result === 'removed') {
            const card = document.getElementById(cardId);
            card.style.opacity = '0';
            card.style.transition = 'opacity 0.3s';
            setTimeout(() => card.remove(), 300);
        }
    });
}
</script>
</body>
</html>
