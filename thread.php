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
$thread = null;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $thread_id = intval($rest_path->action);
    $thread = $THREADS->threadLoadMarkRead($thread_id);
}

if ( ! $thread ) {
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
echo('<div class="tsugi-thread-container">'."\n");
echo('<p class="tsugi-thread-title">'.htmlentities($thread['title']).'</p>');
?>
<p class="tsugi-thread-info">
<?= $thread['displayname'] ?>
 -
<time class="timeago" datetime="<?= $thread['modified_at'] ?>"><?= $thread['modified_at'] ?></time>
</p>
<p class="tsugi-thread-body">
<?= $purifier->purify($thread['body']) ?>
</p>
<p>
<?= $thread['netvote'] ?> Upvotes
<i class="fa fa-arrow-up"></i>
<a href="#reply"
onclick="document.querySelector('#add-comment-div').scrollIntoView({ behavior: 'smooth' });"
><?= __('Reply') ?>
<i class="fa fa-reply-all"></i>
</a>
</div>

<?php
if ( count($comments) < 1 ) {
    echo("<p>".__('No replies yet')."</p>\n");
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
?>
<div id="add-comment-div" title="<?= __("Reply") ?>" >
<form id="add-comment-form" method="post">
<p>
<span id="add-comment-feedback"></span>
<input type="text" name="comment" class="form-control">
</p>
<p>
<input type="submit" id="add-comment-submit" name="submit" value="<?= __('Reply') ?>" >
</p>
</form>
</div>
<?php

Tdiscus::footerStart();

Tdiscus::footerEnd();
