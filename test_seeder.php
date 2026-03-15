<?php
/**
 * test_seeder.php
 * テスト用データを admins / agents / users テーブルに一括登録する
 * 実行: php test_seeder.php  または  ブラウザから http://localhost/sotsu/test_seeder.php
 */

// ===== セキュリティガード（本番環境での誤実行を防止） =====
define('APP_ENV_CHECK', true); // function.php 読み込み前に定義

require_once __DIR__ . '/function.php';

// developmentのみ実行許可
if (APP_ENV !== 'development') {
    exit('❌ このスクリプトはdevelopment環境のみ実行できます。');
}

$pdo = db_conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 共通パスワード
$lpw = password_hash('1111', PASSWORD_DEFAULT);

$results = [];

// ============================================================
// ヘルパー：DELETE→INSERT で冪等に登録
// ============================================================
function upsert(PDO $pdo, string $table, string $lid, string $sql, array $params): string {
    // 既存レコードを削除
    $del = $pdo->prepare("DELETE FROM {$table} WHERE lid = :lid");
    $del->bindValue(':lid', $lid, PDO::PARAM_STR);
    $del->execute();

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $val, $type);
    }
    $stmt->execute();
    return "✅ [{$table}] {$lid} を登録しました";
}

// ============================================================
// admins
// ============================================================
$results[] = upsert($pdo, 'admins', 'admin_test',
    "INSERT INTO admins (name, lid, lpw, kanri_flg, life_flg, indate)
     VALUES (:name, :lid, :lpw, :kanri_flg, :life_flg, NOW())",
    [':name' => '運営テスト管理者', ':lid' => 'admin_test', ':lpw' => $lpw,
     ':kanri_flg' => 1, ':life_flg' => 0]
);

// ============================================================
// agents
// ============================================================

// 検索ヒット太郎
$results[] = upsert($pdo, 'agents', 'agent_hit',
    "INSERT INTO agents
       (name, lid, lpw, area, tags, verification_status,
        life_flg, email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :area, :tags, :vstatus,
        0, 1, NOW(), NOW())",
    [':name' => '検索ヒット太郎', ':lid' => 'agent_hit', ':lpw' => $lpw,
     ':area' => '東京都', ':tags' => '子育て,安心', ':vstatus' => 2]
);

// チャット待機次郎
$results[] = upsert($pdo, 'agents', 'agent_chat',
    "INSERT INTO agents
       (name, lid, lpw, area, tags, verification_status,
        life_flg, email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :area, :tags, :vstatus,
        0, 1, NOW(), NOW())",
    [':name' => 'チャット待機次郎', ':lid' => 'agent_chat', ':lpw' => $lpw,
     ':area' => '大阪府', ':tags' => '現場力,寄り添い', ':vstatus' => 2]
);

// KYC審査待ちAgent
$results[] = upsert($pdo, 'agents', 'agent_pending',
    "INSERT INTO agents
       (name, lid, lpw, area, verification_status,
        life_flg, email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :area, :vstatus,
        0, 1, NOW(), NOW())",
    [':name' => 'KYC審査待ちAgent', ':lid' => 'agent_pending', ':lpw' => $lpw,
     ':area' => '未設定', ':vstatus' => 1]
);

// アカウント停止Agent（life_flg: 1）
$results[] = upsert($pdo, 'agents', 'agent_stop',
    "INSERT INTO agents
       (name, lid, lpw, area, verification_status,
        life_flg, email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :area, :vstatus,
        1, 1, NOW(), NOW())",
    [':name' => 'アカウント停止Agent', ':lid' => 'agent_stop', ':lpw' => $lpw,
     ':area' => '福岡県', ':vstatus' => 2]
);

// ============================================================
// users
// ============================================================

// 診断済ユーザー
$results[] = upsert($pdo, 'users', 'user_diag',
    "INSERT INTO users
       (name, lid, lpw, type_id, life_flg,
        email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :type_id, 0,
        1, NOW(), NOW())",
    [':name' => '診断済ユーザー', ':lid' => 'user_diag', ':lpw' => $lpw,
     ':type_id' => 1]
);

// メッセージ送信ユーザー
$results[] = upsert($pdo, 'users', 'user_msg',
    "INSERT INTO users
       (name, lid, lpw, type_id, life_flg,
        email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :type_id, 0,
        1, NOW(), NOW())",
    [':name' => 'メッセージ送信ユーザー', ':lid' => 'user_msg', ':lpw' => $lpw,
     ':type_id' => 2]
);

// ============================================================
// プロフィール UPDATE（検索ヒット太郎・チャット待機次郎）
// ============================================================

$stmt = $pdo->prepare(
    "UPDATE agents SET
        title       = :title,
        story       = :story,
        philosophy  = :philosophy
     WHERE lid = :lid"
);

$stmt->execute([
    ':lid'        => 'agent_hit',
    ':title'      => '子育て世代の保険相談、丁寧にお答えします',
    ':story'      => '前職は小学校教員。10年以上の教育現場の経験から「伝わる説明」を大切にしています。'
                   . '3人の子を持つ父親として、子育て世代が抱える保険の不安に寄り添います。'
                   . '難しい保険の話をわかりやすく、一緒に考えていきましょう。',
    ':philosophy' => '「難しいことをわかりやすく、親しみやすく」をモットーに、'
                   . 'お客様のライフステージに合ったご提案を心がけています。',
]);
$results[] = '✅ [agents] agent_hit のプロフィールをUPDATEしました';

$stmt->execute([
    ':lid'        => 'agent_chat',
    ':title'      => '現場対応力No.1！いざという時に頼れるパートナー',
    ':story'      => '損害保険業界15年のキャリアを持つベテランです。'
                   . '事故現場への対応から保険金請求サポートまで、スピーディに動くことが強みです。'
                   . '大阪・関西エリアを中心に、現場に近いサポートを提供しています。',
    ':philosophy' => '「困ったときに、すぐ来る」それが私の信念です。'
                   . '保険はご加入後のサポートが最も大切だと考えています。',
]);
$results[] = '✅ [agents] agent_chat のプロフィールをUPDATEしました';

// ============================================================
// 結果出力
// ============================================================
$is_cli = (PHP_SAPI === 'cli');

if (!$is_cli) {
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">';
    echo '<title>Test Seeder - ERAPRO</title>';
    echo '<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:32px;}';
    echo 'h2{color:#4ec9b0;} .ok{color:#6a9955;} .summary{color:#dcdcaa;margin-top:24px;font-size:1.1rem;}</style>';
    echo '</head><body>';
    echo '<h2>🌱 ERAPRO Test Seeder</h2>';
    foreach ($results as $r) {
        echo '<p class="ok">' . htmlspecialchars($r) . '</p>';
    }
    echo '<p class="summary">✔ 全 ' . count($results) . ' 件の処理が完了しました。</p>';
    echo '<hr style="border-color:#444;margin-top:24px;">';
    echo '<p style="color:#888;font-size:0.85rem;">パスワードはすべて <code style="color:#ce9178;">1111</code> です。</p>';
    echo '<table style="border-collapse:collapse;width:100%;margin-top:12px;font-size:0.88rem;">';
    echo '<tr style="color:#9cdcfe;"><th style="text-align:left;padding:6px 12px;border-bottom:1px solid #444;">テーブル</th><th style="text-align:left;padding:6px 12px;border-bottom:1px solid #444;">lid</th><th style="text-align:left;padding:6px 12px;border-bottom:1px solid #444;">備考</th></tr>';
    $summary = [
        ['admins',  'admin_test',    '管理者ログイン'],
        ['agents',  'agent_hit',     '東京・子育て・検索ヒット用'],
        ['agents',  'agent_chat',    '大阪・メッセージ待機用'],
        ['agents',  'agent_pending', 'KYC審査待ち（verification_status=1）'],
        ['agents',  'agent_stop',    'life_flg=1（停止済み）'],
        ['users',   'user_diag',     '診断済（type_id=1）'],
        ['users',   'user_msg',      'メッセージ送信テスト用（type_id=2）'],
    ];
    foreach ($summary as $row) {
        echo '<tr>';
        foreach ($row as $cell) echo '<td style="padding:6px 12px;border-bottom:1px solid #333;">' . htmlspecialchars($cell) . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
} else {
    foreach ($results as $r) echo $r . PHP_EOL;
    echo PHP_EOL . '✔ 全 ' . count($results) . ' 件の処理が完了しました。' . PHP_EOL;
    echo 'パスワードはすべて 1111 です。' . PHP_EOL;
}
