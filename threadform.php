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

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);


$THREADS = new Threads();

if ( count($_POST) > 0 ) {
    $title = U::get($_POST, 'title');
    $body = U::get($_POST, 'body');

    if ( strlen($title) < 1 || strlen($body) < 1 ) {
        $_SESSION['error'] = __('Title and body are required');
        header( 'Location: '.addSession('threadform') ) ;
        return;
    }

    // TODO: Purify pre-insert?
    $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
        (link_id, user_id, title, body) VALUES 
        (:LI, :UI, :TITLE, :BODY)",
        array(
            ':LI' => $LAUNCH->link->id,
            ':UI' => $LAUNCH->user->id,
            ':TITLE' => $title,
            ':BODY' => $body
        )
    );

    $_SESSION['success'] = __('Thread added');
    header( 'Location: '.addSession('index') ) ;
    return;
}

Tdiscus::header();

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<div id="add-thread-div" title="<?= __("New Thread") ?>" >
<form id="add-thread-form" method="post">
<p>
<input type="submit" id="add-thread-submit" value="<?= __('+ Thread') ?>" >
<input type="submit" id="add-thread-cancel" value="<?= __('Cancel') ?>"
onclick='window.location.href="<?= addSession($TOOL_ROOT) ?>";return false;'
>
<span id="add-thread-feedback"></span>
</p>
<p><?= __("Title:") ?> <br/>
<input type="text" name="title" class="form-control">
</p>
<p><?= __("Description:") ?> <br/>
<textarea id="editor" name="body" class="form-control">
</textarea>
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
