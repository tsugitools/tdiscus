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

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Add Thread'), $TOOL_ROOT.'/threadform');
if ( $USER->instructor ) {
    if ( $CFG->launchactivity ) {
        $menu->addRight('Analytics', 'analytics');
    }
    $menu->addRight('Settings', '#', /* push */ false, SettingsForm::attr());
}

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

$title = strlen(Settings::linkget('title')) > 0 ? Settings::linkget('title') : $LAUNCH->link->title;

echo('<div class="tsugi-threads-title">'.htmlentities($title)."</div>\n");

SettingsForm::start();
SettingsForm::text('title',__('Thread title.'));
SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
SettingsForm::checkbox('multi',__('Allow more than one thread.'));
SettingsForm::checkbox('studentthread',__('Allow learners to create a thread.'));
SettingsForm::checkbox('nested',__('Allow nested comments.'));
SettingsForm::number('lockminutes',__('Number of minutes before posts are locked.'));
SettingsForm::dueDate();
SettingsForm::end();

$OUTPUT->flashMessages();

$threads = $THREADS->threads();


Tdiscus::search_box(true);

if ( count($threads) < 1 ) {
    echo("<p>".__('No threads')."</p>\n");
} else {
    echo('<ul class="tsugi-threads-list">');
    foreach($threads as $thread ) {
        $pin = $thread['pin'];
?>
  <li class="tsugi-thread-item">
  <div class="tsugi-thread-item-left">
  <p class="tsugi-thread-item-title">
  <?php if ( $LAUNCH->user->instructor ) { ?>
  <a href="#" id="threadunpin_<?= $thread['thread_id'] ?>" 
        endpoint="threadunpin/<?= $thread['thread_id'] ?>"
        thread="<?= $thread['thread_id'] ?>"
        title="<?= __("Unpin Thread") ?>" 
         <?= ($pin == 0 ? "style=display:none;" : "") ?> 
        class="tsugi-api-call"><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></a>
  <?php } else { ?>
        <span <?= ($pin == 0 ? "style=display:none;" : "") ?><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></span>
  <?php } ?>
  <a href="<?= $TOOL_ROOT.'/thread/'.$thread['thread_id'] ?>">
  <b><?= htmlentities($thread['title']) ?></b></a>
<?php if ( $thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <span class="tsugi-thread-owned-menu">
    <a href="<?= $TOOL_ROOT ?>/threadform/<?= $thread['thread_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/threadremove/<?= $thread['thread_id'] ?>"><i class="fa fa-trash"></i></a>
<?php if ( $LAUNCH->user->instructor ) { ?>
    <a href="#" id="threadpin_<?= $thread['thread_id'] ?>" 
            endpoint="threadpin/<?= $thread['thread_id'] ?>"
            thread="<?= $thread['thread_id'] ?>"
            title="<?= __("Pin Thread") ?>" 
           <?= ($pin == 1 ? "style=display:none;" : "") ?> 
           class="tsugi-api-call"><i class="fa fa-thumb-tack"></i></a>
<?php } ?>
    </span>
<?php } ?>
</p>
<?php
    if ( $thread['staffcreate'] > 0 ) {
        echo('<span class="tsugi-staff-created">Staff Created</span>');
        echo(" Created by ".htmlentities($thread['displayname']));
        echo(' at <time class="timeago" datetime="'.$thread['created_at'].'">'.$thread['created_at'].'</time>');
    } else {
        if ( $thread['staffread'] > 0 ) echo('<span class="tsugi-staff-read">'.__('Staff Read')."</span>\n");
        if ( $thread['staffanswer'] > 0 ) echo('<span class="tsugi-staff-answer">'.__('Staff Answer')."</span>\n");
        echo(__("Last post").' <time class="timeago" datetime="'.$thread['modified_at'].'">'.$thread['modified_at']."</time>\n");
    }

?>
  </div>
  <div class="tsugi-thread-item-right" >
<center>
   Views: <?= $thread['views'] ?><br/>
   Comments: <?= $thread['comments'] ?>
</center>
  </div>
  </li>
<?php 
    }
  echo("</ul>");
}
echo('</div">');

Tdiscus::footerStart();
?>
<script>
$(document).ready( function() {
   $('.tsugi-api-call').click(function(ev) {
        ev.preventDefault()
        var endpoint = $(this).attr('endpoint');
        var thread = $(this).attr('thread');
        console.log('endpoint', endpoint);
        $.post(addSession('<?= $TOOL_ROOT ?>'+'/api/'+endpoint))
            .done( function(data) {
                $('#threadpin_'+thread).toggle();
                $('#threadunpin_'+thread).toggle();
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
                alert('<?= htmlentities(__('Request Failed')) ?>');
            });
    });
});
</script>
<?php

Tdiscus::footerEnd();
