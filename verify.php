<?php
session_start();
include("function.php");

// 1. トークン取得
$token = $_GET["token"];

if(empty($token)){
    exit("無効なアクセスです。");
}

// 2. DB接続
$pdo = db_conn();

// 3. トークン照合 & まだ認証されていないユーザーを探す
$sql = "SELECT * FROM agents WHERE email_token = :token AND email_verified_at IS NULL AND life_flg=0";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':token', $token, PDO::PARAM_STR);
$status = $stmt->execute();

if($status==false){
    sql_error($stmt);
}

$agent = $stmt->fetch();

if( $agent ){
    // 4. 認証成功！日時を更新して本登録状態へ
    $update_sql = "UPDATE agents SET email_verified_at = sysdate() WHERE id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->bindValue(':id', $agent["id"], PDO::PARAM_INT);
    $update_stmt->execute();

    // 5. 自動ログイン処理 (セッション発行)
    $_SESSION["chk_ssid"]  = session_id();
    $_SESSION["name"]      = $agent['name'];
    $_SESSION["id"]        = $agent['id'];
    $_SESSION["user_type"] = 'agent';

    // 6. 次のステップへ (KYCアップロード)
    redirect("agent_kyc.php");

} else {
    // トークンが間違っている、または既に認証済み
    echo "このリンクは無効か、既に認証済みです。<br>";
    echo '<a href="login_agent.php">ログイン画面へ</a>';
}
?>