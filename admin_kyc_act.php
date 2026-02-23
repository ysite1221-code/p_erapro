<?php
session_start();
include("function.php");

// 1. 管理者チェック
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'admin'
) {
    redirect('login_admin.php');
}

// 2. POSTのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_dashboard.php');
}

// 3. パラメータ取得・バリデーション
$agent_id   = (int)($_POST['agent_id'] ?? 0);
$new_status = (int)($_POST['status']   ?? 0);

if ($agent_id <= 0 || !in_array($new_status, [2, 9])) {
    redirect('admin_dashboard.php');
}

// 4. DB接続 & Agent情報取得
$pdo  = db_conn();
$stmt = $pdo->prepare("SELECT id, name, lid, verification_status FROM agents WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();

if (!$agent) {
    redirect('admin_dashboard.php');
}

// 5. verification_status 更新
$stmt = $pdo->prepare("UPDATE agents SET verification_status=:status WHERE id=:id");
$stmt->bindValue(':status', $new_status, PDO::PARAM_INT);
$stmt->bindValue(':id',     $agent_id,   PDO::PARAM_INT);
$ok = $stmt->execute();

if (!$ok) {
    sql_error($stmt);
}

// 6. Agentへメール通知（lidがメールアドレス）
$to   = $agent['lid'];
$name = $agent['name'];

if ($new_status === 2) {
    // 承認
    $subject = '【ERAPRO】本人確認が完了しました';
    $body  = $name . " 様\n\n";
    $body .= "お待たせいたしました。\n";
    $body .= "本人確認書類の審査が完了し、アカウントが承認されました。\n\n";
    $body .= "下記よりダッシュボードにログインして、プロフィールを充実させましょう。\n";
    $body .= "http://localhost/sotsu/login_agent.php\n\n";
    $body .= "--------------------------------------------------\n";
    $body .= "ERAPRO運営事務局\n";
} else {
    // 否認（status=9）
    $subject = '【ERAPRO】本人確認書類の再提出をお願いします';
    $body  = $name . " 様\n\n";
    $body .= "提出いただいた本人確認書類を確認できませんでした。\n";
    $body .= "お手数をおかけしますが、再度ご提出をお願いいたします。\n\n";
    $body .= "【再提出手順】\n";
    $body .= "ログイン後、本人確認ページより書類をアップロードしてください。\n";
    $body .= "http://localhost/sotsu/agent_kyc.php\n\n";
    $body .= "【提出できる書類】\n";
    $body .= "・運転免許証（表面）\n";
    $body .= "・マイナンバーカード（表面のみ）\n";
    $body .= "・パスポート（顔写真ページ）\n\n";
    $body .= "--------------------------------------------------\n";
    $body .= "ERAPRO運営事務局\n";
}

send_mail($to, $subject, $body);

// 7. リダイレクト（結果メッセージ付き）
$result_label = ($new_status === 2) ? 'approved' : 'rejected';
redirect('admin_dashboard.php?result=' . $result_label);
?>
