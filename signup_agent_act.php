<?php
session_start();
include("function.php");

// 1. POSTデータ取得
$name = $_POST["name"];
$lid  = $_POST["lid"];
$lpw  = $_POST["lpw"];

// 2. DB接続
$pdo = db_conn();

// 3. パスワードハッシュ化 & トークン生成
$lpw_hash = password_hash($lpw, PASSWORD_DEFAULT);
$email_token = bin2hex(random_bytes(32)); 

// 4. データ登録SQL
// verification_status = 0 (未提出) で登録
$sql = "INSERT INTO agents(name, lid, lpw, email_token, verification_status, email_notification_flg, kanri_flg, life_flg) 
        VALUES(:name, :lid, :lpw, :email_token, 0, 1, 0, 0)";

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
    $verify_url = "http://localhost/p_erapro/verify.php?token=" . $email_token;
    
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
    // ★重要: ここで function.php の send_mail を呼ぶ
    $is_sent = send_mail($lid, $mail_subject, $mail_body);

    if($is_sent){
        // 送信成功時：画面遷移せず、メッセージを表示して終了
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <title>メール送信完了 - ERAPRO</title>
            <style>
                body{font-family:sans-serif;text-align:center;padding:50px;background-color:#f8f9fa;}
                .box{background:#fff;padding:40px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.05);max-width:600px;margin:0 auto;}
            </style>
        </head>
        <body>
            <div class="box">
                <h2>認証メールを送信しました</h2>
                <p>ご登録のメールアドレス（<?= htmlspecialchars($lid) ?>）宛に認証メールをお送りしました。<br>
                メール内のリンクをクリックして、登録を完了させてください。</p>
                <p><small>※メールが届かない場合は、迷惑メールフォルダもご確認ください。</small></p>
            </div>
        </body>
        </html>
        <?php
        exit(); // ここで処理を止める（ログイン画面には行かせない）
    } else {
        exit("メール送信に失敗しました。管理者にお問い合わせください。");
    }
}
?>