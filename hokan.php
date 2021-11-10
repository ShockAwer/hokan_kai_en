<?php
/* -------------------------------------------------------------------------
画像保管庫
hokan.php Yakuba改 20190527版
----------------------------------------------------------------------------
■元の説明
□画像掲示板
futaba.php v0.8 lot.031015
このスクリプトはレッツPHP!<http://php.s3.to/>のgazou.phpを改造したものです。
配布条件はレッツPHP!に準じます。改造、再配布は自由にどうぞ。
このスクリプトに関する質問はレッツPHP!にしないようにお願いします。
最新版は<http://www.2chan.net/script/>で配布しています。
ご質問は準備板＠ふたば<http://www.2chan.net/junbi/index2.html>までどうぞ。

設置法：
所望のディレクトリのパーミッションを777にします。
srcディレクトリとthumbディレクトリを作り、パーミッションを777にします。<br>
futaba.phpを置き、ブラウザから呼出します(必要なファイルが自動設定されます)。
gif2png<http://www.tuxedo.org/~esr/gif2png/>がある場合は、
gifでもサムネイルを作れます。付属のバイナリはlinux-i386用です。

としあき＠しおから改スクリプト
Ver.1.0.0 2004/04/21 公開開始
futaba.php v0.8 lot.031015からの主な変更点
bmp/swfの明示的な禁止、gif表示方法改造、サムネ画質向上、スレ主強制sage機能、以下省略設定
gif2pngの入手と基本的な設置方法は上記ふたば準備板スレと配布所を参照のこと。
知恵と助言を授けてくれたとしあき達に多大なる感謝と御礼を。
Ver.1.0.1 2004/05/09
設定可能項目に追加
文中自動リンクの可否/時間表示に秒を含めるか/メル欄にsage挿入で常時sage/設定時間後に強制sage
以下省略数指定
Ver.1.0.2 2004/05/19
管理画面にサムネイル差し替え機能を追加/管理画面に強制スレsage化機能を追加
Ver.1.0.3 2004/05/22
複数のサムネイル画像選択機能追加/画像の取得にhtmlを経由機能追加/投稿時にアニGIFのサムネ化選択可能に
/差し替え動作を一部修正/「レス省略」の設定値を定数化
Ver.1.0.3a 2004/05/23
レスに画像添付可能バージョン、開発中ベータ版
Ver.1.0.4 2004/08/01
特定のホストを登録してID(ホスト名を暗号化した文字列)、またはホスト名を表示する機能を追加
Ver.1.0.4a 2004/08/01
レスに画像添付可能バージョンにID、ホスト名表示機能を追加
相変わらずベータ版のまま(いや、ベータ版以前に味見試験版の希ガス･･･)
******************************************！注意！***************************************
・レス可能にするには331行目の//を削除
・img.logのファイル名はデフォルトから変えてご使用ください。
　define(LOGFILE, 'img.log'); // ログファイル名
・設置サーバによってはindex.htmが無いと画像掲示板設置フォルダ内が見えてしまう場合があります。
　空のindex.htmを置くか、スクリプトの入り口ファイル名指定をindex.htmに変更してください。
　define("PHP_SELF2", 'siokara.htm'); // 入り口ファイル名
*****************************************************************************************

■Yakuba改について
このスクリプトは上記を改造したhokan.phpをさらに改造したものです。
ご利用に際しては無保証無サポートですので、それを承知の上でご利用下さい。
Yakubaの改造箇所(ソース内にYakubaと表記しています)につきましてはレッツPHP様・ふたば様・しおから様へは絶対問い合わせないでください。

■更新履歴
□20120206版    ereg系がPHP5.3.0で非推奨となったためpreg系へ置き換え。
                HTML 4.01にちょっと準拠。細かいバグフィクス。
□20120602版    preg系や他の箇所で細かいバグフィクス。
□20121120版    PHP5.4で関数コール時に参照で引数を渡せなくなった事と、htmlspecialcharsの標準動作が変更になった事に対応(ひろ様感謝！)。
□20170319版    UTF-8化。
□20190527版    クッキーの名前欄の不具合修正。
------------------------------------------------------------------------- */

// hage 以下2行は調整用･･･
ignore_user_abort(0);
error_reporting(E_ALL & ~E_NOTICE);
// hage 調整用ここまで･･･

extract($_POST);
extract($_GET);
extract($_COOKIE);
date_default_timezone_set('Asia/Tokyo');        // ◆Yakuba 標準時の設定(dateで必須)
$upfile_name=$_FILES["upfile"]["name"];
$upfile=$_FILES["upfile"]["tmp_name"];

if(!defined('PHP_VERSION_ID')) {                // ◆Yakuba PHP5.2.7未満にPHP_VERSION_IDを設ける
  $v = explode('.',PHP_VERSION);
  define('PHP_VERSION_ID', ($v[0] * 10000 + $v[1] * 100 + $v[2]));
}

// 全般設定---------------------------------------------------------------------
define("LOGFILE", 'img.log');                   // ログファイル名
define("TREEFILE", 'tree.log');                 // ログファイル名
define("IMG_DIR", 'src/');                      // 画像保存ディレクトリ。siokara.phpから見て
define("THUMB_DIR", 'thumb/');                  // サムネイル保存ディレクトリ
define("TITLE", '●●● 保管庫');               // タイトル（<title>とTOP）
define("HOME", '../index.html');                // 「ホーム」へのリンク
define("MAX_KB", 2048);                         // 投稿容量制限 KB（phpの設定により2Mまで
define("MAX_W", 256);                           // 投稿サイズ幅（これ以上はwidthを縮小
define("MAX_H", 256);                           // 投稿サイズ高さ
define("PAGE_DEF", 10);                         // 一ページに表示する記事
define("FOLL_ADD", 1010);                       // 以下省略（一ページに表示する記事×指定頁数＝設定数
define("LOG_MAX", 1010);                        // ログ最大行数
define("ADMIN_PASS", 'admin');                  // 管理者パス
define("RE_COL", '789922');                     // ＞が付いた時の色
define("PHP_SELF", 'hokan.php');                // このスクリプト名
define("PHP_SELF2", 'hokan.htm');               // 入り口ファイル名
define("PHP_EXT", '.htm');                      // 1ページ以降の拡張子
define("RENZOKU", 2);                           // 連続投稿秒数
define("RENZOKU2", 2);                          // 画像連続投稿秒数
define("MAX_RES", 30);                          // 強制sageになるレス数
define("USE_THUMB", 1);                         // サムネイルを作る する:1 しない:0
define("PROXY_CHECK", 0);                       // proxyの書込みを制限する y:1 n:0
define("DISP_ID", 0);                           // IDを表示する 強制:2 する:1 しない:0
define("BR_CHECK", 0);                          // 改行を抑制する行数 しない:0
define("EN_AUTOLINK", 0);                       // URL自動リンクを行う する:1 しない:0
define("EN_SEC", 1);                            // 時間表示に「秒」を含める 含める:1 含めない:0
define("EN_SAGE_START", 0);                     // スレ主強制sage機能を使用する する:1 しない:0
define("MAX_PASSED_HOUR", 0);                   // 強制sageまでの時間 0で強制sageなし
define("ADMIN_SAGE", 1);                        // 管理者強制sage処理 する:1 しない:0
define("NOTICE_SAGE", 0);                       // 強制sageを告知する する:1 しない:0
define("DEF_SUB", '無念');                      // 省略時の題名
define("DEF_NAME",'としあき');                  // 省略時の名前
define("DEF_COM", 'ｷﾀ━━━(ﾟ∀ﾟ)━━━!!');    // 省略時の本文
define("RES_MARK", '…');                       // レスの頭に付ける文字列
define("OMIT_RES", 6);                          // 「レス省略」を表示するレスの数

// レス画像添付機能-------------------------------------------------------------
define("RES_IMG", 0);                           // レスにも画像を添付できるようにする 添付可能:1 添付不可:0

// アニメーションＧＩＦ設定-----------------------------------------------------
// サムネイルを使用しない場合、GIFをそのまま表示するため、 アニメーションGIFが動きます。
define("USE_GIF_THUMB", 1);                     // GIF表示にサムネイルを使用する する:1 しない:0

// ツール避けhtml経由関係-------------------------------------------------------
define("IMG_REFER", 1);                         // ツール避けに画像リンクをhtml経由にする する:1 しない:0
define("IMG_REF_DIR", 'ref/');                  // 経由先html格納ディレクトリ

// サムネイル管理者差換え機能---------------------------------------------------
// 差し替えサムネ(1)[replace_n.jpg]有で差換え有効、無しで無効
define("REPLACE_EXT", '.replaced');             // 差し替えの際、元々のサムネイルファイルのお尻に付ける文字
define("NOTICE_THUMB", 1);                      // サムネ差し替えを告知する する:1 しない:0

// 項目を増やす場合は定数宣言したファイル名、タイトルを$rep_thumb配列に追加します。
// もちろん定数宣言しないで直接配列に追加してもOK
define("R_THUM1", 'replace_n.jpg');             // 差し替えサムネ(1) ファイル名
define("R_TITL1", 'ふつう');                    // 差し替えサムネ(1) タイトル
define("R_THUM2", 'replace_g.jpg');             // 差し替えサムネ(2) ファイル名
define("R_TITL2", 'ぐろ');                      // 差し替えサムネ(2) タイトル
define("R_THUM3", 'replace_l.jpg');             // 差し替えサムネ(3) ファイル名
define("R_TITL3", 'ろり');                      // 差し替えサムネ(3) タイトル
define("R_THUM4", 'replace_3.jpg');             // 差し替えサムネ(4) ファイル名
define("R_TITL4", 'さんじ');                    // 差し替えサムネ(4) タイトル

$rep_thumb = array(R_TITL1=>R_THUM1,R_TITL2=>R_THUM2,R_TITL3=>R_THUM3,R_TITL4=>R_THUM4);
$default_thumb = R_THUM1;                       // デフォルトのサムネファイル名

// hage 追加 2004.8.1
//赤字表示機能追加------------------------------------------------------------
define("HOSTFILE",'host.lst');                  // 晒しホストの記録ファイル
define("IDHOSTFILE",'idhost.lst');              // 晒しIDの記録ファイル
// hage 追加ここまで

//-----------------------------------------------------------------------------
$path = realpath("./").'/'.IMG_DIR;
$badstring = array("dummy_string","dummy_string2","\.ws/","無料動画","無料画像","友達募集");    // 拒絶する文字列
$badfile = array("dummy","dummy2");                     // 拒絶するファイルのmd5
$badip = array("addr.dummy.com","addr2.dummy.com");     // 拒絶するホスト
$addinfo='
<li>注意事項</li>
';

// ▼Yakuba追加
define("NINSYOU", 2);                           // 簡易認証キー(使用しない：0、スレ立＆レスに使用：1、スレ立のみに使用：2)
define("NINSYOU_MAS", '味方');                  // 簡易認証キー(キーワード)
define("NINSYOU_Q", '先輩の○○だよ(漢字2字)'); // 簡易認証キー(質問。レイアウトの都合上、文字は少な目に。)
define("DELON", 1);                             // DEL機能(使用しない：0、使用する：1。同一DIRにdel.phpが必要)
// ▲Yakuba追加

/* ヘッダ */
function head(&$dat){
  $dat.='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja-JP">
<head>
  <meta name="Berry" content="no">
  <meta HTTP-EQUIV="pragma" CONTENT="no-cache">
  <meta HTTP-EQUIV="Content-type" CONTENT="text/html; charset=UTF-8">
  <title>'.TITLE.'</title>
  <!-- <link rel="shortcut icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon"> -->
  <!-- <link rel="icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon"> -->
  <style type="text/css">
    <!--
    body,tr,td,th { font-size:12pt }
    a:hover       { color:#dd0000; }
    span          { font-size:20pt }
    small         { font-size:10pt }
    -->
  </style>
  <script language="JavaScript">
    <!--
    function l(e){var P=getCookie("pwdc"),N=getCookie("namec"),i;with(document){for(i=0;i<forms.length;i++){if(forms[i].pwd)with(forms[i]){pwd.value=P;}if(forms[i].name)with(forms[i]){name.value=N;}}}};onload=l;function getCookie(key, tmp1, tmp2, xx1, xx2, xx3) {tmp1 = " " + document.cookie + ";";xx1 = xx2 = 0;len = tmp1.length;  while (xx1 < len) {xx2 = tmp1.indexOf(";", xx1);tmp2 = tmp1.substring(xx1 + 1, xx2);xx3 = tmp2.indexOf("=");if (tmp2.substring(0, xx3) == key) {return(unescape(tmp2.substring(xx3 + 1, xx2 - xx1 - 1)));}xx1 = xx2 + 1;}return("");}
    //-->
  </script>
</head>
<body bgcolor="#ffffee" text="#800000" link="#0000ee" vlink="#0000ee">
<!-- ◆ここら辺にアナライザを入れるといいかも -->
';
}

/* 投稿フォーム */
function form(&$dat,$resno,$admin=""){
  global $addinfo;
  $maxbyte = MAX_KB * 1024;
  $no=$resno;
  if($resno){
    $msg .= "[<a href=\"".PHP_SELF2."\">板に戻る</a>]\n";
    $msg .= "<table width=\"100%\"><tr><th bgcolor=\"#e04000\">\n";
    $msg .= "<font color=\"#ffffff\">レス送信モード</font>\n";
    $msg .= "</th></tr></table>\n";
  }
  if($admin){
    $hidden = "<input type=\"hidden\" name=\"admin\" value=\"".ADMIN_PASS."\">";
    $msg = "<h4>タグがつかえます</h4>";
  }
  $dat .= $msg.'<center>'.
          '<form action="'.PHP_SELF.'" method="POST" enctype="multipart/form-data">'.
          '<input type="hidden" name="mode" value="regist">'.$hidden.
          '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxbyte.'">';
  if($no){ $dat .= '<input type="hidden" name="resto" value="'.$no.'">'; }
  $dat .= '<table cellpadding="1" cellspacing="1">'.
          '<tr><td bgcolor="#eeaa88"><b>コメント<br>検索タグ</b></td>'.
          '<td><textarea name="com" cols="48" rows="3" wrap="soft"></textarea></td></tr>';
  if(!$resno || RES_IMG){
    // hage 変更 2004.8.1
    // 投稿時にGIF停止できるようにラベルを追加(USE_GIF_THUMBによる判別追加)
    $dat .= '<tr><td bgcolor="#eeaa88"><b>添付File</b></td>'.
            '<td><input type="file" name="upfile" size="35"> <input type="submit" value="送信する">';
    if(!USE_GIF_THUMB){
      $dat .= '[<label><input type="checkbox" name="noanime" value="on" checked="checked">GIFアニメ停止</label>]';
    }
//    $dat .= '[<label><input type="checkbox" name="textonly" value="on">画像なし</label>]</td></tr>';
    // hage 変更ここまで
  }
  $dat .= '<tr><td bgcolor="#eeaa88"><b>削除キー</b></td>'.
          '<td><input type="password" name="pwd" size="8" maxlength="8" value="">'.
          '<small>(記事の削除用。英数字で8文字以内) 　[<a href="hokan.php?mode=admin">管理用</a>]</small></td></tr>';

  // ▼Yakuba追加
  if (NINSYOU==1){
    $dat .= '<tr><td bgcolor="#eeaa88"><b>認証キー</b></td>'.
            '<td><input type="text" name="ninsyou_key" size="8">'.
            '<small>';
    $dat .= ' <font color="#ff0099">必須</font> ';
    $dat .= '('.NINSYOU_Q.')</small></td></tr>';
  }
  if (NINSYOU==2 && !$resno){
    $dat .= '<tr><td bgcolor="#eeaa88"><b>認証キー</b></td>'.
            '<td><input type="text" name="ninsyou_key" size="8">'.
            '<small>';
    $dat .= ' <font color="#ff0099">必須</font> ';
    $dat .= '('.NINSYOU_Q.')</small></td></tr>';
  }
  // ▲Yakuba追加

  $dat .= '<tr><td colspan="2"><small>'.
          '<li>';

  // hage 変更 2004.8.1
  if(RES_IMG){
    $dat .= 'レスに画像添付可。';
  }
  $dat .= '添付可能ファイル：GIF,JPG,PNG。'.MAX_KB.'KBまで。'.MAX_W.'x'.MAX_H.'以上は縮小されます。</li>'.
          '';
  if(!USE_GIF_THUMB){
    $dat .= '<li>GIFは動きます。動かしたい時は投稿時に[GIFアニメ停止]のチェックを解除。</li>';
  }
  // hage 変更ここまで
  $dat .= ''.
          $addinfo.'</small></td></tr></table>
<!-- ◆ここら辺に広告等を組み込めます -->
</form></center><hr>';
}

/* 記事部分 */
function updatelog($resno=0){
  global $path;

  // hage 追加 2004.8.1
  $hostdat = array('dummy');
  if(is_file(HOSTFILE)){
    $hostdat = file(HOSTFILE);
    $counthost = count($hostdat);
    for($i=0;$i<$counthost;++$i){ $hostdat[$i] = trim($hostdat[$i],"\n"); }
  }
  $idhostdat = array('dummy');
  if(is_file(IDHOSTFILE)){
    $idhostdat = file(IDHOSTFILE);
    $counthost = count($idhostdat);
    for($i=0;$i<$counthost;++$i){ $idhostdat[$i] = trim($idhostdat[$i],"\n"); }
  }
  // hage 追加ここまで

  $tree = file(TREEFILE);
  $find = false;
  if($resno){
    $counttree=count($tree);
    for($i = 0;$i<$counttree;$i++){
      list($artno,)=explode(",",rtrim($tree[$i]));
      if($artno==$resno){$st=$i;$find=true;break;} //レス先検索
    }
    if(!$find) error("該当記事がみつかりません");
  }
  $line = file(LOGFILE);
  $countline=count($line);
  for($i = 0; $i < $countline; $i++){
    list($no,) = explode(",", $line[$i]);
    $lineindex[$no]=$i + 1; //逆変換テーブル作成
  }

  $counttree = count($tree);
  for($page=0;$page<$counttree;$page+=PAGE_DEF){
    $dat='';
    head($dat);
    form($dat,$resno);
    if(!$resno){
      $st = $page;
    }
    $dat.='<form action="'.PHP_SELF.'" method="POST">';

  for($i = $st; $i < $st+PAGE_DEF; $i++){
    if($tree[$i]=="") continue;
    $treeline = explode(",", rtrim($tree[$i]));
    $disptree = $treeline[0];
    $j=$lineindex[$disptree] - 1; //該当記事を探して$jにセット
    if($line[$j]=="") continue;   //$jが範囲外なら次の行
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pwd,$ext,$w,$h,$time,$chk) = explode(",", $line[$j]);
    // URLとメールにリンク
    if($email) $name = "<a href=\"mailto:$email\">$name</a>";
    $com = auto_link($com);
    $com = preg_replace("#(^|>)(&gt;[^<]*)#i", "\\1<font color=".RE_COL.">\\2</font>", $com);
    // 画像ファイル名
    $img = $path.$time.$ext;
    $src = IMG_DIR.$time.$ext;

    // 経由先htmlファイル作成
    if (IMG_REFER && is_file($img) && !is_file(IMG_REF_DIR.$time.".htm")){
      $fp=fopen(IMG_REF_DIR.$time.".htm","w");
      flock($fp, 2);
      rewind($fp);
      fputs($fp, "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=../$src\">");
      fclose($fp);
    }

    // <imgタグ作成
    $imgsrc = "";
    $dat_img="";
    if($ext && is_file($img)){
      $size = filesize($img);           // altにサイズ表示
      if($w && $h){                     // サイズがある時
        if(@is_file(THUMB_DIR.$time.'s.jpg') &&
          (USE_GIF_THUMB||$ext!='.gif'||stristr($url,'noanime')||@is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT))){
          $imgsrc = "<small>サムネイルを表示しています.クリックすると元のサイズを表示します.</small><br>";
          if (IMG_REFER) {$imgsrc .= "<a href=\"".IMG_REF_DIR.$time.".htm\" target=_blank>";}
          else{$imgsrc .= "<a href=\"".$src."\" target=_blank>";}
          if ( @is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT)){
            $imgsrc .= "<img src=".THUMB_DIR.$time.'s.jpg'.REPLACE_EXT;
          }
          else{
            $imgsrc .= "<img src=".THUMB_DIR.$time.'s.jpg';
          }
          $imgsrc .= " border=0 align=left width=$w height=$h hspace=20 alt=\"".$size." B\"></a>";
        }else{
          if (IMG_REFER) {$imgsrc = "<a href=\"".IMG_REF_DIR.$time.".htm\" target=_blank>";}
          else{$imgsrc = "<a href=\"".$src."\" target=_blank>";}
          $imgsrc .= "<img src=".$src." border=0 align=left width=$w height=$h hspace=20 alt=\"".$size." B\"></a>";
        }
      }else{                            // それ以外
        if (IMG_REFER) {$imgsrc = "<a href=\"".IMG_REF_DIR.$time.".htm\" target=_blank>";}
        else{$imgsrc = "<a href=\"".$src."\" target=_blank>";}
        $imgsrc .= "<img src=".$src." border=0 align=left hspace=20 alt=\"".$size." B\"></a>";
      }
      if (IMG_REFER) {
        // スレもテーブル型にするために画像関係タグを別変数に
        $dat_img="画像タイトル：<a href=\"".IMG_REF_DIR.$time.".htm\" target=_blank>$time$ext</a>-($size B)<br>$imgsrc";      }else{
        $dat_img="画像タイトル：<a href=\"$src\" target=_blank>$time$ext</a>-($size B)<br>$imgsrc";
      }
    }

    // メイン作成
    $dat .= $dat_img;                   // 画像関係文字列をここに移動
    $dat .= "<input type=\"checkbox\" name=\"$no\" value=\"delete\"><font color=\"#cc1105\" size=\"+1\"><b>$sub</b></font> \n";
    // hage 追加 2004.8.1
    // $dat .= " <font color=#117743><b>$name</b></font> $now No.$no &nbsp; \n";
    $dat .= " <font color=#117743><b>$name</b></font> $now";
    if(!DISP_ID && in_array($host,$idhostdat) && !stristr($now,"ID:")){
      $idtemp = " ID:".substr(crypt(md5($host),'id'),-8);
      $dat .= $idtemp;
    }
    $dat .= " No.$no \n";
    if(DELON){ $dat.="<a href=\"./del.php?res=$no\">del</a>&nbsp;"; }           // ◆Yakuba追加
    // hage 追加ここまで
//    if(!$resno){ $dat.="[<a href=".PHP_SELF."?res=$no>返信</a>]<br>"; }
    // hage 追加 2004.8.1
    // $dat.="\n<blockquote>$com</blockquote>";
    if(in_array($host,$hostdat)){
      $dat .= "\n<blockquote>[<font color=\"#ff0000\">$host</font>]<br>$com</blockquote>";
    }else{
      $dat .= "\n<blockquote>$com</blockquote>";
    }
    // hage 追加ここまで

    // そろそろ消える。
    if($lineindex[$no]-1 >= LOG_MAX*0.95){
     $dat.="<font color=\"#f00000\"><b>このスレは古いので、もうすぐ消えます。</b></font><br>\n";
    }
    // 管理者サムネ差し替え告知
    if(NOTICE_THUMB && @is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT)){
      $dat.="<font color=\"#f00000\"><small><b>".
            "管理者によりサムネイルが差し替えられています。理由はお察しください。<br>".
            "サムネイルをクリックすると元の画像を表示します。".
            "</b></small></font><br>\n";
    }
    // 管理者sage告知
    if(NOTICE_SAGE && stristr($url,'sage')){
      $dat.="<font color=\"#f00000\"><small><b>".
            "管理者によりsage指定されています。理由はお察しください。".
            "</b></small></font><br>\n";
    }
    //レス作成
    if(!$resno){
     $s=count($treeline) - (OMIT_RES-1);
     if($s<1){$s=1;}
     elseif($s>1){
      $dat.="<font color=\"#707070\">レス".
             ($s - 1)."件省略。全て読むには返信ボタンを押してください。</font><br>\n";
     }
    }else{$s=1;}
    for($k = $s; $k < count($treeline); $k++){
      $disptree = $treeline[$k];
      $j=$lineindex[$disptree] - 1;
      if($line[$j]=="") continue;
      list($no,$now,$name,$email,$sub,$com,$url,
           $host,$pwd,$ext,$w,$h,$time,$chk) = explode(",", $line[$j]);
      // URLとメールにリンク
      if($email) $name = "<a href=\"mailto:$email\">$name</a>";
      $com = auto_link($com);
      $com = preg_replace("#(^|>)(&gt;[^<]*)#i", "\\1<font color=".RE_COL.">\\2</font>", $com);

      // 画像ファイル名
      $img = $path.$time.$ext;
      $src = IMG_DIR.$time.$ext;
      // 経由先htmlファイル作成
      if (IMG_REFER && is_file($img) && !is_file(IMG_REF_DIR.$time.".htm")){
        $fp=fopen(IMG_REF_DIR.$time.".htm","w");
        flock($fp, 2);
        rewind($fp);
        fputs($fp, "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=../$src\">");
        fclose($fp);
      }
      // <imgタグ作成
      $imgsrc = "";
      $dat_img="";
      if($ext && is_file($img)){
        $size = filesize($img);         // altにサイズ表示
        if($w && $h){                   // サイズがある時
          // スレ主アニメーション停止指示追加
          if(@is_file(THUMB_DIR.$time.'s.jpg') &&
            (USE_GIF_THUMB||$ext!='.gif'||stristr($url,'noanime')||@is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT))){
            // ツール避けhtml参照を追加
            $imgsrc = "<small>サムネイルを表示しています.クリックすると元のサイズを表示します.</small><br>";
            if (IMG_REFER) {$imgsrc .= "<a href=\"".IMG_REF_DIR.$time.".htm\" target=_blank>";}
            else{$imgsrc .= "<a href=\"".$src."\" target=_blank>";}
            if ( @is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT)){
              $imgsrc .= "<img src=".THUMB_DIR.$time.'s.jpg'.REPLACE_EXT;
            }
            else{
              $imgsrc .= "<img src=".THUMB_DIR.$time.'s.jpg';
            }
            $imgsrc .= " border=\"0\" align=\"left\" width=\"$w\" height=\"$h\" hspace=\"20\" alt=\"".$size." B\"></a>";
          }else{
            if (IMG_REFER) {$imgsrc = "<a href=\"".IMG_REF_DIR.$time.".htm\" target=\"_blank\">";}
            else{$imgsrc = "<a href=\"".$src."\" target=_blank>";}
            $imgsrc .= "<img src=".$src." border=\"0\" align=\"left\" width=\"$w\" height=\"$h\" hspace=\"20\" alt=\"".$size." B\"></a>";
          }
        }else{                          //それ以外
          if (IMG_REFER) {$imgsrc = "<a href=\"".IMG_REF_DIR.$time.".htm\" target=\"_blank\">";}
          else{$imgsrc = "<a href=\"".$src."\" target=\"_blank\">";}
          $imgsrc .= "<img src=".$src." border=\"0\" align=\"left\" hspace=\"20\" alt=\"".$size." B\"></a>";
        }
        if (IMG_REFER) {
          $dat_img = "<br>画像タイトル：<a href=\"".IMG_REF_DIR.$time.
                     ".htm\" target=_blank>$time$ext</a>-($size B)<br>$imgsrc";
        }
        else{
          $dat_img="<br>画像タイトル：<a href=\"$src\" target=_blank>$time$ext</a>-($size B)<br>$imgsrc";
        }
      }

      // 別変数に入れた画像用タグ文字列をテーブルの中に配置
      // メイン作成
      $dat.="<table border=\"0\"><tr><td nowrap align=\"right\" valign=\"top\">".RES_MARK."</td><td bgcolor=\"#f0e0d6\">\n";
      $dat.="<input type=\"checkbox\" name=\"$no\" value=\"delete\"><font color=\"#cc1105\" size=\"+1\"><b>$sub</b></font> \n";
      // hage 追加 2004.8.1
      // $dat.=" <font color=#117743><b><b>$name</b></b></font> $now No.$no &nbsp;";
      $dat .= " <font color=\"#117743\"><b>$name</b></font> $now";
      if(!DISP_ID && in_array($host,$idhostdat) && !stristr($now,"ID:")){
        $idtemp = " ID:".substr(crypt(md5($host),'id'),-8);
        $dat .= $idtemp;
      }
      $dat .= " No.$no \n";
      if(DELON){ $dat.="<a href=\"./del.php?res=$no\">del</a>&nbsp;"; }         // ◆Yakuba追加
      // $dat.="$dat_img<blockquote>$com</blockquote>";
      $dat .= "$dat_img<blockquote>";
      if(in_array($host,$hostdat)){ $dat .= "[<font color=\"#ff0000\">$host</font>]<br>"; }
      $dat .= "$com</blockquote>";
      // hage 追加ここまで
      $dat.="</td></tr></table>\n";
    }
    $dat.="<br clear=\"left\"><hr>\n";
    clearstatcache();//ファイルのstatをクリア
    $p++;
    if($resno){break;} //res時はtree1行だけ
  }

  $dat .= '<table align="right"><tr><td nowrap align="center">'.
          '<input type="hidden" name="mode" value="usrdel">【記事削除】'.
          '[<input type="checkbox" name="onlyimgdel" value="on">画像だけ消す]<br>'.
          '削除キー<input type="password" name="pwd" size="8" maxlength="8" value="">'.
          '<input type="submit" value="削除"></form></td></tr></table>';

    if(!$resno){ //res時は表示しない
      $prev = $st - PAGE_DEF;
      $next = $st + PAGE_DEF;
    // 改ページ処理
      $dat.="<table align=\"left\" border=\"1\"><tr>";
      if($prev >= 0){
        if($prev==0){
          $dat.="<form action=\"".PHP_SELF2."\" method=\"get\"><td>";
        }else{
          $dat.="<form action=\"".$prev/PAGE_DEF.PHP_EXT."\" method=\"get\"><td>";
        }
        $dat.="<input type=\"submit\" value=\"前のページ\">";
        $dat.="</td></form>";
      }else{$dat.="<td>最初のページ</td>";}

      $dat.="<td>";
      for($i = 0; $i < count($tree) ; $i+=PAGE_DEF){
        if($i>=FOLL_ADD){$dat.="[以下省略]";break;}
        if($st==$i){$dat.="[<b>".($i/PAGE_DEF)."</b>] ";}
        else{
          if($i==0){$dat.="[<a href=\"".PHP_SELF2."\">0</a>] ";}
          else{$dat.="[<a href=\"".($i/PAGE_DEF).PHP_EXT."\">".($i/PAGE_DEF)."</a>] ";}
        }
      }
      $dat.="</td>";

      if($p >= PAGE_DEF && count($tree) > $next && $next < FOLL_ADD ){
        $dat.="<form action=\"".$next/PAGE_DEF.PHP_EXT."\" method=get><td>";
        $dat.="<input type=\"submit\" value=\"次のページ\">";
        $dat.="</td></form>";
      }else{$dat.="<td>最後のページ</td>";}
        $dat.="</tr></table><br clear=\"all\">\n";
    }
    foot($dat);
    if($resno){echo $dat;break;}
    if($page==0){$logfilename=PHP_SELF2;}
        else{$logfilename=$page/PAGE_DEF.PHP_EXT;}
    // hage 追加 2004.8.1
    ignore_user_abort(1);
    // hage 追加ここまで
    $fp = fopen($logfilename, "w");
    flock($fp,2);
    set_file_buffer($fp, 0);
    rewind($fp);
    fputs($fp, $dat);
    fclose($fp);
    chmod($logfilename,0666);
    // hage 追加 2004.8.1
    ignore_user_abort(0);
    // hage 追加ここまで
    if($page>=FOLL_ADD){ break; }
  }
  if(!$resno&&is_file(($page/PAGE_DEF+1).PHP_EXT)){unlink(($page/PAGE_DEF+1).PHP_EXT);}
}

/* フッタ */
function foot(&$dat){
  $dat.='
<center>
<small><!-- GazouBBS v3.0 --><!-- ふたば改0.8 --><!-- しおから改1.0.4 -->
- <a href="http://php.s3.to" target="_top">GazouBBS</a> + <a href="http://www.2chan.net/" target=_top>futaba</a> + <a href="http://siokara.que.jp/" target="_top">siokara</a> + <a href="http://t-jun.kemoren.com/" target="_top">yakuba</a> -
</small>
</center>
</body></html>';
}

/* オートリンク */
function auto_link($proto){
  if(EN_AUTOLINK){
  $proto = preg_replace("#(https?|ftp|news)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)#","<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>",$proto);
  }
  return $proto;
}

/* エラー画面 */
function error($mes,$dest=''){
  global $upfile_name,$path;
  if(is_file($dest)) unlink($dest);
  head($dat);
  echo $dat;
  echo "<br><br><hr size=\"1\"><br><br>
        <center><font color=\"red\" size=\"5\"><b>$mes<br><br><a href=".PHP_SELF2.">リロード</a></b></font></center>
        <br><br><hr size=\"1\">";
  die("</body></html>");
}

function  proxy_connect($port) {
  $fp = fsockopen ($_SERVER["REMOTE_ADDR"], $port,$a,$b,2);
  if(!$fp){return 0;}else{return 1;}
}

/* 記事書き込み */
function regist($name,$email,$sub,$com,$url,$pwd,$upfile,$upfile_name,$resto){
  global $path,$badstring,$badfile,$badip,$pwdc,$textonly;
  global $noanime;

  // 時間
  $time = time();
  $tim = $time.substr(microtime(),2,3);

  // ▼Yakuba追加
  if(NINSYOU==1){
    $ninsyou_key2 = ($_POST['ninsyou_key']);
    if (!(NINSYOU_MAS==$ninsyou_key2)) {
      error("認証キーが違います<br>再度確認してください"); 
    }
  }
  if(NINSYOU==2 && !$resto){
    $ninsyou_key2 = ($_POST['ninsyou_key']);
    if (!(NINSYOU_MAS==$ninsyou_key2)) {
      error("認証キーが違います<br>再度確認してください"); 
    }
  }
  // ▲Yakuba追加

  // アップロード処理
  if($upfile&&file_exists($upfile)){
    $dest = $path.$tim.'.tmp';
    move_uploaded_file($upfile, $dest);
    //↑でエラーなら↓に変更
    //copy($upfile, $dest);
    $upfile_name = CleanStr($upfile_name);
    if(!is_file($dest)) error("アップロードに失敗しました<br>サーバがサポートしていない可能性があります",$dest);
    $size = getimagesize($dest);
    if(!is_array($size)) error("アップロードに失敗しました<br>画像ファイル以外は受け付けません",$dest);
    $chk = md5_of_file($dest);
    foreach($badfile as $value){if(preg_match("#^$value#",$chk)){
      error("アップロードに失敗しました<br>同じ画像がありました",$dest); //拒絶画像
    }}
    chmod($dest,0666);
    $W = $size[0];
    $H = $size[1];

    switch ($size[2]) {
      case 1 : $ext=".gif";break;
      case 2 : $ext=".jpg";break;
      case 3 : $ext=".png";break;
     default : $ext=".xxx";error("アップロードに失敗しました<br>GIF,JPG,PNG以外の画像ファイルは受け付けません",$dest);break;
    }

    // 画像表示縮小
    if($W > MAX_W || $H > MAX_H){
      $W2 = MAX_W / $W;
      $H2 = MAX_H / $H;
      ($W2 < $H2) ? $key = $W2 : $key = $H2;
      $W = ceil($W * $key);
      $H = ceil($H * $key);
    }
    $mes = "画像 $upfile_name のアップロードが成功しました<br><br>";
  }

  foreach($badstring as $value){if(preg_match("#$value#",$com)||preg_match("#$value#",$sub)||preg_match("#$value#",$name)||preg_match("#$value#",$email)){
  error("拒絶されました(str)",$dest);};}
  if($_SERVER["REQUEST_METHOD"] != "POST") error("不正な投稿をしないで下さい(post)",$dest);
  // フォーム内容をチェック
  if(!$name||preg_match("#^[ |　|]*$#",$name)) $name="";
  if(!$com||preg_match("#^[ |　|\t]*$#",$com)) $com="";
  if(!$sub||preg_match("#^[ |　|]*$#",$sub))   $sub=""; 

  if(!$resto&&!$textonly&&!is_file($dest)) error("画像がありません",$dest);
  if(!$com&&!is_file($dest)) error("何か書いて下さい",$dest);

  $name=preg_replace("#管理#","\"管理\"",$name);
  $name=preg_replace("#削除#","\"削除\"",$name);

  if(strlen($com) > 1000) error("本文が長すぎます",$dest);
  if(strlen($name) > 100) error("名前が長すぎます",$dest);
  if(strlen($email) > 100) error("メール欄が長すぎます",$dest);
  if(strlen($sub) > 100) error("題名が長すぎます",$dest);
  if(strlen($resto) > 10) error("レス番号の指定が長すぎます",$dest);
  if(strlen($url) > 100) error("URL欄が長すぎます",$dest);

  //ホスト取得
  $host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

  foreach($badip as $value){ //拒絶host
   if(preg_match("#$value$#i",$host)){
    error("拒絶されました(host)",$dest);
  }}
  if(preg_match("#^mail#i",$host)
    || preg_match("#^ns#i",$host)
    || preg_match("#^dns#i",$host)
    || preg_match("#^ftp#i",$host)
    || preg_match("#^prox#i",$host)
    || preg_match("#^pc#i",$host)
    || preg_match("#^[^\.]\.[^\.]$#i",$host)){
    $pxck = "on";
  }
  if(preg_match("#ne\\.jp$#i",$host)||
    preg_match("#ad\\.jp$#i",$host)||
    preg_match("#bbtec\\.net$#i",$host)||
    preg_match("#aol\\.com$#i",$host)||
    preg_match("#uu\\.net$#i",$host)||
    preg_match("#asahi-net\\.or\\.jp$#i",$host)||
    preg_match("#rim\\.or\\.jp$#i",$host)
    ){$pxck = "off";}
  else{$pxck = "on";}

  if($pxck=="on" && PROXY_CHECK){
    if(proxy_connect('80') == 1){
      error("ＥＲＲＯＲ！　公開ＰＲＯＸＹ規制中！！(80)",$dest);
    } elseif(proxy_connect('8080') == 1){
      error("ＥＲＲＯＲ！　公開ＰＲＯＸＹ規制中！！(8080)",$dest);
    }
  }

  // No.とパスと時間とURLフォーマット
  srand((double)microtime()*1000000);
  if($pwd==""){
    if($pwdc==""){
      $pwd=rand();$pwd=substr($pwd,0,8);
    }else{
      $pwd=$pwdc;
    }
  }

  $c_pass = $pwd;
  $pass = ($pwd) ? substr(md5($pwd),2,8) : "*";
  $youbi = array('日','月','火','水','木','金','土');
  $yd = $youbi[gmdate("w", $time+9*60*60)] ;
  if(EN_SEC){
      $now = gmdate("y/m/d",$time+9*60*60)."(".(string)$yd.")".gmdate("H:i:s",$time+9*60*60);
  }
  else{
    $now = gmdate("y/m/d",$time+9*60*60)."(".(string)$yd.")".gmdate("H:i",$time+9*60*60);
  }

  if(DISP_ID){
    if($email&&DISP_ID==1){
      $now .= " ID:???";
    }else{
      $now.=" ID:".substr(crypt(md5($_SERVER["REMOTE_ADDR"].'idの種'.gmdate("Ymd", $time+9*60*60)),'id'),-8);
    }
  }
  //テキスト整形
  $email= CleanStr($email);  $email=preg_replace("#[\r\n]#","",$email);
  $sub  = CleanStr($sub);    $sub  =preg_replace("#[\r\n]#","",$sub);
  $url  = CleanStr($url);    $url  =preg_replace("#[\r\n]#","",$url);
  $resto= CleanStr($resto);  $resto=preg_replace("#[\r\n]#","",$resto);
  $com  = CleanStr($com);
  // 改行文字の統一。 
  $com = str_replace( "\r\n",  "\n", $com); 
  $com = str_replace( "\r",  "\n", $com);
  // 連続する空行を一行
  $com = preg_replace("#\n((　| )*\n){3,}#","\n",$com);
  if(!BR_CHECK || substr_count($com,"\n")<BR_CHECK){
    $com = nl2br($com);         //改行文字の前に<br>を代入する
  }
  $com = str_replace("\n",  "", $com);  //\nを文字列から消す。

  $name=preg_replace("#◆#","◇",$name);
  $name=preg_replace("#[\r\n]#","",$name);
  $names=$name;
  $name = CleanStr($name);
  if(preg_match("/(#|＃)(.*)/",$names,$regs)){
    $cap = $regs[2];
    $cap=strtr($cap,"&amp;", "&");
    $cap=strtr($cap,"&#44;", ",");
    $name=preg_replace("/(#|＃)(.*)/","",$name);
    $salt=substr($cap."H.",1,2);
    $salt=preg_replace("#[^\.-z]#",".",$salt);
    $salt=strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef"); 
    $name.="</b>◆".substr(crypt($cap,$salt),-10)."<b>";
  }

  // 萌え連さんのスクリプトを参考に、省略時文字列を定数化
  if(!$name) $name=DEF_NAME;
  if(!$com) $com=DEF_COM;
  if(!$sub) $sub=DEF_SUB;

  // スレ主のアニメーション停止指示追加
  if ($ext=='.gif' && $noanime==on){ $url.='noanime';}

  //ログ読み込み
  $fp=fopen(LOGFILE,"r+");
  flock($fp, 2);
  rewind($fp);
  $buf=fread($fp,1000000);
  if($buf==''){error("error load log",$dest);}
  $line = explode("\n",$buf);
  $countline=count($line);
  for($i = 0; $i < $countline; $i++){
    if($line[$i]!=""){
      list($artno,)=explode(",", rtrim($line[$i]));  //逆変換テーブル作成
      $lineindex[$artno]=$i+1;
      $line[$i].="\n";
  }}

  // sage判定(スレsageスタート、時間経過sage、管理者sage)
  $flgsage=FALSE;
  if($resto){
    list(,,,$chkemail,,,$chkurl,,,,,,$ltime,) = explode(",", rtrim($line[$lineindex[$resto]-1]));
    if(strlen($ltime) > 10) { $ltime=substr($ltime,0,-3); }
    if(EN_SAGE_START && stristr($chkemail,'sage')){$flgsage=TRUE;}
    if(MAX_PASSED_HOUR && (($time - $ltime) >= (MAX_PASSED_HOUR*60*60))) { $flgsage=TRUE; }
    if(ADMIN_SAGE && stristr($chkurl,'sage')){$flgsage=TRUE;}
  }

  // 二重投稿チェック
  for($i=0;$i<20;$i++){
   list($lastno,,$lname,,,$lcom,,$lhost,$lpwd,,,,$ltime,) = explode(",", $line[$i]);
   if(strlen($ltime)>10){$ltime=substr($ltime,0,-3);}
   if($host==$lhost||substr(md5($pwd),2,8)==$lpwd||substr(md5($pwdc),2,8)==$lpwd){$pchk=1;}else{$pchk=0;}
   if(RENZOKU && $pchk && $time - $ltime < RENZOKU)
    error("連続投稿はもうしばらく時間を置いてからお願い致します",$dest);
   if(RENZOKU && $pchk && $time - $ltime < RENZOKU2 && $upfile_name)
    error("画像連続投稿はもうしばらく時間を置いてからお願い致します",$dest);
   if(RENZOKU && $pchk && $com == $lcom && !$upfile_name)
    error("連続投稿はもうしばらく時間を置いてからお願い致します",$dest);
  }

  // ログ行数オーバー
  if(count($line) >= LOG_MAX){
    for($d = count($line)-1; $d >= LOG_MAX-1; $d--){
      list($dno,,,,,,,,,$dext,,,$dtime,) = explode(",", $line[$d]);
      if(is_file($path.$dtime.$dext)) unlink($path.$dtime.$dext);
      if(is_file(THUMB_DIR.$dtime.'s.jpg')) unlink(THUMB_DIR.$dtime.'s.jpg');
      if(is_file(THUMB_DIR.$dtime.'s.jpg'.REPLACE_EXT)) unlink(THUMB_DIR.$dtime.'s.jpg'.REPLACE_EXT);
      // 参照先htmlファイルも削除対象に
      if(is_file(IMG_REF_DIR.$dtime.'.htm')) unlink(IMG_REF_DIR.$dtime.'.htm');
      $line[$d] = "";
      treedel($dno);
    }
  }
  // アップロード処理
  if($dest&&file_exists($dest)){
    for($i=0;$i<200;$i++){ //画像重複チェック
     list(,,,,,,,,,$extp,,,$timep,$chkp,) = explode(",", $line[$i]);
     if($chkp==$chk&&file_exists($path.$timep.$extp)){
      error("アップロードに失敗しました<br>同じ画像があります",$dest);
    }}
  }
  list($lastno,) = explode(",", $line[0]);
  $no = $lastno + 1;

  $newline = "$no,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,\n";
  $newline.= implode('', $line);
  ftruncate($fp,0);
  set_file_buffer($fp, 0);
  rewind($fp);
  fputs($fp, $newline);

  //ツリー更新
  $find = false;
  $newline = '';
  $tp=fopen(TREEFILE,"r+");
  set_file_buffer($tp, 0);
  rewind($tp);
  $buf=fread($tp,1000000);
  if($buf==''){error("error tree update",$dest);}
  $line = explode("\n",$buf);
  $countline=count($line);
  for($i = 0; $i < $countline; $i++){
    if($line[$i]!=""){
      $line[$i].="\n";
      $j=explode(",", rtrim($line[$i]));
      if($lineindex[$j[0]]==0){
        $line[$i]='';
  } } }
  if($resto){
    for($i = 0; $i < $countline; $i++){
      $rtno = explode(",", rtrim($line[$i]));
      if($rtno[0]==$resto){
        $find = TRUE;
        $line[$i]=rtrim($line[$i]).','.$no."\n";
        $j=explode(",", rtrim($line[$i]));
        if(count($j) > MAX_RES || ((EN_SAGE_START || MAX_PASSED_HOUR || ADMIN_SAGE) && $flgsage)) { $email = 'sage'; }  // ◆Yakuba改(管理者強制sageが出来ない不具合を修正)
        if(!stristr($email,'sage')){
          $newline=$line[$i];
          $line[$i]='';
        }
        break;
  } } }
  if(!$find){if(!$resto){$newline="$no\n";}else{error("スレッドがありません",$dest);}}
  $newline.=implode('', $line);
  ftruncate($tp,0);
  set_file_buffer($tp, 0);
  rewind($tp);
  fputs($tp, $newline);
  fclose($tp);
  fclose($fp);

  //クッキー保存
  setcookie ("pwdc", $c_pass,time()+7*24*3600);  /* 1週間で期限切れ */
  if(function_exists("mb_internal_encoding")&&function_exists("mb_convert_encoding")
      &&function_exists("mb_substr")){
    if(preg_match("#MSIE|Opera|Firefox|Safari#",$_SERVER["HTTP_USER_AGENT"])){      // ◆Yakuba改 火狐狩りに対応(自信なし)
      $i=0; $c_name='';
      mb_internal_encoding("UTF-8");
      while($j=mb_substr($names,$i,1)){
        $j = mb_convert_encoding($j, "UTF-8");
        $c_name.=$j;
        $i++;
      }
      header("Set-Cookie: namec=$c_name; expires=".gmdate("D, d-M-Y H:i:s",time()+7*24*3600)." GMT",false);
    }else{
      $c_name=$names;
      setcookie ("namec", $c_name,time()+7*24*3600);  /* 1週間で期限切れ */
    }
  }

  if($dest&&file_exists($dest)){
    rename($dest,$path.$tim.$ext);
    if(USE_THUMB){thumb($path,$tim,$ext);}
  }
  updatelog();

  echo "<html><head><META HTTP-EQUIV=\"refresh\" content=\"1;URL=".PHP_SELF2."\"></head>";
  echo "<body>$mes 画面を切り替えます</body></html>";
}

//サムネイル作成
function thumb($path,$tim,$ext){
  if(!function_exists("ImageCreate")||!function_exists("ImageCreateFromJPEG"))return;
  $fname=$path.$tim.$ext;
  $thumb_dir = THUMB_DIR;               // サムネイル保存ディレクトリ
  $width     = MAX_W;                   // 出力画像幅
  $height    = MAX_H;                   // 出力画像高さ
  // 画像の幅と高さとタイプを取得
  $size = GetImageSize($fname);
  switch ($size[2]) {
    case 1 :
      if(function_exists("ImageCreateFromGIF")){
        $im_in = @ImageCreateFromGIF($fname);
        if($im_in){break;}
      }
      if(!function_exists("ImageCreateFromPNG"))return;
      if(stristr(PHP_OS,"WIN")){
        if(!file_exists(realpath("./gif2png.exe")))return;
        @exec(realpath("./gif2png.exe")." -z $fname",$a);}
      else{
        if(!is_executable(realpath("./gif2png")))return
        @exec(realpath("./gif2png")." $fname",$a);}
      if(!file_exists($path.$tim.'.png'))return;
      $im_in = @ImageCreateFromPNG($path.$tim.'.png');
      unlink($path.$tim.'.png');
      if(!$im_in)return;
      break;
    case 2 : $im_in = @ImageCreateFromJPEG($fname);
      if(!$im_in){return;}
       break;
    case 3 :
      if(!function_exists("ImageCreateFromPNG"))return;
      $im_in = @ImageCreateFromPNG($fname);
      if(!$im_in){return;}
      break;
    default : return;
  }
  // リサイズ
  if ($size[0] > $width || $size[1] >$height) {
    $key_w = $width / $size[0];
    $key_h = $height / $size[1];
    ($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
    $out_w = ceil($size[0] * $keys);
    $out_h = ceil($size[1] * $keys);
  } else {
    $out_w = $size[0];
    $out_h = $size[1];
  }
  // 出力画像（サムネイル）のイメージを作成
  if(function_exists("ImageCreateTrueColor")&&get_gd_ver()=="2"){
    $im_out = ImageCreateTrueColor($out_w, $out_h);
  }else{$im_out = ImageCreate($out_w, $out_h);}
  // 元画像を縦横とも コピーします。
  imagecopyresampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  // サムネイル画像を保存
  ImageJPEG($im_out, $thumb_dir.$tim.'s.jpg',60);
  chmod($thumb_dir.$tim.'s.jpg',0666);
  // 作成したイメージを破棄
  ImageDestroy($im_in);
  ImageDestroy($im_out);
}
//gdのバージョンを調べる
function get_gd_ver(){
  if(function_exists("gd_info")){
    $gdver=gd_info();
    $phpinfo=$gdver["GD Version"];
  }else{ //php4.3.0未満用
    ob_start();
    phpinfo(8);
    $phpinfo=ob_get_contents();
    ob_end_clean();
    $phpinfo=strip_tags($phpinfo);
    $phpinfo=stristr($phpinfo,"gd version");
    $phpinfo=stristr($phpinfo,"version");
  }
  $end=strpos($phpinfo,".");
  $phpinfo=substr($phpinfo,0,$end);
  $length = strlen($phpinfo)-1;
  $phpinfo=substr($phpinfo,$length);
  return $phpinfo;
}
//ファイルmd5計算 php4.2.0未満用
function md5_of_file($inFile) {
 if (file_exists($inFile)){
  if(function_exists('md5_file')){
    return md5_file($inFile);
  }else{
    $fd = fopen($inFile, 'r');
    $fileContents = fread($fd, filesize($inFile));
    fclose ($fd);
    return md5($fileContents);
  }
 }else{
  return false;
}}
//ツリー削除
function treedel($delno){
  $fp=fopen(TREEFILE,"r+");
  set_file_buffer($fp, 0);
  flock($fp, 2);
  rewind($fp);
  $buf=fread($fp,1000000);
  if($buf==''){error("error tree del");}
  $line = explode("\n",$buf);
  $countline=count($line);
  if($countline>2){
    for($i = 0; $i < $countline; $i++){if($line[$i]!=""){$line[$i].="\n";};}
    for($i = 0; $i < $countline; $i++){
      $treeline = explode(",", rtrim($line[$i]));
      $counttreeline=count($treeline);
      for($j = 0; $j < $counttreeline; $j++){
        if($treeline[$j] == $delno){
          $treeline[$j]='';
          if($j==0){$line[$i]='';}
          else{$line[$i]=implode(',', $treeline);
            $line[$i]=preg_replace("#,,#",",",$line[$i]);
            $line[$i]=preg_replace("#,$#","",$line[$i]);
            $line[$i].="\n";
          }
          break 2;
    } } }
    ftruncate($fp,0);
    set_file_buffer($fp, 0);
    rewind($fp);
    fputs($fp, implode('', $line));
  }
  fclose($fp);
}
/* テキスト整形 */
function CleanStr($str){
  global $admin;
  $str = trim($str);//先頭と末尾の空白除去
  if (get_magic_quotes_gpc()) { //￥を削除
    $str = stripslashes($str);
  }
  if($admin != ADMIN_PASS){     //管理者はタグ可能
    if(PHP_VERSION_ID >= 50400){$str = htmlspecialchars($str, ENT_COMPAT | ENT_HTML401, 'UTF-8');} else {$str = htmlspecialchars($str);}     //タグっ禁止 ◆Yakuba PHP5.4.0以降対応
    $str = str_replace("&amp;", "&", $str);     //特殊文字
  }
  return str_replace(",", "&#44;", $str);       //カンマを変換
}
/* ユーザー削除 */
function usrdel($no,$pwd){
  global $path,$pwdc,$onlyimgdel;
  $host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
  $delno = array(dummy);
  $delflag = FALSE;
  reset($_POST);
    while ($item = each($_POST)){
     if($item[1]=='delete'){array_push($delno,$item[0]);$delflag=TRUE;}
    }
  if($pwd=="" && $pwdc!="") $pwd=$pwdc;
  $fp=fopen(LOGFILE,"r+");
  set_file_buffer($fp, 0);
  flock($fp, 2);
  rewind($fp);
  $buf=fread($fp,1000000);
  fclose($fp);
  if($buf==''){error("error user del");}
  $line = explode("\n",$buf);
  $countline=count($line);
  for($i = 0; $i < $countline; $i++){if($line[$i]!=""){$line[$i].="\n";};}
  $flag = FALSE;
  for($i = 0; $i<count($line); $i++){
    list($dno,,,,,,,$dhost,$pass,$dext,,,$dtim,) = explode(",", $line[$i]);
    if(array_search($dno,$delno) && (substr(md5($pwd),2,8) == $pass || $dhost == $host||ADMIN_PASS==$pwd)){
      $flag = TRUE;
      $line[$i] = "";                   //パスワードがマッチした行は空に
      $delfile = $path.$dtim.$dext;     //削除ファイル
      if(!$onlyimgdel){
        treedel($dno);
      }
      if(is_file($delfile)) unlink($delfile);//削除
      if(is_file(THUMB_DIR.$dtim.'s.jpg')) unlink(THUMB_DIR.$dtim.'s.jpg');//削除
      if(is_file(THUMB_DIR.$dtim.'s.jpg'.REPLACE_EXT)) unlink(THUMB_DIR.$dtim.'s.jpg'.REPLACE_EXT);//削除
      // 参照先htmlファイルも削除対象に
      if(is_file(IMG_REF_DIR.$dtim.'.htm')) unlink(IMG_REF_DIR.$dtim.'.htm');
    }
  }
  if(!$flag) error("該当記事が見つからないかパスワードが間違っています");
}
/* パス認証 */
function valid($pass){
  global $default_thumb;
  if($pass && $pass != ADMIN_PASS) error("パスワードが違います");

  head($dat);
  echo $dat;
  echo "[<a href=\"".PHP_SELF2."\">掲示板に戻る</a>]\n";
  echo "[<a href=\"".PHP_SELF."\">ログを更新する</a>]\n";
  echo "<table width=\"100%\"><tr><th bgcolor=\"#e08000\">\n";
  echo "<font color=\"#ffffff\">管理モード</font>\n";
  echo "</th></tr></table>\n";
  echo "<p><form action=\"".PHP_SELF."\" method=\"POST\">\n";
  // ログインフォーム
  if(!$pass){
    echo "<center><table border=\"0\"><tr><td>";
    echo "<input type=\"radio\" name=\"admin\" value=\"del\" checked>記事削除<BR>";
    echo "<input type=\"radio\" name=\"admin\" value=\"post\">管理人投稿<BR>";
    if (is_file($default_thumb)) echo "<input type=\"radio\" name=\"admin\" value=\"thumb\">サムネイル差し替え<BR>";
    if (ADMIN_SAGE) echo "<input type=\"radio\" name=\"admin\" value=\"sage\">強制sage処理<br>";
    // hage 追加 2004.8.1
    echo "<input type=\"radio\" name=\"admin\" value=\"reghost\">ホスト/ID表示リストに登録<br>";
    echo "<input type=\"radio\" name=\"admin\" value=\"delhost\">ホスト/ID表示リストから削除<br>";
    // hage 追加ここまで
    echo "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
    echo "</td></tr></table>";
    echo "<input type=\"password\" name=\"pass\" size=\"8\">";
    echo "<input type=\"submit\" value=\" 認証 \"></form></center>\n";
    die("</body></html>");
  }
}
/* 管理者削除 */
function admindel($pass){
  global $path,$onlyimgdel;
  $delno = array(dummy);
  $delflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
   if($item[1]=='delete'){array_push($delno,$item[0]);$delflag=TRUE;}
  }
  if($delflag){
    // hage 追加 2004.8.1
    ignore_user_abort(1);
    // hage 追加ここまで
    $fp=fopen(LOGFILE,"r+");
    set_file_buffer($fp, 0);
    flock($fp, 2);
    rewind($fp);
    $buf=fread($fp,1000000);
    if($buf==''){error("error admin del");}
    $line = explode("\n",$buf);
    $countline=count($line);
    for($i = 0; $i < $countline; $i++){if($line[$i]!=""){$line[$i].="\n";};}
    $find = FALSE;
    for($i = 0; $i < $countline; $i++){
      list($no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,$chk) = explode(",",$line[$i]);
      if($onlyimgdel==on){
        if(array_search($no,$delno)){//画像だけ削除
          $delfile = $path.$tim.$ext;   //削除ファイル
          if(is_file($delfile)) unlink($delfile);//削除
          if(is_file(THUMB_DIR.$tim.'s.jpg')) unlink(THUMB_DIR.$tim.'s.jpg');//削除
          if(is_file(THUMB_DIR.$tim.'s.jpg'.REPLACE_EXT)) unlink(THUMB_DIR.$tim.'s.jpg'.REPLACE_EXT);//削除
          // 参照先htmlファイルも削除対象に
          if(is_file(IMG_REF_DIR.$tim.'.htm')) unlink(IMG_REF_DIR.$tim.'.htm');
        }
      }else{
        if(array_search($no,$delno)){//削除の時は空に
          $find = TRUE;
          $line[$i] = "";
          $delfile = $path.$tim.$ext;   //削除ファイル
          if(is_file($delfile)) unlink($delfile);//削除
          if(is_file(THUMB_DIR.$tim.'s.jpg')) unlink(THUMB_DIR.$tim.'s.jpg');//削除
          if(is_file(THUMB_DIR.$tim.'s.jpg'.REPLACE_EXT)) unlink(THUMB_DIR.$tim.'s.jpg'.REPLACE_EXT);//削除
          // 参照先htmlファイルも削除対象に
          if(is_file(IMG_REF_DIR.$tim.'.htm')) unlink(IMG_REF_DIR.$tim.'.htm');
          treedel($no);
        }
      }
    }
    if($find){//ログ更新
      ftruncate($fp,0);
      set_file_buffer($fp, 0);
      rewind($fp);
      fputs($fp, implode('', $line));
    }
    fclose($fp);
    // hage 追加 2004.8.1
    ignore_user_abort(0);
    // hage 追加ここまで
  }
  // 削除画面を表示
  echo "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
  echo "<input type=\"hidden\" name=\"admin\" value=\"del\">\n";
  echo "<input type=\"hidden\" name=\"pass\" value=\"$pass\">\n";
  echo "<center><p>削除したい記事のチェックボックスにチェックを入れ、削除ボタンを押して下さい。</p>\n";
  echo "<p><input type=\"submit\" value=\"削除する\">";
  echo "<input type=\"reset\" value=\"リセット\">";
  echo "[<input type=\"checkbox\" name=\"onlyimgdel\" value=\"on\" checked>画像だけ消す]</p>";
  echo "<p><table border=\"1\" cellspacing=\"0\">\n";
  echo "<tr bgcolor=\"#6080f6\"><th>削除</th><th>記事No</th><th>投稿日</th><th>題名</th>";
  echo "<th>投稿者</th><th>コメント<br>検索タグ</th><th>ホスト名</th><th>添付<br>(Bytes)</th><th>md5</th>";
  echo "</tr>\n";
  $line = file(LOGFILE);

  for($j = 0; $j < count($line); $j++){
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",",$line[$j]);
    // フォーマット
    $now=preg_replace('#.{2}/(.*)$#','\1',$now);
    $now=preg_replace('#\(.*\)#',' ',$now);
    if(strlen($name) > 10) $name = substr($name,0,9).".";
    if(strlen($sub) > 10) $sub = substr($sub,0,9).".";
    if($email) $name="<a href=\"mailto:$email\">$name</a>";
    $com = str_replace("<br>"," ",$com);
    if(PHP_VERSION_ID >= 50400){$com = htmlspecialchars($com, ENT_COMPAT | ENT_HTML401, 'UTF-8');} else {$com = htmlspecialchars($com);}     // ◆Yakuba PHP5.4.0以降対応
    if(strlen($com) > 20) $com = substr($com,0,18) . ".";
    // 画像があるときはリンク
    if($ext && is_file($path.$time.$ext)){
      $img_flag = TRUE;
      $clip = "<a href=\"".IMG_DIR.$time.$ext."\" target=_blank>".$time.$ext."</a><br>";
      $size = filesize($path.$time.$ext);
      $all += $size;                            // 合計計算
      $chk= substr($chk,0,10);
    }else{
      $clip = "";
      $size = 0;
      $chk= "";
    }
    $bg = ($j % 2) ? "d6d6f6" : "f6f6f6";       // 背景色

    echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$no\" value=\"delete\"></th>";
    echo "<th>$no</th><td><small>$now</small></td><td>$sub</td>";
    echo "<td><b>$name</b></td><td><small>$com</small></td>";
    echo "<td>$host</td><td align=center>$clip($size)</td><td>$chk</td>\n";
    echo "</tr>\n";
  }

  echo "</table><p><input type=\"submit\" value=\"削除する$msg\">";
  echo "<input type=\"reset\" value=\"リセット\"></form>";

  $all = (int)($all / 1024);
  echo "【 画像データ合計 : <b>$all</b> KB 】";
  die("</center></body></html>");
}

/* 管理者サムネ差し替え */
// ほとんど管理者削除と一緒･･･統合したいけど･･･
function admin_chgthumb($pass){
  global $path,$stillGIF;
  global $rep_thumb,$default_thumb;
  $thum_name = $default_humb;
  foreach($rep_thumb as $chkthumb){
    if (!is_file($chkthumb)){error("代替サムネイルファイル".$chkthumb."が見つかりません");}
  }

  $chgno = array(dummy);
  $chgflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
   if($item[1]=='chgthumb'){array_push($chgno,$item[0]);$chgflag=TRUE;}
   // 差し替えサムネファイル名取得
   if($item[0]=='thumb'){$thumb_name=$item[1];}
  }
  if($chgflag){
    // hage 追加 2004.8.1
    ignore_user_abort(1);
    // hage 追加ここまで
    $fp=fopen(LOGFILE,"r+");
    set_file_buffer($fp, 0);
    flock($fp, 2);
    rewind($fp);
    $buf=fread($fp,1000000);
    if($buf==''){error("error admin change thumbnail");}
    $line = explode("\n",$buf);
    $countline=count($line);
    for($i = 0; $i < $countline; $i++){if($line[$i]!=""){$line[$i].="\n";};}
    $find = FALSE;
    for($i = 0; $i < $countline; $i++){
      list($no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,$chk) = explode(",",$line[$i]);
      if(array_search($no,$chgno)){
        $find = TRUE;
        // サムネイル差し替え
        $tpath = THUMB_DIR.$tim.'s.jpg';
        $tpathorg = $tpath.REPLACE_EXT;
        if (!is_file($tpathorg)){
          if(!is_file($tpath) && is_file($path.$tim.$ext)) {thumb($path,$tim,$ext);} // サムネがなかったら新規作成
          // サムネ名変更&差し替え仕様変更
          if( is_file($thumb_name) && is_file($tpath)){
            if ((!USE_GIF_THUMB && $ext=='.gif' && $stillGIF=='on')) {copy($tpath,$tpathorg);}
            else {copy($thumb_name,$tpathorg);}
            // サムネサイズを差し替える画像のサイズにする
            $tsize = GetImageSize($tpathorg);
            $w = $tsize[0];
            $h = $tsize[1];
          }
        }
        else{
          unlink($tpathorg);
          $tsize = GetImageSize($tpath);
          $w = $tsize[0];
          $h = $tsize[1];
        }
        $line[$i] = "$no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,$chk,\n";
      }
    }
    if($find){//ログ更新
      ftruncate($fp,0);
      set_file_buffer($fp, 0);
      rewind($fp);
      fputs($fp, implode('', $line));
    }
    fclose($fp);
    updatelog();
    // hage 追加 2004.8.1
    ignore_user_abort(0);
    // hage 追加ここまで
  }

  // 差し替え記事選択画面を表示
  echo "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
  echo "<input type=\"hidden\" name=\"admin\" value=\"thumb\">\n";
  echo "<input type=\"hidden\" name=\"pass\" value=\"$pass\">\n";
  echo "<center><p>サムネイルを差し替えたい記事のチェックボックスにチェックを入れ、差し替えボタンを押して下さい。\n";
  echo "<center>「差替」と「差替解除」が切り替わります。</p>\n";
  echo "<p><input type=\"submit\" value=\"差し替え\">";
  echo "<input type=\"reset\" value=\"リセット\">";
  if(!USE_GIF_THUMB){echo "[<input type=\"checkbox\" name=\"stillGIF\" value=\"on\">GIFをサムネイル化するだけ]";}

  echo "</p><center><BR>";
  $i=0;
  foreach ($rep_thumb as $rtitl => $rname){
    echo "<input type=\"radio\" name=\"thumb\" value=\"$rname\" ";
    if (!$i++) echo "checked";
    echo ">$rtitl";
  }

  echo "<p><table border=\"1\" cellspacing=\"0\"></p>\n";
  echo "<tr bgcolor=\"#6080f6\"><th>選択</th><th>記事No</th><th>状態</th><th>投稿日</th><th>題名</th>";
  echo "<th>投稿者</th><th>コメント<br>検索タグ</th><th>ホスト名</th><th>添付<br>(Bytes)</th>";
  echo "</tr>\n";

  // ログファイル読み出し
  $line = file(LOGFILE);
  $bgcol = 0;
  for($j = 0; $j < count($line); $j++){
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",",$line[$j]);
    if($ext && is_file($path.$time.$ext)){
      // フォーマット
      $now=preg_replace('#.{2}/(.*)$#','\1',$now);
      $now=preg_replace('#\(.*\)#',' ',$now);
      if(strlen($name) > 10) $name = substr($name,0,9).".";
      if(strlen($sub) > 10) $sub = substr($sub,0,9).".";
      if($email) $name="<a href=\"mailto:$email\">$name</a>";
      $com = str_replace("<br>"," ",$com);
      if(PHP_VERSION_ID >= 50400){$com = htmlspecialchars($com, ENT_COMPAT | ENT_HTML401, 'UTF-8');} else {$com = htmlspecialchars($com);}     // ◆Yakuba PHP5.4.0以降対応
      if(strlen($com) > 20) $com = substr($com,0,18) . ".";
      $img_flag = TRUE;
      $clip = "<a href=\"".IMG_DIR.$time.$ext."\" target=_blank>".$time.$ext."</a><br>";
      $size = filesize($path.$time.$ext);
      $all += $size;                       //合計計算
      $chk= substr($chk,0,10);
      $bg = ($bgcol++ % 2) ? "d6d6f6" : "f6f6f6";//背景色

      if (is_file(THUMB_DIR.$time.'s.jpg'.REPLACE_EXT)) {$tstat = "差替";}
      else{
        $tstat = (stristr($url,'noanime')) ? "スレ主" : "　";
      }
      echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$no\" value=\"chgthumb\"></th>";
      echo "<th>$no</th><th>$tstat</th><td><small>$now</small></td><td>$sub</td>";
      echo "<td><b>$name</b></td><td><small>$com</small></td>";
      echo "<td>$host</td><td align=center>$clip($size)</td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table><p><input type=submit value=\"差し替え$msg\">";
  echo "<input type=reset value=\"リセット\"></form>";

  $all = (int)($all / 1024);
  echo "【 画像データ合計 : <b>$all</b> KB 】";
  die("</center></body></html>");
}

/* 管理者sage処理 */
// ここも、ほとんど管理者削除と(ry
function admin_sage($pass){
  global $path;
  $chgno = array('dummy');
  $chgflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
    if($item[1]=='sage'){array_push($chgno,$item[0]);$chgflag=TRUE;}
  }
  if($chgflag){
    // hage 追加 2004.8.1
    ignore_user_abort(1);
    // hage 追加ここまで
    $fp=fopen(LOGFILE,"r+");
    set_file_buffer($fp, 0);
    flock($fp, 2);
    rewind($fp);
    $buf=fread($fp,1000000);
    if($buf==''){error("error admin sage");}
    $line = explode("\n",$buf);
    $countline=count($line);
    for($i = 0; $i < $countline; $i++){if($line[$i]!=""){$line[$i].="\n";};}
    $find = FALSE;
    for($i = 0; $i < $countline; $i++){
      list($no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,$chk) = explode(",",$line[$i]);
      if(array_search($no,$chgno)){
        $find = TRUE;
        // URI枠に'sage'文字切り替え
    $str = str_replace("&amp;", "&", $str);//特殊文字

        if (stristr($url,'sage')) {$url=str_replace('sage','',$url);}
        else { $url .= 'sage'; }
        $line[$i] = "$no,$now,$name,$email,$sub,$com,$url,$host,$pw,$ext,$w,$h,$tim,$chk,\n";
      }
    }
    if($find){//ログ更新
      ftruncate($fp,0);
      set_file_buffer($fp, 0);
      rewind($fp);
      fputs($fp, implode('', $line));
    }
    fclose($fp);
    updatelog();
    // hage 追加 2004.8.1
    ignore_user_abort(0);
    // hage 追加ここまで
  }

  // sage記事選択画面を表示
  echo "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
  echo "<input type=\"hidden\" name=\"admin\" value=\"sage\">\n";
  echo "<input type=\"hidden\" name=\"pass\" value=\"$pass\">\n";
  echo "<center><P>sage状態を変更したい記事のチェックボックスにチェックを入れ、変更ボタンを押して下さい。\n";
  echo "<center>「sage」と「sage解除」が切り替わります。\n";
  echo "<center>「sageスタート」や「レス数sage」による「sage」は解除できません。\n";
  echo "<p><input type=\"submit\" value=\"変更\">";
  echo "<input type=\"reset\" value=\"リセット\">";
  echo "<P><table border=\"1\" cellspacing=\"0\">\n";
  echo "<tr bgcolor=\"#6080f6\"><th>選択</th><th>記事No</th><th>状態</th><th>投稿日</th><th>題名</th>";
  echo "<th>投稿者</th><th>コメント<br>検索タグ<br>検索タグ</th><th>ホスト名</th><th>添付<br>(Bytes)</th>";
  echo "</tr>\n";

  //ツリーファイルからスレ元の記事No.を取得
  $ttree = file(TREEFILE);
  $tno = array(dummy);
  $tfind = false;
  $tcounttree=count($ttree);
  for($i = 0;$i<$tcounttree;$i++){
    list($tartno,)=explode(",",rtrim($ttree[$i]));
    array_push($tno,$tartno);
  }

  //ログファイル読み出し
  $line = file(LOGFILE);
  $bgcol = 0;
  for($j = 0; $j < count($line); $j++){
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",",$line[$j]);
    if(array_search($no,$tno)){
      // フォーマット
      $now=preg_replace('#.{2}/(.*)$#','\1',$now);
      $now=preg_replace('#\(.*\)#',' ',$now);
      if(strlen($name) > 10) $name = substr($name,0,9).".";
      if(strlen($sub) > 10) $sub = substr($sub,0,9).".";
      if($email) $name="<a href=\"mailto:$email\">$name</a>";
      $com = str_replace("<br>"," ",$com);
      if(PHP_VERSION_ID >= 50400){$com = htmlspecialchars($com, ENT_COMPAT | ENT_HTML401, 'UTF-8');} else {$com = htmlspecialchars($com);}     // ◆Yakuba PHP5.4.0以降対応
      if(strlen($com) > 20) $com = substr($com,0,18) . ".";
      $url = (stristr($url,'sage')) ? 'sage' : '　';
      // 画像があるときはリンク
      if($ext && is_file($path.$time.$ext)){
        $img_flag = TRUE;
        $clip = "<a href=\"".IMG_DIR.$time.$ext."\" target=_blank>".$time.$ext."</a><br>";
        $size = filesize($path.$time.$ext);
        $all += $size;                    //合計計算
        $chk= substr($chk,0,10);
      }else{
        $clip = "";
        $size = 0;
        $chk= "";
      }
      $bg = ($bgcol++ % 2) ? "d6d6f6" : "f6f6f6";//背景色

      echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$no\" value=\"sage\"></th>";
      echo "<th>$no</th><th>$url</th><td><small>$now</small></td><td>$sub</td>";
      echo "<td><b>$name</b></td><td><small>$com</small></td>";
      echo "<td>$host</td><td align=center>$clip($size)</td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table><p><input type=submit value=\"変更$msg\">";
  echo "<input type=reset value=\"リセット\"></form>";

  $all = (int)($all / 1024);
  echo "【 画像データ合計 : <b>$all</b> KB 】";
  die("</center></body></html>");
}

// hage 追加 2004.8.1

/* 管理者赤字ホスト登録 */
function regist_host($pass){
  global $path;

  // IP表示オプションのチェック
  $ipflag = (isset($_POST['ipdisp']) && $_POST['ipdisp'] == 'on') ? TRUE : FALSE ;

  // 晒しホストリストファイルの取得
  $hostdat = array('dummy');
  if(is_file(HOSTFILE)){
    $hostdat = file(HOSTFILE);
    $counthost = count($hostdat);
    for($i=0;$i<$counthost;++$i){ $hostdat[$i] = trim($hostdat[$i],"\n"); }
  }
  // 晒しIDリストファイルの取得
  $idhostdat = array('dummy');
  if(is_file(IDHOSTFILE)){
    $idhostdat = file(IDHOSTFILE);
    $counthost = count($idhostdat);
    for($i=0;$i<$counthost;++$i){ $idhostdat[$i] = trim($idhostdat[$i],"\n"); }
  }
  // チェックの付いた記事番号の取得
  $chgno = array('dummy');
  $chgflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
    if($item[1]=='regist'){array_push($chgno,$item[0]);$chgflag=TRUE;}
  }

  // チェックの付いた項目があれば、更新
  $setflag = FALSE;
  $idsetflag = FALSE;
  if($chgflag){
    $logdat = file(LOGFILE);
    foreach($logdat as $line){
      list($no,,,,,,,$host,,,,,,) = explode(",",$line);
      if(in_array($no,$chgno) && $host){
        if($ipflag){
          if(!in_array($host,$hostdat)){
            $setflag = TRUE;
            array_push($hostdat,$host);
          }
        }else{
          if(!in_array($host,$idhostdat)){
            $idsetflag = TRUE;
            array_push($idhostdat,$host);
          }
        }
      }
    }

    if($setflag){
      $fp=fopen(HOSTFILE,"w");
      set_file_buffer($fp, 0);
      flock($fp, 2);
      fputs($fp, implode("\n", $hostdat));
      fclose($fp);
    }
    if($idsetflag){
      $fp=fopen(IDHOSTFILE,"w");
      set_file_buffer($fp, 0);
      flock($fp, 2);
      fputs($fp, implode("\n", $idhostdat));
      fclose($fp);
    }
    if($setflag || $idsetflag){ updatelog(); }
  }

  // 処理記事選択画面を表示
  echo "<input type=\"hidden\" name=\"mode\" value=\"admin\">\n";
  echo "<input type=\"hidden\" name=\"admin\" value=\"reghost\">\n";
  echo "<input type=\"hidden\" name=\"pass\" value=\"$pass\">\n";
  echo "<center><P>対象ホストの記事チェックボックスにチェックを入れ、変更ボタンを押して下さい。<br>\n";
  echo "表示ホストのリストに登録されます。<br>\n";
  echo "[ホスト名を表示させる]にチェックするとホスト名を、しないとIDを表示します。<br>\n";
  echo "<p><input type=\"submit\" value=\"変更\">";
  echo "<input type=\"reset\" value=\"リセット\">";
  echo "<p>[<input type=\"checkbox\" name=\"ipdisp\" value=\"on\">ホスト名を表示させる]";
  echo "<p><table border=\"1\" cellspacing=\"0\">\n";
  echo "<tr bgcolor=\"#6080f6\"><th>選択</th><th>記事No</th><th>状態</th><th>投稿日</th><th>題名</th>";
  echo "<th>投稿者</th><th>コメント<br>検索タグ</th><th>ホスト名</th>";
  echo "</tr>\n";

  //ログファイル読み出し
  $line = file(LOGFILE);
  $bgcol = 0;
  for($j = 0; $j < count($line); $j++){
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",",$line[$j]);
    // フォーマット
    $now=preg_replace('#.{2}/(.*)$#','\1',$now);
    $now=preg_replace('#\(.*\)#',' ',$now);
    if(strlen($name) > 10) $name = substr($name,0,9).".";
    if(strlen($sub) > 10) $sub = substr($sub,0,9).".";
    if($email) $name="<a href=\"mailto:$email\">$name</a>";
    $com = str_replace("<br>"," ",$com);
    if(PHP_VERSION_ID >= 50400){$com = htmlspecialchars($com, ENT_COMPAT | ENT_HTML401, 'UTF-8');} else {$com = htmlspecialchars($com);}     // ◆Yakuba PHP5.4.0以降対応
    if(strlen($com) > 20) $com = substr($com,0,18) . ".";
    $url = '　　　';
    if(in_array($host,$idhostdat)){ $url = 'ID'; }
    if(in_array($host,$hostdat)){ $url = 'ホスト'; }

    $bg = ($bgcol++ % 2) ? "d6d6f6" : "f6f6f6";//背景色

    echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$no\" value=\"regist\"></th>";
    echo "<th>$no</th><th>$url</th><td><small>$now</small></td><td>$sub</td>";
    echo "<td><b>$name</b></td><td><small>$com</small></td><td>$host</td>\n";
    echo "</tr>\n";
  }
  echo "</table><p><input type=submit value=\"変更\">";
  echo "<input type=reset value=\"リセット\"></form>";
  die("</center></body></html>");
}

/* 管理者赤字ホスト削除 */
function delete_host($pass){
  global $path;

  // 晒しホストリストファイルの取得
  $hostdat = array('dummy');
  if(is_file(HOSTFILE)){
    $hostdat = file(HOSTFILE);
    $counthost = count($hostdat);
    for($i=0;$i<$counthost;++$i){ $hostdat[$i] = trim($hostdat[$i],"\n"); }
    $temp = array_shift($hostdat);
  }

  // 晒しIDリストファイルの取得
  $idhostdat = array('dummy');
  if(is_file(IDHOSTFILE)){
    $idhostdat = file(IDHOSTFILE);
    $counthost = count($idhostdat);
    for($i=0;$i<$counthost;++$i){ $idhostdat[$i] = trim($idhostdat[$i],"\n"); }
    $temp = array_shift($idhostdat);
  }

  // チェックの付いた記事番号の取得
  $chgno = array('dummy');
  $chgflag = FALSE;
  $idchgno = array('dummy');
  $idchgflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
    if($item[1]=='delete'){array_push($chgno,$item[0]);$chgflag=TRUE;}
    if($item[1]=='id_delete'){array_push($idchgno,$item[0]);$idchgflag=TRUE;}
  }

  $setflag = FALSE;
  $newdat = array('dummy');
  if($chgflag){
    foreach($hostdat as $line){
      $temp = str_replace('.','_',$line);	// phpではPOST引数の"."を"_"に変換するので･･･
      if(in_array($temp,$chgno)){
        $setflag = TRUE;
      }elseif($line != 'dummy'){
        array_push($newdat,$line);
      }
    }
    if($setflag){
      $hostdat = $newdat;
      $fp=fopen(HOSTFILE,"w");
      set_file_buffer($fp, 0);
      flock($fp, 2);
      fputs($fp, implode("\n", $hostdat));
      fclose($fp);
    }
  }
  $idsetflag = FALSE;
  $idnewdat = array('dummy');
  if($idchgflag){
    foreach($idhostdat as $line){
      $temp = str_replace('.','_',$line);	// phpではPOST引数の"."を"_"に変換するので･･･
      if(in_array($temp,$idchgno)){
        $idsetflag = TRUE;
      }elseif($line != 'dummy'){
        array_push($idnewdat,$line);
      }
    }
    if($idsetflag){
      $idhostdat = $idnewdat;
      $fp=fopen(IDHOSTFILE,"w");
      set_file_buffer($fp, 0);
      flock($fp, 2);
      fputs($fp, implode("\n", $idhostdat));
      fclose($fp);
    }
  }
  if($setflag || $idsetflag){ updatelog(); }

  // 処理記事選択画面を表示
  echo "<input type=hidden name=mode value=admin>\n";
  echo "<input type=hidden name=admin value=delhost>\n";
  echo "<input type=hidden name=pass value=\"$pass\">\n";
  echo "<center><P>リストから削除したいホストのチェックボックスにチェックを入れ、変更ボタンを押して下さい。<br>\n";
  echo "表示ホストのリストから削除されます。\n";
  echo "<p><input type=submit value=\"削除\">";
  echo "<input type=reset value=\"リセット\">";
  echo "<P>ホスト表示リスト<br><table border=1 cellspacing=0>\n";
  echo "<tr bgcolor=6080f6><th>選択</th><th>ホスト名</th></tr>\n";

  foreach($hostdat as $line){
    if($line != 'dummy'){
      $bg = ($bgcol++ % 2) ? "d6d6f6" : "f6f6f6";//背景色
      echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$line\" value=\"delete\"></th>";
      echo "<td>$line</td></tr>\n";
    }
  }
  echo "</table>";
  echo "<P>ID表示リスト<br><table border=1 cellspacing=0>\n";
  echo "<tr bgcolor=\"#6080f6\"><th>選択</th><th>ホスト名</th></tr>\n";

  foreach($idhostdat as $line){
    if($line != 'dummy'){
      $bg = ($bgcol++ % 2) ? "d6d6f6" : "f6f6f6";//背景色
      echo "<tr bgcolor=\"$bg\"><th><input type=\"checkbox\" name=\"$line\" value=\"id_delete\"></th>";
      echo "<td>$line</td></tr>\n";
    }
  }
  echo "</table>";
  echo "<p><input type=submit value=\"削除\">";
  echo "<input type=reset value=\"リセット\"></form>";
  die("</center></body></html>");
}

// hage 追加ここまで

function init(){
  // hage 追加 2004.8.1
  // $chkfile=array(LOGFILE,TREEFILE);
  $chkfile=array(LOGFILE,TREEFILE,HOSTFILE,IDHOSTFILE);
  // hage 追加ここまで
  if(!is_writable(realpath("./")))error("カレントディレクトリに書けません<br>");
  foreach($chkfile as $value){
    if(!file_exists(realpath($value))){
      $fp = fopen($value, "w");
      set_file_buffer($fp, 0);
      if($value==LOGFILE)fputs($fp,"1,2002/01/01(月) 00:00,名無し,,無題,本文なし,,,,,,,,,\n");
      if($value==TREEFILE)fputs($fp,"1\n");
      // hage 追加 2004.8.1
      if($value==HOSTFILE || $value==IDHOSTFILE)fputs($fp,"dummy");
      // hage 追加ここまで
      fclose($fp);
      if(file_exists(realpath($value)))@chmod($value,0666);
    }
    if(!is_writable(realpath($value)))$err.=$value."を書けません<br>";
    if(!is_readable(realpath($value)))$err.=$value."を読めません<br>";
  }
  @mkdir(IMG_DIR,0777);@chmod(IMG_DIR,0777);
  if(!is_dir(realpath(IMG_DIR)))$err.=IMG_DIR."がありません<br>";
  if(!is_writable(realpath(IMG_DIR)))$err.=IMG_DIR."を書けません<br>";
  if(!is_readable(realpath(IMG_DIR)))$err.=IMG_DIR."を読めません<br>";
  if(USE_THUMB){
    @mkdir(THUMB_DIR,0777);@chmod(THUMB_DIR,0777);
    if(!is_dir(realpath(IMG_DIR)))$err.=THUMB_DIR."がありません<br>";
    if(!is_writable(realpath(THUMB_DIR)))$err.=THUMB_DIR."を書けません<br>";
    if(!is_readable(realpath(THUMB_DIR)))$err.=THUMB_DIR."を読めません<br>";
  }
  @mkdir(IMG_REF_DIR,0777);@chmod(IMG_REF_DIR,0777);
  if(!is_dir(realpath(IMG_REF_DIR)))$err.=IMG_REF_DIR."がありません<br>";
  if(!is_writable(realpath(IMG_REF_DIR)))$err.=IMG_REF_DIR."を書けません<br>";
  if(!is_readable(realpath(IMG_REF_DIR)))$err.=IMG_REF_DIR."を読めません<br>";
  if($err)error($err);
}
/*-----------Main-------------*/
$buf='';
init();         //←■■初期設定後は不要なので削除可■■

// ▼Yakuba追加(しおあき様提供。これで無限更新の問題を回避します。ありがとうございました！)
// '/'があれば問答無用で空白にしてみる
$reqcheck = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
if (FALSE !== strpos($reqcheck, '/')) {
die('');
}
// ▲Yakuba追加

switch($mode){
  case 'regist':
    regist($name,$email,$sub,$com,'',$pwd,$upfile,$upfile_name,$resto);
    break;
  case 'admin':
    valid($pass);
    if($admin=="del") admindel($pass);
    if($admin=="post"){
      echo "</form>";
      form($post,$res,1);
      echo $post;
      die("</body></html>");
    }
    if(is_file($default_thumb) && $admin=="thumb") admin_chgthumb($pass);
    if(ADMIN_SAGE && $admin=="sage") admin_sage($pass);
	// hage 追加 2004.8.1
    if($admin == "reghost") regist_host($pass);
    if($admin == "delhost") delete_host($pass);
	// hage 追加ここまで
    break;
  case 'usrdel':
    usrdel($no,$pwd);
  default:
    if($res){
      updatelog($res);
    }else{
      updatelog();
      echo "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=".PHP_SELF2."\">";
    }
}
?>
