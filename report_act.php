<?php
session_start();
include("function.php");

// セッション検証（user または agent のみ）
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    !in_array($_SESSION['user_type'], ['user', 'agent'])
) {
    redirect('login_user.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('search.php');
}

$reporter_type = $_SESSION['user_type'];
$reporter_id   = (int)$_SESSION['id'];
$target_id     = (int)($_POST['target_id'] ?? 0);

if ($target_id <= 0) {
    redirect($reporter_type === 'user' ? 'mypage_user.php' : 'mypage.php');
}

$pdo = db_conn();

// 通報者情報取得
if ($reporter_type === 'user') {
    $reporter_table  = 'users';
    $target_table    = 'agents';
    $target_type_str = 'エージェント';
    $redirect_url    = 'message_room.php?agent_id=' . $target_id;
} else {
    $reporter_table  = 'agents';
    $target_table    = 'users';
    $target_type_str = 'ユーザー';
    $redirect_url    = 'message_room.php?user_id=' . $target_id;
}

$stmt = $pdo->prepare("SELECT name, lid FROM {$reporter_table} WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $reporter_id, PDO::PARAM_INT);
$stmt->execute();
$reporter = $stmt->fetch();

$stmt = $pdo->prepare("SELECT name, lid FROM {$target_table} WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $target_id, PDO::PARAM_INT);
$stmt->execute();
$target = $stmt->fetch();

if (!$reporter || !$target) {
    redirect($reporter_type === 'user' ? 'mypage_user.php' : 'mypage.php');
}

// 運営宛メール送信
$reporter_type_str = $reporter_type === 'user' ? 'ユーザー' : 'エージェント';

$mail_subject = '【ERAPRO】通報が届きました';
$mail_body    = "■ 通報内容\n\n";
$mail_body   .= "━━━━ 通報者情報 ━━━━\n";
$mail_body   .= "種別　　：{$reporter_type_str}\n";
$mail_body   .= "名前　　：{$reporter['name']}\n";
$mail_body   .= "ID　　　：{$reporter_id}\n";
$mail_body   .= "メール　：{$reporter['lid']}\n\n";
$mail_body   .= "━━━━ 通報対象 ━━━━\n";
$mail_body   .= "種別　　：{$target_type_str}\n";
$mail_body   .= "名前　　：{$target['name']}\n";
$mail_body   .= "ID　　　：{$target_id}\n";
$mail_body   .= "メール　：{$target['lid']}\n\n";
$mail_body   .= "管理画面よりメッセージ履歴をご確認の上、対応をお願いいたします。\n";
$mail_body   .= "--\nEKAPRO運営事務局\n";

send_mail(MAIL_FROM_EMAIL, $mail_subject, $mail_body);

// フラッシュメッセージをセッションにセットしてリダイレクト
$_SESSION['flash_message'] = '通報が完了しました。内容を確認の上、対応いたします。';
redirect($redirect_url);
?>
