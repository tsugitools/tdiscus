<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\UI\SettingsForm;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

// View
$OUTPUT->header();
$templates = "load_templates/".$USER->locale;
?>
<link rel=import href="<?= $templates ?>" id="handlebars-templates">
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
<div id="threads-div"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"></div>
<?php
$OUTPUT->footerStart();
?>
<script>
var _TDISCUS = {
    grade: <?= json_encode(Settings::linkGet('grade')) ?>,
    multi: <?= json_encode(Settings::linkGet('multi')) ?>,
};
$(document).ready(function(){
    $.getJSON('<?= addSession('threads') ?>', function(threads) {
        window.console && console.log(threads);
        context = { 
            'tsugi' : _TSUGI,
            'tdiscus' : _TDISCUS,
            'threads' : threads
        };
        tsugiHandlebarsToDiv('threads-div', 'nothreads', context);
    }).fail( function() { alert('getJSON fail'); } );
});
</script>
<?php
$OUTPUT->footerEnd();
