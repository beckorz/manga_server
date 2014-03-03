<?php
    require_once 'settings.php';
    require_once 'functions.php';

    /**
     * テーブル初期化
     */
    function init_tables() {
        create_tables(); // テーブル作成
        dir_tree();
    }

    /**
     * テーブル作成
     */
    function create_tables() {
        // 作成
        if (! file_exists(DB)) {
            $db = new SQLite3(DB);
        }
        if (! exists_table("comics")) {
            $db->exec("BEGIN DEFERRED;");
            query('CREATE TABLE comics (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, pages INTEGER, zip_path TEXT, cover TEXT);', $db);
            $db->exec("COMMIT;");
        }
        if (! exists_table("images")) {
            $db->exec("BEGIN DEFERRED;");
            query('CREATE TABLE images (id INTEGER PRIMARY KEY AUTOINCREMENT, comics_id INTEGER, page INTEGER, filepath TEXT);', $db);
            $db->exec("COMMIT;");
        }
        return true;
    }

    /**
     * テーブル削除
     */
    function delete_db() {
        // 存在していたら削除する
        if (file_exists(DB)) {
            if (is_OnWindows()) {
                // Win系
                $dbpath = preg_replace('/\//', '\\', DB);
                $r = shell('del '. $dbpath);
            } else {
                // Linux系
                $cmd = 'rm -f '.DB;
                $r = shell($cmd);
            }
        }
    }

    /**
     * テーブルが存在しているか?
     *
     * @param $table_name 確認するテーブル名
     */
    function exists_table($table_name) {
        $sql = "select count(*) as count from sqlite_master where type='table' and name='".$table_name."'";
        $r = select($sql);
        return ($r[0]["count"] != 0);
    }

    /**
     * クエリー実行
     */
    function query($query, $db=null) {
        $db_conncted = true;
        if (is_null($db)) {
            $db_conncted = false;
            $db = new SQLite3(DB);
            $db->exec("BEGIN DEFERRED;");
        }

        // クエリ実行
        if (!$db->exec($query)) {
            $db->exec("ROLLBACK;");
			die('fatal error: '.$query);
        }

        if (!$db_conncted) {
            $db->exec("COMMIT;");
            $db->close();
        }

        return true;
    }

    // データ取得
    function select($query) {
        $db = new SQLite3(DB);
        $results = $db->query($query);

        if (!$results) {
            $db->close();
            return false;
        }

        $data = array();
        $count = 0;
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            foreach ($row as $column => $r) {
                $data[$count][$column] = $r;
            }
            $count++;
        }

        $db->close();
        return $data;
    }

    // ディレクトリツリーを取得
    function get_dir_tree() {
        $results = select("select title, zip_path from comics");
        $tree = array();
        foreach ($results as $r) {
            $tree[] = array(
                'title' => $r['title'],
                'zip_path' => $r['zip_path']);
        }
        return $tree;
    }

    // 漫画のタイトルを取得
    function get_title($comics_id) {
        $r = select("SELECT title FROM comics WHERE id = ".$comics_id);
        return $r[0]["title"];
    }

    // 漫画のページ数を取得
    function get_pages($comics_id) {
        $r = select("SELECT pages FROM comics WHERE id = ".$comics_id);
        return intval($r[0]["pages"]);
    }

    // 漫画のページを取得
    function get_filepath($comics_id, $page) {
        $r = select("SELECT filepath FROM images WHERE comics_id = ".$comics_id." AND page = ".$page);
        if (is_array($r)) {
            if (count($r) > 0) {
                return $r[0]["filepath"];
            } else {
                return "";
            }
        } else {
            return "";
        }
    }

    // エスケープ
    function escape($str) {
        return str_replace("'", "''", $str);
    }
?>
