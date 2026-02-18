<?php
session_start();
include("function.php");
loginCheck('agent');

$id = $_SESSION["id"];
$pdo = db_conn();
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$status = $stmt->execute();
$row = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール編集 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        textarea { height: 150px; }
        .btn-submit { background: #004e92; color: #fff; border: none; padding: 15px; width: 100%; font-size: 1.1rem; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container" style="margin-top:40px;">
        <div class="form-container">
            <h2 style="text-align:center; margin-bottom:30px;">プロフィール編集</h2>
            
            <form method="POST" action="update.php" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>プロフィール画像</label>
                    <?php if($row["profile_img"]): ?>
                        <img src="uploads/<?= h($row["profile_img"]) ?>" style="width:100px; margin-bottom:10px;"><br>
                    <?php endif; ?>
                    <input type="file" name="upfile" accept="image/*">
                </div>

                <div class="form-group">
                    <label>お名前</label>
                    <input type="text" name="name" value="<?= h($row["name"]) ?>" required>
                </div>

                <div class="form-group">
                    <label>活動エリア</label>
                    <select name="area">
                        <option value="東京都" <?= $row["area"]=='東京都'?'selected':'' ?>>東京都</option>
                        <option value="大阪府" <?= $row["area"]=='大阪府'?'selected':'' ?>>大阪府</option>
                        <option value="福岡県" <?= $row["area"]=='福岡県'?'selected':'' ?>>福岡県</option>
                        </select>
                </div>

                <div class="form-group">
                    <label>キャッチコピー (Title)</label>
                    <input type="text" name="title" value="<?= h($row["title"]) ?>">
                </div>

                <div class="form-group">
                    <label>得意分野タグ (カンマ区切り)</label>
                    <input type="text" name="tags" value="<?= h($row["tags"]) ?>" placeholder="例: 子育て, 資産形成, 経営者">
                </div>

                <div class="form-group">
                    <label>My Story (原体験)</label>
                    <textarea name="story"><?= h($row["story"]) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Philosophy (哲学)</label>
                    <textarea name="philosophy"><?= h($row["philosophy"]) ?></textarea>
                </div>

                <input type="submit" value="更新する" class="btn-submit">
            </form>
            <div style="text-align:center; margin-top:20px;">
                <a href="mypage.php">マイページに戻る</a>
            </div>
        </div>
    </div>
</body>
</html>