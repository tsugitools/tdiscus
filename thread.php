<?php
if ( ! isset($CFG) ) return; // Don't allow direct calls

use \Tsugi\Util\U;

$rest_path = U::rest_path();
echo("<pre>\n");
var_dump($rest_path);

