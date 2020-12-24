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

echo('<div class="tdiscus-threads-title">'.htmlentities($title)."</div>\n");

SettingsForm::start();
SettingsForm::text('title',__('Thread title.'));
SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
SettingsForm::checkbox('multi',__('Allow more than one thread.'));
SettingsForm::checkbox('studentthread',__('Allow learners to create a thread.'));
SettingsForm::checkbox('nested',__('Allow nested comments.'));
SettingsForm::checkbox('commenttop',__('Put comment box before comments in thread display.'));
SettingsForm::number('lockminutes',__('Number of minutes before posts are locked.'));
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
    echo('<p>Total: '.$retval->total." next=".$retval->next."</p>\n");
    foreach($threads as $thread ) {
        $pin = $thread['pin'];
?>
  <li class="tdiscus-thread-item">
  <div class="tdiscus-thread-item-left">
  <p class="tdiscus-thread-item-title">
  <?php if ( $LAUNCH->user->instructor ) { ?>
  <a href="#" id="threadunpin_<?= $thread['thread_id'] ?>"
        data-endpoint="threadsetboolean/<?= $thread['thread_id'] ?>/pin/0"
        data-thread="<?= $thread['thread_id'] ?>"
        title="<?= __("Unpin Thread") ?>"
         <?= ($pin == 0 ? 'style="display:none;"' : '') ?>
        class="tdiscus-api-call"><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></a>
  <?php } else { ?>
        <span <?= ($pin == 0 ? 'style="display:none;"' : '') ?><i class="fa fa-thumbtack fa-rotate-270" style="color: orange;"></i></span>
  <?php } ?>
  <a href="<?= $TOOL_ROOT.'/thread/'.$thread['thread_id'] ?>">
  <b><?= htmlentities($thread['title']) ?></b></a>
<?php if ( $thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <span class="tdiscus-thread-owned-menu">
    <a href="<?= $TOOL_ROOT ?>/threadform/<?= $thread['thread_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/threadremove/<?= $thread['thread_id'] ?>"><i class="fa fa-trash"></i></a>
<?php if ( $LAUNCH->user->instructor ) { ?>
    <a href="#" id="threadpin_<?= $thread['thread_id'] ?>"
            data-endpoint="threadsetboolean/<?= $thread['thread_id'] ?>/pin/1"
            data-thread="<?= $thread['thread_id'] ?>"
            title="<?= __("Pin Thread") ?>"
           <?= ($pin == 1 ? 'style="display:none;"' : '') ?>
           class="tdiscus-api-call"><i class="fa fa-thumb-tack"></i></a>
<?php } ?>
    </span>
<?php } ?>
</p>
<?php
    if ( $thread['staffcreate'] > 0 ) {
        echo('<span class="tdiscus-staff-created">Staff Created</span>');
        echo(" Created by ".htmlentities($thread['displayname']));
        echo(' at <time class="timeago" datetime="'.$thread['created_at'].'">'.$thread['created_at'].'</time>');
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
}

Tdiscus::footerStart();
?>
<script>
$(document).ready( function() {
   $('.tdiscus-api-call').click(function(ev) {
        ev.preventDefault()
        var endpoint = $(this).attr('data-endpoint');
        console.log('endpoint', endpoint)
        if ( endpoint.includes('pin/0') ) {
            if ( ! confirm('<?= htmlentities(__('Do you want to unpin this thread?')) ?>') ) return;
        } else {
            if ( ! confirm('<?= htmlentities(__('Do you want to pin this thread?')) ?>') ) return;
        }
        var thread = $(this).attr('data-thread');
        $.post(addSession('<?= $TOOL_ROOT ?>'+'/api/'+endpoint))
            .done( function(data) {
                $('#threadpin_'+thread).toggle();
                $('#threadunpin_'+thread).toggle();
            })
            .error( function(xhr, status, error) {
                console.log(xhr);
                console.log(status);
                var message = '<?= htmlentities(__('Request Failed')) ?>';
                if ( error && error.length > 0 ) {
                    message = message + ": "+error.substring(0,40);
                }
                console.log(error);
                alert(message);
            });
    });
});
</script>
<?php

Tdiscus::footerEnd();
