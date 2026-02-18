<?php
include("function.php");

$name = $_POST["name"];
$lid  = $_POST["lid"];
$lpw  = $_POST["lpw"];

// 1. DB接続
$pdo = db_conn();

// 2. パスワードハッシュ化 (セキュリティ対策)
$lpw_hash = password_hash($lpw, PASSWORD_DEFAULT);

// 3. データ登録SQL作成
$sql = "INSERT INTO admins(name, lid, lpw, kanri_flg, life_flg) VALUES(:name, :lid, :lpw, 1, 0)";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$stmt->bindValue(':lpw', $lpw_hash, PDO::PARAM_STR); // ハッシュ化したPWを保存
$status = $stmt->execute();

// 4. データ登録処理後
if($status==false){
    sql_error($stmt);
}else{
    // 成功したらログイン画面へ
    redirect("login_admin.php");
}
?>