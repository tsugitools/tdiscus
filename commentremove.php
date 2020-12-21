<?php
require_once "../config.php";
require_once "util/tdiscus.php";
require_once "util/threads.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tdiscus\Tdiscus;
use \Tdiscus\Threads;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

$THREADS = new Threads();

$rest_path = U::rest_path();

$comment_id = null;
$old_comment = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $comment_id = intval($rest_path->action);
    $old_comment = $THREADS->commentLoadForUpdate($comment_id);
}

if ( ! $old_comment ) {
    $_SESSION['error'] = __('Could not load comment');
    header( 'Location: '.addSession($TOOL_ROOT) ) ;
    return;
}

$come_back = $TOOL_ROOT . '/commentremove/' . $thread_id;

if ( count($_POST) > 0 ) {
    // With the successful LoadForUpdate above, we can use the Dao
    $retval = $THREADS->commentDeleteDao($comment_id);
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header( 'Location: '.addSession($TOOL_ROOT . '/' . $come_back) ) ;
        return;
    }

    $_SESSION['success'] = __('Thread deleted');
    header( 'Location: '.addSession($TOOL_ROOT) ) ;
    return;
}

Tdiscus::header();

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<div id="delete-comment-div" title="<?= __("Delete comment") ?>" >
<form id="delete-comment-form" method="post">
<p>
<input type="submit" id="delete-comment-submit" value="<?= __('Delete') ?>" >
<input type="submit" id="delete-comment-cancel" value="<?= __('Cancel') ?>"
onclick='window.location.href="<?= addSession($TOOL_ROOT) ?>";return false;'
>
</p>
<p><?= __("Comment:") ?> <br/>
<?php 
echo('<b>'.htmlentities($old_comment['comment']).'</b></br>');
?>
</p>
</form>
</div>
<?php

Tdiscus::footerStart();
?>
<script>
$(document).ready( function () {
    CKEDITOR.replace( 'editor' );
});
</script>
<?php
Tdiscus::footerEnd();
