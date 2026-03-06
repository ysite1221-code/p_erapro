<?php
session_start();
include("function.php");

// 管理者チェック
if (
    !isset($_SESSION["chk_ssid"]) ||
    $_SESSION["chk_ssid"] !== session_id() ||
    $_SESSION["user_type"] !== 'admin'
) {
    redirect("login_admin.php");
}

// POSTパラメータ検証
$user_type = $_POST['user_type'] ?? '';
$target_id = (int)($_POST['id']        ?? 0);
$action    = $_POST['action']    ?? '';

if (!in_array($user_type, ['agent', 'user']) || $target_id <= 0 || !in_array($action, ['suspend', 'activate'])) {
    redirect("admin_dashboard.php");
}

$life_flg    = ($action === 'suspend') ? 1 : 0;
$table       = ($user_type === 'agent') ? 'agents' : 'users';
$redirect_to = ($user_type === 'agent') ? 'admin_agent_list.php' : 'admin_user_list.php';

$pdo  = db_conn();
$stmt = $pdo->prepare("UPDATE {$table} SET life_flg=:flg WHERE id=:id");
$stmt->bindValue(':flg', $life_flg, PDO::PARAM_INT);
$stmt->bindValue(':id',  $target_id, PDO::PARAM_INT);
if (!$stmt->execute()) {
    sql_error($stmt);
}

$result = ($action === 'suspend') ? 'suspended' : 'activated';
redirect("{$redirect_to}?result={$result}");
