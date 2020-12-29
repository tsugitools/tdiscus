<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tdiscus\Threads;

require_once "../util/threads.php";

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

$THREADS = new Threads();

$thread_id = U::get($_POST, 'thread_id');
$comment_id = U::get($_POST, 'comment_id');
$comment = U::get($_POST, 'comment');

$retval = $THREADS->commentAddSubComment($thread_id, $comment_id, $comment);
echo("<pre>\n");print_r($retval);echo("</pre>\n");
if ( is_string($retval) ) {
    Net::send400($retval);
    return;
}

$newcomment = $THREADS->commentLoad($retval);
echo("<pre>\n");print_r($newcomment);echo("</pre>\n");

