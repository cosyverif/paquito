<?php

require_once __DIR__.'/../vendor/autoload.php';

use \Symfony\Component\Console\Application;
use \Symfony\Component\Translation\Translator;
use \Symfony\Component\Translation\Loader\YamlFileLoader;
use \Symfony\Component\Console\Input\InputOption;
use \Paquito\Command\Update;
use \Paquito\Command\Check;
use \Paquito\Command\Parse;
use \Paquito\Command\Prune;
use \Paquito\Command\Write;
use \Paquito\Command\Normalize;
use \Paquito\Command\Generate;
use \Paquito\Command\Generate_test;


// FIXME: erase when release
error_reporting(E_ALL | E_STRICT);

$application = new Application('paquito', '0.1');
/* Globals variables */
$application->conf = null;
$application->data = null;
$application->dist_name = null;
$application->dist_version = null;
$application->dist_arch = null;
/* Globals options */
$application->getDefinition()->addOptions([new InputOption('local', 'l', InputOption::VALUE_NONE, 'Creates a package only for the current distribution, version and architecture')]);
/* Register commands */
$application->add(new Update());
$application->add(new Parse());
$application->add(new Check());
$application->add(new Prune());
$application->add(new Write());
$application->add(new Normalize());
$application->add(new Generate());
$application->add(new Generate_test());
// Add i18n:
// http://symfony.com/doc/master/components/translation/usage.html
$application->translator = new Translator(\Locale::getDefault());
$application->translator->setFallbackLocale(array('en'));
$application->translator->addLoader('yaml', new YamlFileLoader());
foreach (new DirectoryIterator(__DIR__.'/i18n/') as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if ($extension == 'yaml') {
        $locale = pathinfo($file, PATHINFO_FILENAME);
        $application->translator->addResource('yaml', __DIR__.'/i18n/'.$file, $locale);
    }
}

$application->run();
