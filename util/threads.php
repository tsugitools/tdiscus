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
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            LEFT JOIN {$CFG->dbprefix}tdiscus_user_user AS O ON O.user_id = :UID
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

        $stmt = $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}tdiscus_user_thread
            (thread_id, user_id, read_at) VALUES
            (:TID, :UID, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()",
            array(
                ':TID' => $thread_id,
                ':UID' => $TSUGI_LAUNCH->user->id,
            )
        );

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the row
        // is inserted as a new row, 2 if an existing row is updated, and 0 if an existing
        // row is set to its current value
        // https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
        $count = $stmt->rowCount();
        if ( $count == 1 ) {
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
        global $TSUGI_LAUNCH;
        $row = self::threadLoad($thread_id);
        if ( $row['owned'] > 0 || $TSUGI_LAUNCH->user->instructor ) return $row;
        return null;
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
            body = :BODY , title= :TITLE, updated_at = NOW(), edited=1
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

    public static function threadSetPin($thread_id, $pin) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;

        if ( ! is_numeric($thread_id) ) {
            return __('Incorrect or missing thread_id');
        }

        $old_thread = self::threadLoadForUpdate($thread_id);

        if ( ! is_array($old_thread) ) {
            return __('Could not load thread for update');
        }

        $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread SET
            pin=:PIN
            WHERE thread_id = :TID",
            array(
                ':PIN' => $pin,
                ':TID' => $thread_id,
            )
        );
    }

    /*
     * sort=latest|popular|unanswered|top|latest
     */
    public static function threads($info=false) {
        global $PDOX, $TSUGI_LAUNCH, $CFG;
        if ( ! is_array($info) ) $info = $_GET;

        // Default is latest
        $order_by = "modified_at DESC, netvote DESC";
        $sort = U::get($info, "sort");
        if ( $sort == "latest" ) {
            $order_by = "modified_at DESC, netvote DESC";
        } else if ( $sort == "unanswered" ) {
            $order_by = "comments ASC, netvote DESC, modified_at DESC";
        } else if ( $sort == "popular" ) {
            $order_by = "views DESC, comments DESC, netvote DESC, modified_at DESC";
        } else if ( $sort == "active" ) {
            $order_by = "comments DESC, views DESC, netvote DESC, modified_at DESC";
        } else if ( $sort == "votes" ) {
            $order_by = "netvote, DESC, comments DESC, views DESC, modified_at DESC";
        }

        $subst = array(':UID' => $TSUGI_LAUNCH->user->id, ':LID' => $TSUGI_LAUNCH->link->id);

        $search = U::get($info, "search");
        $whereclause = "";
        if ( strlen(trim($search)) > 0 ) {
            $whereclause = " AND (LOWER(title) LIKE :SEARCH OR LOWER(body) LIKE :SEARCH) ";
            $subst[':SEARCH'] = '%'.strtolower($search).'%';
        }

        $rows = $PDOX->allRowsDie("SELECT T.thread_id AS thread_id, body, title,
            pin, views, staffcreate, staffread, staffanswer, comments, displayname,
            T.created_at AS created_at, T.updated_at AS updated_at,
            COALESCE(T.updated_at, T.created_at) AS modified_at,
            CASE WHEN T.user_id = :UID THEN TRUE ELSE FALSE END AS owned,
            (COALESCE(T.upvote, 0)-COALESCE(T.downvote, 0)) AS netvote
            FROM {$CFG->dbprefix}tdiscus_thread AS T
            JOIN {$CFG->dbprefix}lti_user AS U ON  U.user_id = T.user_id
            WHERE link_id = :LID $whereclause
            ORDER BY T.pin DESC, T.rank_value DESC, $order_by",
            $subst
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

        $staffcreate = $TSUGI_LAUNCH->user->instructor ? 1 : 0;
        // TODO: Purify pre-insert?
        $stmt = $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}tdiscus_thread
            (link_id, user_id, staffcreate, title, body) VALUES
            (:LID, :UID, :STAFF, :TITLE, :BODY)",
            array(
                ':LID' => $TSUGI_LAUNCH->link->id,
                ':UID' => $TSUGI_LAUNCH->user->id,
                ':STAFF' => $staffcreate,
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
            (COALESCE(C.upvote, 0)-COALESCE(C.downvote, 0)) AS netvote,
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
        if ( $retval > 0 ) {
            $staffanswer = "";
            if ( $TSUGI_LAUNCH->user->instructor ) $staffanswer = "staffanswer=1, ";

            // A little denormalization saves a COUNT / GROUP BY and makes sorting super fast
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
                SET $staffanswer comments=(SELECT count(comment_id) FROM {$CFG->dbprefix}tdiscus_comment
                     WHERE thread_id = :TID), updated_at=NOW()
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

    public static function commentDeleteDao($comment_id, $thread_id) {
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

        // A little denormalization saves a COUNT / GROUP BY and makes sorting super fast
        $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}tdiscus_thread
            SET comments=(SELECT count(comment_id) FROM {$CFG->dbprefix}tdiscus_comment
                 WHERE thread_id = :TID)
            WHERE thread_id = :TID",
            array(
                ':TID' => $thread_id,
             )
        );

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
