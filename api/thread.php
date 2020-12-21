<?php
if ( ! isset($CFG) ) return; // Don't allow direct calls

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

$rest_path = U::rest_path();
error_log("thread ".$rest_path->action);

$rows = $PDOX->rowDie("SELECT *
    FROM {$CFG->dbprefix}tdiscus_thread
     WHERE link_id = :LI AND thread_id = :TID",
     array(':LI' => $LAUNCH->link->id, ':TID' => $rest_path->action)
);

$OUTPUT->headerJson();
$OUTPUT->jsonOutput($rows);

