<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Parse extends Command
{
    protected function configure()
    {
        $this
            ->setName('parse')
            ->setDescription('Parse a YaML file')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Name of a YaML file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');
        $logger = new ConsoleLogger($output);
        if (!is_file($filename)) {
            $logger->err("L'élément $filename n'existe pas ou n'est pas un fichier régulier");

            return -1;
        } elseif (!is_readable($filename)) {
            $logger->err("Droits insuffisants pour lire le fichier $filename");

            return -1;
        }
        # Parse le fichier et retourne son contenu sous forme d'un tableau (hashmap)
        return Yaml::parse(file_get_contents($filename));
    }
}
