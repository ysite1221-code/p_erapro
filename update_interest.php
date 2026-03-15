<?php
session_start();
include("function.php");
loginCheck('user');

header('Content-Type: application/json; charset=utf-8');

$user_id  = (int)$_SESSION['id'];
$interest = trim($_POST['interest'] ?? '');

if (empty($interest)) {
    echo json_encode(['result' => 'error', 'message' => 'invalid']);
    exit;
}

$pdo = db_conn();

// カラム自動追加
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN interests VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {}

// 現在の関心事リストを取得
$stmt = $pdo->prepare("SELECT interests FROM users WHERE id=:uid AND life_flg=0");
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$current = $stmt->fetchColumn();

$list = (!empty($current))
    ? array_values(array_filter(array_map('trim', explode(',', $current))))
    : [];

// トグル
if (in_array($interest, $list)) {
    $list   = array_values(array_filter($list, fn($v) => $v !== $interest));
    $action = 'removed';
} else {
    $list[] = $interest;
    $action = 'added';
}

$new_interests = implode(',', $list);

$stmt = $pdo->prepare("UPDATE users SET interests=:interests WHERE id=:uid AND life_flg=0");
$stmt->bindValue(':interests', $new_interests !== '' ? $new_interests : null, PDO::PARAM_STR);
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();

echo json_encode(['result' => $action, 'interests' => $new_interests]);
