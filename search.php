<?php
include("function.php");
$pdo = db_conn();

// 1. 検索条件の受け取り
$area = isset($_GET["area"]) ? $_GET["area"] : ""; // エリア
$tag  = isset($_GET["tag"])  ? $_GET["tag"]  : ""; // タグ（フリーワード）

// 2. SQLの組み立て
$sql = "SELECT * FROM agents WHERE life_flg=0";

// エリアが選択されていたら絞り込む
if(!empty($area)){
    $sql .= " AND area = :area";
}
// タグ入力があったら絞り込む（部分一致）
if(!empty($tag)){
    $sql .= " AND (tags LIKE :tag OR title LIKE :tag OR story LIKE :tag)";
}

$sql .= " ORDER BY id DESC";

// 3. 実行
$stmt = $pdo->prepare($sql);

if(!empty($area)){
    $stmt->bindValue(':area', $area, PDO::PARAM_STR);
}
if(!empty($tag)){
    $stmt->bindValue(':tag', '%'.$tag.'%', PDO::PARAM_STR);
}

$status = $stmt->execute();

// 4. 表示作成
$view = "";
if ($status == false) {
    sql_error($stmt);
} else {
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $img = $r['profile_img'] ? 'uploads/'.$r['profile_img'] : 'https://placehold.co/400x200/e0e0e0/888?text=No+Image';
        
        $view .= '<a href="profile.php?id='.$r["id"].'" class="card">';
        $view .= '<img src="'.$img.'" style="width:100%; height:150px; object-fit:cover;">';
        $view .= '<div class="card-body">';
        $view .= '<h3 style="margin:0 0 5px 0; color:#004e92;">'.h($r["name"]).'</h3>';
        $view .= '<p style="font-weight:bold; font-size:0.9rem; margin-bottom:10px;">'.h($r["title"]).'</p>';
        $view .= '<div style="margin-bottom:10px;">';
        
        $tags = explode(",", $r["tags"]);
        foreach($tags as $t){
            if(!empty($t)) $view .= '<span class="tag">#'.h($t).'</span>';
        }
        
        $view .= '</div>';
        $view .= '<p style="font-size:0.85rem; color:#666;">'.h(mb_substr($r["story"],0,40)).'...</p>';
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
    <header>
        <div class="header-inner">
            <a href="index.php" class="logo">ERAPRO</a>
        </div>
    </header>

    <div class="container">
        <h2>プロフェッショナルを探す</h2>

        <form action="search.php" method="get" class="search-box">
            <select name="area" class="search-select">
                <option value="">エリアを選択</option>
                <option value="東京都">東京都</option>
                <option value="大阪府">大阪府</option>
                <option value="福岡県">福岡県</option>
                </select>
            <input type="text" name="tag" class="search-input" placeholder="キーワード（例: 子育て, 相続）">
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