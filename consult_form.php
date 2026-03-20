<?php
session_start();
include("function.php");
loginCheck('user');

$agent_id = (int)($_GET['agent_id'] ?? 0);
if ($agent_id <= 0) {
    redirect('search.php');
}

$pdo = db_conn();

// Agent情報取得
$stmt = $pdo->prepare("SELECT id, name, title, profile_img FROM agents WHERE id=:id AND life_flg=0 AND verification_status=2");
$stmt->bindValue(':id', $agent_id, PDO::PARAM_INT);
$stmt->execute();
$agent = $stmt->fetch();
if (!$agent) {
    redirect('search.php');
}

$agent_img = !empty($agent['profile_img'])
    ? (strpos($agent['profile_img'], 'http') === 0 ? $agent['profile_img'] : 'uploads/' . $agent['profile_img'])
    : 'https://placehold.co/60x60/e0e0e0/888?text=No+Img';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>相談フォーム - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .consult-wrap {
            max-width: 680px;
            margin: 48px auto;
            padding: 0 16px 64px;
        }

        /* ── ステップバー ── */
        .step-flow {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 40px;
        }
        .step-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .step-item + .step-item::before {
            content: '';
            position: absolute;
            top: 18px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }
        .step-item.done + .step-item::before,
        .step-item.active + .step-item::before {
            background: #004e92;
        }
        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #eee;
            color: #aaa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            border: 2px solid #ddd;
            transition: all 0.2s;
        }
        .step-item.done .step-circle {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }
        .step-item.active .step-circle {
            background: #004e92;
            color: #fff;
            border-color: #004e92;
            box-shadow: 0 0 0 4px rgba(0,78,146,0.15);
        }
        .step-label {
            margin-top: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #aaa;
            text-align: center;
            line-height: 1.4;
        }
        .step-item.active .step-label {
            color: #004e92;
        }
        .step-item.done .step-label {
            color: #28a745;
        }

        /* ── エージェントプレビュー ── */
        .agent-preview {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f8f9ff;
            border: 1px solid #dce4f5;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 28px;
        }
        .agent-preview img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .agent-preview-info .agent-name {
            font-weight: 700;
            font-size: 1rem;
            color: #222;
        }
        .agent-preview-info .agent-title {
            font-size: 0.82rem;
            color: #666;
            margin-top: 2px;
        }

        /* ── フォームカード ── */
        .form-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 36px 40px;
        }
        .form-card h2 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .form-card .form-subtitle {
            font-size: 0.88rem;
            color: #888;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        /* ── フォーム部品 ── */
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 7px;
        }
        .form-group label .badge-required {
            display: inline-block;
            background: #dc3545;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .form-group label .badge-optional {
            display: inline-block;
            background: #6c757d;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 0.92rem;
            color: #333;
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }
        .select-wrap {
            position: relative;
        }
        .select-wrap::after {
            content: '▾';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
            font-size: 0.9rem;
        }
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #004e92;
            box-shadow: 0 0 0 3px rgba(0,78,146,0.12);
            background: #fff;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.7;
        }

        /* ── ラジオボタン ── */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: #fafafa;
        }
        .radio-option:hover {
            border-color: #004e92;
            background: #f0f4ff;
        }
        .radio-option input[type="radio"] {
            accent-color: #004e92;
            width: 17px;
            height: 17px;
            flex-shrink: 0;
        }
        .radio-option input[type="radio"]:checked + span {
            font-weight: 700;
            color: #004e92;
        }
        .radio-option:has(input:checked) {
            border-color: #004e92;
            background: #f0f4ff;
        }
        .radio-option span {
            font-size: 0.9rem;
            color: #444;
        }

        /* ── 送信ボタン ── */
        .btn-submit-consult {
            display: block;
            width: 100%;
            padding: 15px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }
        .btn-submit-consult:hover {
            background: #003a70;
            transform: translateY(-1px);
        }
        .btn-back-link {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: 0.84rem;
            color: #888;
            text-decoration: none;
        }
        .btn-back-link:hover { color: #004e92; }

        @media (max-width: 600px) {
            .form-card { padding: 24px 20px; }
            .step-label { font-size: 0.65rem; }
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="consult-wrap">

        <!-- ステップフロー -->
        <div class="step-flow">
            <div class="step-item active">
                <div class="step-circle">1</div>
                <div class="step-label">フォーム<br>送信</div>
            </div>
            <div class="step-item">
                <div class="step-circle">2</div>
                <div class="step-label">チャットで<br>日程調整</div>
            </div>
            <div class="step-item">
                <div class="step-circle">3</div>
                <div class="step-label">プロと面談<br>（オンライン/対面）</div>
            </div>
        </div>

        <!-- 相談相手プレビュー -->
        <div class="agent-preview">
            <img src="<?= h($agent_img) ?>" alt="<?= h($agent['name']) ?>">
            <div class="agent-preview-info">
                <div class="agent-name"><?= h($agent['name']) ?> さんへ相談する</div>
                <?php if (!empty($agent['title'])): ?>
                <div class="agent-title"><?= h($agent['title']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- フォームカード -->
        <div class="form-card">
            <h2>ご相談の内容を教えてください</h2>
            <p class="form-subtitle">フォームを送信すると、プロのエージェントとのチャットが始まります。<br>気軽にご相談ください。</p>

            <form action="consult_act.php" method="post">
                <input type="hidden" name="agent_id" value="<?= $agent_id ?>">

                <!-- 相談の目的 -->
                <div class="form-group">
                    <label for="purpose">相談の目的 <span class="badge-required">必須</span></label>
                    <div class="select-wrap">
                        <select name="purpose" id="purpose" required>
                            <option value="">選択してください</option>
                            <option value="新規加入">新規加入</option>
                            <option value="保険の見直し">保険の見直し</option>
                            <option value="セカンドオピニオン">セカンドオピニオン</option>
                            <option value="その他">その他</option>
                        </select>
                    </div>
                </div>

                <!-- きっかけ -->
                <div class="form-group">
                    <label for="trigger">相談のきっかけ <span class="badge-required">必須</span></label>
                    <div class="select-wrap">
                        <select name="trigger" id="trigger" required>
                            <option value="">選択してください</option>
                            <option value="結婚・出産">結婚・出産</option>
                            <option value="住宅購入">住宅購入</option>
                            <option value="就職・転職">就職・転職</option>
                            <option value="更新時期">更新時期</option>
                            <option value="なんとなく">なんとなく</option>
                            <option value="その他">その他</option>
                        </select>
                    </div>
                </div>

                <!-- 面談スタイル -->
                <div class="form-group">
                    <label>希望の面談スタイル <span class="badge-required">必須</span></label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="style" value="オンライン希望" required>
                            <span>💻 オンライン希望</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="style" value="対面希望">
                            <span>🤝 対面希望</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="style" value="まずはメッセージで相談したい">
                            <span>💬 まずはメッセージで相談したい</span>
                        </label>
                    </div>
                </div>

                <!-- 自由記入欄 -->
                <div class="form-group">
                    <label for="note">その他・ご要望 <span class="badge-optional">任意</span></label>
                    <textarea name="note" id="note" placeholder="その他、気になっていることやご要望があればご記入ください"></textarea>
                </div>

                <button type="submit" class="btn-submit-consult">この内容で送信する →</button>
            </form>

            <a href="profile.php?id=<?= $agent_id ?>" class="btn-back-link">← エージェントのプロフィールに戻る</a>
        </div>

    </div>
</body>
</html>
