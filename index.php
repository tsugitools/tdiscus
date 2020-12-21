<?php
require_once "../config.php";
require_once "tdiscus.php";

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;
use \Tdiscus\Tdiscus;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

global $TOOL_ROOT;
if ( ! isset($TOOL_ROOT) ) $TOOL_ROOT = dirname($_SERVER['SCRIPT_NAME']);
// View
$OUTPUT->header();
Tdiscus::load_templates();
Tdiscus::setup_tdiscuss();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

echo('<span style="float: right; margin-bottom: 10px;">');
if ( $USER->instructor ) {
    if ( $CFG->launchactivity ) {
        echo('<a href="jsontest" target="_blank" class="btn btn-default">Test JSON</a> ');
        echo('<a href="analytics" class="btn btn-default">Launches</a> ');
    }
}
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
SettingsForm::checkbox('multi',__('Allow more than one thread.'));
SettingsForm::checkbox('nested',__('Allow nested comments.'));
SettingsForm::dueDate();
SettingsForm::end();

$OUTPUT->welcomeUserCourse();
Tdiscus::main_div();
$OUTPUT->footerStart();

Tdiscus::load_xss();
Tdiscus::render('tdiscus-c-index');

$OUTPUT->footerEnd();
