<?php
include("function.php");

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    redirect("search.php");
}

$pdo = db_conn();
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status == false) {
    sql_error($stmt);
}
$row = $stmt->fetch();
if (!$row) {
    redirect("search.php");
}

// 画像処理
$img = $row['profile_img'] ? 'uploads/'.$row['profile_img'] : 'https://placehold.co/800x300/e0e0e0/888?text=No+Image';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($row["name"]) ?> - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="search.php" style="font-size:0.9rem;">← 一覧に戻る</a>
            <div class="logo">ERAPRO</div>
            <div style="width:60px;"></div> </div>
    </header>

    <img src="<?= $img ?>" style="width:100%; height:300px; object-fit:cover;">

    <div class="container" style="max-width:700px; background:#fff; margin-top:-50px; position:relative; border-radius:10px; padding:40px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
        
        <div class="profile-header">
            <span style="background:#004e92; color:#fff; padding:5px 15px; border-radius:20px; font-size:0.8rem;"><?= h($row["area"]) ?></span>
            <h1 style="margin:15px 0 5px;"><?= h($row["name"]) ?></h1>
            <p style="font-weight:bold; font-size:1.1rem; color:#004e92;"><?= h($row["title"]) ?></p>
        </div>

        <h2 class="section-title">My Story (原体験)</h2>
        <div class="narrative-text">
            <?= h($row["story"]) ?>
        </div>

        <h2 class="section-title">Philosophy (哲学)</h2>
        <div class="narrative-text">
            <?= h($row["philosophy"]) ?>
        </div>

        <div style="margin-top:50px; text-align:center;">
            <a href="#" class="btn-search" style="background:#004e92; width:100%; box-sizing:border-box;">この人に相談する</a>
        </div>

    </div>
</body>
</html>