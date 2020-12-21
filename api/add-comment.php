<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

$rest_path = U::rest_path();
error_log("add-comment ".$rest_path->action);

if ( U::get($_POST, 'comment') && isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $retval = $PDOX->queryReturnError("INSERT INTO {$CFG->dbprefix}tdiscus_comment
        (thread_id, user_id, comment) VALUES 
        (:TH, :UI, :COM)",
        array(
            ':TH' => $rest_path->action,
            ':UI' => $LTI->user->id,
            ':COM' => $_POST['comment'],
        )
    );
    if ( $retval->success ) return;
    Net::send400('Not inserted');
    return;
}

Net::send400('Missing Data');
