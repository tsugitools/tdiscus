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

echo("<!-- Handlebars version of the tool -->\n");
echo('<div id="attend-div"><img src="'.$OUTPUT->getSpinnerUrl().'"></div>'."\n");

$OUTPUT->footerStart();
$OUTPUT->templateInclude(array('attend'));

if ( $USER->instructor ) {
?>
<script>
$(document).ready(function(){
    $.getJSON('<?= addSession('getrows.php') ?>', function(rows) {
        window.console && console.log(rows);
        context = { 'rows' : rows,
            'instructor' : true,
            'old_code' : '<?= $old_code ?>'
        };
        tsugiHandlebarsToDiv('attend-div', 'attend', context);
    }).fail( function() { alert('getJSON fail'); } );
});
</script>
<?php } else { ?>
<script>
$(document).ready(function(){
    tsugiHandlebarsToDiv('attend-div', 'attend', {});
});
</script>
<?php
} // End $USER->instructor
$OUTPUT->footerEnd();
