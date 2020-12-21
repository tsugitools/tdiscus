<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Threads {

    public static function threads() {
        global $PDOX, $LINK, $CFG;
        $rows = $PDOX->allRowsDie("SELECT *
            FROM {$CFG->dbprefix}tdiscus_thread
            WHERE link_id = :LI ORDER BY pin, rank, created_at DESC",
            array(':LI' => $LINK->id)
        );
        return $rows;
    }
}
