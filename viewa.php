<?php
/* -------------------------------------------------------------------------
画像掲示板ビューワ Yakuba改 20170319版
----------------------------------------------------------------------------
  ログから、画像情報などを抜き取り表示します
  futaba系のログ方式に対応しています

  最低限「ログファイル名」と「画像保存ディレクトリ」だけは設定してください

  2005/05/01 更新

■Yakuba改について
　Yakuba改は上記のオリジナルviewa.phpを元に下記の改造を加えた物です。

■更新履歴
□20120206版    「ereg_replace」がPHP5.3.0で非推奨となったため
                「preg_replace」へ置き換え。
□20120602版     バグフィクス。$fselectって要らないよね…？
□20121120版    PHP5.4で関数コール時に参照で引数を渡せなくなった事と、htmlspecialcharsの標準動作が変更になった事に対応(ひろ様感謝！)。
□20170319版    UTF-8化。
------------------------------------------------------------------------- */

// 調整用 エラーレベル設定
//error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE);

if(!defined('PHP_VERSION_ID')) {                // ◆Yakuba PHP5.2.7未満にPHP_VERSION_IDを設ける
  $v = explode('.',PHP_VERSION);
  define('PHP_VERSION_ID', ($v[0] * 10000 + $v[1] * 100 + $v[2]));
}

// 設定項目 -------------------------------------------------------------------
// 画像掲示板（futaba.php/siokara.php/moepic.phpなど）の設定部分と同じにすること.
define("LOGFILE", 'img.log');                   // ログファイル名
define("TREEFILE", 'tree.log');                 // ログファイル名
define("IMG_DIR", 'src/');                      // 画像保存ディレクトリ
define("THUMB_DIR", 'thumb/');                  // サムネイル保存ディレクトリ
define("IMG_REF_DIR", 'ref/');                  // 経由先html格納ディレクトリ
define("RES_DIR", 'res/');                      // レスhtml格納ディレクトリ
define("PHP_SELF", 'hokan.php');                // 画像板のスクリプト名
define("RE_COL", '#789922');                    // ＞が付いた時の色

// VIEW設定基本項目 -----------------------------------------------------------
define("PHP_SELF_IMG", 'viewa.php');            // このスクリプト名
define("TITLE", '●●● 保管庫');               // タイトル（<title>とTOP
define("HOME", '../index.html');                // 「ホーム」へのリンク
define("PAGE_DEF", 50);                         // 1ページに表示する画像数
define("PAGE_COLS", 5);                         // 1行に表示する画像数
define("HSIZE", 100);                           // サムネ表示の横サイズ
define("VSIZE", 100);                           // サムネ表示の縦サイズ

// VIEW設定拡張項目 ※重要※ --------------------------------------------------
// 以下の全項目は siokara.php 特有のものです.ほかのスクリプトには恐らくありません.
// 自動ﾁｪｯｸとは siokara.php の処理によりログ内に付けられた印を自動ﾁｪｯｸする機能です.
define("RES_FILE", 0);                          // レスhtml経由機能を使用している
// レスhtml経由機能が無い、もしくは使用していない場合は、必ず '0' に設定してください
define("SAGE_START", 0);                        // スレ主強制sage機能を使用している
// スレ主強制sage機能が無い、もしくは使用していない場合は、必ず '0' に設定してください

define("SAGE_MOJI", '(sage)');                  // スレ主強制sageが効いていることを知らせる文字
define("UGO_MOJI", 'Animation!');               // アニメーションGIF時の文字（自動ﾁｪｯｸ:siokara.php
define("RPL_MOJI", '    ');                     // 画像差し替え時の文字（自動ﾁｪｯｸ:siokara.php
define("REPLACE_EXT", '.replaced');             // 差し替えの際、元々のサムネイルのお尻に付いた文字

// ナロブロ機能 ---------------------------------------------------------------
define("NARO_BURO", 0);                         // ナロブロ機能を使用している
define("NOANI_OPTION", 'noani=1');              // GIF停止ページからのページの追加引数
define("PHP_EXT", '.htm');                      // 拡張子(GIF動作ページ)
define("PHP_EXT_NOANI", 'g.htm');               // 拡張子(GIF停止ページ)

// LOGS SEARCH 設定項目 -------------------------------------------------------
define("TITLE2", 'ログ検索わはー');             // タイトル（<title>とTOP
define("LINK_LIM", 15);                         // [1] [2] [3]...の表示制限  '0'で無効化

$path = realpath("./").'/'.IMG_DIR;
$thumb_path = realpath("./").'/'.THUMB_DIR;

/* ヘッダ */
function head(&$dat){

  $self_path = PHP_SELF;
  $self_img_path = PHP_SELF_IMG;
  $home_path = HOME;
  $title_str = TITLE;

  $dat .= <<<__HEAD__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja-JP">
<head>
  <meta name="Berry" content="no">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="Content-Script-Type" content="text/javascript">
  <title>$title_str</title>
  <!-- meta http-equiv="Pragma" content="no-cache" -->
  <!-- meta http-equiv="Cache-Control" content="no-cache" -->
  <!--<link rel="shortcut icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon">-->
  <!--<link rel="icon" href="http://アドレス/favicon.ico" type="image/vnd.microsoft.icon">-->
  <style type="text/css">
    <!--
    body,tr,td,th { font-size:12pt; }
    a:link { color:#0000ee; }
    a:visited { color:#0000ee; }
    a:active { color:#dd0000; }
    a:hover { color:#dd0000; }
    span { font-size:20pt; }
    small { font-size:10pt; }

    .thr { background-color:#ffffee; }
    .res { background-color:#d6d6f6; }
    .csrc { border:none; }
    .cfileimg { }
    .cfileinfo { font-size:10pt; }

    .potype {
      background-color:#f0e0d6;
      color:#800000;
      border:1px 1px 1px 1px solid #eeaa88;
    }

    table  { border:none; padding:0px; margin:0px; border-collapse:collapse; }
    -->
  </style>
</head>
<body bgcolor="#ffffee" text="#800000" link="#0000ee" vlink="#0000ee" alink="#dd0000">
<!-- アナライザなどを入れるといいかも -->
__HEAD__;
}

/* エラー画面 */
function error($mes,$self){
//  head($dat);
//  echo $dat;
  echo "<hr size=\"1\"><br><br>\n",
       "<center><font color=\"red\" size=\"5\"><b>",$mes,"<br><br><a href=\"",$self,"\">リロード</a></b></font></center>\n",
       "<br><br><hr size=\"1\">\n";
  die("</body></html>");
}

/* 画像一覧 */
function img_view(){
  global $path,$thumb_path;

  // ツリーファイルからスレ元の記事No.とレス数を取得し配列に格納
  $tree = file(TREEFILE);
  $counttree = count($tree);
  $thread_no = array('dummy');
  for($i = 0; $i < $counttree; $i++) {
    $item = explode(",", rtrim($tree[$i]));
    $thread_no[$item[0]] = (count($item)-1);
  }

  // スレ配列からキーだけを抜き出す
  $thread_key = array_keys($thread_no);

  // 初期設定
  $image_cnt = 0;
  $image_cnt_thread = 0;
  $image_cnt_res = 0;
//  $p = 0;
  $counter = 0;
  $fimg = "";
  $finfo = "";
  $dispmsg = "";

  // 記事情報を表示するかどうか判断
  if (!isset($_POST["fileinfo"])) $_POST["fileinfo"] = "";
  $info_flag = (!strcmp($_POST["fileinfo"], "on")) ? TRUE : FALSE; // ファイル情報

  // クッキー保存
  $cooke_flag = FALSE;
  if (isset($_POST["submit"])) {
//    $cookiev = implode(",", array($info_flag));
    $cookiev = $info_flag;
    setcookie("user_data", $cookiev, time()+7*24*3600); /* 1週間で期限切れ */
    $cooke_flag = TRUE;
  }
  // クッキー読み出し
  if (isset($_COOKIE["user_data"]) && !$cooke_flag) {
//    $usdt = explode(",", $_COOKIE["user_data"]);
    $usdt = $_COOKIE["user_data"];
    $info_flag = ($usdt) ? TRUE : FALSE; // ファイル情報
  }

  // ページリンク作成
  $_GET['pg'] = (isset($_GET['pg'])) ? $_GET['pg'] : 1;
  $psta = PAGE_DEF * ($_GET['pg'] - 1) + 1;
  $pend = PAGE_DEF * $_GET['pg'];

  //ログファイル読み出し
  $line = @file(LOGFILE);
  $countline = count($line);

  // 情報取得のため全行繰り返し
  $i = 0; // while ﾙｰﾌﾟ
  while($i < $countline) { // while ﾙｰﾌﾟ
//  for($i = 0; $i < $countline; $i++) { // for ﾙｰﾌﾟ
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$url,
         $host,$pw,$ext,$w,$h,$time,$chk) = explode(",", $line[$i]);

    $i++; // while ﾙｰﾌﾟ
    // $extが存在しない(画像がない)場合はスキップ
    if (empty($ext)) { continue; }
    // 記事に画像があるかどうか判別
    $image_flag = (@is_file($path.$time.$ext)) ? TRUE : FALSE;
    if ($image_flag) {
      // スレ配列に該当記事があるかどうか判別
      $res_no = array_search($no, $thread_key);
      $thread_flag = ($res_no) ? TRUE : FALSE;
      // 画像数カウント
      if ($thread_flag) { $image_cnt_thread++; }
      else { $image_cnt_res++; }
      $image_cnt++;
      // 表示制限
//      $p++;
//      if ($p < $psta || $p > $pend) { continue; }
      if ($image_cnt < $psta || $image_cnt > $pend) { continue; }
      // No.変数を初期化
      $thread_time = ""; $fno = "";
      // 強制sageかどうか判断
      $sage_flag = (SAGE_START && $thread_flag && stristr($email, 'sage')) ? TRUE : FALSE;
      // アニメーションGIFかどうか判断
      $ani_flag = (!strcmp($ext, '.gif') && stristr($url, '_ugo_')) ? TRUE : FALSE;
      // 差し替えかどうか判断
      $rpl_flag = (@is_file($thumb_path.$time.'s.jpg'.REPLACE_EXT)) ? TRUE : FALSE;

      // ファイル情報を表示する
      if ($info_flag) {
        // レス主リンク
        if ($thread_flag) {
          $thread_time = $time; $fno = $no;
        } else {
          $find = FALSE;
          // ツリーファイルから、該当番号を探す
          for($j = 0; $j < $counttree; $j++) {
            $item = explode(",", rtrim($tree[$j]));
            if (!strcmp($no, $item[0]) || array_search($no, $item)) {
              $tno = $item[0];
              // ログファイルから時間を取得
              for($k = 0; $k < $countline; $k++) {
                list($nos,,,,,,,,,,,,$times,) = explode(",", $line[$k]);
                if (!strcmp($nos, $tno)) {
                  $thread_time = $times; $fno = $tno;
                  $find = TRUE;
                  break 2; // 該当するものがあればループを抜け出す
          } } } }
        }

        // スレ主、レス主を表示
        if ($thread_flag) {
          $note = 'Set:<a class="thr">スレ主</a>';
          if ($sage_flag) { $note .= SAGE_MOJI; }
          $note .= "<br>";
        } else {
          $note = 'Set:<a class="res">レス主</a><br>';
        }

        // 記事リンクを表示
        $no_noani = "";
        if (RES_FILE && $thread_time && @is_file(RES_DIR.$thread_time.PHP_EXT)) {
          $no_noani = '<a href="'.RES_DIR.$thread_time.PHP_EXT_NOANI.'">'.$no.'</a>';
          $no = '<a href="'.RES_DIR.$thread_time.PHP_EXT.'">'.$no.'</a>';
        } elseif (!RES_FILE && $fno) {
          $no_noani = '<a href="'.PHP_SELF.'?res='.$fno.'&'.NOANI_OPTION.'">'.$no.'</a>';
          $no = '<a href="'.PHP_SELF.'?res='.$fno.'">'.$no.'</a>';
        }
        $no = "No:".$no;
        if (NARO_BURO && RES_FILE && @is_file(RES_DIR.$thread_time.PHP_EXT_NOANI)) { $no .= " / ".$no_noani; }
        elseif (NARO_BURO && !RES_FILE) { $no .= " / ".$no_noani; }

        // 情報整理:あにGIF,差し替え
        $ugo = ($ani_flag) ? '<br><font color="#FF0099">'.UGO_MOJI.'</font>' : ""; // GIF
        $rpl = ($rpl_flag) ? '<br><font color="#FF0099">'.RPL_MOJI.'</font>' : ""; // 差し替え
      }
      // 画像リンク
      $img_flag = TRUE;
      if (@is_file($thumb_path.$time.'s.jpg')) { // サムネイルがある場合
        $scaleh = 1.0;
        $scalev = 1.0;
        if ($w > HSIZE) { $scaleh = HSIZE / $w; }
        if ($h > VSIZE) { $scalev = VSIZE / $h; }
        if ($scaleh > $scalev) { $scaleh = $scalev; }
        if ($rpl_flag) { // 差し替え切替
          $clip2 = '<img src="'.THUMB_DIR.$time.'s.jpg'.REPLACE_EXT.'" class="csrc"';
        } else {
          $clip2 = '<img src="'.THUMB_DIR.$time.'s.jpg" class="csrc"';
        }
        if (@is_file(IMG_REF_DIR.$time.'.htm')) { // ファイル経由切替
          $clip = '<a href="'.IMG_REF_DIR.$time.'.htm" target="_blank">'.$clip2.
          '  width="'.ceil($w * $scaleh).'" height="'.ceil($h * $scaleh).'" alt="'.$time.$ext.'"></a>';
        } else {
          $clip = '<a href="'.IMG_DIR.$time.$ext.'" target="_blank">'.$clip2.
          '  width="'.ceil($w * $scaleh).'" height="'.ceil($h * $scaleh).'" alt="'.$time.$ext.'"></a>';
        }
      } else { // サムネイルがない場合
        $clip = '<a href="'.IMG_DIR.$time.$ext.'" target="_blank">'.$time.$ext.'</a>';
      }

      $fimg .= '    <td align="center" valign="middle" class="cfileimg">'.$clip."</td>\n";
      if ($info_flag) { $finfo .= '    <td align="center" valign="middle" class="cfileinfo">'.$note.$no.$ugo.$rpl."</td>\n"; }
      $disp_flag = FALSE;
      $counter++;
      if (($counter % PAGE_COLS) == 0) {
        $dispmsg .= "  <tr>\n".$fimg."  </tr>\n";
        if ($info_flag) { $dispmsg .= "  <tr>\n".$finfo."  </tr>\n"; }
        $disp_flag = TRUE;
        $fimg = ""; $finfo = ""; // ｸﾘｱ
      }
//      clearstatcache(); // ファイルのstatをクリア
    }
  }
  if(!isset($disp_flag)){$disp_flag="";}
  if (!$disp_flag) {
    $dispmsg .= "  <tr>\n".$fimg."  </tr>\n";
    if ($info_flag) { $dispmsg .= "  <tr>\n".$finfo."  </tr>\n"; }
  }

  // ページリンク作成
  $pages = "";
  if ($image_cnt > PAGE_DEF) { // １ページのみの場合は表示しない
    $prev = $_GET['pg'] - 1;
    $next = $_GET['pg'] + 1;
//    $pages .= sprintf(" %d 番目から %d 番目の画像を表示<br>", $psta, $psta+$counter-1);
    ($_GET['pg'] > 1) ? $pages .=" <a href=\"".PHP_SELF_IMG."?pg=".$prev."\">&lt;&lt;前へ</a> " : $pages .= "&lt;&lt;前へ ";
    $page_cnt = ceil($image_cnt / PAGE_DEF);
    for($i = 1; $i <= $page_cnt; $i++) {
      if ($_GET['pg'] == $i) { // 今表示しているのはﾘﾝｸしない
        $pages .= " [<b>$i</b>] ";
      } else {
        $pages .= sprintf(" [<a href=\"%s?pg=%d\"><b>%d</b></a>] ", PHP_SELF_IMG, $i, $i); // 他はﾘﾝｸ
      }
    }
    (($image_cnt - $pend) >= 1) ? $pages .= " <a href=\"".PHP_SELF_IMG."?pg=".$next."\">次へ&gt;&gt;</a>" : $pages .= " 次へ&gt;&gt;";
  }

  // 表示項目選択
//  $fselect = "";
//  $fselect .= "<form action=\"".PHP_SELF_IMG."?pg=".$_GET['pg']."\" method=\"POST\">\n";
//  $fselect .= '[<label><input type="checkbox" name="fileinfo" value="on"';
//  if ($info_flag) { $fselect .= " checked"; } $fselect .= ">ファイル情報を表示</label>]\n";
//  $fselect .= '<BR><input type="submit" name="submit" class="potype" value="ポチッとな"></form>';

  // ブラウザに表示する
  $self_img_path = PHP_SELF_IMG;
  $pgdeftxt = PAGE_DEF;
  $outnotxt = sprintf(" %d から %d までの画像を表示", $psta, $psta+$counter-1);
  head($heads); // ﾍｯﾀﾞ
  echo <<<EOD
$heads
<div align="center">
$pages
</p>
<table>
$dispmsg</table>
<p>
$pages
</p>
<form action="$self_img_path" method="GET">
<table bgcolor="#f6f6f6">
<tr><th bgcolor="#d6d6f6" colspan="3">情報一覧</th></tr>
<tr><td align="center">総画像枚数</td><td align="right"> <b>$image_cnt</b></td><td>枚</td></tr>
<tr><td align="center">スレ画像数</td><td align="right"> <b>$image_cnt_thread</b></td><td>枚</td></tr>
<tr><td align="center">レス画像数</td><td align="right"> <b>$image_cnt_res</b></td><td>枚</td></tr>
<tr><td align="center">壱ページ表示数</td><td align="right"> <b>$pgdeftxt</b></td><td>枚</td></tr>
<tr><td align="center" colspan="3"><small>$outnotxt</small></td></tr>
<tr><td align="center" colspan="3"><input type="hidden" name="mode" value="s">
<input type="text" name="w" value="" size="14" onFocus="this.style.backgroundColor='#FEF1C0';" onBlur="this.style.backgroundColor='#FFFFFF';">
<input type="submit" value=" 検 索 ">
</td></tr>
</table>
</form>
<!--▼広告欄(なにか適当に広告などを入れるといいかも)-->
<!--▲広告欄-->
</div>
</body>
</html>
EOD;
  die();
}

/* 検索モード */
function search($word,$start,$page_def){

  if (get_magic_quotes_gpc()) $word = stripslashes($word); // ￥消去
  $joy = (trim($word)) ? "ドーモ" : "モード"; // お遊び

  $self_path = PHP_SELF;
  $self_img_path = PHP_SELF_IMG;
  $home_path = HOME;
  $title2_str = TITLE2;

  echo <<<__HEAD__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja-JP">
<head>
<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE">
<meta http-equiv="ROBOTS" content="NOINDEX,NOFOLLOW">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<title>$title2_str</title>
<style type="text/css">
<!--
body,tr,td,th { font-size:12pt; }
a:link { text-decoration:none;color:#0000ee; }
a:visited { text-decoration:none;color:#0000ee; }
a:active { text-decoration:underline;color:#dd0000; }
a:hover { text-decoration:underline;color:#dd0000; }
span { font-size:20pt; }
small { font-size:10pt; }

.ctext   { color:#000000; background-color:#ffffff; border:1px solid #2f5376; }
.csubmit {
  font-family: Osaka , helvetica;
  font-size: 10pt;
  color:#2f5376;
  background-color:#e6e6fa;
  border:1px groove #74aff0;
}
-->
</style>
</head>
<body bgcolor="#ffffee" text="#800000" link="#0000ee" vlink="#0000ee" alink="#dd0000">
<p align="right">
[<a href="$home_path" target="_top">ホーム</a>]
[<a href="$self_path?mode=admin">管理用</a>]
<p align="center">
<font color="#800000" size=5><b><span>$title2_str</span></b></font>
<p align="left">
[<a href="$self_img_path">戻る</a>]
<table width="100%"><tr><th bgcolor="#bfb08f" align="center">
<font color="#551133">検索$joy</font>
</th></tr></table>
<center>
<table><tr><td align="left">
・ キーワードを複数指定する場合は スペース で区切ってください。<br>
・ 検索条件は、AND検索 [A B] = (A かつ B) となっています。<br>
・ 検索対象は、 記事No、名前、題名、本文、目欄 です。<br>
・ 大文字小文字は区別されます。 (A≠a) (ABC≠aBC)<br>
・ 検索単語は４色繰り返し使用して色つきで表示します。google風？<br>
・ 変な検索方法はなるべくお控えください。バグがあればご報告を。<br>
・ この検索エンジンのベースは logoogle.php ver 0.1.1 です。<br>
</td></tr></table>
<hr width="90%" size=1>
<form action="$self_img_path" method="GET">
<input type="hidden" name="mode" value="s">
<input type="text" name="w" value="$word" class="ctext" size=60 onFocus="this.style.backgroundColor='#BFBFFF';" onBlur="this.style.backgroundColor='#FFFFFF';">
<input type="submit" value=" 検 索 " class="csubmit" onmouseover="this.style.backgroundColor='#A2E391';" onmouseout="this.style.backgroundColor='#E6E6FA';">
<input type="reset" value="リセット" class="csubmit" onmouseover="this.style.backgroundColor='#A2E3E1';" onmouseout="this.style.backgroundColor='#E6E6FA';">
<BR>表示件数：
<input class="csubmit" type="radio" name="pp" value="10" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';">10
<input class="csubmit" type="radio" name="pp" value="20" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';" checked>20
<input class="csubmit" type="radio" name="pp" value="30" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';">30
<input class="csubmit" type="radio" name="pp" value="40" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';">40
<input class="csubmit" type="radio" name="pp" value="50" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';">50
<input class="csubmit" type="radio" name="pp" value="100" onmouseover="this.style.backgroundColor='#6F50FA';" onmouseout="this.style.backgroundColor='#E6E6FA';">100
</form>\n
__HEAD__;

  // 前後のスペース除去
  if (trim($word) != "") {
    // 検索時間
    $ktime = getmicrotime();
    // URL ｴﾝｺｰﾄﾞ
    $words = $word;
    $word2 = urlencode($words);
    // 検索文字フォーマット
//    $word = str_replace("<", "&lt;", $word); // 検索に < を含める
//    $word = str_replace(">", "&gt;", $word); // 検索に > を含める
    if(PHP_VERSION_ID >= 50400){$word = htmlspecialchars($word);} else {$word = htmlspecialchars($word);}       // 変換 ◆Yakuba PHP5.4.0以降対応
    $word = str_replace("&amp;", "&", $word); // 特殊文字
    $word = str_replace(",", "&#44;", $word); // 検索に , を含める
    // スペース区切りを配列に
//    $word = preg_split("#(　| )+#", $word);
    $word = preg_replace('#(　| )+#', ' ', $word);
    $word = preg_replace('#(^ | $)+#', '', $word);
    $word = explode(' ', $word);
    // word に単語がない場合エラーを出す
    if ($word[0] == "") { error("変な検索はしちゃダメ！",$self_img_path."?mode=s"); }
    // 重複確認
    $word = array_unique($word);
    //ログを読み込む
    $tree = file(TREEFILE);
    $loge = @file(LOGFILE);
    // ツリー配列
    $trees = array();
    foreach($tree as $l) {
      $trees[] = explode(",", rtrim($l));
    }
    // ログ配列
    $logs = array();
    foreach($loge as $l) {
      $line = explode(",", rtrim($l));
      $logs[$line[0]] = $line;
    }
    // 記事No格納
    $hits = array();
    // 記事を検索する
    foreach($trees as $thread) {
      foreach($thread as $no) {
        $res = $logs[$no];
        $res[5] = str_replace("<br>", " ", $res[5]); // <br> を検索しちゃｲﾔﾝ!
        $found = 0;
        foreach($word as $w) {
          foreach(array(0,2,3,4,5) as $idx) {//"$no,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,\n";
            if(strpos($res[$idx], $w) !== FALSE) {
              $found++;
              break;
            }
          }
        }
        if($found == count($word)) {
          $hits[] = array('no' => $no, 'thread' => $thread[0]);
        }
      }
    }
    // ページリンク作成
    $pages = "";
    $all = count($hits);
    $maxs = $all - 1;
    $ends = $start + $page_def - 1;
    if ($all > $page_def) {
      // prevﾍﾟｰｼﾞ
      if ($start > 0) {
        $prevstart = $start - $page_def;
        $pages .= "<a href=\"".PHP_SELF_IMG."?mode=s&w=$word2&pp=$page_def&st=$prevstart\">&lt;&lt;前へ</a>　";
      } else { $pages .= "&lt;&lt;前へ　"; }
      // ﾍﾟｰｼﾞｽﾞ
      $ima = ceil($start / $page_def); // ｲﾏﾉﾄｺﾛ
      $goukei = ceil($all / $page_def); // ｽﾍﾞﾃ
      $go = $ima - ceil(LINK_LIM / 2) + 1;
      if ($go < 0) { $go = 0; }
      $stop = $go + LINK_LIM;
      if ($stop > $goukei){
        $stop = $goukei;
        $go = $stop - LINK_LIM;
        if ($go < 0) { $go = 0; }
      }
      if (!LINK_LIM) { $go = 0; $stop = $goukei; }
      for ($a = $go; $a < $stop; $a++) {
        if ($a == $ima) { $pages .= "[<b>$a</b>] "; }
        else { $pages .= "[<a href=\"".PHP_SELF_IMG."?mode=s&w=$word2&pp=$page_def&st=".($a*$page_def)."\"><b>$a</b></a>] "; }
      }
      // nextﾍﾟｰｼﾞ
      if ($ends < $maxs) {
        $nextstart = $ends+1;
        $pages .= "　<a href=\"".PHP_SELF_IMG."?mode=s&w=$word2&pp=$page_def&st=$nextstart\">次へ&gt;&gt;</a><br>";
      } else { $pages .= "　次へ&gt;&gt;<br>"; }
    }

    // 検索単語を表示
    $searchlist = "";
    $i=0;$j=0;$k=0;
    foreach($word as $w) {
//      if($i++ % 2) { $bg = ($j++ % 2) ? "color:black;background-color:#ff9999;" : "color:black;background-color:#A0FFFF;"; }
//      else { $bg = ($k++ % 2) ? "color:black;background-color:#99ff99;" : "color:black;background-color:#ffff66;"; }
      if($i++ % 2) {
        if($j++ % 2){ $rpllist[] = str_replace($w, "<>>,".$w.",<<<", $w); $bg = "color:black;background-color:#ff9999;"; }
        else { $rpllist[] = str_replace($w, ">><,".$w.",<<<", $w); $bg = "color:black;background-color:#A0FFFF;"; }
      } else {
        if($k++ % 2){ $rpllist[] = str_replace($w, "><>,".$w.",<<<", $w); $bg = "color:black;background-color:#99ff99;"; }
        else { $rpllist[] = str_replace($w, ">>>,".$w.",<<<", $w); $bg = "color:black;background-color:#ffff66;"; }
      }
      $recol = str_replace($w, "<b style=\"$bg\">$w</b>", $w);
      $searchlist .= $recol." ";
    }

    // 検索結果を表示
    $resultlist = "";
//    foreach($hits as $h) {
    while(list($line, $h) = each($hits)) {
      if ($line < $start) { continue; } // ﾍﾟｰｼﾞﾘﾐｯﾄ
      $thread = $logs[$h['thread']];
      $res = $logs[$h['no']];

      if (RES_FILE) {
        $reslist = (NARO_BURO) ? "スレリンク：<a href=\"".RES_DIR.$thread[12].PHP_EXT."\">$thread[0]</a> / <a href=\"".RES_DIR.$thread[12].PHP_EXT_NOANI."\">$thread[0]</a>" :
        "スレリンク：<a href=\"".RES_DIR.$thread[12].PHP_EXT."\">$thread[0]</a>";
      } else {
        if (NARO_BURO) { $reslist = "スレリンク：<a href=\"".PHP_SELF."?res=".$thread[0]."\">$thread[0]</a> / <a href=\"".PHP_SELF."?res=".$thread[0]."&".NOANI_OPTION."\">$thread[0]</a>"; }
        else { $reslist = "スレリンク：<a href=\"".PHP_SELF."?res=".$thread[0]."\">$thread[0]</a>"; }
      }
      if ($res[3]) { $email = (!strcmp($res[3], "sage")) ? "　sage" : "　<a href=\"mailto:".$res[3]."\">mail</a>"; }

      $res[5] = str_replace("<br>", "\n", $res[5]); // \nに変換
      $res = str_replace($word, $rpllist, $res); // ><, にマッチした単語を変換
//      $res = preg_replace("#\[b\](.*?)\[\/b\]#si", "<b>\\1</b>", $res);
/*
      $search = array(">>>,",
                      ">><,",
                      "><>,",
                      "<>>,",
                      ",<<<");
      $replace = array('<b style="color:black;background-color:#ffff66;">',
                       '<b style="color:black;background-color:#A0FFFF;">',
                       '<b style="color:black;background-color:#99ff99;">',
                       '<b style="color:black;background-color:#ff9999;">',
                       '</b>');
      $res = str_replace($search, $replace, $res);
*/
      $res = str_replace(">>>,", '<b style="color:black;background-color:#ffff66;">', $res);
      $res = str_replace(">><,", '<b style="color:black;background-color:#A0FFFF;">', $res);
      $res = str_replace("><>,", '<b style="color:black;background-color:#99ff99;">', $res);
      $res = str_replace("<>>,", '<b style="color:black;background-color:#ff9999;">', $res);
      $res = str_replace(",<<<", "</b>", $res);
      $res[5] = str_replace("\n", "<br>", $res[5]);  // \nを<br>に変換
      $res[5] = preg_replace("#(^|>)(&gt;[^<]*)#i", "\\1<font color=\"".RE_COL."\">\\2</font>", $res[5]); // 色をついでに付ける

//      $res[5] = preg_replace("#(^|[^=\]h])(ttp:)#i", "\\1http:", $res[5]); // ttp:→http:
//      $res[5] = preg_replace("#(https?|ftp|news)(:\/\/[0-9a-z\[\]\+\$\;\?\.%,!#~*\/:@&=_-]+)#i", "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>", $res[5]); // ｵｰﾄﾘﾝｸ
//      // 長いURL省略
//      $length = 60;
//      preg_match_all("#(^|[^=\]])(https?:\/\/[\!-;\=\?-\~]+)#si", $res[5], $reg);
//      for($i=0;$i<count($reg[0]);$i++){
//        $out[$i] = (strlen($reg[2][$i]) > $length) ? substr($reg[2][$i],0,$length)."..." : $reg[2][$i];
//        $res[5] = str_replace($reg[0][$i],$reg[1][$i]."<a href=".$reg[2][$i]." target=_blank>".$out[$i]."</a>", $res[5]);
//      }

      $resultlist .= <<<END_OF_TR
<table width="80%" bgcolor="#F0E0D6" cellspacing=0 cellpadding=0 style="margin:10px 0px 10px 0px;border:1px solid #749FF1;"><tr><td>
<table width="100%" border=0 cellspacing=0 cellpadding=3><tr><td bgcolor="#eeaa88" align="left">
No.<b>$res[0]</b>　題名：<font color="#cc1105" size="+1"><b>$res[4]</b></font>　名前：<font color="#117743"><b>$res[2]</b></font>　日付：$res[1]$email</td></tr>
<tr><td align="left">$res[5]</td></tr>
<tr><td align="right"><small>$reslist</small></td></tr></table>
</td></tr></table>
END_OF_TR;
      if ($line == $ends) { break; } // ﾍﾟｰｼﾞﾘﾐｯﾄ
    }

//    $resultlist = ($resultlist) ? $resultlist : "<table><tr><td>キーワードにマッチする記事がありませんでした。</td></tr></table>";

    // 現在の位置、フォーム
    if ($all) {
      $nowstate = ($ends < $maxs) ? "<b>".($start+1)."</b> - <b>".($ends+1)."</b>" : "<b>".($start+1)."</b> - <b>".$all."</b>";
      $forms = <<<__TMP__
<form action="$self_img_path" method="GET">
<table width="100%" border=0 cellpadding=0 cellspacing=0 style="margin:10px 0px 10px 0px;">
  <tr><td bgcolor="#3366cc"></td></tr>
  <tr>
    <td bgcolor="#e5ecf9" align="center">
      &nbsp;<br>
      <table align="center" border=0 cellpadding=0 cellspacing=0>
        <tr><td nowrap><font size="-1">
          <input type="hidden" name="mode" value="s">
          <input type="text" name="w" value="$words" class="ctext" size=60 onFocus="this.style.backgroundColor='#BFBFFF';" onBlur="this.style.backgroundColor='#FFFFFF';">
          <input type="submit" value=" 検 索 " class="csubmit" onmouseover="this.style.backgroundColor='#A2E391';" onmouseout="this.style.backgroundColor='#E6E6FA';">
          <input type="hidden" name="pp" value="$page_def">
        </font></td></tr>
      </table>
      <br><font size="-1"><a href="$self_img_path">画像検索</a> | <a href="$self_img_path?mode=s">検索ツール</a> | <a style="text-decoration:underline;color:#0000EE;">ヘルプ</a></font><br><br>
    </td>
  </tr>
  <tr><td bgcolor="#3366cc"></td></tr>
</table>
</form>
__TMP__;
    } else {
      $nowstate = "<b>0</b>";
      $joy2 = "";// お遊び
      if (count($word) - 1) {
        $joy2 = <<<__TMP__
<table cellpadding=0 cellspacing=0 border=0>
  <tr>
    <td valign="bottom" height=30><font size="-1"><font color="#cc0000">ヒント:</font> より多くの検索結果を得るには、検索条件から、ぶっちゃけありえない単語を削除してください。</font></td>
  </tr>
</table>
__TMP__;
      }
      $forms = <<<__TMP__
<div align="left">
$joy2
<br><br> $searchlist に該当する単語が見つかりませんでした。<br><br>
検索のヒント
<blockquote>
- キーワードに誤字・脱字がないか確かめてください。<br>
- 違うキーワードを使ってみてください。<br>
- より一般的な言葉を使ってみてください。<br>
- キーワードの数を少なくしてみてください。<br>
- 自分のセンスを疑ってみてください。<br>
</blockquote>
<table cellpadding=0 cellspacing=0 border=0>
  <tr>
    <td valign="bottom" height=30><font size="-1"><font color="#cc0000">追記:</font> 上記のことを試してもダメな場合は、恐らくもう手遅れなのでしょう。潔くあきらめてください。</font></td>
  </tr>
</table>
<br clear=all>
<center>
<table width="100%" border=0 cellpadding=0 cellspacing=0 style="margin:0px 0px 15px 0px;">
  <tr><td bgcolor="#3366cc"></td></tr>
  <tr><td align="center" bgcolor="#e5ecf9"><font size="-1">&nbsp;</font></td></tr>
</table>
</div>
__TMP__;
    }

    // 検索時間
    $ktime = getmicrotime() - $ktime;
    $ktime = substr($ktime, 0, 6);

    // 結果をブラウザに表示
    echo <<<EOD
<table width="100%" border=0 cellpadding=0 cellspacing=0>
  <tr><td bgcolor="#3366cc"></td></tr>
</table>
<table width="100%" bgcolor="#e5ecf9" border=0 cellpadding=0 cellspacing=0>
  <tr>
    <td bgcolor="#e5ecf9" nowrap><font face="arial,sans-serif" size="-1">&nbsp;<b>ログ内</b></font>&nbsp;</td>
    <td bgcolor="#e5ecf9" align="right" nowrap><font face="arial,sans-serif" size="-1">$searchlist の検索結果  <b>$all</b> 件中 $nowstate 件目  (<b>$ktime</b> 秒)&nbsp;</font></td>
  </tr>
</table>
<p align="center">
$pages
$resultlist
$pages
$forms\n
EOD;
  }
  echo "<small>&copy;2005 +にじうら+</small></center>\n";
  die("</body></html>");
}

/* 現在の時間をマイクロ秒単位で返す関数 */
function getmicrotime(){
  list($msec, $sec) = explode(" ", microtime());
  return ((float)$sec + (float)$msec);
}

/*-----------Main-------------*/
if (!isset($_REQUEST['mode'])) $_REQUEST['mode'] = "";
if (!isset($_GET["w"])) $_GET["w"] = "";
if (!isset($_GET["st"])) $_GET["st"] = 0;
if (!isset($_GET["pp"])) $_GET["pp"] = 20;
switch ($_REQUEST['mode']) {
  // 検索モード
  case 's':
    search($_GET["w"], $_GET["st"], $_GET["pp"]);
    break;
  // 通常表示
  default:
    img_view();
}
?>
