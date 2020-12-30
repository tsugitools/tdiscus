<?php
require_once "../config.php";
require_once "util/tdiscus.php";
require_once "util/threads.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;
use \Tdiscus\Tdiscus;
use \Tdiscus\Threads;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

$THREADS = new Threads();

Tdiscus::header();

$pagesize = intval(U::get($_GET, 'pagesize', Threads::default_page_size));
$start = intval(U::get($_GET, 'start', 0));
$comeback = $TOOL_ROOT.'/';

// Does not include start
$copyparms = array('search', 'sort', 'pagesize');
foreach ( $copyparms as $parm ) {
    $val = U::get($_GET, $parm);
    if ( strlen($val) == 0 ) continue;
    $comeback = U::add_url_parm($comeback, $parm, $val);
}


$menu = false;
if ( $USER->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    if ( $CFG->launchactivity ) {
        $menu->addRight('Analytics', 'analytics');
    }
    $menu->addRight('Settings', '#', /* push */ false, SettingsForm::attr());
}

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

$dicussion_title = strlen(Settings::linkget('title')) > 0 ? Settings::linkget('title') : $LAUNCH->link->title;

echo('<div>');
echo('<span class="tdiscus-threads-title">');
echo('<a href="'.$comeback.'">');
echo(htmlentities($dicussion_title));
echo('</a></span>' );
echo('<a style="float: right;" href="'.$TOOL_ROOT.'/threadform'.'">');
echo('<i class="fa fa-plus"></i> ');
echo(__('Add Thread'));
echo('</a>');
echo("</div>\n");

SettingsForm::start();
SettingsForm::text('title',__('Thread title override.'));
// SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
// SettingsForm::checkbox('studentthread',__('Allow learners to create a thread.'));
SettingsForm::checkbox('commenttop',__('Put comment box before comments in thread display.'));
// SettingsForm::number('lockminutes',__('Number of minutes before posts are locked.'));
SettingsForm::number('maxdepth',__('Allowed depth of nested comments. Set to < 1 for no nested comments.'));
SettingsForm::dueDate();
SettingsForm::end();

$OUTPUT->flashMessages();

$retval = $THREADS->threads();
$threads = $retval->rows;


$sortable = $THREADS->threadsSortableBy();
Tdiscus::search_box($sortable);

if ( count($threads) < 1 ) {
    echo("<p>".__('No threads')."</p>\n");
} else {
    echo('<ul class="tdiscus-threads-list">');
    echo('<!-- Total: '.$retval->total." next=".$retval->next."-->\n");
    foreach($threads as $thread ) {
        $pin = $thread['pin'];
        $locked = $thread['locked'];
        $hidden = $thread['hidden'];
        $thread_id = $thread['thread_id'];
        $subscribe = $thread['subscribe'];
        $favorite = $thread['favorite'];
?>
  <li class="tdiscus-thread-item">
  <div class="tdiscus-thread-item-left">
  <p class="tdiscus-thread-item-title">
  <?php
    if ( $LAUNCH->user->instructor ) {
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 0, 'fa-thumbtack fa-rotate-270', 'orange');
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 0, 'fa-eye-slash', 'orange');
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 0, 'fa-lock', 'orange');
        Tdiscus::renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 0, 'fa-star', 'green');
        // Tdiscus::renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 0, 'fa-envelope', 'green');
    } else {
        echo('<span '.($pin == 0 ? 'style="display:none;"' : '').'><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></span>');
        echo('<span '.($locked == 0 ? 'style="display:none;"' : '').'><i class="fa fa-lock fa-rotate-270" style="color: orange;"></i></span>');
    }
?>
  <a href="<?= $TOOL_ROOT.'/thread/'.$thread['thread_id'] ?>">
  <b<?= ($hidden ? ' style="text-decoration: line-through;"' : '') ?>><?= htmlentities($thread['title']) ?></b></a>
<?php if ( $thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <span class="tdiscus-thread-owned-menu">
    <a href="<?= $TOOL_ROOT ?>/threadform/<?= $thread['thread_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/threadremove/<?= $thread['thread_id'] ?>"><i class="fa fa-trash"></i></a>
  <?php
    if ( $LAUNCH->user->instructor ) {
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'pin', 'pin', $pin, 1, 'fa-thumbtack');
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'hidden', 'hide', $hidden, 1, 'fa-eye-slash');
        Tdiscus::renderBooleanSwitch('thread', $thread_id, 'locked', 'lock', $locked, 1, 'fa-lock');
        Tdiscus::renderBooleanSwitch('threaduser', $thread_id, 'favorite', 'favorite', $favorite, 1, 'fa-star');
        // Tdiscus::renderBooleanSwitch('threaduser', $thread_id, 'subscribe', 'subscribe', $subscribe, 1, 'fa-envelope');
    }
?>
    </span>
<?php } ?>
</p>
<?php
    if ( $thread['staffcreate'] > 0 ) {
        echo('<span class="tdiscus-staff-created">Staff Created</span>');
        echo(" ".__("Created by")." ".htmlentities($thread['displayname']));
        echo(' - '.__("last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at'].'</time>');
    } else {
        if ( $thread['staffread'] > 0 ) echo('<span class="tdiscus-staff-read">'.__('Staff Read')."</span>\n");
        if ( $thread['staffanswer'] > 0 ) echo('<span class="tdiscus-staff-answer">'.__('Staff Answer')."</span>\n");
        echo(__("Last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at']."</time>\n");
    }

?>
  </div>
  <div class="tdiscus-thread-item-right" >
<center>
   Views: <?= $thread['views'] ?><br/>
   Comments: <?= $thread['comments'] ?>
</center>
  </div>
  </li>
<?php
    }
  echo("</ul>");
  Tdiscus::paginator($comeback, $start, $pagesize, $retval->total);
}

Tdiscus::footerStart();
Tdiscus::renderBooleanScript();
Tdiscus::footerEnd();
