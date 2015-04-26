<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Write extends Command
{
    protected function configure()
    {
        $this
            ->setName('write')
            ->setDescription('Write a YaML file')
            ->addArgument(
                'structure',
                InputArgument::REQUIRED,
                'Data structure of a YaML file'
            )
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Name of a YaML file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $struct = $input->getArgument('structure');
        $filename = $input->getArgument('filename');
        $logger = new ConsoleLogger($output);

        if (file_put_contents($filename, Yaml::dump($struct)) === false) {
            $logger->err("Erreur lors de l'écriture dans le fichier $filename");

            return -1;
        }
    }
}
