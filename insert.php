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

// 利用規約への同意チェック
if (empty($_POST['agree_terms'])) {
    exit('利用規約とプライバシーポリシーへの同意が必要です。ブラウザの戻るボタンでお戻りください。');
}

$name      = $_POST["name"];
$lid       = $_POST["lid"];
$lpw       = $_POST["lpw"];
$user_type = $_POST["user_type"]; // agent or user

// 2. DB接続
$pdo = db_conn();

// 3. パスワードハッシュ化 & メール認証トークン生成
$lpw_hash    = password_hash($lpw, PASSWORD_DEFAULT);
$email_token = bin2hex(random_bytes(32));

// 4. INSERT SQL（email_token を保存、email_verified_at は NULL のまま＝未認証）
if ($user_type === 'agent') {
    $sql = "INSERT INTO agents(name, lid, lpw, email_token, indate, life_flg, plan_type)
            VALUES(:name, :lid, :lpw, :email_token, sysdate(), 0, 0)";
} else {
    $sql = "INSERT INTO users(name, lid, lpw, email_token, indate, life_flg)
            VALUES(:name, :lid, :lpw, :email_token, sysdate(), 0)";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name',        $name,        PDO::PARAM_STR);
$stmt->bindValue(':lid',         $lid,         PDO::PARAM_STR);
$stmt->bindValue(':lpw',         $lpw_hash,    PDO::PARAM_STR);
$stmt->bindValue(':email_token', $email_token, PDO::PARAM_STR);

$status = $stmt->execute();

// 5. エラー確認
if ($status == false) {
    $error = $stmt->errorInfo();
    if (strpos($error[2], 'Duplicate entry') !== false) {
        exit('このメールアドレスは既に登録されています。');
    }
    sql_error($stmt);
}

// 6. 認証メール送信
$verify_url   = 'http://localhost/sotsu/verify.php?token=' . $email_token;
$mail_subject = '【ERAPRO】メールアドレスの認証をお願いします';
$mail_body    = $name . " 様\n\n";
$mail_body   .= "ERAPROにご登録いただきありがとうございます。\n";
$mail_body   .= "以下のリンクをクリックして、メールアドレスの認証を完了してください。\n\n";
$mail_body   .= $verify_url . "\n\n";
$mail_body   .= "※このリンクの有効期限は24時間です。\n";
$mail_body   .= "※このメールに心当たりがない場合は無視してください。\n\n";
$mail_body   .= "--\nERAPRO運営事務局\n";

send_mail($lid, $mail_subject, $mail_body);

// 7. 登録完了画面へリダイレクト
redirect('signup_success.php?email=' . urlencode($lid) . '&user_type=' . urlencode($user_type));
?>