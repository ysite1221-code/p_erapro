<?php
session_start();
include("function.php");

// 1. ログインチェック
if(!isset($_SESSION["chk_ssid"]) || $_SESSION["chk_ssid"]!=session_id() || $_SESSION["user_type"]!='admin'){
    redirect("login_admin.php");
}

// 2. 対象のAgentID取得
$agent_id = $_GET["id"];

// 3. データ取得
$pdo = db_conn();

// Agent基本情報
$stmt = $pdo->prepare("SELECT * FROM agents WHERE id=:id");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();

// 提出された書類画像 (最新のもの)
$stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE agent_id=:id ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$kyc_doc = $stmt->fetch();

$img_path = $kyc_doc['file_path'] ?? ''; // 画像がない場合は空
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>KYC審査 - ERAPRO Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* ダッシュボード共通CSS (簡易版) */
        body { font-family: sans-serif; background-color: #f4f6f9; margin: 0; display: flex; }
        .sidebar { width: 250px; background-color: #343a40; color: #fff; min-height: 100vh; padding: 20px 0; }
        .sidebar h2 { text-align: center; font-size: 1.2rem; margin-bottom: 30px; }
        .menu a { display: block; color: #c2c7d0; padding: 15px 20px; text-decoration: none; border-bottom: 1px solid #4b545c; }
        .menu a:hover { background-color: #007bff; color: #fff; }
        .content { flex: 1; padding: 30px; }
        
        /* 審査画面固有CSS */
        .kyc-container { display: flex; gap: 30px; }
        .info-box, .image-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .info-box { flex: 1; }
        .image-box { flex: 2; text-align: center; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { width: 30%; color: #666; font-weight: normal; }
        
        .kyc-image { max-width: 100%; max-height: 600px; border: 1px solid #ddd; }
        
        .action-area { margin-top: 30px; text-align: right; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: #fff; font-size: 1rem; margin-left: 10px; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .btn-back { background-color: #6c757d; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 4px; color: #fff; font-size: 1rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ERAPRO ADMIN</h2>
        <div class="menu">
            <a href="admin_dashboard.php">ダッシュボード</a>
        </div>
    </div>

    <div class="content">
        <h2>本人確認書類の審査</h2>
        
        <div class="kyc-container">
            <div class="info-box">
                <h3>申請者情報</h3>
                <table>
                    <tr><th>ID</th><td><?= h($agent['id']) ?></td></tr>
                    <tr><th>氏名</th><td><?= h($agent['name']) ?></td></tr>
                    <tr><th>ログインID</th><td><?= h($agent['lid']) ?></td></tr>
                    <tr><th>活動エリア</th><td><?= h($agent['area']) ?></td></tr>
                    <tr><th>申請日時</th><td><?= h($kyc_doc['uploaded_at'] ?? '未提出') ?></td></tr>
                </table>
            </div>

            <div class="image-box">
                <h3>提出書類</h3>
                <?php if($img_path): ?>
                    <img src="<?= h($img_path) ?>" class="kyc-image">
                    <p><a href="<?= h($img_path) ?>" target="_blank">画像を別タブで開く</a></p>
                <?php else: ?>
                    <div style="padding: 50px; background: #eee; color: #999;">画像が見つかりません</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-area">
            <a href="admin_dashboard.php" class="btn-back">戻る</a>
            
            <form action="admin_kyc_act.php" method="post" style="display:inline;">
                <input type="hidden" name="agent_id" value="<?= h($agent['id']) ?>">
                <button type="submit" name="status" value="9" class="btn btn-reject" onclick="return confirm('本当に否認しますか？');">否認する</button>
                <button type="submit" name="status" value="2" class="btn btn-approve" onclick="return confirm('承認してよろしいですか？');">承認する</button>
            </form>
        </div>

    </div>

</body>
</html>