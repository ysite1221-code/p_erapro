<?php
session_start();
include("function.php");
loginCheck();

// 1. POSTデータ取得
$name       = $_POST["name"];
$title      = $_POST["title"];
$story      = $_POST["story"];
$philosophy = $_POST["philosophy"];
$area       = $_POST["area"];
$tags       = $_POST["tags"];
$id         = $_SESSION["id"];

$img_name = ""; // 画像ファイル名を格納する変数

// 2. 画像アップロード処理
// もしファイルが送信されていて、エラーがない場合
if (isset($_FILES["upfile"]) && $_FILES["upfile"]["error"] == 0) {
    
    // ファイル名を取得 (例: myphoto.jpg)
    $file_name = $_FILES["upfile"]["name"];
    
    // 一時保存場所 (tmpフォルダ)
    $tmp_path  = $_FILES["upfile"]["tmp_name"];
    
    // 拡張子を取得 (jpg, pngなど)
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
    
    // ユニークなファイル名を生成 (日付_乱数.jpg) → 被り防止！
    $file_dir_path = "uploads/" . date("YmdHis") . md5(session_id()) . "." . $extension;
    
    // ファイルを移動 (tmp -> uploadsフォルダ)
    if (move_uploaded_file($tmp_path, $file_dir_path)) {
        chmod($file_dir_path, 0644); // 権限設定
        
        // 保存したファイル名だけをDBに入れるために変数に入れる
        $img_name = date("YmdHis") . md5(session_id()) . "." . $extension;
    }
}

// 3. DB更新SQL作成
$pdo = db_conn();

// 画像がアップロードされた場合と、されていない場合でSQLを分ける
if($img_name != "") {
    // 画像あり更新
    $sql = "UPDATE agents SET name=:name, title=:title, story=:story, philosophy=:philosophy, area=:area, tags=:tags, profile_img=:img WHERE id=:id";
} else {
    // 画像なし更新（profile_imgは更新しない）
    $sql = "UPDATE agents SET name=:name, title=:title, story=:story, philosophy=:philosophy, area=:area, tags=:tags WHERE id=:id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name',       $name,       PDO::PARAM_STR);
$stmt->bindValue(':title',      $title,      PDO::PARAM_STR);
$stmt->bindValue(':story',      $story,      PDO::PARAM_STR);
$stmt->bindValue(':philosophy', $philosophy, PDO::PARAM_STR);
$stmt->bindValue(':area',       $area,       PDO::PARAM_STR);
$stmt->bindValue(':tags',       $tags,       PDO::PARAM_STR);
$stmt->bindValue(':id',         $id,         PDO::PARAM_INT);

// 画像がある場合だけバインド
if($img_name != ""){
    $stmt->bindValue(':img', $img_name, PDO::PARAM_STR);
}

$status = $stmt->execute();

if ($status == false) {
    sql_error($stmt);
} else {
    // 更新完了したらマイページへ戻る
    redirect("mypage.php");
}
?>