<?php

require_once 'sqlite.php';

$enc = "eucjp-win, sjis-win, ASCII, JIS, UTF-8, EUC-JP, SJIS";

function is_iphone() {
    return (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPhone');
}

function is_ipad() {
    return (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad');
}

function is_image($filename, $image_ext) {
    $filename = trim($filename);
    $ext = get_ext($filename);
    return in_array($ext, $image_ext);
}

function is_OnWindows() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return true;
    } else {
        return false;
    }
}

function get_ext($filename) {
    $filename = trim($filename);
    return substr(strrchr($filename, '.'), 1);
}

function get_filename($filename) {
    $filename = trim($filename);
    return end(explode('/', $filename));
}

function get_filename_without_ext($filename) {
    $filename = trim($filename);
    #pathinfo が日本語パスだとうまくいかなかった。
    $path = explode('/', $filename);
    return strstr(end($path), '.', true);
}

function is_jpg($ext) {
    if (strcasecmp($ext, "jpg") === 0) {
        return true;
    } else if (strcasecmp($ext, "jpeg") === 0) {
        return true;
    } else {
        return false;
    }
}

function is_png($ext) {
    return (strcasecmp($ext, "png") === 0);
}

function cache_clean() {
    if (dir_size(CACHE) > CACHELIMIT) {
        // キャッシュディレクトリが上限サイズを超えている場合
        delete_caches();    // キャッシュ削除
        return true;
    } else {
        // ディレクトリサイズが制限値より低い場合
        return false;
    }
}

/**
 * NEWマーク用の出力
 * return $比較元の日付
 */
function output_new_mark($fileTimeCreated) {
    $basetime1 = strtotime("-".NEW_DATE_TIME1. "day");  // -30
    $basetime2 = strtotime("-".NEW_DATE_TIME2. "day");  // -15
    if ($basetime2 <= $fileTimeCreated) {
        return '<span class="newmark2">new!</span>';
    } elseif ($basetime1 <= $fileTimeCreated) {
        return '<span class="newmark1">new!</span>';
    } else {
        return '';
    }
}

function delete_caches() {
    if (is_OnWindows()) {
        // Win系
        shell('rmdir '. CACHE . ' /s /q');
        shell('mkdir '. CACHE . '');
    } else {
        // Linux系
        shell('rm -f '.CACHE.'/*');
    }
    // init_tables(); // 初期化
    query("DELETE FROM images");
    query("UPDATE comics SET pages = NULL");
}

function dir_size($dir) {
  $handle = opendir($dir);

  $mas = 0;
  while ($file = readdir($handle)) {
    if ($file != '..' && $file != '.' && !is_dir($dir.'/'.$file)) {
      $mas += filesize($dir.'/'.$file);
    } else if (is_dir($dir.'/'.$file) && $file != '..' && $file != '.') {
      $mas += dir_size($dir.'/'.$file);
    }
  }
  closedir($handle);

  return $mas;
}

// Shellコマンド実行
function shell($cmd) {
    // キャッシュディレクトリ内には画像のみ格納するよう変更したので戻す。
    $r = shell_exec($cmd);
    if (is_null($r)) {
        return false;
    } else {
        return $r;
    }
}


function get_dir_tree_old() {
  if (!$handle = fopen(DIRTREE, "rb")) {
    die("[ERR]FILE OPEN : get_dir_tree");
  }

  $tree = array();
  while (($buffer = fgets($handle, 4096)) !== false) {
    $tree[] = trim($buffer);
  }
  if (!feof($handle)) {
    echo "Error: unexpected fgets() fail\n";
  }
  fclose($handle);

  return $tree;
}

function dir_tree() {
  fn_directory_recursion(COMIC_DIR, "!.*/,*.".COMIC_EXT, "dir_tree_callback");
}

function dir_tree_callback($path) {
    mb_language("Japanese");    // Windows版xamppでmbstringの設定がおかしい時があるので、明示的に入れてみる。
    $length = mb_strlen(COMIC_DIR."/");

    $path = trim(mb_substr($path, $length));
    $group_name = mb_substr($path, 0, mb_strrpos($path, "/"));
    $group_name = mb_convert_encoding($group_name, "UTF-8", "auto");
    $file_created = filemtime(COMIC_DIR."/".$path);
    $file_created = date("Y-m-d\TH:i:s", $file_created);
    $path = mb_convert_encoding($path, "UTF-8", "auto");
    $title = get_filename_without_ext($path);
    $title = escape($title);
    $path = escape($path);
    query("INSERT INTO comics (title, group_name, zip_path, created) values ('".$title."', '".$group_name."', '".$path."', '".$file_created."')");
}

function save_thumbnail($thumb) {

  // fread で読み込む際、最大読み込みサイズを画像のサイズにしていたので、
  // データの最後の方が読み込まれていなかったみたい。
  $imgbinary = fread(fopen($thumb["filepath"], "rb"), filesize($thumb["filepath"]) * 2);
  $img_str = base64_encode($imgbinary);

  // メモリ解放（効果なし？）
  unset($imgbinary);

  $ext = $thumb["ext"];

  if (strcasecmp($ext, "jpg") === 0) {
    $ext = "jpeg";
  }

  $img_str = 'data:image/'.$ext.';base64,'.$img_str;
  query("UPDATE comics SET cover = '".$img_str."' WHERE id = ".$thumb["id"]);

  // メモリ解放（効果なし？）
  unset($img_str);
  return true;
}

function make_thumbnail($file) {
  $image = null;
  if (is_jpg($file["ext"])) {
    $image = imagecreatefromjpeg($file["filepath"]);
  } else if (is_png($file["ext"])) {
    $image = imagecreatefrompng($file["filepath"]);
  }

  if (is_null($image)) {
    return false;
  }

  $org = array(
    "w" => imagesx($image),
    "h" => imagesy($image)
  );

  $new = null;
  if ($org["w"] < $org["h"] || $org["w"] == $org["h"]) {
    $w = MAXWIDTHTHUMB;
    $rate = $w / $org["w"];
    $new = array(
      "w" => $w,
      "h" => intval($org["h"] * $rate)
    );
  } else {
    $h = MAXHEIGHTTHUMB;
    $rate = $h / $org["h"];
    $new = array(
      "w" => intval($org["w"] * $rate),
      "h" => $h 
    );
  }

  $r = false;
  if (USEIMAGEMAGICK) {
    $cmd = "convert ";
    if (is_jpg($file["ext"])) {
      $cmd .= '-define jpeg:size='.$new["w"].'x'.$new["h"];
    }
    $cmd .= ' -resize '.$new["w"].'x'.$new["h"].' -quality '.THUMBQUALITY.' '.$file["filepath"].' '.$file["filepath"];
    $r = system($cmd);
    if ($r !== false) {
      $r = true;
    }
  } else {
    $new_image = imagecreatetruecolor($new["w"], $new["h"]);
    $r = imagecopyresampled(
      $new_image, $image, 0, 0, 0, 0,
      $new["w"],$new["h"],$org["w"],$org["h"]);

    if ($r === false) {
      imagedestroy($new_image);
      return false;
    }
    $r = imagejpeg($new_image, $file["filepath"], THUMBQUALITY);
    imagedestroy($new_image);
  }
  imagedestroy($image);

  return $r;
}

// テスト用
function d($value) {
  echo '<h3>var_dump</h3>';
  var_dump($value);

  //echo '<h3>print_r</h3>';
  //print_r($value);
}


/* 
 * phpでディレクトリツリーを辿りファイルを再帰的に処理する関数 | とりさんのソフト屋さん
 * http://soft.fpso.jp/develop/php/entry_2818.html
 */

/**
 * $targetを再帰的に$patternに一致したファイル走査し、$callbackを実行する関数
 *
 * @param string $target 再帰して検索させるディレクトリ
 * @param string $pattern 調べるパターン。シェルワイルドカードで指定。ディレクトリは除外パターン(先頭に!)のみ有効。カンマ区切りで複数指定可
 * @param string $callback 検索されたファイルを処理する関数名
 * @param array $args $callbackに渡す引数(可変ではない)
 * @return void
 */
function fn_directory_recursion($target, $pattern, $callback='', $args=null) {
    if (!is_dir($target)) return false;

    $target = add_last_slash($target); //ディレクトリは最後に/を付加

    $nodes = array();
  //ディレクトリ内のファイル名を１つずつを取得
  $node = scandir($target);
  $node_count = count($node);
  mb_language("Japanese");  // Windows版xamppでmstringの設定がおかしい時があるので、明示的に入れてみる。
  for ($i = 0; $i < $node_count; $i++) {
    if ($node[$i] !== '.' && $node[$i] !== '..') {
//  echo "■<br />";
//      print(mb_convert_encoding($node[$i], "UTF-8", "auto"));
//  echo "■<br />";
//      $nodes[] = mb_convert_encoding($node[$i], "UTF-8", "auto"); //ファイルリスト作成
      $nodes[] = $node[$i]; //ファイルリスト作成
    }
  }
    //ファイルリストをチェック
    foreach ($nodes as $node) {
        $path = $target.$node;
        if (is_dir($path)) {
            //ディレクトリの場合、除外パターンだけ有効
            $node = add_last_slash($node); //ディレクトリは最後に/を付加
            if (is_pattern_match($pattern, $node) !== -1) { //除外以外の場合
                fn_directory_recursion($path, $pattern, $callback, $args);
            }
        } else if(is_file($path)) {
            //ファイルの場合、一致パターンだけ有効
            if (is_pattern_match($pattern, $node)) {
                if (is_array($callback)) {
                    // 正しいものとしてチェックを行わない。
                    $instace = $callback[0];
                    $method = $callback[1];
                    $instace->$method($path,$args);
                } else {
                    $callback($path,$args); //あるものとして存在チェックは行わない。
                }
            }
        }
    }
}

/**
 * $strの最後に/を付加する関数。$strの最後に/が初めから付いている場合は付加しない。
 *
 * @param string $str /を付加する文字列
 * @return string /を最後に付加した文字列
 */
function add_last_slash($str) {
    if (!preg_match('/\/$/',$str)) {
        $str .= '/';
    }
    return $str;
}

/**
 * $strが$patternに一致していることを調べる関数
 *
 * @param string $pattern 調べるパターン。シェルワイルドカードで指定。ディレクトリは除外パターン(先頭に!)のみ有効。カンマ区切りで複数指定可
 * @param string $str 調べる文字列
 * @return int -1:除外パターン一致、1:パターン一致、0:一致パターン無し
 */
function is_pattern_match($pattern, $str) {
    $pats = explode(',',$pattern);
    foreach ($pats as $pat) {
        //頭が!で始まるパターンは除外パターン
        if (preg_match('/^!(.*)/',$pat,$m)) {
            $pat = $m[1];
            if (fnmatch($pat, $str)) {
                return -1; //除外
            }
        } else {
            if (fnmatch($pat, $str)) {
                return 1; //一致
            }
        }
    }
    return 0;
}

?>
