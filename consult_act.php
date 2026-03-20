<?php
session_start();
include("function.php");
loginCheck('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('search.php');
}

$agent_id = (int)($_POST['agent_id'] ?? 0);
$purpose  = trim($_POST['purpose']  ?? '');
$trigger  = trim($_POST['trigger']  ?? '');
$style    = trim($_POST['style']    ?? '');
$note     = trim($_POST['note']     ?? '');

// バリデーション
if ($agent_id <= 0 || $purpose === '' || $trigger === '' || $style === '') {
    redirect('search.php');
}

$user_id = (int)$_SESSION['id'];
$pdo     = db_conn();

// Agent存在確認
$stmt = $pdo->prepare("SELECT id, name, lid FROM agents WHERE id=:id AND life_flg=0 AND verification_status=2");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();
if (!$agent) {
    redirect('search.php');
}

// User名取得（メール本文用）
$stmt = $pdo->prepare("SELECT name FROM users WHERE id=:id AND life_flg=0");
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();
if (!$user) {
    redirect('login_user.php');
}

// ── テンプレートメッセージ組み立て ──────────────────────────────
$message  = "はじめまして。相談フォームからご連絡しました。\n\n";
$message .= "【相談の目的】\n" . $purpose . "\n\n";
$message .= "【相談のきっかけ】\n" . $trigger . "\n\n";
$message .= "【希望の面談スタイル】\n" . $style . "\n";

if ($note !== '') {
    $message .= "\n【その他・ご要望】\n" . $note . "\n";
}

$message .= "\nよろしくお願いいたします。";
// ────────────────────────────────────────────────────────────────

// messages テーブルに INSERT（sender_type=1: User）
$stmt = $pdo->prepare(
    "INSERT INTO messages (sender_id, receiver_id, sender_type, message, created_at)
     VALUES (:sender_id, :receiver_id, 1, :message, NOW())"
);
$stmt->bindValue(':sender_id',   $user_id,  PDO::PARAM_INT);
$stmt->bindValue(':receiver_id', $agent_id, PDO::PARAM_INT);
$stmt->bindValue(':message',     $message,  PDO::PARAM_STR);
$stmt->execute();

// ── Agent へメール通知 ──────────────────────────────────────────
$mail_subject = '【ERAPRO】相談フォームからメッセージが届きました';
$mail_body    = $agent['name'] . " 様\n\n";
$mail_body   .= $user['name'] . " 様より相談フォームからメッセージが届きました。\n\n";
$mail_body   .= "▼マイページのメッセージ欄からご確認ください\n";
$mail_body   .= "http://localhost/sotsu/messages_list.php\n\n";
$mail_body   .= "--\nEKAPRO運営事務局\n";
send_mail($agent['lid'], $mail_subject, $mail_body);
// ────────────────────────────────────────────────────────────────

redirect('message_room.php?agent_id=' . $agent_id);
?>
