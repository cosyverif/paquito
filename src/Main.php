<?php

require_once __DIR__.'/../vendor/autoload.php';

use \Symfony\Component\Console\Application;
use \Paquito\Command\Update;
use \Paquito\Command\Check;
use \Paquito\Command\Parse;
use \Paquito\Command\Prune;
use \Paquito\Command\Write;
use \Paquito\Command\Normalize;

// FIXME: erase when release
error_reporting(E_ALL | E_STRICT);

$application = new Application('paquito', '0.1');
$application->add(new Update());
$application->add(new Parse());
$application->add(new Check());
$application->add(new Prune());
$application->add(new Write());
$application->add(new Normalize());
$application->run();
