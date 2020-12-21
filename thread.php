<?php
require_once "../config.php";
require_once "render.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tdiscus\Render;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

// View
$OUTPUT->header();

Render::load_templates();
Render::setup_tdiscuss();

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

Render::main_div();

$OUTPUT->footerStart();
Render::load_xss();
Render::render('tdiscus-c-thread');

$OUTPUT->footerEnd();
