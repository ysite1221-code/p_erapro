<?php
// 設定ファイルの読み込み
// config.php が存在しない場合は config.php.example をコピーして作成してください
if (!file_exists(__DIR__ . '/config.php')) {
    exit('設定ファイルが見つかりません。config.php.example をコピーして config.php を作成してください。');
}
require_once(__DIR__ . '/config.php');

// デバッグ設定（development 環境のみエラーを表示）
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// XSS対策関数
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES);
}

// DB接続関数
function db_conn() {
    try {
        return new PDO(
            'mysql:dbname=' . DB_NAME . ';charset=utf8;host=' . DB_HOST,
            DB_USER,
            DB_PASS
        );
    } catch (PDOException $e) {
        exit('DB Connection Error: ' . $e->getMessage());
    }
}

// SQLエラー関数
function sql_error($stmt) {
    $error = $stmt->errorInfo();
    exit('SQLError: ' . $error[2]);
}

// リダイレクト関数
function redirect($file_name) {
    header('Location: ' . $file_name);
    exit();
}

// セッション有効期限（秒）: 最終操作から2時間
// TODO: 本リリース（有料化）時にここの機能制限を復活させる場合、この定数も見直すこと
define('SESSION_LIFETIME', 7200);

// ログインチェック関数
// $type: '' = チェックなし / 'agent' = Agent専用 / 'user' = User専用 / 'admin' = Admin専用
function loginCheck($type = '') {
    // ── セッション有効期限チェック（最終操作から2時間） ──
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            // 期限切れ: セッションを完全破棄してログインページへ
            session_unset();
            session_destroy();
            $login_page = match($type) {
                'agent' => 'login_agent.php',
                'admin' => 'login_admin.php',
                default => 'login_user.php',
            };
            redirect($login_page);
        }
    }
    // 操作のたびに最終操作時刻を更新（タイムアウトをリセット）
    $_SESSION['last_activity'] = time();

    // ── セッション認証チェック ──
    // セッション未認証はログインページへ
    if (!isset($_SESSION['chk_ssid']) || $_SESSION['chk_ssid'] !== session_id()) {
        $login_page = match($type) {
            'agent' => 'login_agent.php',
            'admin' => 'login_admin.php',
            default => 'login_user.php',
        };
        redirect($login_page);
    }

    // ユーザータイプの不一致はトップページへ
    if ($type !== '' && (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $type)) {
        redirect('index.php');
    }

    // セッションIDを更新してセッション固定攻撃を防止
    $_SESSION['chk_ssid'] = session_id();
}

// KYC承認チェック関数（未承認AgentはKYCページへ強制リダイレクト）
function check_agent_approval() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'agent') {
        $pdo = db_conn();
        $stmt = $pdo->prepare("SELECT verification_status FROM agents WHERE id=:id");
        $stmt->bindValue(':id', (int)$_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetchColumn() !== 2) {
            redirect('agent_kyc.php');
        }
    }
}

// SendGrid メール送信関数（cURL版）
function send_mail($to, $subject, $body) {
    $data = [
        'personalizations' => [['to' => [['email' => $to]]]],
        'from'    => ['email' => MAIL_FROM_EMAIL, 'name' => MAIL_FROM_NAME],
        'subject' => $subject,
        'content' => [['type' => 'text/plain', 'value' => $body]],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SENDGRID_API_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200 || $http_code === 202);
}
