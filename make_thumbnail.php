<?php
    require_once 'settings.php';
    require_once 'functions.php';
    require_once 'template.php';
    require_once 'sqlite.php';

    error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

    if (DONT_MAKE_THUMBNAIL) {
        die("Fatal error : Can not make thumbnail.");
        return;
    }

    $shell_exec = true;

    // CUIからの実行か?
    if (!is_null($argv) && !empty($argv[1])) {
        $shell_exec = false;
    }
  
    if (ASYNC_MAKE_THUMBNAILS && $shell_exec) {
        $cmd = PHP_PATH. "php ".APP_ROOT."/make_thumbnail.php true";
        if (!is_OnWindows()) {
            $cmd .= " > /dev/null &";
        }
        shell($cmd);
        return;
    }

    // データベース再構築
    cache_clean();
    $dir = get_dir_tree();
    $files = count($dir);
    
    for ($i = 0; $i < $files; $i++) {
        $count = 1;
        $zip_file = COMIC_DIR."/".$dir[$i]['zip_path'];
        if (is_OnWindows()) {
            // Windowsの場合、ファイル名エンコードをSJIS
            $zip_file = mb_convert_encoding($zip_file, "SJIS", "auto");
        }
        $comic = zip_open($zip_file);
        if (is_resource($comic)) { 
            $file_name = "";
            $count = 1;
            while (($entry = zip_read($comic)) !== false) { 
                $file_name = zip_entry_name($entry);
                // ■file
                $file_name = mb_convert_encoding($file_name, "UTF-8", $enc);
                d($file_name);
                // もう走査しなくていい
                if ($count > FORCOVER) {
                    break;
                }

                // 画像か否か
                if (!is_image($file_name, $image_ext)) {
                    continue;
                }

                // サムネイルを作るべき画像か
                if ($count == FORCOVER) {
                    $data = zip_entry_read($entry, zip_entry_filesize($entry));
                    $ext = get_ext($file_name);
                    $thumb = array(
                        "id" => $i+1,
                        "zip" => $zip_file,
                        "filepath" => CACHE."/thumb.".$ext,
                        "ext" => $ext
                    );
                    file_put_contents($thumb["filepath"], $data);

                    $r = make_thumbnail($thumb);
                    if ($r) {
                        save_thumbnail($thumb);
                    }
                }
                $count++;
            }
            zip_close($comic);
        } else { 
            die("[ERR]ZIP_OPEN : ".$zip_file); 
            // ここに代替画像
        }
    }

    echo 'ok';
    //header('Location: index.php');

?>
