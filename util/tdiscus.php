<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Tdiscus {

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
        $selectedtop = $sortvalue == "top" ? 'selected="selected"' : "";
        $selectedlatest = $sortvalue == "latest" ? 'selected="selected"' : "";
        $selectedunanswered = $sortvalue == "unanswered" ? 'selected="selected"' : "";
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
  <input type="text" placeholder="Search.." name="search"
  <?= $searchvalue ?>
  >
  <button type="submit"><i class="fa fa-search"></i></button>
  <a href="<?= $TOOL_ROOT ?>"><i class="fa fa-undo"></i></a>
</div>
<?php
        echo("</form></div>\n");
    }

    public static function add_comment() {
?>
<div id="tdiscus-add-comment-div" class="tdiscus-add-comment-container" title="<?= __("Comment") ?>" >
<form id="tdiscus-add-comment-form" method="post">
<p>
<input type="text" name="comment" class="form-control">
</p>
<p>
<input type="submit" id="tdiscus-add-comment-submit" name="submit" value="<?= __('Comment') ?>" >
</p>
</form>
</div>
<?php
    }

}
