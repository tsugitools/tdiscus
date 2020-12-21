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
        self::load_templates();
        self::setup_tdiscuss();
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

    public static function load_templates() {
        global $TOOL_ROOT, $USER;
        echo('<link rel=import href="'.$TOOL_ROOT."/load_templates/".$USER->locale.'">'."\n");
    }

    public static function main_div() {
        global $OUTPUT;
        echo('<div id="main-div"><img src="'.$OUTPUT->getSpinnerUrl().'"></div>'."\n");
    }

    public static function load_ckeditor() {
        global $CFG;
        echo('<script src="'.$CFG->staticroot.'/util/ckeditor_4.8.0/ckeditor.js"></script>'."\n");
    }

    public static function load_xss() {
        global $CFG;
?>
<script src="<?= $CFG->staticroot ?>/util/js-xss/dist/xss.js"></script>

<script>
// Set up XSS processing
var whiteList = filterXSS.getDefaultWhiteList();
console.log(whiteList);
whiteList.div = ['style'];
whiteList.span = ['style'];
var options = {
  whiteList: whiteList,
  stripIgnoreTagBody: ["script"] // the script tag is a special case, we need
}; 
var TsugiXSS = new filterXSS.FilterXSS(options);
console.log(TsugiXSS);
</script>
<?php
    }

    public static function setup_tdiscuss()
    {
        global $TOOL_ROOT;
?>
<script>
var _TDISCUS = {
    tool_root: "<?= $TOOL_ROOT ?>",
    grade: <?= json_encode(Settings::linkGet('grade')) ?>,
    multi: <?= json_encode(Settings::linkGet('multi')) ?>,
    nested: <?= json_encode(Settings::linkGet('nested')) ?>,
};
</script>
<?php
    }

    public static function render($template_name, $context = false) {
?>
<script>
$(document).ready(function(){
    // Nothing in particular to do here...
});
var _CONTEXT = <?= json_encode($context) ?>;
window.addEventListener('WebComponentsReady', function() {
    tsugiHandlebarsToDiv('main-div', '<?= $template_name ?>', { 'tsugi' : _TSUGI, 'tdiscus' : _TDISCUS, 'context' : _CONTEXT });
});
</script>
<?php
    }

}
