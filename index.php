<?php
  require_once 'settings.php';
  require_once 'template.php';
  require_once 'functions.php';

  dir_tree();
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
    <a class="controller next_file next_control left_control">次のファイル</a>
    <a id="switch_half_page" class="controller ">単ページ切替</a>
    <a id="paint_index">蔵書一覧</a>
    <a id="paint_settings">設定</a>
    <a class="controller previous previous_control right_control">1 ページ戻る</a>
    <a class="controller previous_file previous_control right_control">前のファイル</a>
  </nav>

  <nav id="index">
    <?php
      $count = count($tree);
      for ($i = 0; $i < $count; $i++) {
        echo '<a id="comic_'.$i.'" class="comic_title">'.$tree[$i].'</a>';
      }
    ?>
  </nav>

  <section id="settings">
    <ul>
      <li id="right_click_to_next_wrapper">
        <?php
          $checked = $_COOKIE["right_click_to_next"];
          if (!empty($checked) && $checked == "true") { ?>
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
          $checked = $_COOKIE["right_paginate"];
          if (!empty($checked) && $checked == "true") { ?>
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
      <li>
        <a href="make_thumbnail.php">漫画の表紙を生成する</a>
        <br />
        <span class="notice">
          まだきちんと動かないかもしれません。漫画ファイルが多いと時間がかかります。
        </span>
      </li>
    </ul>
  </section>

  <input type="hidden" id="current_title" name="current_title" value="" />
  <input type="hidden" id="current_page" name="current_page" value="" />

<?php
  end_html();
?>
