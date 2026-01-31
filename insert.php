<?php
// デバッグ設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include("function.php");

// 1. データ受け取り確認
if(
    !isset($_POST["name"]) || $_POST["name"]=="" ||
    !isset($_POST["lid"]) || $_POST["lid"]=="" ||
    !isset($_POST["lpw"]) || $_POST["lpw"]==""
){
    exit('ParamError: 入力データが足りません');
}

$name      = $_POST["name"];
$lid       = $_POST["lid"];
$lpw       = $_POST["lpw"];
$user_type = $_POST["user_type"]; // agent or user

// 2. DB接続
$pdo = db_conn();

// 3. パスワードハッシュ化
$lpw_hash = password_hash($lpw, PASSWORD_DEFAULT);

// 4. SQL作成（テーブル名が 'agents' か 'p_agents' か確認！）
if ($user_type === 'agent') {
    // 募集人の場合
    $sql = "INSERT INTO agents(name, lid, lpw, indate, life_flg, plan_type)
            VALUES(:name, :lid, :lpw, sysdate(), 0, 0)";
    $redirect_url = "login_agent.php";
} else {
    // 一般ユーザーの場合
    $sql = "INSERT INTO users(name, lid, lpw, indate, life_flg)
            VALUES(:name, :lid, :lpw, sysdate(), 0)";
    $redirect_url = "login_user.php";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid',  $lid,  PDO::PARAM_STR);
$stmt->bindValue(':lpw',  $lpw_hash, PDO::PARAM_STR);

$status = $stmt->execute();

// 5. エラー確認
if ($status == false) {
    sql_error($stmt); // ここでSQLエラーがあれば画面に出る
} else {
    redirect($redirect_url);
}
?>