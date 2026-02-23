<?php
session_start();
include("function.php");

// ユーザーログインチェック（未ログインは401）
if (
    !isset($_SESSION['chk_ssid']) ||
    $_SESSION['chk_ssid'] !== session_id() ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'user'
) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit();
}

// POSTのみ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

header('Content-Type: application/json');

$user_id  = (int)$_SESSION['id'];
$agent_id = (int)($_POST['agent_id'] ?? 0);
$action   = $_POST['action'] ?? ''; // 'favorite' or 'my_agent'

if ($agent_id <= 0 || !in_array($action, ['favorite', 'my_agent'])) {
    echo json_encode(['error' => '不正なパラメータです']);
    exit();
}

// status値: 1=お気に入り, 2=My Agent
$new_status = ($action === 'my_agent') ? 2 : 1;

$pdo = db_conn();

// favoritesテーブルをなければ作成
$pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    agent_id   INT NOT NULL,
    status     TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_agent (user_id, agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 現在の状態を取得
$stmt = $pdo->prepare("SELECT id, status FROM favorites WHERE user_id=:uid AND agent_id=:aid");
$stmt->bindValue(':uid', $user_id,  PDO::PARAM_INT);
$stmt->bindValue(':aid', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['status'] === $new_status) {
        // 同じ状態 → 削除（トグルOFF）
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id=:id");
        $stmt->bindValue(':id', $existing['id'], PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['result' => 'removed', 'action' => $action]);
    } else {
        // 別のステータスに変更（例: お気に入り→My Agent）
        $stmt = $pdo->prepare("UPDATE favorites SET status=:status, updated_at=NOW() WHERE id=:id");
        $stmt->bindValue(':status', $new_status,     PDO::PARAM_INT);
        $stmt->bindValue(':id',     $existing['id'], PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['result' => 'updated', 'action' => $action, 'status' => $new_status]);
    }
} else {
    // 新規追加
    $stmt = $pdo->prepare(
        "INSERT INTO favorites (user_id, agent_id, status, created_at)
         VALUES (:uid, :aid, :status, NOW())"
    );
    $stmt->bindValue(':uid',    $user_id,    PDO::PARAM_INT);
    $stmt->bindValue(':aid',    $agent_id,   PDO::PARAM_INT);
    $stmt->bindValue(':status', $new_status, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['result' => 'added', 'action' => $action, 'status' => $new_status]);
}
?>
