<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

// Dropping tables
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}tdiscus_read",
"drop table if exists {$CFG->dbprefix}tdiscus_flag",
"drop table if exists {$CFG->dbprefix}tdiscus_closure",
"drop table if exists {$CFG->dbprefix}tdiscus_comment",
"drop table if exists {$CFG->dbprefix}tdiscus_thread",
"drop table if exists {$CFG->dbprefix}tdiscus_user_thread",
"drop table if exists {$CFG->dbprefix}tdiscus_user_comment",
"drop table if exists {$CFG->dbprefix}tdiscus_user_user",
);

// Creating tables
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}tdiscus_thread",
"create table {$CFG->dbprefix}tdiscus_thread (
    thread_id   INTEGER NOT NULL KEY AUTO_INCREMENT,
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,

    title       TEXT NULL,
    body        TEXT NULL,
    cleaned     TINYINT(1) NOT NULL DEFAULT 0,
    json        TEXT NULL,
    settings    TEXT NULL,

    views       INTEGER NOT NULL DEFAULT 0,
    comments    INTEGER NOT NULL DEFAULT 0,
    staffcreate TINYINT(1) NOT NULL DEFAULT 0,
    staffread   TINYINT(1) NOT NULL DEFAULT 0,
    staffanswer TINYINT(1) NOT NULL DEFAULT 0,
    frozen      TINYINT(1) NOT NULL DEFAULT 0,
    edited      TINYINT(1) NOT NULL DEFAULT 0,
    hidden      TINYINT(1) NOT NULL DEFAULT 0,
    locked      TINYINT(1) NOT NULL DEFAULT 0,
    pin         TINYINT(1) NOT NULL DEFAULT 0,
    rank_value  SMALLINT(2) NOT NULL DEFAULT 0,
    upvote      INTEGER NOT NULL DEFAULT 0,
    downvote    INTEGER NOT NULL DEFAULT 0,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_thread_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}tdiscus_user_thread",
"create table {$CFG->dbprefix}tdiscus_user_thread (
    thread_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    views       INTEGER NOT NULL DEFAULT 0,
    comments    INTEGER NOT NULL DEFAULT 0,
    vote        TINYINT(1) NOT NULL DEFAULT 0,
    mute        TINYINT(1) NOT NULL DEFAULT 0,
    favorite    TINYINT(1) NOT NULL DEFAULT 0,
    report      TINYINT(1) NOT NULL DEFAULT 0,
    read_at     TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_thread_ibfk_1`
        UNIQUE (`thread_id`, `user_id`),

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_thread_ibfk_2`
        FOREIGN KEY (`thread_id`)
        REFERENCES `{$CFG->dbprefix}tdiscus_thread` (`thread_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_thread_ibfk_3`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}tdiscus_comment",
"create table {$CFG->dbprefix}tdiscus_comment (
    comment_id  INTEGER NOT NULL KEY AUTO_INCREMENT,
    thread_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    depth       INTEGER NOT NULL DEFAULT 0,

    comment     TEXT NULL,
    json        TEXT NULL,
    settings    TEXT NULL,

    pin         TINYINT(1) NOT NULL DEFAULT 0,
    rank_value  SMALLINT(2) NOT NULL DEFAULT 0,
    upvote      INTEGER NOT NULL DEFAULT 0,
    downvote    INTEGER NOT NULL DEFAULT 0,
    edited      TINYINT(1) NOT NULL DEFAULT 0,
    hidden      TINYINT(1) NOT NULL DEFAULT 0,
    locked      TINYINT(1) NOT NULL DEFAULT 0,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_comment_ibfk_1`
        FOREIGN KEY (`thread_id`)
        REFERENCES `{$CFG->dbprefix}tdiscus_thread` (`thread_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}tdiscus_user_comment",
"create table {$CFG->dbprefix}tdiscus_user_comment (
    comment_id  INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    vote        TINYINT(1) NOT NULL DEFAULT 0,
    report      TINYINT(1) NOT NULL DEFAULT 0,
    favorite    TINYINT(1) NOT NULL DEFAULT 0,
    read_at     TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_comment_ibfk_1`
        UNIQUE (`comment_id`, `user_id`),

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_comment_ibfk_2`
        FOREIGN KEY (`comment_id`)
        REFERENCES `{$CFG->dbprefix}tdiscus_comment` (`comment_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_comment_ibfk_3`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}tdiscus_user_user",
"create table {$CFG->dbprefix}tdiscus_user_user (
    user_id     INTEGER NOT NULL,
    other_user_id  INTEGER NOT NULL,
    mute        TINYINT(1) NOT NULL DEFAULT 0,
    report      TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_user_ibfk_1`
        UNIQUE (`user_id`, `other_user_id`),

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_user_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_user_user_ibfk_3`
        FOREIGN KEY (`other_user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


/*
https://stackoverflow.com/questions/192220/what-is-the-most-efficient-elegant-way-to-parse-a-flat-table-into-a-tree/192462#192462

https://www.slideshare.net/billkarwin/models-for-hierarchical-data

https://stackoverflow.com/questions/8252323/mysql-closure-table-hierarchical-database-how-to-pull-information-out-in-the-c
*/

// A closure table approach to hierarchy
array( "{$CFG->dbprefix}tdiscus_closure",
"create table {$CFG->dbprefix}tdiscus_closure (
    parent_id   INTEGER NOT NULL,
    child_id    INTEGER NOT NULL,
    depth       INTEGER NOT NULL,
    children    INTEGER NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL,
    
    CONSTRAINT `{$CFG->dbprefix}tdiscus_closure_ibfk_1`
        FOREIGN KEY (`parent_id`)
        REFERENCES `{$CFG->dbprefix}tdiscus_comment` (`comment_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}tdiscus_closure_ibfk_2`
        FOREIGN KEY (`child_id`)
        REFERENCES `{$CFG->dbprefix}tdiscus_comment` (`comment_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(parent_id, child_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


);

// Database upgrade
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    return 201710191330;
}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
}

