<?php

namespace Tdiscus;

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

global $TOOL_ROOT;

class Threads {

    public static function threadLoad($thread_id) {
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

    public static function threadLoadMarkRead($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        // This also makes sure we can see the thread_id
        $row = self::threadLoad($thread_id);
        if ( ! $row ) return $row;

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the row
        // is inserted as a new row, 2 if an existing row is updated, and 0 if an existing
        // row is set to its current value
        // https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
        $stmt = $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_read_thread
            (thread_id, user_id) VALUES
            (:TID, :UID)",
            array(
                ':TID' => $thread_id,
                ':UID' => $TSUGI_LAUNCH->user->id,
            )
        );

        $count = $stmt->rowCount();
        if ( $count > 0 ) {
            $staffread = "";
            if ( $TSUGI_LAUNCH->user->instructor ) $staffread = ", staffread=1";
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET views=views+1 $staffread
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                )
            );
       }

       return $row;
    }

    public static function threadLoadForUpdate($thread_id) {

        $row = self::threadLoad($thread_id);
        if ( $row['owned'] != 1 ) return null;

        return $row;
    }

    public static function threadUpdate($thread_id, $data=false) {
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

    public static function threadDelete($thread_id) {
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
        $rows = $PDOX->allRowsDie("SELECT T.thread_id AS thread_id, body, title,
            views, staffread, staffanswer,
            COALESCE(T.updated_at, T.created_at) AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            COUNT(C.thread_id) AS comment_count
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            LEFT JOIN {$CFG->dbprefix}tdiscus_comment AS C ON  C.thread_id = T.thread_id
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            WHERE link_id = :LID
            GROUP BY T.thread_id, C.thread_id
            ORDER BY T.pin DESC, T.rank DESC, modified_at DESC, netvote DESC",
            array(':UID' => $TSUGI_LAUNCH->user->id, ':LID' => $TSUGI_LAUNCH->link->id)
        );
        return $rows;
    }

    public static function threadInsert($data=null) {
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

    public static function comments($thread_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $comments = $PDOX->allRowsDie("SELECT comment_id, comment, displayname,
            C.updated_at AS updated_at, C.created_at AS created_at,
            COALESCE(C.updated_at, C.created_at) AS modified_at,
            CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_comment AS C
            JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
            WHERE T.link_id = :LI AND C.thread_id = :TID
            ORDER BY C.created_at DESC",
            array(
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':LI' => $TSUGI_LAUNCH->link->id,
                ':TID' => $thread_id
            )
        );
        return($comments);
    }

    public static function commentInsertDao($thread_id, $comment) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }

         $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_comment
            (thread_id, user_id, comment) VALUES
            (:TH, :UI, :COM)",
            array(
                ':TH' => $thread_id,
                ':UI' => $TSUGI_LAUNCH->user->id,
                ':COM' => $comment,
            )
        );

        $retval = intval($PDOX->lastInsertId());

        // Update the thread
        // TODO: ?? Count comments here?
        if ( $retval > 0 ) {
            $staffanswer = "";
            if ( $TSUGI_LAUNCH->user->instructor ) $staffanswer = ", staffanswer=1";

            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET updated_at=NOW() $staffanswer
                WHERE thread_id = :TID",
                array(
                    ':TID' => $thread_id,
                 )
            );
        }

        return $retval;
    }

    public static function commentLoad($comment_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $row = $PDOX->rowDie("SELECT *, COALESCE(C.updated_at, C.created_at) AS modified_at,
            CASE WHEN C.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_comment AS C
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = C.user_id
            JOIN {$CFG->dbprefix}tdiscus_thread AS T ON  C.thread_id = T.thread_id
            WHERE link_id = :LID AND comment_id = :CID",
            array(':LID' => $TSUGI_LAUNCH->link->id,  ':UID' => $TSUGI_LAUNCH->user->id, ':CID' => $comment_id)
        );
        return $row;
    }

    public static function commentLoadForUpdate($comment_id) {

        $row = self::commentLoad($comment_id);
        if ( $row['owned'] != 1 ) return null;

        return $row;
    }

    public static function commentDeleteDao($comment_id) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}tdiscus_comment
            WHERE comment_id = :CID",
            array(
                ':CID' => $comment_id,
            )
        );

        if (isset($stmt->rowCount)) {
            if ( $stmt->rowCount == 0 ) {
                return __('Unable to delete comment');
            }
        }
        return $stmt;
    }

    public static function commentUpdateDao($comment_id, $comment) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        if ( strlen($comment) < 1 ) {
            return __('Non-empty comment required');
        }


        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_comment SET
            comment = :COM, updated_at = NOW()
            WHERE comment_id = :TID",
            array(
                ':TID' => $comment_id,
                ':COM' => $comment,
            )
        );
    }

}
