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
$templates = "loadtemplates/".$USER->locale;
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
$OUTPUT->exitButton();
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
SettingsForm::checkbox('multi',__('Allow more than one thread.'));
SettingsForm::dueDate();
SettingsForm::end();
?>
<h1>Teaching and Threading...</h1>
<p>Test in-line <?= U::__('Time to create the form') ?></p>
<!--
<template id="template">
  <style>
    ...
  </style>
  <div>
    <h1>Web Components</h1>
    <img src="http://webcomponents.org/img/logo.svg">
  </div>
  <script>
    alert('yada');
  </script>
</template>
-->
<div id="threads-div"><img src="<?= $OUTPUT->getSpinnerUrl() ?>"></div>
<div id="host"></div>
<!--
<script>
  var template = document.querySelector('#template');
  var clone = document.importNode(template.content, true);
  var host = document.querySelector('#host');
  alert('zap');
  host.appendChild(clone);
</script>
-->

<!--
<script>
    // var link = document.querySelector('link[rel="import"]');
    var link = document.querySelector('#test2');
    console.log(link);
    var content = link.import;

    // Grab DOM from warning.html's document.
    var el = content.querySelector('#nothreads');
    console.log(el);
    console.log(el.content);

  var compile = Handlebars.compile(el.content);
  var html = template(context);
  var render = document.querySelector('#host');
  alert('zap');
  host.appendChild(render);
    // document.body.appendChild(el.cloneNode(true));
  </script>
-->
<?php
$OUTPUT->footerStart();
// $OUTPUT->templateInclude(array('nothreads'));
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
