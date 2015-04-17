<?php

require_once __DIR__.'/../vendor/autoload.php';

use \Symfony\Component\Console\Application;
use \Paquito\Command\Update;

// FIXME: erase when release
error_reporting(E_ALL | E_STRICT);

$application = new Application('paquito', '0.1');
$application->add(new Update());
$application->run();
