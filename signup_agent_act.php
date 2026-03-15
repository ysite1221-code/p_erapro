<?php
session_start();
include("function.php");

// 1. POSTデータ取得
$name = $_POST["name"];
$lid  = $_POST["lid"];
$lpw  = $_POST["lpw"];

// 利用規約への同意チェック
if (empty($_POST['agree_terms'])) {
    exit('利用規約とプライバシーポリシーへの同意が必要です。ブラウザの戻るボタンでお戻りください。');
}

// 2. DB接続
$pdo = db_conn();

// 3. パスワードハッシュ化 & トークン生成
$lpw_hash = password_hash($lpw, PASSWORD_DEFAULT);
$email_token = bin2hex(random_bytes(32)); 

// 4. データ登録SQL
// verification_status = 0 (未提出) で登録
$sql = "INSERT INTO agents(name, lid, lpw, email_token, verification_status, email_notification_flg, life_flg)
        VALUES(:name, :lid, :lpw, :email_token, 0, 1, 0)";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$stmt->bindValue(':lpw', $lpw_hash, PDO::PARAM_STR);
$stmt->bindValue(':email_token', $email_token, PDO::PARAM_STR);
$status = $stmt->execute();

// 5. 実行後の処理
if($status==false){
    // メールアドレス重複エラーなどを想定
    $error = $stmt->errorInfo();
    if(strpos($error[2], 'Duplicate entry') !== false){
        exit("このメールアドレスは既に登録されています。");
    }
    sql_error($stmt);
}else{
    // --------------------------------------------------------
    // メール送信処理
    // --------------------------------------------------------
    
    // 認証用URL (localhost部分はご自身の環境に合わせてください)
    $verify_url = "http://localhost/sotsu/verify.php?token=" . $email_token;
    
    // メール本文作成
    $mail_subject = "【ERAPRO】メールアドレス認証のお願い";
    $mail_body  = $name . " 様\n\n";
    $mail_body .= "ERAPRO（エラプロ）にご登録いただきありがとうございます。\n";
    $mail_body .= "以下のリンクをクリックして、メールアドレス認証を完了させてください。\n\n";
    $mail_body .= $verify_url . "\n\n";
    $mail_body .= "※このリンクの有効期限は24時間です。\n";
    $mail_body .= "--------------------------------------------------\n";
    $mail_body .= "ERAPRO運営事務局\n";
    $mail_body .= "https://erapro.jp/";

    // メール送信実行
    send_mail($lid, $mail_subject, $mail_body);

    // 登録完了画面へリダイレクト
    redirect('signup_success.php?email=' . urlencode($lid) . '&user_type=agent');
}
?>