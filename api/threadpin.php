<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tdiscus\Threads;

require_once "../util/threads.php";

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

$THREADS = new Threads();

$rest_path = U::rest_path();

$retval = $THREADS->threadSetPin($rest_path->action, 1);
if ( is_string($retval) ) {
    Net::send400($retval);
    return;
}
