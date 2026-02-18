<?php
// デバッグ設定（エラーがあれば画面に出す）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// XSS対策関数（修正箇所：NULL対応）
function h($str) {
    // $str が NULL なら空文字 '' に変換してから処理する
    return htmlspecialchars($str ?? '', ENT_QUOTES);
}

// DB接続関数
function db_conn() {
   try {
        $db_name = 'p_erapro';    // ローカルで作ったDB名
        $db_host = 'localhost';   // 
        $db_id   = 'root';        // XAMPPのデフォルトID
        $db_pw   = '';            // XAMPPのデフォルトパスワード（空）
    
        return new PDO('mysql:dbname='.$db_name.';charset=utf8;host='.$db_host, $db_id, $db_pw);
    } catch (PDOException $e) {
        exit('DB Connection Error:' . $e->getMessage());
    }
}

// SQLエラー関数
function sql_error($stmt) {
    $error = $stmt->errorInfo();
    exit("SQLError:" . $error[2]);
}

// リダイレクト関数
function redirect($file_name) {
    header("Location: " . $file_name);
    exit();
}

/// ログインチェック関数
function loginCheck() {
    // ログインしていなければエラー
    if (!isset($_SESSION["chk_ssid"]) || $_SESSION["chk_ssid"] != session_id()) {
        exit("LOGIN ERROR: ログインしていません");
    } else {
        // session_regenerate_id(true); 
        
        $_SESSION["chk_ssid"] = session_id();
    }
}

// ==========================================
// SendGridメール送信関数 (cURL版)
// ==========================================
function send_mail($to, $subject, $body) {
    // ▼▼▼ ここにさっきのAPIキーを貼る ▼▼▼
    $api_key = 'APIKEY IS HERE'; 
    
    // ▼▼▼ SendGridで認証した「From Email」を貼る ▼▼▼
    $from_email = 'info@erapro.jp'; 
    $from_name  = 'ERAPRO運営事務局';

    $url = 'https://api.sendgrid.com/v3/mail/send';

    $data = [
        "personalizations" => [
            [
                "to" => [
                    [
                        "email" => $to
                    ]
                ]
            ]
        ],
        "from" => [
            "email" => $from_email,
            "name"  => $from_name
        ],
        "subject" => $subject,
        "content" => [
            [
                "type" => "text/plain",
                "value" => $body
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 202(Accepted) が返ってくれば成功
    if ($http_code == 200 || $http_code == 202) {
        return true;
    } else {
        // エラー時はログに残すなどの処理推奨
        // echo "Mail Error: " . $response; 
        return false;
    }
}

?>