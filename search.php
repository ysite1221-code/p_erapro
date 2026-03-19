<?php
session_start();
include("function.php");
$pdo = db_conn();

// area_detailカラムを自動追加
try {
    $pdo->exec("ALTER TABLE agents ADD COLUMN area_detail VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}

// reviewsテーブルがなければ作成
$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    agent_id   INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_agent (user_id, agent_id),
    INDEX idx_agent (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
    '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
    '新潟県','富山県','石川県','福井県','山梨県','長野県',
    '岐阜県','静岡県','愛知県','三重県',
    '滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県',
    '鳥取県','島根県','岡山県','広島県','山口県',
    '徳島県','香川県','愛媛県','高知県',
    '福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];

// 1. 検索条件の受け取り
$area      = isset($_GET["area"]) ? $_GET["area"] : "";
$tag       = isset($_GET["tag"])  ? $_GET["tag"]  : "";
$diag_type = isset($_GET["type"]) ? $_GET["type"] : "";

// 診断タイプ → バナー情報（新3タイプ）
$type_info = [
    'logic_seeker'   => ['label' => '論理・データ重視タイプ', 'emoji' => '📊', 'color' => '#004e92'],
    'empathy_seeker' => ['label' => 'バランス重視タイプ',     'emoji' => '🤝', 'color' => '#2e7d32'],
    'support_seeker' => ['label' => '感情・寄り添い重視タイプ','emoji' => '💛', 'color' => '#e65100'],
];
$matched_type = isset($type_info[$diag_type]) ? $type_info[$diag_type] : null;

// ログイン中ユーザーのスコア・関心事をDBから取得
$user_score     = null;
$user_interests = [];
if (isset($_SESSION['id'], $_SESSION['user_type']) && $_SESSION['user_type'] === 'user') {
    $stmt_u = $pdo->prepare("SELECT diagnosis_score, interests FROM users WHERE id=:uid AND life_flg=0");
    $stmt_u->bindValue(':uid', (int)$_SESSION['id'], PDO::PARAM_INT);
    $stmt_u->execute();
    $user_row = $stmt_u->fetch(PDO::FETCH_ASSOC);
    if ($user_row) {
        $sc = $user_row['diagnosis_score'];
        if ($sc !== false && $sc !== null) {
            $user_score = (int)$sc;
        }
        $int_str = $user_row['interests'] ?? '';
        if (!empty($int_str)) {
            $user_interests = array_values(array_filter(array_map('trim', explode(',', $int_str))));
        }
    }
}

// 2. SQLの組み立て
$sql    = "SELECT a.*, ROUND(AVG(r.rating),1) AS avg_rating, COUNT(r.id) AS review_count FROM agents a LEFT JOIN reviews r ON r.agent_id=a.id WHERE a.life_flg=0 AND a.verification_status=2 AND a.profile_img != '' AND a.title != '' AND a.story != ''";
$params = [];

if (!empty($area)) {
    $sql .= " AND a.area = :area";
    $params[':area'] = $area;
}
if (!empty($tag)) {
    $sql .= " AND (a.tags LIKE :tag OR a.title LIKE :tag OR a.story LIKE :tag OR a.area_detail LIKE :tag)";
    $params[':tag'] = '%' . $tag . '%';
}

// ORDER BY の組み立て
// 優先度: ①関心事合致 → ②スコア相性 → ③登録順
$order_parts = [];

if (!empty($user_interests)) {
    $int_cases = [];
    foreach ($user_interests as $i => $kw) {
        $key = ':iord' . $i;
        $int_cases[] = "a.tags LIKE $key";
        $params[$key] = '%' . $kw . '%';
    }
    $order_parts[] = "CASE WHEN (" . implode(' OR ', $int_cases) . ") THEN 0 ELSE 1 END ASC";
}

if ($user_score !== null) {
    $order_parts[] = "ABS(COALESCE(a.diagnosis_score, 50) - :user_score) ASC";
    $params[':user_score'] = $user_score;
}

$order_parts[] = "a.id DESC";
$sql .= " GROUP BY a.id ORDER BY " . implode(', ', $order_parts);

// 3. 実行
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $type = ($k === ':user_score') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}

$status = $stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. カードHTML生成
$view = "";
if ($status == false) {
    sql_error($stmt);
} else {
    foreach ($rows as $r) {
        // 画像なしの場合は picsum.photos で一貫した写真を使用
        $img = !empty($r['profile_img'])
            ? (strpos($r['profile_img'], 'http') === 0 ? $r['profile_img'] : 'uploads/' . $r['profile_img'])
            : 'https://picsum.photos/seed/agent' . $r['id'] . '/600/360';

        $tags_html = '';
        $tags = explode(",", $r["tags"] ?? '');
        foreach ($tags as $t) {
            if (!empty(trim($t))) {
                $tags_html .= '<span class="tag">#' . h(trim($t)) . '</span>';
            }
        }

        // 関心事ハイライトバッジ
        $interest_badge_html = '';
        if (!empty($user_interests) && !empty($r['tags'])) {
            $agent_tags_lower = mb_strtolower($r['tags']);
            foreach ($user_interests as $kw) {
                if (mb_strpos($agent_tags_lower, mb_strtolower($kw)) !== false) {
                    $interest_badge_html = '<span class="interest-badge">💡 あなたの関心（' . h($kw) . '）に強いプロです</span>';
                    break;
                }
            }
        }

        // 相性バッジ
        $compat_html = '';
        if ($user_score !== null && isset($r['diagnosis_score']) && $r['diagnosis_score'] !== null) {
            $compat = 100 - abs($user_score - (int)$r['diagnosis_score']);
            $compat_html = '<span class="compat-badge">✨ 相性 ' . $compat . '%</span>';
        }

        // クチコミ評価
        if (!empty($r['avg_rating']) && (int)$r['review_count'] > 0) {
            $rating_html = '<div class="card-rating">★ ' . number_format((float)$r['avg_rating'], 1) . ' <span class="rating-count">(' . (int)$r['review_count'] . '件)</span></div>';
        } else {
            $rating_html = '<div class="card-rating card-rating-empty">クチコミ未投稿</div>';
        }

        // エリア表示（都道府県 + 市区町村の先頭1件）
        $area_chip = $r['area'] ?: '未設定';
        if (!empty($r['area_detail'])) {
            $detail_parts = array_map('trim', explode(',', $r['area_detail']));
            if (!empty($detail_parts[0])) {
                $area_chip .= ' ' . $detail_parts[0];
            }
        }

        $view .= '<a href="profile.php?id=' . $r["id"] . '" class="card">';
        $view .= '<div class="card-img-wrap">';
        $view .= '<img src="' . h($img) . '" class="card-img" alt="' . h($r["name"]) . '">';
        $view .= '<span class="card-area-chip">📍 ' . h($area_chip) . '</span>';
        $view .= '</div>';
        $view .= '<div class="card-body">';
        if ($interest_badge_html) { $view .= $interest_badge_html; }
        if ($compat_html) { $view .= $compat_html; }
        $view .= $rating_html;
        $view .= '<p class="card-catch">' . h(mb_substr($r["title"] ?? '', 0, 45)) . '</p>';
        $view .= '<h3 class="card-name">' . h($r["name"]) . '</h3>';
        if ($tags_html) {
            $view .= '<div class="card-tags">' . $tags_html . '</div>';
        }
        $view .= '<p class="card-story">' . h(mb_substr($r["story"] ?? '', 0, 52)) . (mb_strlen($r["story"] ?? '') > 52 ? '…' : '') . '</p>';
        $view .= '</div>';
        $view .= '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロを探す - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f5f5f5; }

        /* ===== ページヘッダー ===== */
        .page-header {
            background: #fff;
            border-bottom: 1px solid #ebebeb;
            padding: 48px 28px 40px;
        }
        .page-header-inner {
            max-width: 1040px;
            margin: 0 auto;
        }
        .page-header h1 {
            font-size: 2rem;
            font-weight: 900;
            color: #111;
            margin: 0 0 6px;
            letter-spacing: -0.02em;
        }
        .page-header p {
            font-size: 0.9rem;
            color: #999;
            margin: 0;
        }

        /* ===== 検索フォーム ===== */
        .search-wrap {
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 28px;
        }
        .search-box {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 24px 28px;
            margin: 32px 0 0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-select,
        .search-input {
            padding: 12px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #333;
            background: #fafafa;
            transition: border-color 0.2s;
        }
        .search-select { min-width: 140px; }
        .search-input { flex: 1; min-width: 180px; }
        .search-select:focus,
        .search-input:focus { outline: none; border-color: #004e92; background: #fff; }
        .btn-submit {
            background: #004e92;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: 0.03em;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #003a70; }

        /* ===== 診断バナー ===== */
        .diag-banner {
            border-radius: 8px;
            padding: 20px 24px;
            margin-top: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #fff;
        }
        .diag-banner-emoji { font-size: 2rem; line-height: 1; }
        .diag-banner-label { font-size: 0.78rem; opacity: 0.8; margin-bottom: 2px; }
        .diag-banner-title { font-size: 1.05rem; font-weight: 700; }
        .diag-filter-badge {
            font-size: 0.78rem;
            background: rgba(255,255,255,0.2);
            padding: 3px 12px;
            border-radius: 4px;
            margin-left: 12px;
        }
        .diag-retry-link {
            margin-left: auto;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.75);
            white-space: nowrap;
        }
        .diag-retry-link:hover { color: #fff; }

        /* ===== 件数表示 ===== */
        .result-meta {
            max-width: 1040px;
            margin: 32px auto 0;
            padding: 0 28px;
            font-size: 0.85rem;
            color: #999;
            font-weight: 500;
        }

        /* ===== カードグリッド ===== */
        .card-list-wrap {
            max-width: 1040px;
            margin: 16px auto 80px;
            padding: 0 28px;
        }

        /* ===== カード ===== */
        .card-img-wrap {
            position: relative;
            overflow: hidden;
        }
        .card-img {
            width: 100%;
            height: 210px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .card:hover .card-img { transform: scale(1.04); }
        .card-area-chip {
            position: absolute;
            bottom: 10px;
            left: 12px;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 4px;
            backdrop-filter: blur(4px);
        }
        .card-body { padding: 20px 20px 22px; }
        .card-catch {
            font-size: 0.92rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 6px;
            line-height: 1.5;
        }
        .card-name {
            font-size: 0.82rem;
            font-weight: 500;
            color: #888;
            margin: 0 0 12px;
        }
        .card-tags { margin-bottom: 12px; }
        .card-story {
            font-size: 0.83rem;
            color: #999;
            line-height: 1.7;
            margin: 0;
        }

        /* ===== 関心事ハイライトバッジ ===== */
        .interest-badge {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        /* ===== 相性バッジ ===== */
        .compat-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f4c430, #e8961c);
            color: #fff;
            padding: 3px 10px;
            border-radius: 12px;
            margin-bottom: 8px;
        }

        /* ===== クチコミ評価 ===== */
        .card-rating {
            font-size: 0.78rem;
            font-weight: 700;
            color: #e6a800;
            margin-bottom: 6px;
        }
        .card-rating.card-rating-empty {
            color: #ccc;
            font-weight: 400;
        }
        .rating-count {
            color: #999;
            font-weight: 400;
        }

        /* ===== 空状態 ===== */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #bbb;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <!-- ページヘッダー -->
    <div class="page-header">
        <div class="page-header-inner">
            <h1>プロフェッショナルを探す</h1>
            <p>エリア・キーワードで、あなたに合う保険のプロを見つけましょう。</p>
        </div>
    </div>

    <div class="search-wrap">
        <!-- 診断バナー -->
        <?php if ($matched_type): ?>
        <div class="diag-banner" style="background:<?= h($matched_type['color']) ?>;">
            <span class="diag-banner-emoji"><?= $matched_type['emoji'] ?></span>
            <div>
                <div class="diag-banner-label">診断タイプ</div>
                <div class="diag-banner-title">
                    <?= h($matched_type['label']) ?>
                    <?php if ($user_score !== null): ?>
                    <span class="diag-filter-badge">相性順に表示中</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="diagnosis.php?retry=1" class="diag-retry-link">再診断する →</a>
        </div>
        <?php endif; ?>

        <!-- 検索フォーム -->
        <form action="search.php" method="get" class="search-box">
            <select name="area" class="search-select">
                <option value="">都道府県を選択</option>
                <?php foreach ($prefectures as $p): ?>
                <option value="<?= h($p) ?>" <?= $area === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="tag" class="search-input"
                   placeholder="キーワード（例: 子育て, 相続）"
                   value="<?= h($tag) ?>">
            <?php if (!empty($diag_type)): ?>
            <input type="hidden" name="type" value="<?= h($diag_type) ?>">
            <?php endif; ?>
            <input type="submit" value="検索" class="btn-submit">
        </form>
    </div>

    <!-- 件数 -->
    <div class="result-meta">
        <?= count($rows) ?> 件のプロフェッショナル
    </div>

    <!-- カードグリッド -->
    <div class="card-list-wrap">
        <div class="card-list">
            <?php if (!empty($rows)): ?>
                <?= $view ?>
            <?php else: ?>
                <div class="empty-state">
                    条件に一致するプロフェッショナルは見つかりませんでした。
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
