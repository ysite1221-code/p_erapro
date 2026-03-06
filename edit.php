<?php
session_start();
include("function.php");
loginCheck('agent');

$id = (int)$_SESSION["id"];
$pdo = db_conn();
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集 - ERAPRO Agent</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <?php include("header_agent.php"); ?>

    <div class="form-wrap">
        <div class="form-card">
            <h2>プロフィール編集</h2>

            <form method="POST" action="update.php" enctype="multipart/form-data">

                <div class="form-group">
                    <label class="form-label">プロフィール画像</label>
                    <?php if ($row["profile_img"]): ?>
                    <div style="margin-bottom:12px;">
                        <img src="uploads/<?= h($row["profile_img"]) ?>"
                             style="width:72px; height:72px; object-fit:cover; border-radius:50%; border:3px solid #f0f0f0; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="upfile" accept="image/*" style="font-size:0.875rem; color:#555;">
                </div>

                <div class="form-group">
                    <label class="form-label">お名前 / 活動名</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= h($row["name"]) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">活動エリア</label>
                    <select name="area" class="form-control">
                        <option value="東京都" <?= $row["area"] === '東京都' ? 'selected' : '' ?>>東京都</option>
                        <option value="大阪府" <?= $row["area"] === '大阪府' ? 'selected' : '' ?>>大阪府</option>
                        <option value="福岡県" <?= $row["area"] === '福岡県' ? 'selected' : '' ?>>福岡県</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">キャッチコピー</label>
                    <input type="text" name="title" class="form-control"
                           value="<?= h($row["title"]) ?>"
                           placeholder="例: 子育て世代の保険相談、気軽に話しましょう">
                </div>

                <div class="form-group">
                    <label class="form-label">得意分野タグ（カンマ区切り）</label>
                    <input type="text" name="tags" class="form-control"
                           value="<?= h($row["tags"]) ?>"
                           placeholder="例: 子育て, 資産形成, 経営者">
                </div>

                <div class="form-group">
                    <label class="form-label">My Story（原体験）</label>
                    <textarea name="story" class="form-control form-control-textarea"
                              placeholder="なぜこの仕事を選んだのか、原点となる体験を書いてみましょう。"><?= h($row["story"]) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Philosophy（哲学）</label>
                    <textarea name="philosophy" class="form-control form-control-textarea"
                              placeholder="あなたが大切にしていること、仕事への姿勢を書いてみましょう。"><?= h($row["philosophy"]) ?></textarea>
                </div>

                <button type="submit" class="btn-submit">プロフィールを更新する</button>

            </form>

            <div style="text-align:center; margin-top:20px;">
                <a href="mypage.php" style="font-size:0.875rem; color:#999;">← ダッシュボードに戻る</a>
            </div>
        </div>
    </div>

</body>
</html>
