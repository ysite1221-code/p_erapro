<?php
session_start();
include("function.php");
loginCheck('agent');

$id  = (int)$_SESSION["id"];
$pdo = db_conn();

// affiliation_url カラムを追加（既存の場合は無視）
try {
    $pdo->exec("ALTER TABLE agents ADD COLUMN affiliation_url VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
    // カラムが既に存在する場合のエラーは無視
}

// Agent情報取得
$stmt = $pdo->prepare("SELECT verification_status, name, lid, affiliation_url FROM agents WHERE id=:id AND life_flg=0");
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
// POST処理：所属先URL提出（新規 or 再提出）
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($vstatus === 0 || $vstatus === 9)) {

    $affiliation_url = trim($_POST['affiliation_url'] ?? '');

    if ($affiliation_url === '') {
        $error = 'URLを入力してください。';
    } elseif (!filter_var($affiliation_url, FILTER_VALIDATE_URL)) {
        $error = '有効なURLを入力してください（例: https://example.com/profile）。';
    } elseif (mb_strlen($affiliation_url) > 255) {
        $error = 'URLは255文字以内で入力してください。';
    } else {
        // agents テーブルを更新
        $stmt = $pdo->prepare(
            "UPDATE agents SET affiliation_url=:url, verification_status=1 WHERE id=:id"
        );
        $stmt->bindValue(':url', $affiliation_url, PDO::PARAM_STR);
        $stmt->bindValue(':id',  $id,              PDO::PARAM_INT);
        $stmt->execute();

        $vstatus = 1;
        $success = true;

        // Agent本人への受付完了メール
        $agent_mail_body  = $agent['name'] . " 様\n\n";
        $agent_mail_body .= "本人確認情報を受け付けました。\n";
        $agent_mail_body .= "通常1〜3営業日以内に審査結果をメールでお知らせします。\n";
        $agent_mail_body .= "審査完了までしばらくお待ちください。\n\n";
        $agent_mail_body .= "--------------------------------------------------\n";
        $agent_mail_body .= "ERAPRO運営事務局\n";
        $agent_mail_body .= "https://erapro.jp/";
        send_mail($agent['lid'], '【ERAPRO】本人確認情報を受け付けました', $agent_mail_body);

        // 管理者への通知メール
        send_mail(
            MAIL_FROM_EMAIL,
            '【ERAPRO】本人確認情報が提出されました',
            $agent['name'] . ' 様より本人確認情報が提出されました。管理画面からご確認ください。'
        );
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>本人確認 - ERAPRO</title>
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

        /* ステップバー */
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
        .step.done   { background: #28a745; color: #fff; }
        .step.active { background: #004e92; color: #fff; }

        .logo { font-size: 1.4rem; font-weight: 800; color: #004e92; margin-bottom: 28px; letter-spacing: 1px; }
        h2 { font-size: 1.5rem; margin-bottom: 8px; }
        .sub { color: #666; font-size: 0.95rem; margin-bottom: 28px; line-height: 1.7; }

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
        .notice strong { display: block; margin-bottom: 6px; color: #004e92; }

        /* URLフォーム */
        .url-input-wrap {
            margin-bottom: 20px;
        }
        .url-input-wrap label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 8px;
        }
        .url-input-wrap input[type="url"] {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #333;
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .url-input-wrap input[type="url"]:focus {
            outline: none;
            border-color: #004e92;
            box-shadow: 0 0 0 3px rgba(0,78,146,0.12);
            background: #fff;
        }
        .url-input-wrap .url-hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 6px;
        }

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

        /* メッセージ */
        .msg-error   { background: #fff0f0; border-left: 4px solid #dc3545; padding: 12px 16px; border-radius: 4px; color: #dc3545; font-size: 0.9rem; margin-bottom: 20px; }
        .msg-success { background: #f0fff4; border-left: 4px solid #28a745; padding: 12px 16px; border-radius: 4px; color: #28a745; font-size: 0.9rem; margin-bottom: 20px; }

        /* ステータス表示 */
        .status-box { text-align: center; padding: 30px 0; }
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

        @media (max-width: 480px) {
            .card { padding: 32px 20px; }
        }
    </style>
</head>
<body>
<div class="card">

    <div class="logo">ERAPRO</div>

    <!-- ステップバー -->
    <div class="step-bar">
        <div class="step done">① メール認証</div>
        <div class="step <?= ($vstatus === 0 || $vstatus === 9) ? 'active' : 'done' ?>">② 本人確認</div>
        <div class="step <?= ($vstatus >= 2) ? 'active' : '' ?>">③ 審査完了</div>
    </div>

    <?php if ($vstatus === 0): ?>
        <!-- ── 初回提出フォーム ── -->
        <h2>本人確認情報の提出</h2>
        <p class="sub">
            ようこそ、<?= h($agent['name']) ?> さん！<br>
            ERAPROでプロとして活動いただくために、所属先の情報をご提出ください。
        </p>

        <?php if ($error): ?>
            <div class="msg-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="notice">
            <strong>ご提出いただくURL</strong>
            ご本人確認のため、お勤め先の企業HPや、募集人としての登録情報が確認できるURLをご入力ください。<br>
            例）生命保険会社・代理店の公式ページ、金融庁の登録情報ページ、募集人プロフィールページ など
        </div>

        <form method="post" id="kycForm">
            <div class="url-input-wrap">
                <label for="affiliation_url">所属先・登録情報のURL</label>
                <input type="url" name="affiliation_url" id="affiliation_url"
                       placeholder="https://example.com/profile"
                       value="<?= h($_POST['affiliation_url'] ?? '') ?>"
                       required>
                <p class="url-hint">https:// から始まるURLを入力してください</p>
            </div>
            <button type="submit" class="btn-submit">提出する</button>
        </form>

    <?php elseif ($vstatus === 1): ?>
        <!-- ── 審査待ちステータス ── -->
        <h2>情報を受け付けました</h2>
        <div class="status-box">
            <div class="status-badge badge-pending">🕐 審査中</div>
            <p style="color:#666; line-height:1.8;">
                ご提出いただきありがとうございます。<br>
                通常1〜3営業日以内に審査結果をメールでお知らせします。<br>
                審査完了まで、ERAPROのトップページ等をご覧になりながら今しばらくお待ちください。
            </p>
            <a href="index.php" class="dashboard-link">ERAPROトップページへ戻る</a>
            <a href="logout.php" style="display:block; margin-top:16px; color:#999; font-size:0.85rem;">ログアウト</a>
        </div>

    <?php elseif ($vstatus === 2): ?>
        <!-- ── 承認済み ── -->
        <h2>本人確認が完了しています</h2>
        <div class="status-box">
            <div class="status-badge badge-approved">✅ 承認済み</div>
            <p style="color:#666;">ERAPROをご利用いただけます。</p>
            <a href="mypage.php" class="dashboard-link">ダッシュボードへ</a>
        </div>

    <?php elseif ($vstatus === 9): ?>
        <!-- ── 否認・再提出フォーム ── -->
        <h2>情報の再提出をお願いします</h2>
        <div class="status-box" style="padding-bottom: 20px;">
            <div class="status-badge badge-rejected">⚠️ 再提出が必要です</div>
            <p style="color:#666; line-height:1.8;">
                ご提出いただいた情報を確認できませんでした。<br>
                お手数ですが、別のURLで再度ご提出をお願いいたします。
            </p>
        </div>

        <?php if ($error): ?>
            <div class="msg-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="notice">
            <strong>再提出するURL</strong>
            お勤め先の企業HPや、募集人としての登録情報が確認できるURLをご入力ください。<br>
            例）生命保険会社・代理店の公式ページ、金融庁の登録情報ページ、募集人プロフィールページ など
        </div>

        <form method="post" id="kycForm">
            <div class="url-input-wrap">
                <label for="affiliation_url">所属先・登録情報のURL</label>
                <input type="url" name="affiliation_url" id="affiliation_url"
                       placeholder="https://example.com/profile"
                       value="<?= h($agent['affiliation_url'] ?? '') ?>"
                       required>
                <p class="url-hint">https:// から始まるURLを入力してください</p>
            </div>
            <button type="submit" class="btn-submit">再提出する</button>
        </form>
        <a href="logout.php" style="display:block; margin-top:20px; text-align:center; color:#999; font-size:0.85rem;">ログアウト</a>

    <?php endif; ?>

</div>
</body>
</html>
