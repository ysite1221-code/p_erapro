<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プライバシーポリシー - ERAPRO</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f9f9f9; }
        .terms-wrap {
            max-width: 800px;
            margin: 40px auto 80px;
            padding: 0 20px;
        }
        .terms-box {
            background: #fff;
            border-radius: 12px;
            padding: 48px 56px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .terms-box h1 {
            font-size: 1.6rem;
            color: #004e92;
            margin-bottom: 8px;
        }
        .terms-box .updated {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 32px;
        }
        .terms-box h2 {
            font-size: 1.05rem;
            color: #333;
            margin: 28px 0 10px;
            padding-left: 12px;
            border-left: 4px solid #004e92;
        }
        .terms-box p, .terms-box li {
            font-size: 0.92rem;
            line-height: 1.8;
            color: #555;
        }
        .terms-box ul {
            padding-left: 20px;
            margin: 8px 0;
        }
        .btn-back {
            display: inline-block;
            margin-top: 32px;
            padding: 10px 24px;
            background: #004e92;
            color: #fff;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .btn-back:hover { background: #003366; }
    </style>
</head>
<body>

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('header.php');
?>

<div class="terms-wrap">
    <div class="terms-box">
        <h1>プライバシーポリシー</h1>
        <p class="updated">最終更新日：2025年1月1日</p>

        <p>ERAPRO運営事務局（以下「当社」）は、本サービス「ERAPRO」におけるユーザーの個人情報の取り扱いについて、以下のとおりプライバシーポリシー（以下「本ポリシー」）を定めます。</p>

        <h2>第1条（収集する個人情報）</h2>
        <p>当社は、本サービスの提供にあたり、以下の個人情報を収集します。</p>
        <ul>
            <li>氏名（活動名）</li>
            <li>メールアドレス</li>
            <li>プロフィール情報（写真・自己紹介・活動エリア等）</li>
            <li>サービス利用履歴（メッセージ・お気に入り・閲覧履歴等）</li>
            <li>IPアドレス・アクセスログ</li>
        </ul>

        <h2>第2条（個人情報の利用目的）</h2>
        <p>当社は、収集した個人情報を以下の目的のために利用します。</p>
        <ul>
            <li>本サービスの提供・運営・改善</li>
            <li>ユーザーへの重要なお知らせの送信</li>
            <li>新機能・キャンペーン等のご案内（メール通知）</li>
            <li>不正利用・規約違反への対応</li>
            <li>サービス利用状況の統計・分析（個人を特定しない形式）</li>
        </ul>

        <h2>第3条（個人情報の第三者提供）</h2>
        <p>当社は、以下の場合を除き、ユーザーの個人情報を第三者に提供しません。</p>
        <ul>
            <li>ユーザーの同意がある場合</li>
            <li>法令に基づく場合</li>
            <li>人の生命・身体・財産の保護のために必要な場合</li>
            <li>公衆衛生の向上または児童の健全育成のために必要な場合</li>
        </ul>

        <h2>第4条（個人情報の管理）</h2>
        <p>当社は、個人情報の正確性を保ち、漏えい・滅失・毀損の防止のために適切なセキュリティ対策を実施します。個人情報の取り扱いを委託する場合には、委託先において適切な管理が行われるよう監督します。</p>

        <h2>第5条（Cookie等の利用）</h2>
        <p>本サービスでは、ユーザーの利便性向上のためにセッションCookieを使用しています。ブラウザの設定によりCookieを無効にすることができますが、一部のサービス機能が利用できなくなる場合があります。</p>

        <h2>第6条（個人情報の開示・訂正・削除）</h2>
        <p>ユーザーは、当社が保有する自己の個人情報の開示・訂正・削除を請求することができます。請求は下記お問い合わせ先までご連絡ください。本人確認の上、合理的な期間内に対応いたします。</p>

        <h2>第7条（ポリシーの変更）</h2>
        <p>当社は、法令の変更やサービス内容の変更に応じて、本ポリシーを改訂することがあります。改訂後のポリシーは本サービス上に掲載した時点から効力を生じます。</p>

        <h2>第8条（お問い合わせ）</h2>
        <p>個人情報の取り扱いに関するお問い合わせは、以下の窓口までご連絡ください。</p>
        <p>ERAPRO運営事務局<br>
        メール：info@erapro.jp</p>

        <a href="javascript:window.close();" class="btn-back" onclick="if(!window.opener){history.back();} return false;">閉じる / 戻る</a>
    </div>
</div>

</body>
</html>
