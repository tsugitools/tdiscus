<?php

$REGISTER_LTI2 = array(
"name" => "Threaded Discussion",
"FontAwesome" => "fa-comments",
"short_name" => "Discussion tool",
"description" => "This is a threaded discussion tool.",
    // By default, accept launch messages..
    "messages" => array("launch", "launch_grade"),
    "privacy_level" => "public",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "source_url" => "https://github.com/tsugitools/attend",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
/*
        "store/screen-01.png",
        "store/screen-02.png",
        "store/screen-03.png"
*/
    )

);
