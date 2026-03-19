<?php
session_start();
include("function.php");
loginCheck('agent');
check_agent_approval();

$id = (int)$_SESSION["id"];
$pdo = db_conn();

// area_detailカラムを自動追加
try {
    $pdo->exec("ALTER TABLE agents ADD COLUMN area_detail VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}

$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県',
    '茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県',
    '新潟県','富山県','石川県','福井県','山梨県','長野県',
    '岐阜県','静岡県','愛知県','三重県',
    '滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県',
    '鳥取県','島根県','岡山県','広島県','山口県',
    '徳島県','香川県','愛媛県','高知県',
    '福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];
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
                    <label class="form-label">活動エリア（都道府県）</label>
                    <select name="area" class="form-control">
                        <option value="">-- 選択してください --</option>
                        <?php foreach ($prefectures as $p): ?>
                        <option value="<?= h($p) ?>" <?= ($row["area"] ?? '') === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">主な活動市区町村</label>
                    <input type="text" name="area_detail" class="form-control"
                           value="<?= h($row["area_detail"] ?? '') ?>"
                           placeholder="例: 世田谷区, 渋谷区, 横浜市全域">
                    <p style="font-size:0.78rem; color:#aaa; margin-top:6px;">カンマ区切りで複数入力可。ユーザーのキーワード検索にも使われます。</p>
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

                <div class="form-group">
                    <label class="form-label">営業スタイル自己評価</label>
                    <div style="display:flex; justify-content:space-between; font-size:0.78rem; color:#888; margin-bottom:6px;">
                        <span>感情・寄り添い重視<br>（支援先行型）</span>
                        <span style="text-align:right;">論理・データ重視<br>（価値伝達型）</span>
                    </div>
                    <input type="range" name="diagnosis_score" min="0" max="100" step="10"
                           value="<?= (int)($row['diagnosis_score'] ?? 50) ?>"
                           oninput="document.getElementById('scoreVal').textContent=this.value"
                           style="width:100%; accent-color:#004e92;">
                    <div style="text-align:center; font-size:0.88rem; color:#004e92; font-weight:bold; margin-top:6px;">
                        現在の値：<span id="scoreVal"><?= (int)($row['diagnosis_score'] ?? 50) ?></span> 点
                    </div>
                    <p style="font-size:0.78rem; color:#aaa; margin-top:6px;">
                        0〜39: 支援先行型 ／ 40〜59: ハイブリッド型 ／ 60〜100: 価値伝達型
                    </p>
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
