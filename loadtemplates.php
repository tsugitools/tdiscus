<?php

use \Tsugi\Util\U;
use \Tsugi\UI\Output;

require_once "../config.php";

if ( $CFG->localhost() ) {
    Output::maxCacheHeader(3600*24); // 1 day
}

$rest_path = U::rest_path();

$TSUGI_LOCALE = null;


if ( $rest_path->action && strlen($rest_path->action) > 0 ) {
    echo("<!-- Locale ".htmlentities($rest_path->action)." -->\n");
    U::setLocale($rest_path->action);
} else {
    U::setLocale();
}
echo("<!-- td=".htmlentities(textdomain(null))." -->\n");

$count = 0;
foreach(glob('templates/*.hbs') as $name) {   
    $count++;
    echo "<template id=\"" . basename($name, '.hbs') . "\">\n";  
    $template = file_get_contents($name);
    echo(\Tsugi\UI\Output::templateProcess($template));
    echo("</template>\n");
}

if ( $count == 0 ) {
    echo("<!-- no templates found -->\n");
}

