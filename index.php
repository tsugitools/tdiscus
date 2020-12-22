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

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index') ) ;
    return;
}

$THREADS = new Threads();

Tdiscus::header();

$OUTPUT->bodyStart();

echo('<span style="float: right; margin-bottom: 10px;">');
if ( $USER->instructor ) {
    if ( $CFG->launchactivity ) {
        echo('<a href="jsontest" target="_blank" class="btn btn-default">Test JSON</a> ');
        echo('<a href="analytics" class="btn btn-default">Launches</a> ');
    }
}
SettingsForm::button();
echo('</span>');

SettingsForm::start();
SettingsForm::checkbox('grade',__('Give a 100% grade for a student making a post or a comment.'));
SettingsForm::checkbox('multi',__('Allow more than one thread.'));
SettingsForm::checkbox('studentthread',__('Allow learners to create a thread.'));
SettingsForm::checkbox('nested',__('Allow nested comments.'));
SettingsForm::number('lockminutes',__('Number of minutes before posts are locked.'));
SettingsForm::dueDate();
SettingsForm::end();

echo("<h1>".htmlentities($LAUNCH->link->title)."</h1>\n");
$OUTPUT->flashMessages();

$threads = $THREADS->threads();

?>
<p>
<form id="main-form">
<input type="text" name="search" id="search-text">
<input type="submit" id="search" value="<?= __( 'Search') ?>">
<input type="submit" id="clear-search" value="<?= __( 'Clear Search') ?>">
<input type="submit" id="add-thread" value="<?= __( '+ Thread') ?>"
onclick='window.location.href="<?= U::addSession($TOOL_ROOT.'/threadform') ?>";return false;'>
</form>
</p>
<?php
if ( count($threads) < 1 ) {
    echo("<p>".__('No threads')."</p>\n");
} else {
    foreach($threads as $thread ) {
?>
  <p><a href="<?= $TOOL_ROOT.'/thread/'.$thread['thread_id'] ?>">
  <b><?= htmlentities($thread['title']) ?></b></a>
  <?php if ( $thread['comment_count'] > 0 ) { ?>
  <span class="threadcount"><?= $thread['comment_count'] ?> comments </span>
  <?php } ?>
  (Updated: <time class="timeago" datetime="<?= $thread['modified_at'] ?>"><?= $thread['modified_at'] ?></time>
   Views: <?= $thread['views'] ?>
<?php if ( $thread['staffread'] > 0 ) echo(" -Staff Read- "); ?>
<?php if ( $thread['staffanswer'] > 0 ) echo(" -Staff Answer- "); ?>
)
  <?php if ( $thread['owned'] || $LAUNCH->user->instructor ) { ?>
    <a href="<?= $TOOL_ROOT ?>/threadform/<?= $thread['thread_id'] ?>"><i class="fa fa-pencil"></i></a>
    <a href="<?= $TOOL_ROOT ?>/threadremove/<?= $thread['thread_id'] ?>"><i class="fa fa-trash"></i></a>
  <?php } ?>
  <br/>
  <div style="padding-left: 10px;"><?= $purifier->purify($thread['body']) ?></div>
  </p>
<?php 
    }
}

Tdiscus::footerStart();

Tdiscus::footerEnd();
