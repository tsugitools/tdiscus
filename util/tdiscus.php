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

    public static function add_comment() {
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

    public static function renderBooleanSwitch($thread_id, $variable, $title, $value, $set, $icon, $color=false)
    {
        $action = ($set ? '' : 'un').$title;
?>
        <a href="#"
        class="thread<?= $variable ?>_<?= $thread_id ?> tdiscus-pin-api-call"
        data-class="thread<?= $variable ?>_<?= $thread_id ?>"
        data-endpoint="threadsetboolean/<?= $thread_id ?>/<?= $variable ?>/<?= $set ?>"
        data-confirm="<?= htmlentities(__('Do you want to '.$action.' this thread?')) ?>"
        title="<?= __(ucfirst($action)." Thread") ?>"
         <?= ($value == $set ? 'style="display:none;"' : '') ?>
         ><i class="fa <?= $icon ?>" <?= ($color ? 'style="color: '.$color.'";' : '') ?>></i></a>
<?php
    }

    public static function renderBooleanScript()
    {
        global $TOOL_ROOT;
?>
<script>
$(document).ready( function() {
   $('.tdiscus-pin-api-call').click(function(ev) {
        ev.preventDefault()
        var endpoint = $(this).attr('data-endpoint');
        console.log('endpoint', endpoint);
        var data_class = $(this).attr('data-class');
        console.log('data_class',data_class);
        if ( ! confirm($(this).attr('data-confirm')) ) return;
        $.post(addSession('<?= $TOOL_ROOT ?>'+'/api/'+$(this).attr('data-endpoint')))
            .done( function(data) {
                console.log('start', data_class);
                $('.'+data_class).toggle();
                console.log('end', data_class);
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
});
</script>
<?php
    }
}
