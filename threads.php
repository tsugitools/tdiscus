<?php
if ( ! isset($CFG) ) return; // Don't allow direct calls

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

$rows = $PDOX->allRowsDie("SELECT *
    FROM {$CFG->dbprefix}tdiscus_thread
     WHERE link_id = :LI ORDER BY pin, rank, updated_at DESC",
     array(':LI' => $LINK->id)
);

$OUTPUT->headerJson();
$OUTPUT->jsonOutput($rows);

