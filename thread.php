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

$rest_path = U::rest_path();

$thread_id = null;
$old_thread = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $thread_id = intval($rest_path->action);
    $old_thread = $THREADS->threadLoadMarkRead($thread_id);
}

if ( ! $old_thread ) {
    $_SESSION['error'] = __('Could not load thread');
    header( 'Location: '.addSession($TOOL_ROOT) ) ;
    return;
}

$come_back = $TOOL_ROOT . '/thread/' . $thread_id;
$all_done = $TOOL_ROOT;

if ( count($_POST) > 0 ) {
    $retval = $THREADS->commentInsertDao($thread_id, U::get($_POST, 'comment') );
    if ( is_string($retval) ) {
        $_SESSION['error'] = $retval;
        header( 'Location: '.addSession($come_back) ) ;
        return;
    }

    header( 'Location: '.addSession($come_back) ) ;
    return;
}

$comments = $THREADS->comments($thread_id);

Tdiscus::header();

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('All Threads'), $TOOL_ROOT);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
Tdiscus::search_box();
$OUTPUT->flashMessages();
$threadpin = $TOOL_ROOT. '/threadpin/' . $old_thread['thread_id'];
  if ( $old_thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <span class="tsugi-thread-owned-menu">
    <a href="<?= $TOOL_ROOT ?>/threadform/<?= $old_thread['thread_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/threadremove/<?= $old_thread['thread_id'] ?>"><i class="fa fa-trash"></i></a>
    <a href="#" title="<?= __("Pin Thread") ?>"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame',
     '<?= addSession($threadpin) ?>', _TSUGI.spinnerUrl, true); return false;" >
     <i class="fa fa-thumb-tack"></i>
  </a>
    </span>

<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <img src="<?= $OUTPUT->getSpinnerUrl() ?>" id="iframe-spinner"><br/>
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame"
    onload="document.getElementById('iframe-spinner').style.display='none';">
   </iframe>
</div>
  <?php } 

echo('<div class="tsugi-thread-title">'.htmlentities($old_thread['title']).'</div>');
?>
</p>
<?= $purifier->purify($old_thread['body']) ?>
</p>

<div id="add-comment-div" title="<?= __("New Comment") ?>" >
<form id="add-comment-form" method="post">
<p>
<span id="add-comment-feedback"></span>
<input type="text" name="comment" class="form-control">
</p>
<p>
<input type="submit" id="add-comment-submit" name="submit" value="<?= __('Comment') ?>" >
</p>
</form>
</div>

<?php
if ( count($comments) < 1 ) {
    echo("<p>".__('No comments')."</p>\n");
} else {
    foreach($comments as $comment ) {
?>
  <b><?= htmlentities($comment['displayname']) ?></b>
  (Modified: <time class="timeago" datetime="<?= $comment['modified_at'] ?>"><?= $comment['modified_at'] ?></time>)
  <?php if ( $comment['owned'] || $LAUNCH->user->instructor ) { ?>
    <a href="<?= $TOOL_ROOT ?>/commentform/<?= $comment['comment_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/commentremove/<?= $comment['comment_id'] ?>"><i class="fa fa-trash"></i></a>
  <?php } ?>
  <br/>
  <div style="padding-left: 10px;"><?= htmlentities($comment['comment']) ?></div>
  </p>
<?php
    }
}

Tdiscus::footerStart();

Tdiscus::footerEnd();
