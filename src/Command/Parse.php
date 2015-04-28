<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Translation\Translator;

class Parse extends Command
{
    protected function configure()
    {
        $this
            ->setName('parse')
            ->setDescription('Parse a YaML file')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Name of a YaML file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get path and name of the input file */
        $filename = $input->getArgument('input');
    /* Launch Logger module */
    $logger = new ConsoleLogger($output);
    /* The file not exists */
        if (!is_file($filename)) {
            $logger->error($this->getApplication()->translator->trans('parse.exist', array('%filename%' => $filename)));

            return -1;
    /* The file is not readable */
        } elseif (!is_readable($filename)) {
            $logger->error($this->getApplication()->translator->trans('parse.right', array('%filename%' => $filename)));

            return -1;
        }
        # Parse the file and return its content like a array (hashmap)
        $this->getApplication()->data = Yaml::parse(file_get_contents($filename));
    }
}
