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

$thread_locked = intval($thread['locked']) && ! $LAUNCH->user->instructor;

if ( ! $thread ) {
    $_SESSION['error'] = __('Could not load thread');
    header( 'Location: '.addSession($TOOL_ROOT) ) ;
    return;
}

$come_back = $TOOL_ROOT . '/thread/' . $thread_id;
$all_done = $TOOL_ROOT;
$discussion_title = strlen(Settings::linkget('title')) > 0 ? Settings::linkget('title') : $LAUNCH->link->title;

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

$retval = $THREADS->comments($thread_id);
$comments = $retval->rows;

Tdiscus::header();

$pagesize = intval(U::get($_GET, 'pagesize', Threads::default_page_size));
$start = intval(U::get($_GET, 'start', 0));
$page_base = $come_back;

// Does not include start
$copyparms = array('search', 'sort', 'pagesize');
foreach ( $copyparms as $parm ) {
    $val = U::get($_GET, $parm);
    if ( strlen($val) == 0 ) continue;
    $page_base = U::add_url_parm($page_base, $parm, $val);
}

$menu = false;
// $menu->addLeft(__('All Threads'), $TOOL_ROOT);

$commenttop = (Settings::linkGet('commenttop') == 1);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$sortable = $THREADS->commentsSortableBy();
$OUTPUT->flashMessages();
echo('<div class="tdiscus-thread-container">'."\n");
echo('<p>');
echo('<a style="float: right;" href="'.$TOOL_ROOT.'"><i class="fa fa-home" title="'.__('All Threads').'"></i> '.__('All Threads').'</a></p>');
echo('<span class="tdiscus-thread-title"><a href="'.$page_base.'"'.($thread['hidden'] ? ' style="text-decoration: line-through;"' : '').'>'.htmlentities($thread['title']).'</a>');
echo("</span></p>\n");
?>
<p class="tdiscus-thread-info">
<?= $thread['displayname'] ?>
 -
<time class="timeago" datetime="<?= $thread['modified_at'] ?>"><?= $thread['modified_at'] ?></time>
<?php if ( $thread['edited'] == 1 ) {
    echo(" - ".__("edited"));
} ?>
</p>
<p class="tdiscus-thread-body">
<?= $purifier->purify($thread['body']) ?>
</p>
<p>
<!--
<?= $thread['netvote'] ?> Upvotes
<i class="fa fa-arrow-up"></i>
<a href="#reply"
onclick="document.querySelector('#tdiscus-add-comment-div').scrollIntoView({ behavior: 'smooth' });"
><?= __('Reply') ?>
<i class="fa fa-reply-all"></i>
</a>
-->
</div>
<div class="tdiscus-comments-container">
<div class="tdiscus-comments-sort">
<?php
Tdiscus::search_box($sortable);
if ( $commenttop && ! $thread_locked) Tdiscus::add_comment($thread_id);
?>
</div>
<div class="tdiscus-comments-list">

<?php
if ( count($comments) < 1 ) {
    echo("<p>".__('No replies yet')."</p>\n");
} else {
    foreach($comments as $comment ) {
        $locked = $comment['locked'];
        $hidden = $comment['hidden'];
        $comment_id = $comment['comment_id'];

        if ( $LAUNCH->user->instructor ) {
                Tdiscus::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
        }

        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
        } else {
            echo('<span '.($locked == 0 ? 'style="display:none;"' : '').'><i class="fa fa-lock fa-rotate-270" style="color: orange;"></i></span>');
        }
?>
  <b><?= htmlentities($comment['displayname']) ?></b>
  <time class="timeago" datetime="<?= $comment['modified_at'] ?>"><?= $comment['modified_at'] ?></time>
  <?php if ( $comment['owned'] || $LAUNCH->user->instructor ) { ?>
    <a href="<?= $TOOL_ROOT ?>/commentform/<?= $comment['comment_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/commentremove/<?= $comment['comment_id'] ?>"><i class="fa fa-trash"></i></a>
  <?php } ?>
<?php
        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
        }
        if ( $LAUNCH->user->instructor ) {
            Tdiscus::renderBooleanSwitch('comment', $comment_id, 'locked', 'lock', $locked, 1, 'fa-lock');
        }
?>
  <br/>
  <div style="padding-left: 10px;<?= ($hidden ? ' text-decoration: line-through;' : '') ?>"><?= htmlentities($comment['comment']) ?></div>
  </p>
<?php
        if ( Settings::linkGet('maxdepth') > 0 ) {
            Tdiscus::add_sub_comment($comment_id, $thread_id, 1);
        }
    }
}
?>
</div> <!-- tdiscus-comments-list -->
</div> <!-- tdiscus-comments-container -->
<?php

  Tdiscus::paginator($page_base, $start, $pagesize, $retval->total);

if ( ! $commenttop && ! $thread_locked) Tdiscus::add_comment();

Tdiscus::footerStart();
Tdiscus::renderBooleanScript();
Tdiscus::footerEnd();
