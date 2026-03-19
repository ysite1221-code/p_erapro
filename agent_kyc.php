<?php
session_start();
include("function.php");
loginCheck('agent');

$id  = $_SESSION["id"];
$pdo = db_conn();

// Agent情報取得
$stmt = $pdo->prepare("SELECT verification_status, name, lid, email_notification_flg FROM agents WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();

if (!$agent) {
    redirect('login_agent.php');
}

$vstatus = (int)$agent['verification_status'];
$error   = '';
$success = false;

// ---------------------------------------------------
// POST処理：書類アップロード
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $vstatus === 0) {

    if (!isset($_FILES['kyc_image']) || $_FILES['kyc_image']['error'] !== UPLOAD_ERR_OK) {
        $error = '画像ファイルを選択してください。';
    } else {
        $file          = $_FILES['kyc_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo         = new finfo(FILEINFO_MIME_TYPE);
        $mime          = $finfo->file($file['tmp_name']);
        $max_size      = 10 * 1024 * 1024; // 10MB

        if (!in_array($mime, $allowed_types)) {
            $error = 'JPG・PNG・GIF形式の画像のみアップロード可能です。';
        } elseif ($file['size'] > $max_size) {
            $error = 'ファイルサイズは10MB以下にしてください。';
        } else {
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $save_dir = __DIR__ . '/uploads/kyc/';

            if (!is_dir($save_dir)) {
                mkdir($save_dir, 0755, true);
            }

            $save_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $save_path = $save_dir . $save_name;

            if (move_uploaded_file($file['tmp_name'], $save_path)) {
                // kyc_documentsに保存
                $stmt = $pdo->prepare(
                    "INSERT INTO kyc_documents (agent_id, file_path, uploaded_at)
                     VALUES (:agent_id, :file_path, NOW())"
                );
                $stmt->bindValue(':agent_id',  $id,                             PDO::PARAM_INT);
                $stmt->bindValue(':file_path', 'uploads/kyc/' . $save_name,    PDO::PARAM_STR);
                $stmt->execute();

                // verification_status → 1（審査待ち）
                $stmt = $pdo->prepare("UPDATE agents SET verification_status=1 WHERE id=:id");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $vstatus = 1;
                $success = true;

                // Agent本人への受付完了メール
                $agent_mail_body  = $agent['name'] . " 様\n\n";
                $agent_mail_body .= "本人確認書類を受け付けました。\n";
                $agent_mail_body .= "通常1〜3営業日以内に審査結果をメールでお知らせします。\n";
                $agent_mail_body .= "審査完了までしばらくお待ちください。\n\n";
                $agent_mail_body .= "--------------------------------------------------\n";
                $agent_mail_body .= "ERAPRO運営事務局\n";
                $agent_mail_body .= "https://erapro.jp/";
                send_mail($agent['lid'], '【ERAPRO】本人確認書類を受け付けました', $agent_mail_body);

                // 管理者への通知メール
                send_mail(
                    MAIL_FROM_EMAIL,
                    '【ERAPRO】本人確認書類が提出されました',
                    $agent['name'] . ' 様より本人確認書類が提出されました。管理画面からご確認ください。'
                );
            } else {
                $error = 'アップロードに失敗しました。再度お試しください。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本人確認書類の提出 - ERAPRO</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", sans-serif;
            background: #f4f6f9;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 600px;
            width: 100%;
            padding: 48px 40px;
        }
        .step-bar {
            display: flex;
            gap: 0;
            margin-bottom: 36px;
            border-radius: 6px;
            overflow: hidden;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .step { flex: 1; padding: 10px 4px; text-align: center; background: #eee; color: #aaa; }
        .step.done  { background: #28a745; color: #fff; }
        .step.active{ background: #004e92; color: #fff; }

        .logo { font-size: 1.4rem; font-weight: 800; color: #004e92; margin-bottom: 28px; letter-spacing: 1px; }
        h2 { font-size: 1.5rem; margin-bottom: 8px; }
        .sub { color: #666; font-size: 0.95rem; margin-bottom: 28px; line-height: 1.7; }

        /* アップロードゾーン */
        .upload-zone {
            border: 2px dashed #ccd6e0;
            border-radius: 10px;
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            margin-bottom: 20px;
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: #004e92; background: #f0f4ff; }
        .upload-zone .icon { font-size: 2.5rem; margin-bottom: 10px; }
        .upload-zone p { font-size: 0.9rem; color: #666; }
        .upload-zone input[type="file"] { display: none; }
        .preview-name { font-size: 0.9rem; color: #004e92; font-weight: bold; margin-top: 8px; }

        /* 注意書き */
        .notice {
            background: #f0f4ff;
            border-left: 4px solid #004e92;
            border-radius: 4px;
            padding: 14px 16px;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 24px;
            line-height: 1.8;
        }
        .notice strong { display: block; margin-bottom: 6px; }

        /* ボタン */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #003a70; }
        .btn-submit:disabled { background: #aaa; cursor: not-allowed; }

        /* メッセージ */
        .msg-error   { background: #fff0f0; border-left: 4px solid #dc3545; padding: 12px 16px; border-radius: 4px; color: #dc3545; font-size: 0.9rem; margin-bottom: 20px; }
        .msg-success { background: #f0fff4; border-left: 4px solid #28a745; padding: 12px 16px; border-radius: 4px; color: #28a745; font-size: 0.9rem; margin-bottom: 20px; }

        /* ステータス表示 */
        .status-box {
            text-align: center;
            padding: 30px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 16px;
        }
        .badge-pending  { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .dashboard-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            background: #004e92;
            color: #fff;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
        }
        .dashboard-link:hover { background: #003a70; }
    </style>
</head>
<body>
<div class="card">

    <div class="logo">ERAPRO</div>

    <!-- ステップバー -->
    <div class="step-bar">
        <div class="step done">① メール認証</div>
        <div class="step <?= ($vstatus === 0) ? 'active' : 'done' ?>">② 本人確認</div>
        <div class="step <?= ($vstatus >= 2) ? 'active' : '' ?>">③ 審査完了</div>
    </div>

    <?php if ($vstatus === 0): ?>
        <!-- --- アップロードフォーム --- -->
        <h2>本人確認書類の提出</h2>
        <p class="sub">
            ようこそ、<?= h($agent['name']) ?> さん！<br>
            ERAPROでプロとして活動いただくために、本人確認書類をご提出ください。
        </p>

        <?php if ($error): ?>
            <div class="msg-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="notice">
            <strong>提出できる書類（いずれか1点）</strong>
            ・運転免許証（表面）<br>
            ・マイナンバーカード（表面のみ）<br>
            ・パスポート（顔写真ページ）<br>
            ※ JPG・PNG・GIF形式、10MB以下
        </div>

        <form method="post" enctype="multipart/form-data" id="kycForm">
            <div class="upload-zone" id="dropZone" onclick="document.getElementById('kyc_image').click()">
                <div class="icon">📄</div>
                <p>クリックまたはドラッグ&ドロップで画像を選択</p>
                <p class="preview-name" id="previewName"></p>
                <input type="file" name="kyc_image" id="kyc_image" accept="image/*" required>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" disabled>提出する</button>
        </form>

    <?php elseif ($vstatus === 1): ?>
        <!-- --- 審査待ちステータス --- -->
        <h2>書類を受け付けました</h2>
        <div class="status-box">
            <div class="status-badge badge-pending">🕐 審査中</div>
            <p style="color:#666; line-height:1.8;">
                ご提出いただきありがとうございます。<br>
                通常1〜3営業日以内に審査結果をメールでお知らせします。<br>
                審査完了までしばらくお待ちください。
            </p>
            <a href="mypage.php" class="dashboard-link">ダッシュボードへ</a>
        </div>

    <?php elseif ($vstatus === 2): ?>
        <!-- --- 承認済み --- -->
        <h2>本人確認が完了しています</h2>
        <div class="status-box">
            <div class="status-badge badge-approved">✅ 承認済み</div>
            <p style="color:#666;">ERAPROをご利用いただけます。</p>
            <a href="mypage.php" class="dashboard-link">ダッシュボードへ</a>
        </div>

    <?php elseif ($vstatus === 9): ?>
        <!-- --- 否認 --- -->
        <h2>書類の再提出をお願いします</h2>
        <div class="status-box">
            <div class="status-badge badge-rejected">⚠️ 再提出が必要です</div>
            <p style="color:#666; line-height:1.8; margin-bottom:20px;">
                提出いただいた書類を確認できませんでした。<br>
                お手数ですが、再度ご提出をお願いいたします。
            </p>
        </div>

        <?php if ($error): ?>
            <div class="msg-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="notice">
            <strong>再提出できる書類（いずれか1点）</strong>
            ・運転免許証（表面）<br>
            ・マイナンバーカード（表面のみ）<br>
            ・パスポート（顔写真ページ）
        </div>

        <form method="post" enctype="multipart/form-data" id="kycForm">
            <input type="hidden" name="resubmit" value="1">
            <div class="upload-zone" id="dropZone" onclick="document.getElementById('kyc_image').click()">
                <div class="icon">📄</div>
                <p>クリックまたはドラッグ&ドロップで画像を選択</p>
                <p class="preview-name" id="previewName"></p>
                <input type="file" name="kyc_image" id="kyc_image" accept="image/*" required>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn" disabled>再提出する</button>
        </form>

    <?php endif; ?>

</div>

<script>
const fileInput  = document.getElementById('kyc_image');
const dropZone   = document.getElementById('dropZone');
const previewName= document.getElementById('previewName');
const submitBtn  = document.getElementById('submitBtn');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            previewName.textContent = '選択中: ' + this.files[0].name;
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0 && fileInput) {
            fileInput.files = e.dataTransfer.files;
            previewName.textContent = '選択中: ' + e.dataTransfer.files[0].name;
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}
</script>
</body>
</html>
