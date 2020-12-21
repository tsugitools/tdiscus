<?php
require_once "../config.php";
require_once "render.php";

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tdiscus\Render;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

$LAUNCH = LTIX::requireData();

// View
$OUTPUT->header();
Render::load_templates();
Render::setup_tdiscuss();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();
Render::main_div();
$OUTPUT->footerStart();
Render::load_ckeditor();
Render::render('tdiscus-c-newthread');
$OUTPUT->footerEnd();
