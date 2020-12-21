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
        $rows = $PDOX->allRowsDie("SELECT *, COALESCE(updated_at, created_at) AS modified_at
            FROM {$CFG->dbprefix}tdiscus_thread
            WHERE link_id = :LI ORDER BY pin, rank, modified_at DESC",
            array(':LI' => $LINK->id)
        );
        return $rows;
    }

    public static function addThread($data=null) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        if ( $data == null ) $data = $_POST;
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');

        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        // TODO: Purify pre-insert?
        $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
            (link_id, user_id, title, body) VALUES
            (:LI, :UI, :TITLE, :BODY)",
            array(
                ':LI' => $TSUGI_LAUNCH->link->id,
                ':UI' => $TSUGI_LAUNCH->user->id,
                ':TITLE' => $title,
                ':BODY' => $body
            )
        );

        return intval($PDOX->lastInsertId());
    }

}
