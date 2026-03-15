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

// スコア → 診断タイプ分類
function diag_type_from_score(int $score): string {
    if ($score >= 60) return '価値伝達型';
    if ($score >= 40) return 'ハイブリッド型';
    return '支援先行型';
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
// agents（diagnosis_score を 0〜100 で均等に散らした8件）
// ============================================================

// Agent INSERT テンプレート（全プロフィール + スコアを一括登録）
$agent_sql =
    "INSERT INTO agents
       (name, lid, lpw, area, tags, title, story, philosophy,
        diagnosis_score, diagnosis_type, verification_status,
        life_flg, email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :area, :tags, :title, :story, :philosophy,
        :dscore, :dtype, :vstatus,
        0, 1, NOW(), NOW())";

// ---------- score=20 | 支援先行型 ----------
$sc = 20;
$results[] = upsert($pdo, 'agents', 'agent_hit', $agent_sql, [
    ':name'   => '検索ヒット太郎',
    ':lid'    => 'agent_hit',
    ':lpw'    => $lpw,
    ':area'   => '東京都',
    ':tags'   => '子育て,安心,ライフプラン',
    ':title'  => '子育て世代の保険相談、丁寧にお答えします',
    ':story'  => '前職は小学校教員。10年以上の教育現場の経験から「伝わる説明」を大切にしています。'
               . '3人の子を持つ父親として、子育て世代が抱える保険の不安に寄り添います。',
    ':philosophy' => '「難しいことをわかりやすく、親しみやすく」をモットーに、'
                   . 'お客様のライフステージに合ったご提案を心がけています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=10 | 支援先行型 ----------
$sc = 10;
$results[] = upsert($pdo, 'agents', 'agent_score10', $agent_sql, [
    ':name'   => '寄り添い花子',
    ':lid'    => 'agent_score10',
    ':lpw'    => $lpw,
    ':area'   => '福岡県',
    ':tags'   => '相続,女性向け,介護',
    ':title'  => '女性目線で、老後・相続のご不安をまるごとサポート',
    ':story'  => '介護福祉士として5年間、高齢者の方々に寄り添ってきました。'
               . 'その経験から、老後のお金の不安は「人生の問題」だと実感。保険の世界へ転身しました。'
               . '感情に寄り添いながら、最善のプランをご一緒に考えます。',
    ':philosophy' => '「安心をお届けすること」が私の使命です。'
                   . 'お客様が笑顔で帰れる相談を大切にしています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=35 | 支援先行型 ----------
$sc = 35;
$results[] = upsert($pdo, 'agents', 'agent_score35', $agent_sql, [
    ':name'   => '安心サポート三郎',
    ':lid'    => 'agent_score35',
    ':lpw'    => $lpw,
    ':area'   => '東京都',
    ':tags'   => '高齢者,医療,安心',
    ':title'  => '高齢者・ご家族の医療保険選びをじっくりサポート',
    ':story'  => '地域密着型のFPとして10年。ご高齢のお客様との相談を通じ、「寄り添いの大切さ」を学びました。'
               . '難しい数字より、まずお気持ちを聞くことを心がけています。',
    ':philosophy' => 'お客様が「話してよかった」と思える相談を目指しています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=45 | ハイブリッド型 ----------
$sc = 45;
$results[] = upsert($pdo, 'agents', 'agent_score45', $agent_sql, [
    ':name'   => 'バランス設計四郎',
    ':lid'    => 'agent_score45',
    ':lpw'    => $lpw,
    ':area'   => '大阪府',
    ':tags'   => '経営者,中小企業,バランス型',
    ':title'  => '感情と数字、両方から最適な保険を設計します',
    ':story'  => '銀行員として融資審査を担当後、FPへ転身。数字の分析と、経営者の想いへの共感を両立した提案が強みです。'
               . '中小企業オーナーの「もしもの備え」を一緒に考えます。',
    ':philosophy' => '「感情と論理のバランス」がよい保険設計の鍵だと考えています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=55 | ハイブリッド型 ----------
$sc = 55;
$results[] = upsert($pdo, 'agents', 'agent_score55', $agent_sql, [
    ':name'   => '総合提案五郎',
    ':lid'    => 'agent_score55',
    ':lpw'    => $lpw,
    ':area'   => '東京都',
    ':tags'   => '資産形成,NISA,バランス型',
    ':title'  => '保険×資産形成、トータルで将来を設計します',
    ':story'  => '証券会社出身のFPとして、保険と投資を組み合わせたトータル提案を得意としています。'
               . '「保険だけ」「投資だけ」ではなく、人生全体の設計図を描きます。',
    ':philosophy' => '「バランスのとれた資産設計が、安心な老後を作る」がモットーです。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=75 | 価値伝達型 ----------
$sc = 75;
$results[] = upsert($pdo, 'agents', 'agent_chat', $agent_sql, [
    ':name'   => 'チャット待機次郎',
    ':lid'    => 'agent_chat',
    ':lpw'    => $lpw,
    ':area'   => '大阪府',
    ':tags'   => '現場力,スピード対応,損保',
    ':title'  => '現場対応力No.1！いざという時に頼れるパートナー',
    ':story'  => '損害保険業界15年のキャリアを持つベテランです。'
               . '事故現場への対応から保険金請求サポートまで、データに基づきスピーディに動くことが強みです。'
               . '大阪・関西エリアを中心に、現場に近いサポートを提供しています。',
    ':philosophy' => '「数字と実績で信頼を作る」それが私の信念です。'
                   . '保険はご加入後のサポートが最も大切だと考えています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=70 | 価値伝達型 ----------
$sc = 70;
$results[] = upsert($pdo, 'agents', 'agent_score70', $agent_sql, [
    ':name'   => 'データ活用六郎',
    ':lid'    => 'agent_score70',
    ':lpw'    => $lpw,
    ':area'   => '福岡県',
    ':tags'   => '損保,データ重視,論理派',
    ':title'  => 'データと実績で、最適な保険を論理的にご提案',
    ':story'  => 'システムエンジニアからFPへ転身。徹底したデータ分析で、感情に流されない最適プランを提案します。'
               . '「なぜこの保険なのか」を数字で説明できることが私の強みです。',
    ':philosophy' => '「根拠のある提案だけをする」がポリシーです。データが最良のアドバイザーだと信じています。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- score=90 | 価値伝達型 ----------
$sc = 90;
$results[] = upsert($pdo, 'agents', 'agent_score90', $agent_sql, [
    ':name'   => '戦略立案七郎',
    ':lid'    => 'agent_score90',
    ':lpw'    => $lpw,
    ':area'   => '東京都',
    ':tags'   => '法人,税務,戦略的',
    ':title'  => '法人・経営者向け。税務と保険を組み合わせた戦略的設計',
    ':story'  => '大手コンサルファームでM&Aアドバイザリーに従事した後、保険業界へ。'
               . '財務諸表と保険設計を一体で考える、完全ロジック型のアドバイザーです。',
    ':philosophy' => '「感情ではなく、数字と戦略で人生を守る」。それが私の唯一の哲学です。',
    ':dscore' => $sc,
    ':dtype'  => diag_type_from_score($sc),
    ':vstatus' => 2,
]);

// ---------- KYC審査待ち（スコアなし） ----------
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

// ---------- アカウント停止Agent（life_flg=1） ----------
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

// 診断済ユーザー（score=30: 支援先行型ユーザー → agent_hit/score10/score35 と相性高）
$results[] = upsert($pdo, 'users', 'user_diag',
    "INSERT INTO users
       (name, lid, lpw, type_id, diagnosis_type, diagnosis_score, life_flg,
        email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :type_id, :dtype, :dscore, 0,
        1, NOW(), NOW())",
    [':name' => '診断済ユーザー', ':lid' => 'user_diag', ':lpw' => $lpw,
     ':type_id' => 1, ':dtype' => 'support_seeker', ':dscore' => 30]
);

// メッセージ送信ユーザー（score=80: 価値伝達型ユーザー → agent_chat/score70/score90 と相性高）
$results[] = upsert($pdo, 'users', 'user_msg',
    "INSERT INTO users
       (name, lid, lpw, type_id, diagnosis_type, diagnosis_score, life_flg,
        email_notification_flg, email_verified_at, indate)
     VALUES
       (:name, :lid, :lpw, :type_id, :dtype, :dscore, 0,
        1, NOW(), NOW())",
    [':name' => 'メッセージ送信ユーザー', ':lid' => 'user_msg', ':lpw' => $lpw,
     ':type_id' => 2, ':dtype' => 'logic_seeker', ':dscore' => 80]
);

// ============================================================
// 結果出力
// ============================================================
$is_cli = (PHP_SAPI === 'cli');

if (!$is_cli) {
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">';
    echo '<title>Test Seeder - ERAPRO</title>';
    echo '<style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 32px; }
        h2  { color: #4ec9b0; }
        .ok { color: #6a9955; }
        .summary { color: #dcdcaa; margin-top: 24px; font-size: 1.1rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; font-size: 0.88rem; }
        th { color: #9cdcfe; text-align: left; padding: 6px 12px; border-bottom: 2px solid #555; }
        td { padding: 6px 12px; border-bottom: 1px solid #333; }
        .type-v { color: #4ec9b0; }
        .type-h { color: #dcdcaa; }
        .type-s { color: #ce9178; }
        code { color: #ce9178; }
    </style>';
    echo '</head><body>';
    echo '<h2>🌱 ERAPRO Test Seeder</h2>';
    foreach ($results as $r) {
        echo '<p class="ok">' . htmlspecialchars($r) . '</p>';
    }
    echo '<p class="summary">✔ 全 ' . count($results) . ' 件の処理が完了しました。</p>';
    echo '<hr style="border-color:#444; margin: 24px 0;">';
    echo '<p style="color:#888; font-size:0.85rem;">パスワードはすべて <code>1111</code> です。</p>';

    // Agentsテーブル
    echo '<h3 style="color:#9cdcfe; margin-top:24px;">agents（有効: 8件）</h3>';
    echo '<table>';
    echo '<tr><th>lid</th><th>名前</th><th>エリア</th><th>score</th><th>diagnosis_type</th><th>備考</th></tr>';
    $agents_summary = [
        ['agent_hit',     '検索ヒット太郎',   '東京都',  20, '支援先行型', ''],
        ['agent_score10', '寄り添い花子',     '福岡県',  10, '支援先行型', ''],
        ['agent_score35', '安心サポート三郎', '東京都',  35, '支援先行型', ''],
        ['agent_score45', 'バランス設計四郎', '大阪府',  45, 'ハイブリッド型', ''],
        ['agent_score55', '総合提案五郎',     '東京都',  55, 'ハイブリッド型', ''],
        ['agent_chat',    'チャット待機次郎', '大阪府',  75, '価値伝達型', ''],
        ['agent_score70', 'データ活用六郎',   '福岡県',  70, '価値伝達型', ''],
        ['agent_score90', '戦略立案七郎',     '東京都',  90, '価値伝達型', ''],
        ['agent_pending', 'KYC審査待ちAgent', '未設定', '-', '-', 'vstatus=1（審査待ち）'],
        ['agent_stop',    'アカウント停止',   '福岡県', '-', '-', 'life_flg=1（停止）'],
    ];
    foreach ($agents_summary as $row) {
        $type = $row[4];
        $cls = ($type === '価値伝達型') ? 'type-v' : (($type === 'ハイブリッド型') ? 'type-h' : 'type-s');
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars($row[0]) . '</code></td>';
        echo '<td>' . htmlspecialchars($row[1]) . '</td>';
        echo '<td>' . htmlspecialchars($row[2]) . '</td>';
        echo '<td style="text-align:right;">' . htmlspecialchars((string)$row[3]) . '</td>';
        echo '<td class="' . $cls . '">' . htmlspecialchars($type) . '</td>';
        echo '<td style="color:#888;">' . htmlspecialchars($row[5]) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Usersテーブル
    echo '<h3 style="color:#9cdcfe; margin-top:24px;">users</h3>';
    echo '<table>';
    echo '<tr><th>lid</th><th>名前</th><th>score</th><th>diagnosis_type</th><th>マッチング傾向</th></tr>';
    echo '<tr><td><code>user_diag</code></td><td>診断済ユーザー</td><td style="text-align:right;">30</td><td class="type-s">support_seeker</td><td style="color:#888;">agent_hit / score10 / score35 と相性が高い</td></tr>';
    echo '<tr><td><code>user_msg</code></td><td>メッセージ送信ユーザー</td><td style="text-align:right;">80</td><td class="type-v">logic_seeker</td><td style="color:#888;">agent_chat / score70 / score90 と相性が高い</td></tr>';
    echo '</table>';

    echo '<h3 style="color:#9cdcfe; margin-top:24px;">admins</h3>';
    echo '<table>';
    echo '<tr><th>lid</th><th>名前</th></tr>';
    echo '<tr><td><code>admin_test</code></td><td>運営テスト管理者</td></tr>';
    echo '</table>';

    echo '</body></html>';
} else {
    foreach ($results as $r) echo $r . PHP_EOL;
    echo PHP_EOL . '✔ 全 ' . count($results) . ' 件の処理が完了しました。' . PHP_EOL;
    echo 'パスワードはすべて 1111 です。' . PHP_EOL;
    echo PHP_EOL . '--- Agents (diagnosis_score) ---' . PHP_EOL;
    echo 'agent_score10 :  10pt (支援先行型)' . PHP_EOL;
    echo 'agent_hit     :  20pt (支援先行型)' . PHP_EOL;
    echo 'agent_score35 :  35pt (支援先行型)' . PHP_EOL;
    echo 'agent_score45 :  45pt (ハイブリッド型)' . PHP_EOL;
    echo 'agent_score55 :  55pt (ハイブリッド型)' . PHP_EOL;
    echo 'agent_score70 :  70pt (価値伝達型)' . PHP_EOL;
    echo 'agent_chat    :  75pt (価値伝達型)' . PHP_EOL;
    echo 'agent_score90 :  90pt (価値伝達型)' . PHP_EOL;
}
