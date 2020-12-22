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

    public static function search_box() {
        $searchvalue = U::get($_GET,'search') ? 'value="'.htmlentities(U::get($_GET,'search')).'" ' : "";
?>
<div style="float:right">
<!-- https://www.w3schools.com/howto/howto_css_search_button.asp -->
<form>
  <input type="text" placeholder="Search.." name="search"
  <?= $searchvalue ?>
  >
  <button type="submit"><i class="fa fa-search"></i></button>
  <a href="<?= $TOOL_ROOT ?>"><i class="fa fa-undo"></i></a>
</form>
</div>
<?php
    }

}
