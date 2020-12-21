<?php
if ( ! isset($CFG) ) return; // Don't allow direct calls

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

$rest_path = U::rest_path();
error_log("thread ".$rest_path->action);

$retval = new \stdClass();

$row = $PDOX->rowDie("SELECT *
    FROM {$CFG->dbprefix}tdiscus_thread
     WHERE link_id = :LI AND thread_id = :TID",
     array(':LI' => $LAUNCH->link->id, ':TID' => $rest_path->action)
);

$comments = $PDOX->allRowsDie("SELECT comment, C.updated_at AS updated_at, 
    displayname, C.created_at AS created_at, 
     CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
     FROM {$CFG->dbprefix}tdiscus_comment AS C
     JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
     JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
     WHERE T.link_id = :LI AND C.thread_id = :TID
     ORDER BY C.created_at DESC",
     array(
        ':UID' => $LAUNCH->user->id, 
        ':LI' => $LAUNCH->link->id, 
        ':TID' => $rest_path->action
    )
);

for($i=0;$i<count($comments);$i++) {
    $comments[$i]['owned'] = $comments[$i]['owned'] == "1";
    if ( $comments[$i]['owned'] ) {
        $comments[$i]['edit'] = true;
        $comments[$i]['delete'] = true;
    }
    if ( $comments[$i]['updated_at'] == null ) $comments[$i]['updated_at'] = $comments[$i]['created_at'];
}

$retval->thread = $row;
$retval->comments = $comments;

$OUTPUT->headerJson();
$OUTPUT->jsonOutput($retval);

