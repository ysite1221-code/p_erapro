<?php
// 1. セッション開始
session_start();

// 2. セッション変数を空にする（記憶喪失にさせる）
$_SESSION = array();

// 3. セッションIDのクッキーも削除する（鍵を捨てる）
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// 4. セッション破壊
session_destroy();

// 5. トップページへ移動
header("Location: index.php");
exit();
?>