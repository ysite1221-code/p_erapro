<?php
session_start();
include('function.php');

// ログインチェック（userまたはagentのみ）
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    !in_array($_SESSION['user_type'], ['user', 'agent'])
) {
    redirect('index.php');
}

// POSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$user_type = $_SESSION['user_type'];
$user_id   = (int)$_SESSION['id'];

$pdo = db_conn();

// life_flg を 1（退会済み）に更新
if ($user_type === 'agent') {
    $stmt = $pdo->prepare("UPDATE agents SET life_flg=1 WHERE id=:id AND life_flg=0");
} else {
    $stmt = $pdo->prepare("UPDATE users SET life_flg=1 WHERE id=:id AND life_flg=0");
}
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// セッションを完全に破棄
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// トップページへリダイレクト
redirect('index.php');
?>
