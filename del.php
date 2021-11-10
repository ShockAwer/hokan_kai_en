<?php
/* -------------------------------------------------------------------------
del機能
del.php 20170319 by Yakuba
----------------------------------------------------------------------------
■del.phpについて
某ふたばに実装されてるDEL機能をまねたものです。
削除依頼が出されると、管理者へメールが届きます。
鯖によってはPHPからのメール送信機能が制限されている事があります。
その場合はこの機能が使えませんのでご了承下さい。

■余談
このスクリプトは「futaba.php v0.8 lot.041107 Yakuba改 20110108版」以降の
バージョンと連動してますが、実際にはコレ単体で動いていますので、
<?php $dat.="<a href=\"./del.php?res=$no\">del</a>&nbsp;"; ?>
等のようにfutaba.phpに組み込むとこのdel機能と連動出来ます。
但し組み込む場所は「$no」にレス番号が代入されている場所に限ります。
それ以外の場所に組み込むと使えません。

このスクリプトはあくまでも管理者へのメール通知方式です。
累積点数方式による自動削除スクリプトとかも理論上可能ですが、私にはそんなスキルはありません。

■転載・著作について
基本的には他のスクリプトに組み込んでも改造しても構いません。
しかしその場合は改造箇所の表記と、制作者であるYakubaの名前を残してください。
著作権は放棄していません。

■更新履歴
□20110118版    初版
□20110510版    メール送信時にホスト名も送るようにした
□20110517版    細かなバグ修正と制作者表記
□20120602版    PHPの一部バージョンでエラーが発生していた為、修正
□20170319版    UTF-8化しました。
------------------------------------------------------------------------- */

// 全般設定-----------------------------------------------------------------
define("TITLE", '●●● 保管庫');               // 掲示板の名前
define("PHP_SELF", 'hokan.php');                // 掲示板のスクリプト名(本体に合わせる事)
define("PHP_SELF2", 'hokan.htm');               // 入り口ファイル名(本体に合わせる事)
define("MASTERMAIL", 'hoge@hoge.hoge');         // 送信先(管理者)メールアドレス(必ず変更すること！)
define("FROMMAIL", 'hoge@hoge.hoge');           // 送信元メールアドレス(必ず変更すること！)
define("MAILOK", 0);                            // アドレス未変更のまま送信しないための保険。設定が終わったら1にすると送信が出来ます。
// -------------------------------------------------------------------------

if (!isset($dat)){$dat = "";}
if (!isset($no)){$no = "";}
if (!isset($subject)){$subject = "";}
if (!isset($_POST['logno'])){$_POST['logno'] = "";}
if (!isset($_GET["res"])){$_GET["res"]="";} else {$no = $_GET["res"];}
date_default_timezone_set('Asia/Tokyo');        // ◆Yakuba 標準時の設定(dateで必須)

// ヘッダ-------------------------------
$dat.='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja-JP">
<head>
  <meta name="robots" content="noindex,nofollow">
  <meta name="Berry" content="no">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="Content-Style-Type" content="text/css">
  <meta http-equiv="Content-Script-Type" content="text/javascript">
  <title>削除依頼フォーム</title>
  <meta name="description" content="del機能">
  <meta name="keywords" content="ふたば,ちゃんねる,画像,二次元">
  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="cache-control" content="no-cache">
  <!--<meta http-equiv="expires" content="-1">-->
  <!-- <link rel="shortcut icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon"> -->
  <!-- <link rel="icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon"> -->
</head>
<body>
';

// 送信処理-----------------------------
if($_POST['logno']){
//  $IP = getenv("REMOTE_ADDR");
  $IP = $_SERVER["REMOTE_ADDR"];
  $HOST = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
  $datetime = date("Y-n-j H:i:s");

  $from = FROMMAIL;
  $to = MASTERMAIL;
  $subject = "削除依頼 [".TITLE."]";
  $message = TITLE." にて次の削除依頼が出されました。\n\nログNo：".$_POST['logno']."\n理由：".$_POST['reason']."\n申告者IP：$IP\n申告者HOST：$HOST\n申告日時：$datetime\n\n以上です。";

  if(MAILOK){
    mb_language("Japanese");
    mb_internal_encoding ("SJIS");
    mb_send_mail($to, $subject, $message, "From: ".$from);
    $dat .= '
<h1>削除依頼のフォーム</h1>
<hr>
削除依頼を受け付けました。<br>
但し、必ずしもご希望に添えられる訳ではありませんので<br>
その点はご了承下さい。<br>
<br>
<a href="javascript:void(0);" onclick="history.go(-2);return(false);">戻る</a><br>
(携帯の場合は戻るボタンを押して元の画面に戻ってください)<br>
<hr>
';

  } else {
    $dat .= '
<h1>削除依頼のフォーム</h1>
<hr>
申し訳ありませんが現在こちらから削除依頼ができない状態です。<br>
<br>
お手数ですが準備板かメールフォームより管理人へ<br>
エラー内容(メール不通)と削除を希望するレス番号とその理由をお知らせ下さい。<br>
<br>
<a href="./'.PHP_SELF2.'">戻る</a><br>
<hr>
';
  }

}

// 本文---------------------------------
if(!$no && !$_POST['logno']){
  $dat .= '
<h1>削除依頼のフォーム</h1>
<hr>
申し訳ありませんが必要な情報が取得できませんでした。<br>
再度掲示板よりdelを押してみてください。<br>
<br>
それでもこの表示が出る場合は<br>
お手数ですが準備板かメールフォームより管理人へ<br>
エラー内容(情報未取得)と削除を希望するレス番号とその理由をお知らせ下さい。<br>
<br>
<a href="./'.PHP_SELF2.'">戻る</a><br>
<hr>
';
}

if($no && !$subject){
  $dat .= '
<h1>削除依頼のフォーム</h1>
<hr>
削除を依頼する方は、削除理由を選んで<br>
[削除依頼をする]を押してください<br>
<br>
<a href="javascript:void(0);" onclick="history.go(-1);return(false);">戻る</a>
(携帯の場合は戻るボタンを押して元の画面に戻ってください)<br>
<hr>
<form action="del.php" method="post">
<table border="1"><tr><td>文字・画像<br>
<input type="radio" name="reason" value="101-中傷・侮辱・名誉毀損">中傷・侮辱・名誉毀損<br>
<input type="radio" name="reason" value="102-脅迫・自殺">脅迫・自殺<br>
<input type="radio" name="reason" value="103-個人情報・プライバシー">個人情報・プライバシー<br>
<input type="radio" name="reason" value="104-つきまとい・ストーカー">つきまとい・ストーカー<br>
<input type="radio" name="reason" value="105-連投・負荷増大・無意味な羅列">連投・負荷増大・無意味な羅列<br>
<input type="radio" name="reason" value="106-広告・spam">広告・spam<br>
<input type="radio" name="reason" value="107-売春・援交">売春・援交<br>
<input type="radio" name="reason" value="108-侵害・妨害">侵害・妨害<br>
<input type="radio" name="reason" value="109-板違い">板違い<br>
<input type="radio" name="reason" value="110-荒らし・嫌がらせ・混乱の元">荒らし・嫌がらせ・混乱の元<br>
<input type="radio" name="reason" value="111-政治・宗教・民族">政治・宗教・民族<br>
</td></tr><tr><td>２次画像<br>
<input type="radio" name="reason" value="201-グロ画像(２次)">グロ画像(２次)<br>
</td></tr><tr><td>３次画像<br>
<input type="radio" name="reason" value="301-グロ画像(３次)">グロ画像(３次)<br>
<input type="radio" name="reason" value="302-エロ画像(３次)">エロ画像(３次)<br>
<input type="radio" name="reason" value="303-児童ポルノ画像(３次)">児童ポルノ画像(３次)<br>
<input type="radio" name="reason" value="304-猥褻画像・無修正画像(３次)">猥褻画像・無修正画像(３次)<br>
</td></tr></table>
<input type="hidden" name="logno" value="'.$no.'">
<!--<input type="hidden" name="b" value="30">-->
<input type="submit" value="削除依頼をする">
</form>
これは自分自身の記事を削除するフォームではありません.<br>
自分自身の記事を削除する場合は,記事の左側のチェック欄<input type="checkbox">にチェックを入れて<input type="checkbox" checked="checked"><br>
画面下端の右側にある[削除]ボタンを押して下さい.<br>
<hr>
';
}

// フッタ-------------------------------
$dat .= '
<center><small>- <a href="http://t-jun.kemoren.com" target="_blank">Yakuba</a> -</small></center><br>
</body>
</html>
';

echo $dat;

?>
