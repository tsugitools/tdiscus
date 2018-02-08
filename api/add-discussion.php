<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

if ( U::get($_POST, 'title') && U::get($_POST, 'body') ) {
    $retval = $PDOX->queryReturnError("INSERT INTO {$CFG->dbprefix}tdiscus_thread
        (link_id, title, body) VALUES 
        (:LI, :TITLE, :BODY)",
        array(
            ':LI' => $LINK->id,
            ':TITLE' => $_POST['title'],
            ':BODY' => $_POST['body']
        )
    );
    if ( $retval->success ) return;
    Net::send400('Not inserted');
    return;
}

Net::send400('Missing Data');
