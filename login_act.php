<?php
// デバッグ設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include("function.php");

$lid       = $_POST["lid"];
$lpw       = $_POST["lpw"];
$user_type = $_POST["user_type"]; 

$pdo = db_conn();

// 1. SQL実行
if($user_type === 'agent'){
    $sql = "SELECT * FROM agents WHERE lid=:lid AND life_flg=0";
} else {
    $sql = "SELECT * FROM users WHERE lid=:lid AND life_flg=0";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$status = $stmt->execute();

if($status==false){
    sql_error($stmt);
}

$val = $stmt->fetch();

// 2. データがあるか確認
if( !$val ){
    exit("Login Error: IDが見つかりません。登録されていますか？");
}

// 3. パスワード確認
if( password_verify($lpw, $val['lpw']) ){
    // 成功
    $_SESSION["chk_ssid"]  = session_id();
    $_SESSION["name"]      = $val['name'];
    $_SESSION["id"]        = $val['id'];
    $_SESSION["user_type"] = $user_type;

    if($user_type === 'agent'){
        redirect("mypage.php");
    } else {
        redirect("index.php");
    }
}else{
    // 失敗
    exit("Login Error: パスワードが違います。");
}
?>