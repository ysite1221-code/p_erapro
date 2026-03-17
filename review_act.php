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

// reviewsテーブルがなければ作成
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
} catch (PDOException $e) {}

// 既存の重複データをクリーンアップ（最新のものだけ残す）
$pdo->exec("DELETE t1 FROM reviews t1 INNER JOIN reviews t2 ON t1.user_id = t2.user_id AND t1.agent_id = t2.agent_id AND t1.id < t2.id");

// 既存レコードを確認（SELECT → INSERT or UPDATE）
$chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=:uid AND agent_id=:aid LIMIT 1");
$chk->bindValue(':uid', $user_id,  PDO::PARAM_INT);
$chk->bindValue(':aid', $agent_id, PDO::PARAM_INT);
$chk->execute();
$existing_id = $chk->fetchColumn();

if ($existing_id) {
    // 既存レコードを上書き UPDATE
    $stmt = $pdo->prepare(
        "UPDATE reviews SET rating=:rating, comment=:comment, updated_at=NOW()
         WHERE id=:id"
    );
    $stmt->bindValue(':rating',  $rating,      PDO::PARAM_INT);
    $stmt->bindValue(':comment', $comment,     PDO::PARAM_STR);
    $stmt->bindValue(':id',      $existing_id, PDO::PARAM_INT);
} else {
    // 新規 INSERT
    $stmt = $pdo->prepare(
        "INSERT INTO reviews (user_id, agent_id, rating, comment)
         VALUES (:uid, :aid, :rating, :comment)"
    );
    $stmt->bindValue(':uid',     $user_id,  PDO::PARAM_INT);
    $stmt->bindValue(':aid',     $agent_id, PDO::PARAM_INT);
    $stmt->bindValue(':rating',  $rating,   PDO::PARAM_INT);
    $stmt->bindValue(':comment', $comment,  PDO::PARAM_STR);
}

if (!$stmt->execute()) {
    sql_error($stmt);
}

redirect('profile.php?id=' . $agent_id . '&review=1');
