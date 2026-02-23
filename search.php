<?php
include("function.php");
$pdo = db_conn();

// 1. 検索条件の受け取り
$area      = isset($_GET["area"]) ? $_GET["area"] : "";
$tag       = isset($_GET["tag"])  ? $_GET["tag"]  : "";
$diag_type = isset($_GET["type"]) ? $_GET["type"] : "";

// 診断タイプ → バナー情報 & 推奨タグキーワード
$type_info = [
    'logical'    => ['label' => '論理的ストラテジスト', 'emoji' => '📊', 'color' => '#004e92', 'keywords' => ['論理派','データ重視','損保']],
    'balanced_l' => ['label' => '着実なプランナー',     'emoji' => '🗂️', 'color' => '#2e7d32', 'keywords' => ['バランス型','丁寧']],
    'balanced_e' => ['label' => '共感重視のパートナー型','emoji' => '🤝', 'color' => '#e65100', 'keywords' => ['寄り添い','親身']],
    'emotional'  => ['label' => '情熱的なサポーター型', 'emoji' => '💛', 'color' => '#c62828', 'keywords' => ['現場力','安心','親身']],
];
$matched_type = isset($type_info[$diag_type]) ? $type_info[$diag_type] : null;

// 2. SQLの組み立て
$sql    = "SELECT * FROM agents WHERE life_flg=0";
$params = [];

if (!empty($area)) {
    $sql .= " AND area = :area";
    $params[':area'] = $area;
}
if (!empty($tag)) {
    $sql .= " AND (tags LIKE :tag OR title LIKE :tag OR story LIKE :tag)";
    $params[':tag'] = '%' . $tag . '%';
}

// 診断タイプによるタグ絞り込み（タグ検索が空の場合のみ適用）
$diag_filter_active = false;
if (!empty($matched_type) && empty($tag)) {
    $keyword_conditions = [];
    foreach ($matched_type['keywords'] as $ki => $kw) {
        $key = ':dtag' . $ki;
        $keyword_conditions[] = "tags LIKE $key";
        $params[$key] = '%' . $kw . '%';
    }
    $sql .= " AND (" . implode(' OR ', $keyword_conditions) . ")";
    $diag_filter_active = true;
}

$sql .= " ORDER BY id DESC";

// 3. 実行
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$status = $stmt->execute();

// 絞り込み結果が0件なら診断フィルタを外して再取得
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($diag_filter_active && count($rows) === 0) {
    $sql2    = "SELECT * FROM agents WHERE life_flg=0";
    $params2 = [];
    if (!empty($area)) { $sql2 .= " AND area = :area"; $params2[':area'] = $area; }
    $sql2 .= " ORDER BY id DESC";
    $stmt2 = $pdo->prepare($sql2);
    foreach ($params2 as $k => $v) $stmt2->bindValue($k, $v, PDO::PARAM_STR);
    $stmt2->execute();
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $diag_filter_active = false; // フォールバック
}

// 4. 表示作成
$view = "";
if ($status == false) {
    sql_error($stmt);
} else {
    foreach ($rows as $r) {
        $img  = $r['profile_img'] ? 'uploads/'.$r['profile_img'] : 'https://placehold.co/400x200/e0e0e0/888?text=No+Image';

        $view .= '<a href="profile.php?id='.$r["id"].'" class="card">';
        $view .= '<img src="'.$img.'" style="width:100%; height:150px; object-fit:cover;">';
        $view .= '<div class="card-body">';
        $view .= '<h3 style="margin:0 0 5px 0; color:#004e92;">'.h($r["name"]).'</h3>';
        $view .= '<p style="font-weight:bold; font-size:0.9rem; margin-bottom:10px;">'.h($r["title"]).'</p>';
        $view .= '<div style="margin-bottom:10px;">';

        $tags = explode(",", $r["tags"] ?? '');
        foreach ($tags as $t) {
            if (!empty(trim($t))) $view .= '<span class="tag">#'.h(trim($t)).'</span>';
        }

        $view .= '</div>';
        $view .= '<p style="font-size:0.85rem; color:#666;">'.h(mb_substr($r["story"] ?? '',0,50)).'...</p>';
        $view .= '</div>';
        $view .= '</a>';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロを探す - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* 検索フォーム用の追加CSS */
        .search-box { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; gap: 10px; flex-wrap: wrap; }
        .search-input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; flex: 1; }
        .search-select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-submit { background: #004e92; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container">
        <h2>プロフェッショナルを探す</h2>

        <?php if ($matched_type): ?>
        <div style="background:<?= h($matched_type['color']) ?>; color:#fff; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; gap:14px;">
            <span style="font-size:2rem;"><?= $matched_type['emoji'] ?></span>
            <div>
                <div style="font-size:0.8rem; opacity:0.8; margin-bottom:2px;">診断タイプ</div>
                <strong style="font-size:1.1rem;"><?= h($matched_type['label']) ?></strong>
                <?php if ($diag_filter_active): ?>
                    <span style="font-size:0.8rem; margin-left:10px; background:rgba(255,255,255,0.2); padding:3px 10px; border-radius:12px;">相性の高いプロを表示中</span>
                <?php endif; ?>
            </div>
            <a href="diagnosis.php?retry=1" style="margin-left:auto; font-size:0.8rem; color:rgba(255,255,255,0.8); white-space:nowrap;">再診断する</a>
        </div>
        <?php endif; ?>

        <form action="search.php" method="get" class="search-box">
            <select name="area" class="search-select">
                <option value="">エリアを選択</option>
                <option value="東京都">東京都</option>
                <option value="大阪府">大阪府</option>
                <option value="福岡県">福岡県</option>
                </select>
            <input type="text" name="tag" class="search-input" placeholder="キーワード（例: 子育て, 相続）">
            <?php if (!empty($diag_type)): ?>
            <input type="hidden" name="type" value="<?= h($diag_type) ?>">
            <?php endif; ?>
            <input type="submit" value="検索" class="btn-submit">
        </form>
        <div class="card-list">
            <?php if($view !== ""): ?>
                <?= $view ?>
            <?php else: ?>
                <p>条件に一致するプロフェッショナルは見つかりませんでした。</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>