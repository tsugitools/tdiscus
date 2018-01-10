<?php
if ( ! isset($CFG) ) return; // Don't allow direct calls

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->welcomeUserCourse();
?>
<h1>JSON Testing</h1>
<ul>
<li><a href="threads" target="_blank">threads</a>
</ul>
<?php
$OUTPUT->footer();
