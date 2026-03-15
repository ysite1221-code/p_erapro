<?php
session_start();
include("function.php");
loginCheck('user');

$user_id  = (int)$_SESSION['id'];
$agent_id = (int)($_POST['agent_id'] ?? 0);
$rating   = (int)($_POST['rating']   ?? 0);
$comment  = trim($_POST['comment']   ?? '');

// バリデーション
if ($agent_id <= 0 || $rating < 1 || $rating > 5) {
    redirect('mypage_user.php');
}

$pdo = db_conn();

// reviewsテーブルをなければ作成
$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    agent_id   INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_agent (user_id, agent_id),
    INDEX idx_agent (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// テーブルスキーマの自動アップデート
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
} catch (PDOException $e) {
    // カラムが既に存在する場合のエラーは無視する
}

// INSERT or UPDATE（同一ユーザー→同一Agentへの重複は上書き）
$stmt = $pdo->prepare(
    "INSERT INTO reviews (user_id, agent_id, rating, comment)
     VALUES (:uid, :aid, :rating, :comment)
     ON DUPLICATE KEY UPDATE
         rating     = VALUES(rating),
         comment    = VALUES(comment),
         updated_at = NOW()"
);
$stmt->bindValue(':uid',     $user_id,  PDO::PARAM_INT);
$stmt->bindValue(':aid',     $agent_id, PDO::PARAM_INT);
$stmt->bindValue(':rating',  $rating,   PDO::PARAM_INT);
$stmt->bindValue(':comment', $comment,  PDO::PARAM_STR);
if (!$stmt->execute()) {
    sql_error($stmt);
}

redirect('profile.php?id=' . $agent_id . '&review=1');
