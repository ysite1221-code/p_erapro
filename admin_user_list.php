<?php
session_start();
include("function.php");

// 管理者チェック
if (
    !isset($_SESSION["chk_ssid"]) ||
    $_SESSION["chk_ssid"] !== session_id() ||
    $_SESSION["user_type"] !== 'admin'
) {
    redirect("login_admin.php");
}

$pdo = db_conn();

// 検索条件
$search = trim($_GET['q'] ?? '');

// SQL組み立て
$sql    = "SELECT * FROM users";
$params = [];
if ($search !== '') {
    $sql   .= " WHERE (name LIKE :q OR CAST(id AS CHAR) LIKE :q2 OR lid LIKE :q3)";
    $params[':q']  = '%' . $search . '%';
    $params[':q2'] = '%' . $search . '%';
    $params[':q3'] = '%' . $search . '%';
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User一覧 - ERAPRO Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background: #f4f6f9;
            margin: 0;
            display: flex;
            min-height: 100vh;
            font-size: 14px;
            color: #333;
        }

        /* ===== サイドバー ===== */
        .sidebar {
            width: 240px;
            background: #1e2330;
            color: #fff;
            min-height: 100vh;
            flex-shrink: 0;
        }
        .sidebar-brand {
            padding: 22px 24px;
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            color: #fff;
        }
        .sidebar-brand span { font-size: 0.65rem; font-weight: 400; color: #888; display: block; margin-top: 2px; letter-spacing: 0.04em; }
        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #9aa0b0;
            padding: 14px 24px;
            font-size: 0.875rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s;
        }
        .menu a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .menu a.active { background: rgba(0,123,255,0.15); color: #4da6ff; border-left-color: #007bff; font-weight: 700; }
        .menu .icon { font-size: 1.1rem; }

        /* ===== メインコンテンツ ===== */
        .content { flex: 1; padding: 36px 40px 80px; min-width: 0; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 900;
            color: #111;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .page-header-meta { font-size: 0.82rem; color: #aaa; margin-top: 4px; }
        .btn-logout {
            background: #dc3545;
            color: #fff;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            transition: background 0.2s;
        }
        .btn-logout:hover { background: #b02a37; color: #fff; }

        .flash {
            padding: 14px 20px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .flash-success { background: #f0faf2; border-left: 4px solid #2e7d32; color: #2e7d32; }
        .flash-warning { background: #fff8e1; border-left: 4px solid #d39e00; color: #856404; }

        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input {
            flex: 1;
            max-width: 340px;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
            color: #333;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-bar input:focus { border-color: #007bff; }
        .search-bar button {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s;
        }
        .search-bar button:hover { background: #0062cc; }
        .search-bar .btn-clear {
            padding: 10px 16px;
            background: #f5f5f5;
            color: #666;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            font-family: inherit;
        }
        .search-bar .btn-clear:hover { background: #e0e0e0; }

        .table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .table-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-card-header h2 { font-size: 1rem; font-weight: 700; color: #111; margin: 0; }
        .table-count { font-size: 0.82rem; color: #aaa; }

        table { width: 100%; border-collapse: collapse; }
        thead { border-bottom: 2px solid #f0f0f0; }
        th {
            background: #fff;
            color: #888;
            font-size: 0.73rem;
            font-weight: 700;
            padding: 12px 20px;
            text-align: left;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        td {
            padding: 14px 20px;
            font-size: 0.875rem;
            border-bottom: 1px solid #f5f5f5;
            color: #333;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafbff; }

        .badge {
            display: inline-block;
            font-size: 0.72rem;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-active    { background: #f0faf2; color: #2e7d32; }
        .badge-suspended { background: #fff0f0; color: #c62828; }

        .btn-suspend {
            padding: 6px 14px;
            background: #fff0f0;
            color: #c62828;
            border: 1px solid #f5c6c6;
            border-radius: 5px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }
        .btn-suspend:hover { background: #dc3545; color: #fff; border-color: #dc3545; }
        .btn-activate {
            padding: 6px 14px;
            background: #f0faf2;
            color: #2e7d32;
            border: 1px solid #b7dfbf;
            border-radius: 5px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }
        .btn-activate:hover { background: #28a745; color: #fff; border-color: #28a745; }

        .empty-row td { text-align: center; padding: 48px; color: #bbb; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        ERAPRO ADMIN
        <span>管理パネル</span>
    </div>
    <div class="menu">
        <a href="admin_dashboard.php"><span class="material-icons-outlined icon">dashboard</span>ダッシュボード</a>
        <a href="admin_agent_list.php"><span class="material-icons-outlined icon">people</span>Agent一覧</a>
        <a href="admin_user_list.php" class="active"><span class="material-icons-outlined icon">face</span>User一覧</a>
    </div>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <h1>User一覧</h1>
            <div class="page-header-meta">管理者: <?= h($_SESSION['name']) ?></div>
        </div>
        <a href="logout.php" class="btn-logout">ログアウト</a>
    </div>

    <?php if (isset($_GET['result'])): ?>
        <?php if ($_GET['result'] === 'suspended'): ?>
        <div class="flash flash-warning">⚠️ アカウントを停止しました。</div>
        <?php elseif ($_GET['result'] === 'activated'): ?>
        <div class="flash flash-success">✅ アカウントを再開しました。</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- 検索フォーム -->
    <form method="get" class="search-bar">
        <input type="text" name="q" value="<?= h($search) ?>"
               placeholder="名前・ID・ログインIDで検索...">
        <button type="submit">検索</button>
        <?php if ($search !== ''): ?>
        <a href="admin_user_list.php"><button type="button" class="btn-clear">クリア</button></a>
        <?php endif; ?>
    </form>

    <!-- テーブル -->
    <div class="table-card">
        <div class="table-card-header">
            <h2>登録User</h2>
            <span class="table-count">
                <?php if ($search !== ''): ?>
                「<?= h($search) ?>」の検索結果：<?= count($users) ?> 件
                <?php else: ?>
                <?= count($users) ?> 件
                <?php endif; ?>
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>ログインID</th>
                    <th>アカウント</th>
                    <th>アクション</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr class="empty-row"><td colspan="5">該当するUserが見つかりませんでした。</td></tr>
                <?php else: ?>
                <?php foreach ($users as $u):
                    $is_active = ((int)$u['life_flg'] === 0);
                ?>
                <tr>
                    <td style="color:#aaa; font-size:0.78rem;">#<?= h($u['id']) ?></td>
                    <td><strong><?= h($u['name']) ?></strong></td>
                    <td style="color:#666;"><?= h($u['lid']) ?></td>
                    <td>
                        <?php if ($is_active): ?>
                        <span class="badge badge-active">有効</span>
                        <?php else: ?>
                        <span class="badge badge-suspended">停止中</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="admin_status_act.php" style="display:inline;"
                              onsubmit="return confirm('<?= $is_active ? 'このUserを停止しますか？' : 'このUserを再開しますか？' ?>');">
                            <input type="hidden" name="user_type" value="user">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <?php if ($is_active): ?>
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="btn-suspend">停止する</button>
                            <?php else: ?>
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn-activate">再開する</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
