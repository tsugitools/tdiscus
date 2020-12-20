<?php
require_once "../config.php";

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

// View
$OUTPUT->header();
?>
<link rel=import href="load_templates/<?= $USER->locale ?>">
<?php
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
?>
<div id="main-div"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"></div>
<?php

$OUTPUT->footerStart();
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
var _TDISCUS = {
    grade: <?= json_encode(Settings::linkGet('grade')) ?>,
    multi: <?= json_encode(Settings::linkGet('multi')) ?>,
    nested: <?= json_encode(Settings::linkGet('nested')) ?>,
};
$(document).ready(function(){
    // Nothing in particular to do here...
});
window.addEventListener('WebComponentsReady', function() {
    tsugiHandlebarsToDiv('main-div', 'tdiscus-c-main', { 'tsugi' : _TSUGI, 'tdiscus' : _TDISCUS });
});
</script>
<?php
$OUTPUT->footerEnd();
