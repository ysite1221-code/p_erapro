<?php
session_start();
include("function.php");

$lid = $_POST["lid"];
$lpw = $_POST["lpw"];

// 1. DB接続
$pdo = db_conn();

// 2. SQL実行（管理者テーブルから取得）
$sql = "SELECT * FROM admins WHERE lid=:lid AND life_flg=0";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$status = $stmt->execute();

if($status==false){
    sql_error($stmt);
}

$val = $stmt->fetch();

// 3. データがあればパスワード検証
// ※注意: SQLで入れた初期データがハッシュ化されている前提です。
// もしハッシュ化されていない生パスワード('admin')でテストしたい場合は、
// 一時的に if($val['lpw'] == $lpw) { ... } に書き換えてください。
// 今回はpassword_verifyを使います。

if( $val && password_verify($lpw, $val['lpw']) ){
    // Login成功時
    $_SESSION["chk_ssid"]  = session_id();
    $_SESSION["kanri_flg"] = $val['kanri_flg'];
    $_SESSION["name"]      = $val['name'];
    $_SESSION["user_type"] = 'admin'; // 管理者フラグ

    redirect("admin_dashboard.php"); // このあと作ります
}else{
    // Login失敗時
    redirect("login_admin.php");
}
?>