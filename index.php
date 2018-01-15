<?php
require_once "../config.php";

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

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
<link rel=import href="<?= $CFG->staticroot ?>/webcomponents/tsugi/hello-world3.html">
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
SettingsForm::dueDate();
SettingsForm::end();
?>
<h1>Teaching and Threading...</h1>
<div id="main-div"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"></div>
<?php
$OUTPUT->footerStart();
?>
<script>
var _TDISCUS = {
    grade: <?= json_encode(Settings::linkGet('grade')) ?>,
    multi: <?= json_encode(Settings::linkGet('multi')) ?>,
};
$(document).ready(function(){
    // Nothing in particular to do here...
});
window.addEventListener('WebComponentsReady', function() {
    tsugiHandlebarsToDiv('main-div', 'tdiscus-main', { 'tsugi' : _TSUGI, 'tdiscus' : _TDISCUS });
});
</script>
<?php
$OUTPUT->footerEnd();
