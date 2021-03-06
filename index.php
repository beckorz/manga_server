<?php
  require_once 'settings.php';
  require_once 'template.php';
  require_once 'functions.php';

  $tree = get_dir_tree();
  start_html();
?>

  <article id="viewer">
    <section id="full_page">
      <div id="page_1" class="right_page"></div>
      <div id="page_2" class="left_page"></div>
      <div class="clear"></div>
    </section>

    <section id="half_page">
      <div class="controllers">
        <div class="next next_control left_control"></div>
        <div class="previous previous_control right_control"></div>
      </div>
      <div id="half_page_image"></div>
      <div class="clear"></div>
    </section>
  </article>

  <nav id="menu">
    <a class="controller next next_control left_control">1 ページ進む</a>
    <a class="controller previous previous_control right_control">1 ページ戻る</a>
    <div class="move_page_wrapper">
      <input type="range" name="slider" id="slider_controller" value="1" min="1" max="1" />
      <input type="number" name="current_page" id="current_page" value="0" min="1" max="1" />
      / <span id="all_page"></span>
    </div>
    <div class="clear"></div>
<?php
  if (is_iphone()) {
?>
    <a class="controller next_file next_control left_control">次のファイル</a>
    <a class="controller previous_file previous_control right_control">前のファイル</a>
    <div class="clear"></div>
    <a id="paint_index" class="selected">蔵書一覧</a>
    <a id="paint_settings">設定</a>
    <a id="paint_help">使い方</a>
<?php
  } else {
?>
    <a class="controller next_file next_control left_control">&lt; 次のファイル</a>
    <a id="switch_half_page" class="controller ">単ページ切替</a>
    <a id="paint_index" class="selected">蔵書一覧</a>
    <a id="paint_settings">設定</a>
    <a id="paint_help">使い方</a>
    <span id="current_commic_name"></span>
    <a class="controller previous_file previous_control right_control">前のファイル &gt;</a>
<?php
  }
?>
  </nav>

  <nav id="index">
    <section>
    <?php
      $count = 1;
      $previous_path = null;
      foreach ($tree as $t) {
        $path = explode("/", $t['zip_path']);
        $steps = count($path);

        $current_path = '';
        if ($steps > 1) {
          for ($i = 0; $i < $steps - 1; $i++) {
            $current_path .= $path[$i].' ';
          }
        }

        // 
        if ($previous_path !== $current_path) {
          if (!is_null($previous_path)) {
            // 1フォルダ分完了
            echo '</ul></section><section>';
            echo '</div>';
          }

          // 蔵書タイトル(1フォルダ分)
          echo '<div class="contentBody">';
              echo '<div class="toggle hbox">';
                  echo '<h2 class="boxFlex">';
                  echo (empty($current_path)) ? '未分類': trim($current_path);
                  $created = strtotime(get_commic_group_created($t['group_name'])); // 作成日取得
                  echo output_new_mark($created);

                  echo '</h2>';
                  echo '<span class="plus"><img src="images/plus.png"></span>';
              echo '</div>';
          echo '<ul>';
        }

        // 蔵書リンク
        echo '<li><a id="comic_'.$count.'" class="comic_title" title="'.$t['zip_path'].'">'.$t['title'].'</a></li>';

        $previous_path = $current_path;
        $count++;
      }
      echo '</ul>';
    ?>
    </section>
  </nav>

  <section id="settings">
    <ul>
      <li id="right_click_to_next_wrapper">
        <?php
          if (empty($_COOKIE["right_click_to_next"])) {
            $checked = "false";
          } else {
            $checked = $_COOKIE["right_click_to_next"];
          }

          if ($checked == "true") { ?>
            <input type="checkbox" name="right_click_to_next" id="right_click_to_next" value="1" checked="checked" />
        <?php
          } else { ?>
            <input type="checkbox" name="right_click_to_next" id="right_click_to_next" value="1" />
        <?php
          } ?>
        <label for="right_click_to_next">
          画面の右側をクリックして次のページに移動する
        </label><br />
        <span class="notice">
          標準設定：画面の左側をクリックして次のページに移動
        </span>
      </li>

      <li id="right_paginate_wrapper">
        <?php
          if (empty($_COOKIE["right_paginate"])) {
            $checked = "false";
          } else {
            $checked = $_COOKIE["right_paginate"];
          }

          if ($checked == "true") { ?>
            <input type="checkbox" name="right_paginate" id="right_paginate" value="1" checked="checked" />
        <?php
          } else { ?>
            <input type="checkbox" name="right_paginate" id="right_paginate" value="1" />
        <?php
          } ?>
        <label for="right_paginate">
          左のページから読む
        </label><br />
        <span class="notice">
          標準設定：右のページから読む
        </span>
      </li>

      <li id="background_color">
        背景：
        <?php
          if (empty($_COOKIE["background_color"])) {
            $bg_color = "false";
          } else {
            $bg_color = $_COOKIE["background_color"];
          }

          if ($bg_color == "false") {
        ?>
          <input type="radio" name="background_color" id="background_white" value="1" checked="checked" />
          <label for="background_white"> 明るい </label>
          <input type="radio" name="background_color" id="background_black" value="1" />
          <label for="background_black"> 暗い </label>
        <?php
          } else {
        ?>
          <input type="radio" name="background_color" id="background_white" value="1" />
          <label for="background_white"> 明るい </label>
          <input type="radio" name="background_color" id="background_black" value="1" checked="checked" />
          <label for="background_black"> 暗い </label>
        <?php
          }
        ?>
      </li>

      <li>
        <a id="make_thumbnail">漫画の表紙を生成する</a>
        <span id="disable_make_thumbnail">漫画の表紙を生成する</span>
        <progress id="making_thumbnail"></progress>
        <br />
        <span class="notice">
          まだきちんと動かないかもしれません。漫画ファイルが多いと時間がかかります。
        </span>
      </li>

    </ul>
  </section>

  <section id="help">
    <h1>iPhone、iPad などの iOS 製品をお使いの方へ</h1>
    <ul>
      <li>Web クリップ機能で、「ホーム画面に追加」するとフルスクリーンで漫画を楽しめます。</li>
    </ul>
    <h1>本を開く</h1>
    <ol>
      <li>「蔵書一覧」をクリック。</li>
      <li>本のタイトルか表紙をクリック（「設定」から表紙の生成が可能）。</li>
    </ol>
    <h1>次のページに移動</h1>
    <ul>
      <li>左側のページをクリック。</li>
      <li>1 ページ表示の場合、画面の左側をクリック。</li>
      <li>キーボードの「左矢印」キーを押す。</li>
      <li>キーボードの「 a 」キーを押す。</li>
      <li class="notice">
        「設定」で「画面の右側をクリックして次のページに移動する」がオンの場合、「前のページに移動」の操作と逆転します。
      </li>
    </ul>
    <h1>前のページに移動</h1>
    <ul>
      <li>右側のページをクリック。</li>
      <li>1 ページ表示の場合、画面の右側をクリック。</li>
      <li>キーボードの「右矢印」キーを押す。</li>
      <li>キーボードの「 d 」キーを押す。</li>
      <li class="notice">
        「設定」で「画面の右側をクリックして次のページに移動する」がオフの場合、「次のページに移動」の操作と逆転します。
      </li>
    </ul>
    <h1>1 ページ／2 ページ表示を切り替える</h1>
    <ol>
      <li>「単ページ切替」をクリックするたびに切り替わります。</li>
    </ol>
    <h1>2 ページ表示のとき、1 ページだけ移動する</h1>
    <ul>
      <li>次のページに移動するときは「 1 ページ進む」をクリック。</li>
      <li>前のページに移動するときは「 1 ページ戻る」をクリック。</li>
    </ul>
  </section>

<?php
  end_html();
?>
