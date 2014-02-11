<?php
	require_once 'settings.php';
	require_once 'functions.php';
    require_once 'Log.php';

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
		// 存在していたら削除する
        $file = Log::factory('file', LOG_DIR.'/'.date('Y-m-d').'.log', 'SQLite');
        if (file_exists(DB)) {
            // キャッシュディレクトリ内には画像のみ格納するよう変更したので戻す。
            if (is_OnWindows()) {
                // Win系
                $dbpath = preg_replace('/\//', '\\', DB);
                $r = shell('del '. $dbpath);
            } else {
                // Linux系
                // shell_exec('rm -f '.CACHE.'/*');
                $cmd = 'rm -f '.DB;
                $r = shell($cmd);
            }
        }
        // 作成
        $db = new SQLite3(DB);
        $db->exec("BEGIN DEFERRED;");
        query('CREATE TABLE comics (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, pages INTEGER, zip_path TEXT, cover TEXT);', $db);
        query('CREATE TABLE images (id INTEGER PRIMARY KEY AUTOINCREMENT, comics_id INTEGER, page INTEGER, filepath TEXT);', $db);
        $db->exec("COMMIT;");
        return true;
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
		//print $query;
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
		$results = select("select zip_path from comics");
		$tree = array();
		foreach ($results as $r) {
			$tree[] = $r["zip_path"];
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
?>
