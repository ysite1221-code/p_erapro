<?php
session_start();
include("function.php");

// ---------------------------------------------------
// 再診断リクエスト：HTMLを出力する前にここで処理
// diagnosis_type / diagnosis_score は消さず、
// diag_retry フラグだけ立ててリダイレクト
// ---------------------------------------------------
if (isset($_GET['retry'])) {
    $_SESSION['diag_retry'] = true;
    redirect('diagnosis.php');
}

// ---------------------------------------------------
// POST：診断結果を計算してセッションに保存
// ---------------------------------------------------
$result_type  = null;
$result_data  = null;

$types = [
    'logical' => [
        'label'   => '論理的ストラテジスト',
        'emoji'   => '📊',
        'color'   => '#004e92',
        'desc'    => 'あなたはデータと数字で判断するタイプ。保険選びでも「根拠のある説明」「比較データ」を重視します。感情ではなく論理で納得したい、合理的な決断者です。',
        'ideal'   => 'データや数字を丁寧に示しながら、論理的に説明してくれるプロが最適です。',
        'tags'    => ['論理派', 'データ重視', '損保'],
        'bg'      => 'linear-gradient(135deg, #004e92, #1a73e8)',
    ],
    'balanced_l' => [
        'label'   => '着実なプランナー',
        'emoji'   => '🗂️',
        'color'   => '#2e7d32',
        'desc'    => 'あなたは論理と感情のバランスが取れたタイプ。根拠ある説明を求めながらも、担当者への信頼感も大切にします。計画的に着実に進める実直な判断者です。',
        'ideal'   => '丁寧な説明と親身な対応を両立できるプロが最適です。',
        'tags'    => ['バランス型', '丁寧'],
        'bg'      => 'linear-gradient(135deg, #2e7d32, #43a047)',
    ],
    'balanced_e' => [
        'label'   => '共感重視のパートナー型',
        'emoji'   => '🤝',
        'color'   => '#e65100',
        'desc'    => 'あなたは人との関係を大切にするタイプ。保険を選ぶ際も「この人なら信頼できる」という感覚を重視します。長く付き合えるパートナーを探しています。',
        'ideal'   => '温かみがあり、長期的に寄り添ってくれるプロが最適です。',
        'tags'    => ['寄り添い', '親身'],
        'bg'      => 'linear-gradient(135deg, #e65100, #f57c00)',
    ],
    'emotional' => [
        'label'   => '情熱的なサポーター型',
        'emoji'   => '💛',
        'color'   => '#c62828',
        'desc'    => 'あなたは感情と共感を重視するタイプ。「難しいことはよく分からないけど、いざという時に助けてほしい」という安心感を最も大切にします。',
        'ideal'   => '事故や緊急時に真っ先に駆けつけてくれる、現場力の高いプロが最適です。',
        'tags'    => ['現場力', '安心', '親身'],
        'bg'      => 'linear-gradient(135deg, #c62828, #ef5350)',
    ],
];

// スコアに基づくタイプ判定
function get_type_by_score(int $score): string {
    if ($score >= 16) return 'logical';
    if ($score >= 11) return 'balanced_l';
    if ($score >= 6)  return 'balanced_e';
    return 'emotional';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
    $answers     = $_POST['answers'];
    $total_score = 0;
    foreach ($answers as $a) {
        $total_score += (int)$a;
    }
    $total_score = max(0, min(20, $total_score));
    $type_key    = get_type_by_score($total_score);

    $_SESSION['diagnosis_type']  = $type_key;
    $_SESSION['diagnosis_score'] = $total_score;
    unset($_SESSION['diag_retry']); // 完了したので再診断フラグを解除

    // ログイン中のユーザーならDBにも永続保存（type_idカラム）
    $type_id_map = ['logical' => 1, 'balanced_l' => 2, 'balanced_e' => 3, 'emotional' => 4];
    if (
        isset($_SESSION['id'], $_SESSION['user_type']) &&
        $_SESSION['user_type'] === 'user' &&
        isset($type_id_map[$type_key])
    ) {
        $pdo  = db_conn();
        $stmt = $pdo->prepare("UPDATE users SET type_id=:tid WHERE id=:id AND life_flg=0");
        $stmt->bindValue(':tid', $type_id_map[$type_key], PDO::PARAM_INT);
        $stmt->bindValue(':id',  (int)$_SESSION['id'],    PDO::PARAM_INT);
        $stmt->execute();
    }

    $result_type = $type_key;
    $result_data = $types[$type_key];
}

// セッションから復元（再表示）
// diag_retry フラグが立っている場合はフォームを表示するため復元しない
if (!$result_type && isset($_SESSION['diagnosis_type']) && empty($_SESSION['diag_retry'])) {
    $result_type = $_SESSION['diagnosis_type'];
    $result_data = $types[$result_type];
}

// ---------------------------------------------------
// 質問データ（10問）
// 各選択肢のスコア: 2=論理寄り / 1=中間 / 0=感情寄り
// ---------------------------------------------------
$questions = [
    [
        'q' => '保険を選ぶとき、まず何が気になりますか？',
        'options' => [
            ['text' => '保険料・補償内容の具体的な数字', 'score' => 2],
            ['text' => '担当者の人柄・信頼感',           'score' => 0],
            ['text' => '知人のおすすめや口コミ',         'score' => 1],
        ]
    ],
    [
        'q' => '大きな買い物をする前に、あなたはどうしますか？',
        'options' => [
            ['text' => '複数の商品をスペックで比較する',   'score' => 2],
            ['text' => 'まず店員と話して雰囲気で決める',   'score' => 0],
            ['text' => 'ネットのレビューをひと通り見る',   'score' => 1],
        ]
    ],
    [
        'q' => '担当者に一番求めるものは何ですか？',
        'options' => [
            ['text' => '正確で分かりやすいデータ説明',       'score' => 2],
            ['text' => '事故や緊急時にすぐ来てくれる対応力', 'score' => 0],
            ['text' => '話しやすい雰囲気と親身な姿勢',       'score' => 1],
        ]
    ],
    [
        'q' => '「保険」と聞いて、最初に浮かぶ感情は？',
        'options' => [
            ['text' => '「必要経費として合理的に考えるもの」', 'score' => 2],
            ['text' => '「いざという時の心強い味方」',         'score' => 0],
            ['text' => '「難しくてよく分からない」',           'score' => 1],
        ]
    ],
    [
        'q' => '仕事や勉強で行き詰まったとき、あなたはどうしますか？',
        'options' => [
            ['text' => '原因を分析して論理的に解決策を探す', 'score' => 2],
            ['text' => '信頼できる人に相談して気持ちを楽にする', 'score' => 0],
            ['text' => '少し休んで直感が戻るのを待つ',       'score' => 1],
        ]
    ],
    [
        'q' => '担当者と初めて会ったとき、あなたが大事にするのは？',
        'options' => [
            ['text' => '説明の正確さ・知識量',           'score' => 2],
            ['text' => '表情や話し方の温かさ',           'score' => 0],
            ['text' => '清潔感と礼儀正しさ',             'score' => 1],
        ]
    ],
    [
        'q' => '保険の相談をするとしたら、どんな形が望ましいですか？',
        'options' => [
            ['text' => 'チャットで簡潔にやりとりして効率よく',     'score' => 2],
            ['text' => '対面でじっくり話して関係を築いてから',     'score' => 0],
            ['text' => 'ビデオ通話で手軽にサクッと',               'score' => 1],
        ]
    ],
    [
        'q' => '友人が「いい保険に入ったよ」と言ったとき、あなたはどう反応しますか？',
        'options' => [
            ['text' => '「どんな補償内容？費用対効果は？」と詳しく聞く', 'score' => 2],
            ['text' => '「どんな担当者？信頼できそう？」と聞く',         'score' => 0],
            ['text' => '「へえ、私も探してみようかな」と思う',           'score' => 1],
        ]
    ],
    [
        'q' => '保険に加入した後、最も安心できるのはどれですか？',
        'options' => [
            ['text' => '自分で内容を把握して最適な選択ができたと確信できた', 'score' => 2],
            ['text' => '担当者が「何かあればすぐ連絡ください」と言ってくれた', 'score' => 0],
            ['text' => '周りの人と同じ保険に入れていた',                     'score' => 1],
        ]
    ],
    [
        'q' => '「理想の保険担当者」を一言で表すと？',
        'options' => [
            ['text' => '私の状況を正確に分析してくれる「専門家」', 'score' => 2],
            ['text' => '困った時に真っ先に駆けつけてくれる「頼れる人」', 'score' => 0],
            ['text' => '気軽に何でも相談できる「かかりつけ医」みたいな存在', 'score' => 1],
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>保険タイプ診断 - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ===== 診断ページ固有スタイル ===== */
        .diagnosis-wrap {
            max-width: 700px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }

        /* ヒーロー */
        .diag-hero {
            text-align: center;
            padding: 48px 20px 40px;
            background: linear-gradient(135deg, #004e92, #000428);
            color: #fff;
            border-radius: 16px;
            margin-bottom: 40px;
        }
        .diag-hero .badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border-radius: 30px;
            padding: 6px 18px;
            font-size: 0.8rem;
            margin-bottom: 16px;
            letter-spacing: 1px;
        }
        .diag-hero h1 { font-size: 1.9rem; margin-bottom: 10px; }
        .diag-hero p  { font-size: 1rem; opacity: 0.85; line-height: 1.8; }

        /* プログレスバー */
        .progress-wrap {
            margin-bottom: 32px;
        }
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
        }
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #004e92, #1a73e8);
            border-radius: 4px;
            transition: width 0.4s ease;
        }

        /* 質問カード */
        .question-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            padding: 36px 32px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .question-card.active { display: block; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        .q-number {
            font-size: 0.8rem;
            color: #004e92;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .q-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: #222;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        /* 選択肢 */
        .option-list { list-style: none; padding: 0; margin: 0; }
        .option-list li { margin-bottom: 12px; }
        .option-btn {
            width: 100%;
            padding: 16px 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            color: #333;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .option-btn:hover {
            border-color: #004e92;
            background: #f0f4ff;
            color: #004e92;
        }
        .option-btn .opt-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            color: #666;
            flex-shrink: 0;
        }
        .option-btn:hover .opt-icon {
            background: #004e92;
            color: #fff;
        }

        /* 結果カード */
        .result-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            margin-bottom: 32px;
        }
        .result-header {
            padding: 40px 32px;
            color: #fff;
            text-align: center;
        }
        .result-emoji { font-size: 3.5rem; margin-bottom: 12px; }
        .result-title-sub {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .result-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0;
        }
        .result-body { background: #fff; padding: 32px; }
        .result-body h3 { font-size: 1rem; color: #666; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .result-body p  { line-height: 1.9; color: #333; margin-bottom: 20px; }
        .ideal-box {
            background: #f0f4ff;
            border-left: 4px solid #004e92;
            border-radius: 4px;
            padding: 14px 16px;
            font-size: 0.95rem;
            color: #004e92;
            margin-bottom: 28px;
        }

        /* CTA */
        .cta-btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: #004e92;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .cta-btn:hover { background: #003a70; color: #fff; }
        .retry-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #999;
            font-size: 0.9rem;
        }
        .retry-link:hover { color: #004e92; }
    </style>
</head>
<body>

<?php include("header.php"); ?>

<div class="diagnosis-wrap">

    <?php if (!$result_type): ?>
    <!-- ======== 診断UI ======== -->

    <div class="diag-hero">
        <div class="badge">3分で完了</div>
        <h1>保険タイプ診断</h1>
        <p>10の質問に答えるだけで、<br>あなたに合う保険のプロが見つかります。</p>
    </div>

    <div class="progress-wrap">
        <div class="progress-info">
            <span id="progressLabel">質問 1 / 10</span>
            <span id="progressPct">0%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width:0%"></div>
        </div>
    </div>

    <form method="post" id="diagForm">
        <?php foreach ($questions as $i => $q): ?>
        <div class="question-card <?= ($i === 0) ? 'active' : '' ?>" id="q<?= $i ?>">
            <div class="q-number">Q<?= $i + 1 ?></div>
            <div class="q-text"><?= h($q['q']) ?></div>
            <ul class="option-list">
                <?php foreach ($q['options'] as $j => $opt):
                    $icons = ['A','B','C'];
                ?>
                <li>
                    <button type="button"
                            class="option-btn"
                            data-question="<?= $i ?>"
                            data-score="<?= $opt['score'] ?>">
                        <span class="opt-icon"><?= $icons[$j] ?></span>
                        <?= h($opt['text']) ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <!-- hidden input for this question's answer -->
            <input type="hidden" name="answers[<?= $i ?>]" id="ans<?= $i ?>" value="">
        </div>
        <?php endforeach; ?>

        <!-- 最終送信（最後の質問後に自動submit） -->
    </form>

    <?php else: ?>
    <!-- ======== 診断結果 ======== -->

    <div class="result-card">
        <div class="result-header" style="background: <?= $result_data['bg'] ?>;">
            <div class="result-emoji"><?= $result_data['emoji'] ?></div>
            <div class="result-title-sub">あなたのタイプは</div>
            <div class="result-title"><?= h($result_data['label']) ?></div>
        </div>
        <div class="result-body">
            <h3>あなたの特徴</h3>
            <p><?= h($result_data['desc']) ?></p>

            <div class="ideal-box">
                💡 <strong>あなたに最適なプロ：</strong><br>
                <?= h($result_data['ideal']) ?>
            </div>

            <a href="search.php?type=<?= h($result_type) ?>" class="cta-btn">
                あなたに合うプロを探す →
            </a>
        </div>
    </div>

    <a href="diagnosis.php?retry=1" class="retry-link">
        もう一度診断する
    </a>

    <?php endif; ?>

</div>

<script>
const TOTAL = <?= count($questions) ?>;
let current = 0;
const scores = new Array(TOTAL).fill(null);

// 選択肢クリック
document.querySelectorAll('.option-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const qIdx   = parseInt(this.dataset.question);
        const score  = parseInt(this.dataset.score);

        // スコア記録
        scores[qIdx] = score;
        document.getElementById('ans' + qIdx).value = score;

        // 選択済みハイライト
        document.querySelectorAll('#q' + qIdx + ' .option-btn').forEach(b => {
            b.style.borderColor  = '';
            b.style.background   = '';
            b.querySelector('.opt-icon').style.background = '';
            b.querySelector('.opt-icon').style.color      = '';
        });
        this.style.borderColor = '#004e92';
        this.style.background  = '#f0f4ff';
        this.querySelector('.opt-icon').style.background = '#004e92';
        this.querySelector('.opt-icon').style.color      = '#fff';

        // 少し待ってから次の質問へ
        setTimeout(() => {
            if (qIdx < TOTAL - 1) {
                goToQuestion(qIdx + 1);
            } else {
                // 全問回答 → 送信
                document.getElementById('diagForm').submit();
            }
        }, 350);
    });
});

function goToQuestion(idx) {
    document.querySelectorAll('.question-card').forEach(c => c.classList.remove('active'));
    document.getElementById('q' + idx).classList.add('active');
    current = idx;
    updateProgress(idx);
}

function updateProgress(idx) {
    const pct = Math.round(((idx) / TOTAL) * 100);
    document.getElementById('progressFill').style.width  = pct + '%';
    document.getElementById('progressLabel').textContent = '質問 ' + (idx + 1) + ' / ' + TOTAL;
    document.getElementById('progressPct').textContent   = pct + '%';
}
</script>


</body>
</html>
