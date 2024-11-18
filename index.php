<?php

declare(strict_types=1);

use CSApp\Controller\SiteController;
use CSApp\Request;

$config = require_once('src/config.php');

@session_start();
$request = new Request($_GET, $_POST, $_SERVER, $_COOKIE, $_SESSION);

$controller = new SiteController($request, $config);
$controller->run();
