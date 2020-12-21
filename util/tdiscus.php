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

}
