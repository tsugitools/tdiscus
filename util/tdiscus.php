<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Tdiscus {

    const default_paginator_width = 7;

    public static function header() {
        global $OUTPUT, $TOOL_ROOT;
        if ( ! isset($TOOL_ROOT) ) $TOOL_ROOT = dirname($_SERVER['SCRIPT_NAME']);
        $OUTPUT->header();
        echo('<link href="'.$TOOL_ROOT.'/static/coursera.css" rel="stylesheet">'."\n");
    }

    public static function footerStart() {
        global $OUTPUT;
        $OUTPUT->footerStart();
        self::load_ckeditor();
        echo('<script>$(document).ready(function() { jQuery("time.timeago").timeago(); });</script>'."\n");
    }

    public static function footerEnd() {
        global $OUTPUT;
        $OUTPUT->footerEnd();
    }

    public static function load_ckeditor() {
        global $CFG;
        echo('<script src="'.$CFG->staticroot.'/util/ckeditor_4.8.0/ckeditor.js"></script>'."\n");
    }

    public static function search_box($sortby=false) {
        global $TOOL_ROOT;
        $searchvalue = U::get($_GET,'search') ? 'value="'.htmlentities(U::get($_GET,'search')).'" ' : "";
        $sortvalue = U::get($_GET,'sort');

        // https://www.w3schools.com/howto/howto_css_search_button.asp
        echo('<div class="tdiscus-threads-search-sort"><form>'."\n");
        if ( is_array($sortby) ) {
?>
<div class="tdiscus-threads-sort">
<label for="sort"><?= __("Sort by") ?></label>
<select name="sort" id="sort" onclick="this.form.submit();">
<?php
foreach($sortby as $sort) {
  echo('<option value="'.$sort.'" '.($sortvalue == $sort ? 'selected="selected"' : '').' >'.__(ucfirst($sort)).'</option>'."\n");
}
?>
</select>
</div>
<?php
        }
?>
<div class="tdiscus-threads-search">
  <input type="text" id="tdiscus-threads-search-input" placeholder="Search.." name="search"
  <?= $searchvalue ?>
  >
  <button type="submit"><i class="fa fa-search"></i></button>
  <button type="submit" onclick='document.getElementById("tdiscus-threads-search-input").value = "";'><i class="fa fa-undo"></i></button>
</div>
<?php
        echo("</form></div>\n");
    }

    public static function add_comment($thread_id) {
?>
<div id="tdiscus-add-comment-div" class="tdiscus-add-comment-container" title="<?= __("Reply") ?>" >
<form id="tdiscus-add-comment-form" method="post">
<p>
<input type="text" name="comment" class="form-control">
</p>
<p>
<input type="submit" id="tdiscus-add-comment-submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    public static function add_sub_comment($html_id, $thread_id, $comment_id, $depth) {
?>
<div id="<?= $html_id ?>" class="tdiscus-add-sub-comment-container" title="<?= __("Reply") ?>"  style="display:none;">
<form method="post" 
    data-click-done="<?= $html_id ?>_toggle" 
    class="tdiscus-add-sub-comment-form">
<p>
<input type="hidden" name="comment_id" value="<?= $comment_id ?>">
<input type="hidden" name="thread_id" value="<?= $thread_id ?>">
<textarea style="width:100%;" class="tdiscus-add-sub-comment-text" name="comment" class="form-control">
</textarea>
</p>
<p>
<input type="submit" id="tdiscus-add-comment-submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php
    }

    public static function paginator($baseurl, $start, $pagesize, $total) {
    // echo("<p>baseurl=$baseurl start=$start size=$pagesize total=$total</p>\n");
    if ( $start == 0 && $total < $pagesize ) return;

    $laststart = intval($total /$pagesize) * $pagesize;
    $showpages = self::default_paginator_width; // The number of pages
    $firststart = $start - (intval($showpages/2) * $pagesize);
    if ( $firststart < 0 ) $firststart = 0;
?>
<nav aria-label="Page navigation">
  <ul class="pagination">
  <li class="page-item<?= ($start>0) ? '' : ' disabled'?>">
    <a class="page-link" href="<?= add_url_parm($baseurl, 'start', "0") ?>" aria-label="First">
        First
      </a>
    </li>
<?php
    if ( $firststart > 0 ) {
        $prefirststart = $firststart - $pagesize;
        echo('<li class="page-item"><a class="page-link" href="'.add_url_parm($baseurl, 'start', $prefirststart).'">...</a></li>');
    }
    for($i=0;$i<$showpages;$i++) {
        if ( $firststart > $laststart ) break;
        $active = ($firststart == $start ) ? ' active' : '';
        $pageno = intval($firststart/$pagesize);
        echo('<li class="page-item'.$active.'"><a class="page-link" href="'.add_url_parm($baseurl, 'start', $firststart).'">'.($pageno+1)."</a></li>\n");
        $firststart = $firststart + $pagesize;
    }
    if ( $firststart <= $laststart ) {
        echo('<li class="page-item"><a class="page-link" href="'.add_url_parm($baseurl, 'start', $firststart).'">...</a></li>');
    }
?>
    <li class="page-item<?= ($start<$laststart) ? '' : ' disabled'?>">
      <a class="page-link" href="<?= add_url_parm($baseurl, 'start', ($laststart)) ?>" aria-label="Last">
        Last
      </a>
    </li>
  </ul>
</nav>
<?php
    }

    public static function renderBooleanSwitch($type, $thread_id, $variable, $title, $value, $set, $icon, $color=false)
    {
        $action = ($set ? '' : 'un').$title;
        $uitype = $type;
        if ( $uitype == 'threaduser' ) $uitype = 'thread';
        if ( $uitype == 'threadcomment' ) $uitype = 'comment';
?>
        <a href="#"
        class="<?= $type ?><?= $variable ?>_<?= $thread_id ?> tdiscus-pin-api-call"
        data-class="<?= $type ?><?= $variable ?>_<?= $thread_id ?>"
        data-endpoint="<?= $type ?>setboolean/<?= $thread_id ?>/<?= $variable ?>/<?= $set ?>"
        data-confirm="<?= htmlentities(__('Do you want to '.$action.' this '.$uitype.'?')) ?>"
        title="<?= __(ucfirst($action)." ".ucfirst($uitype)) ?>"
         <?= ($value == $set ? 'style="display:none;"' : '') ?>
         ><i class="fa <?= $icon ?>" <?= ($color ? 'style="color: '.$color.'";' : '') ?>></i></a>
<?php
    }

    public static function renderToggle($title, $id, $icon, $color=false)
    {
?>
        <a href="#"
        id="<?= $id ?>_toggle"
        data-id="<?= $id ?>"
        class="tdiscus-toggle-api-call"
        title="<?= htmlentities(__("Toggle").' '.$title) ?>">
        <i id="<?= $id ?>_icon_on" class="fa <?= $icon ?>"></i>
        <i id="<?= $id ?>_icon_off" class="fa <?= $icon ?>"
         style="display:none; <?= ($color ? ('color: '.$color.';') : '') ?>"></i></a>
<?php
    }

    public static function renderBooleanScript()
    {
        global $TOOL_ROOT, $OUTPUT;
?>
<script>
$(document).ready( function() {
   $('.tdiscus-pin-api-call').click(function(ev) {
        ev.preventDefault()
        if ( ! confirm($(this).attr('data-confirm')) ) return;
        var data_class = $(this).attr('data-class');
        $.post(addSession('<?= $TOOL_ROOT ?>'+'/api/'+$(this).attr('data-endpoint')))
            .done( function(data) {
                $('.'+data_class).toggle();
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                var message = '<?= htmlentities(__('Request Failed')) ?>';
                if ( error && error.length > 0 ) {
                    message = message + ": "+error.substring(0,40);
                }
                console.log(error);
                alert(message);
            });
   });

   $('.tdiscus-toggle-api-call').click(function(ev) {
        ev.preventDefault()
        var data_id = $(this).attr('data-id');
        $('#'+data_id).toggle();
        $('#'+data_id+"_icon_on").toggle();
        $('#'+data_id+"_icon_off").toggle();
    });

   $(".tdiscus-add-sub-comment-form").on('submit', function (ev) {
       var comment = $(this).find('textarea[name="comment"]').val();
       console.log('comment=',comment);
       ev.preventDefault();
       var ser = $(this).serialize();
       var form = $(this);
       console.log('ser', ser);
       var click_done = $(this).attr('data-click-done');
       var txt3 = document.createElement("p");  // Create with DOM
       txt3.innerHTML = '<img src="<?= $OUTPUT->getSpinnerUrl() ?>">';
       $(this).closest('.tdiscus-sub-comment-container').prepend(txt3);
       // Hide during the processing
       if ( click_done ) $('#'+click_done).click();
       $(this).find('textarea[name="comment"]').val('');
       $.post(addSession('<?= $TOOL_ROOT ?>/api/addsubcomment'), ser)
            .done( function(data) {
                console.log('data', data);
                // if ( comment.length > 0 ) txt3.innerHTML = htmlentities(comment);
                if ( comment.length > 0 ) txt3.innerHTML = data;
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
                alert(error);
            });
   });

});
</script>
<?php
    }
}
