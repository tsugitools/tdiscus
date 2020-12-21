<?php

require_once('../config.php');
require_once('util/tdiscus.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tdiscus\Tdiscus;

// The path to the folder that contains this file in URL space
// /py4e/mod/tdiscus

$TOOL_ROOT = dirname($_SERVER['SCRIPT_NAME']);
$rest_path = U::rest_path();
// echo("<pre>\n");print_r($rest_path);echo("</pre>");
// echo("<pre>\n");print_r($_SERVER);echo("</pre>");

if ( isset($rest_path->controller) ) {
    $template = 'tdiscus-c-'.$rest_path->controller;
    if ( file_exists('templates/'.$template.'.hbs') ) {
        $LAUNCH = LTIX::requireData();

        // View
        $OUTPUT->header();

        Tdiscus::load_templates();
        Tdiscus::setup_tdiscuss();

        $OUTPUT->bodyStart();
        $OUTPUT->flashMessages();

        Tdiscus::main_div();

        $OUTPUT->footerStart();
        Tdiscus::load_xss();
        Tdiscus::load_ckeditor();
        Tdiscus::render($template);

        $OUTPUT->footerEnd();
        return;
    }
}

$tool = new \Tsugi\Core\Tool();
$tool->run();
