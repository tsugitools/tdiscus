<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Threads {

    public static function loadThread($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $row = $PDOX->rowDie("SELECT *, COALESCE(T.updated_at, T.created_at) AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            WHERE link_id = :LID AND thread_id = :TID",
            array(':LID' => $TSUGI_LAUNCH->link->id,  ':UID' => $TSUGI_LAUNCH->user->id, ':TID' => $thread_id)
        );
        return $row;
    }

    public static function loadThreadForUpdate($thread_id) {

        $row = self::loadThread($thread_id);
        if ( $row['owned'] != 1 ) return null;

        return $row;
    }

    public static function replaceThread($thread_id, $data=false) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        if ( $data == null ) $data = $_POST;
        $title = U::get($data, 'title');
        $body = U::get($data, 'body');

        if ( strlen($title) < 1 || strlen($body) < 1 ) {
            return __('Title and body are required');
        }

        // TODO: Purify pre-update
        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread SET
            body = :BODY , title= :TITLE, updated_at = NOW()
            WHERE link_id = :LID AND thread_id = :TID AND user_id = :UID",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':TID' => $thread_id,
                ':TITLE' => $title,
                ':BODY' => $body
            )
        );
    }

    public static function deleteThread($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_thread 
            WHERE link_id = :LID AND thread_id = :TID AND user_id = :UID",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':TID' => $thread_id,
            )
        );

        if (isset($stmt->rowCount)) {
            if ( $stmt->rowCount == 0 ) {
                return __('Unable to delete thread');
            }
        }
        return $stmt;
    }

    public static function threads() {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        $rows = $PDOX->allRowsDie("SELECT *, COALESCE(T.updated_at, T.created_at) AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned,
            (COALESCE(upvote, 0)-COALESCE(downvote, 0)) AS netvote
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            WHERE link_id = :LID ORDER BY pin DESC, rank DESC, modified_at DESC, netvote DESC",
            array(':UID' => $TSUGI_LAUNCH->user->id, ':LID' => $TSUGI_LAUNCH->link->id)
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
        $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
            (link_id, user_id, title, body) VALUES
            (:LID, :UID, :TITLE, :BODY)",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':TITLE' => $title,
                ':BODY' => $body
            )
        );

        return intval($PDOX->lastInsertId());
    }

}
