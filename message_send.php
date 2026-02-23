<?php
session_start();
include("function.php");

header('Content-Type: application/json');

// セッション検証（user または agent のみ）
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    !in_array($_SESSION['user_type'], ['user', 'agent'])
) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit();
}

// POSTのみ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$user_type   = $_SESSION['user_type'];
$sender_id   = (int)$_SESSION['id'];
$sender_type = ($user_type === 'user') ? 1 : 2;

$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

// バリデーション
if ($receiver_id <= 0) {
    echo json_encode(['error' => '送信先が不正です']);
    exit();
}
if ($message === '') {
    echo json_encode(['error' => 'メッセージを入力してください']);
    exit();
}
if (mb_strlen($message) > 2000) {
    echo json_encode(['error' => 'メッセージは2000文字以内で入力してください']);
    exit();
}

$pdo = db_conn();

// receiver が実在するか確認
if ($sender_type === 1) {
    // user → agent へ送信
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE id=:rid AND life_flg=0");
} else {
    // agent → user へ送信
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id=:rid AND life_flg=0");
}
$stmt->bindValue(':rid', $receiver_id, PDO::PARAM_INT);
$stmt->execute();
if (!$stmt->fetch()) {
    echo json_encode(['error' => '送信先が見つかりません']);
    exit();
}

// 自分自身への送信を禁止
if ($sender_id === $receiver_id && $sender_type === 2) {
    echo json_encode(['error' => '自分自身へは送信できません']);
    exit();
}

// INSERT
$stmt = $pdo->prepare(
    "INSERT INTO messages (sender_id, receiver_id, sender_type, message, created_at)
     VALUES (:sender_id, :receiver_id, :sender_type, :message, NOW())"
);
$stmt->bindValue(':sender_id',   $sender_id,   PDO::PARAM_INT);
$stmt->bindValue(':receiver_id', $receiver_id, PDO::PARAM_INT);
$stmt->bindValue(':sender_type', $sender_type, PDO::PARAM_INT);
$stmt->bindValue(':message',     $message,     PDO::PARAM_STR);
$stmt->execute();

$new_id = (int)$pdo->lastInsertId();

echo json_encode([
    'result'  => 'ok',
    'message' => [
        'id'          => $new_id,
        'sender_id'   => $sender_id,
        'sender_type' => $sender_type,
        'message'     => $message,
        'created_at'  => date('Y-m-d H:i:s'),
    ],
]);
?>
