<?php
session_start();
include('function.php');

// POSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('password_reset.php');
}

$token            = trim($_POST['token'] ?? '');
$new_password     = $_POST['new_password'] ?? '';
$password_confirm = $_POST['new_password_confirm'] ?? '';

// バリデーション
if ($token === '') {
    redirect('password_reset.php?error=' . urlencode('無効なリクエストです'));
}
if ($new_password === '') {
    redirect('password_reset_form.php?token=' . urlencode($token) . '&error=' . urlencode('パスワードを入力してください'));
}
if (mb_strlen($new_password) < 8) {
    redirect('password_reset_form.php?token=' . urlencode($token) . '&error=' . urlencode('パスワードは8文字以上で入力してください'));
}
if ($new_password !== $password_confirm) {
    redirect('password_reset_form.php?token=' . urlencode($token) . '&error=' . urlencode('パスワードが一致しません'));
}

$pdo = db_conn();

// トークンの再検証
$stmt = $pdo->prepare(
    "SELECT * FROM password_resets WHERE token=:token AND expires_at > NOW()"
);
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    redirect('password_reset.php?error=' . urlencode('このURLは無効か、有効期限が切れています。再度お手続きください。'));
}

// パスワードを更新
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$table    = ($reset['user_type'] === 'agent') ? 'agents' : 'users';

$stmt = $pdo->prepare("UPDATE {$table} SET lpw=:lpw WHERE lid=:email AND life_flg=0");
$stmt->bindValue(':lpw',   $new_hash,      PDO::PARAM_STR);
$stmt->bindValue(':email', $reset['email'], PDO::PARAM_STR);
$stmt->execute();

// 使用済みトークンを削除
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE token=:token");
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$stmt->execute();

// ログイン画面へリダイレクト
$login_page = ($reset['user_type'] === 'agent') ? 'login_agent.php' : 'login_user.php';
redirect($login_page . '?reset=success');
?>
