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
$OUTPUT->welcomeUserCourse();
?>
<div id="main-div"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"></div>
<?php

$OUTPUT->footerStart();
?>
<script src="<?= $CFG->staticroot ?>/util/ckeditor_4.8.0/ckeditor.js"></script>

<script>
var _TDISCUS = {
    grade: <?= json_encode(Settings::linkGet('grade')) ?>,
    multi: <?= json_encode(Settings::linkGet('multi')) ?>,
};
$(document).ready(function(){
    // Nothing in particular to do here...
});
window.addEventListener('WebComponentsReady', function() {
    tsugiHandlebarsToDiv('main-div', 'tdiscus-c-newthread', { 'tsugi' : _TSUGI, 'tdiscus' : _TDISCUS });
});
</script>
<?php
$OUTPUT->footerEnd();
