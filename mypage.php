<?php
session_start();
include("function.php");
loginCheck(); // ログインチェック

$id = $_SESSION["id"];
$pdo = db_conn();

// データ取得
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status == false) {
    sql_error($stmt);
} else {
    $row = $stmt->fetch();
}

// 画像パス
$img = !empty($row['profile_img']) ? 'uploads/' . $row['profile_img'] : 'https://placehold.co/150x150/e0e0e0/888?text=No+Img';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>マイページ - ERAPRO</title>
    <link rel="stylesheet" href="css/admin.css">
</head>

<body style="background-color: #ffffff; color: #000000;">

    <div style="padding: 20px; border-bottom: 1px solid #ccc;">
        <h1>ERAPRO Agent Dashboard</h1>
        <a href="logout.php">ログアウト</a>
    </div>

    <div style="padding: 20px;">
        <p>ようこそ、<strong><?= h($row["name"]) ?></strong> さん</p>

        <hr>

        <h2>現在のプロフィール</h2>

        <table border="1" cellpadding="10" style="border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top;">
                    <img src="<?= $img ?>" width="150"><br>
                    <br>
                    <a href="edit.php">プロフィールを編集する</a>
                </td>
                <td style="vertical-align: top;">
                    <h3><?= h($row["name"]) ?> (<?= h($row["area"]) ?>)</h3>
                    <p style="font-weight:bold; color:blue;"><?= h($row["title"]) ?></p>
                    <p>タグ: <?= h($row["tags"]) ?></p>

                    <hr>

                    <h4>My Story:</h4>
                    <p><?= h(mb_substr($row["story"] ?? '', 0, 100)) ?>...</p>

                    <br>
                    <a href="profile.php?id=<?= $id ?>" target="_blank">公開ページを確認</a>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>