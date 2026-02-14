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
?>