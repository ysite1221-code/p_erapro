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

// ── メール通知 ──────────────────────────────────────────────────
// 受信者テーブルを判定（sender_type=1→user送信→agentsへ通知, type=2→agent送信→usersへ通知）
$recv_table = ($sender_type === 1) ? 'agents' : 'users';
$stmt2 = $pdo->prepare("SELECT lid, name FROM {$recv_table} WHERE id=:rid AND life_flg=0");
$stmt2->bindValue(':rid', $receiver_id, PDO::PARAM_INT);
$stmt2->execute();
$recv = $stmt2->fetch(PDO::FETCH_ASSOC);

if ($recv) {
    $mail_subject = '【ERAPRO】新着メッセージが届きました';
    $mail_body  = $recv['name'] . " 様\n\n";
    $mail_body .= "ERAPROに新しいメッセージが届いています。\n\n";
    $mail_body .= "▼マイページからご確認ください\n";
    $mail_body .= "http://localhost/sotsu/messages_list.php\n\n";
    $mail_body .= "--\nEKAPRO運営事務局\n";
    send_mail($recv['lid'], $mail_subject, $mail_body);
    // ※送信失敗はサイレント扱い（メッセージ保存の成功レスポンスはそのまま返す）
}
// ──────────────────────────────────────────────────────────────

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
