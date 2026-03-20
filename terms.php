<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>利用規約 - ERAPRO</title>
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
        <h1>利用規約</h1>
        <p class="updated">最終更新日：2025年1月1日</p>

        <p>本利用規約（以下「本規約」）は、ERAPRO運営事務局（以下「当社」）が提供するサービス「ERAPRO」（以下「本サービス」）の利用条件を定めるものです。ユーザーおよび募集人の皆様には、本規約に従って本サービスをご利用いただきます。</p>

        <h2>第1条（適用）</h2>
        <p>本規約は、本サービスの利用に関わるすべての関係に適用されます。当社が本サービス上に掲載するルールや注意事項等は、本規約の一部を構成するものとします。</p>

        <h2>第2条（利用登録）</h2>
        <p>登録希望者が本規約に同意の上、当社の定める方法によって利用登録を申請し、当社がこれを承認することによって利用登録が完了します。当社は、以下の場合に利用登録を拒否することがあります。</p>
        <ul>
            <li>虚偽の事項を届け出た場合</li>
            <li>本規約に違反したことがある者からの申請である場合</li>
            <li>その他、当社が利用登録を相当でないと判断した場合</li>
        </ul>

        <h2>第3条（禁止事項）</h2>
        <p>ユーザーは、本サービスの利用にあたり、以下の行為をしてはなりません。</p>
        <ul>
            <li>法令または公序良俗に違反する行為</li>
            <li>犯罪行為に関連する行為</li>
            <li>当社または第三者のサーバーやネットワークの機能を妨害する行為</li>
            <li>当社のサービスの運営を妨害する行為</li>
            <li>他のユーザーの個人情報を収集・蓄積する行為</li>
            <li>不正アクセスをし、またはこれを試みる行為</li>
            <li>他のユーザーに成りすます行為</li>
            <li>当社のサービスに関連して、反社会的勢力に対して直接または間接に利益を供与する行為</li>
            <li>その他、当社が不適切と判断する行為</li>
        </ul>

        <h2>第4条（メッセージの閲覧・監視）</h2>
        <p>当社は、トラブル防止および本サービスの安全性確保のため、ユーザーと募集人間で送受信されるメッセージの内容を閲覧・確認し、必要に応じて削除等の措置を行うことができるものとします。ユーザーおよび募集人は、本サービスの利用をもってこれに同意したものとみなします。</p>

        <h2>第5条（サービス内容の変更等）</h2>
        <p>当社は、ユーザーへの事前の通知なく、本サービスの内容を変更、追加または廃止することがあり、ユーザーはこれを承諾するものとします。</p>

        <h2>第6条（免責事項）</h2>
        <p>当社は、本サービスに関して、明示または黙示を問わず、完全性、正確性、確実性、有用性等についていかなる保証も行いません。本サービスに起因してユーザーに生じたあらゆる損害について、当社の故意または重大な過失による場合を除き、当社は一切の責任を負いません。</p>

        <h2>第7条（退会）</h2>
        <p>ユーザーは、当社の定める退会手続きにより、本サービスから退会できます。退会後は登録情報が利用できなくなります。</p>

        <h2>第8条（規約の変更）</h2>
        <p>当社は、必要と判断した場合には、ユーザーへの通知なく、本規約を変更することができるものとします。変更後の本規約は、本サービス上に掲載した時点から効力を生じるものとします。</p>

        <h2>第9条（準拠法・管轄）</h2>
        <p>本規約の解釈にあたっては、日本法を準拠法とします。本サービスに関して紛争が生じた場合には、当社の所在地を管轄する裁判所を専属的合意管轄とします。</p>

        <a href="javascript:window.close();" class="btn-back" onclick="if(!window.opener){history.back();} return false;">閉じる / 戻る</a>
    </div>
</div>

</body>
</html>
