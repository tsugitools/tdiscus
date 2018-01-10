<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\Net;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

// Model
$p = $CFG->dbprefix;

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<h1>Teaching and Threading...</h1>
<p>
Some cool stuff will go here.
</p>
<p>
<a href="jsontest">JSON Testing</a>
</p>
<?php
$OUTPUT->footerStart();
$OUTPUT->templateInclude(array('attend'));
$OUTPUT->footerEnd();
