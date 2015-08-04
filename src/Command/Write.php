<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Console\Input\ArrayInput;

class Write extends Command
{
    protected function configure()
    {
        $this
            ->setName('write')
            ->setDescription('Write a YaML file')
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Name of a YaML file'
            )
            ->addArgument(
                'input',
                InputArgument::OPTIONAL,
                'Name of the directory which contains the sources and the paquito.yaml file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Optionnal argument (input file, which will be parsed) */
    $input_file = $input->getArgument('input');
    /* If the optionnal argument is present */
    if ($input_file) {
        /* Get references of the command parse() */
        $command = $this->getApplication()->find('parse');
        /* Declare the arguments in a array (arguments has to gave like this) */
        $arguments = array(
            'command' => 'parse',
            'input' => $input_file,
        );
        $array_input = new ArrayInput($arguments);
        /* Run command */
        $command->run($array_input, $output);
    }
    /* Get path and name of the output file */
    $output_file = $input->getArgument('output');
    /* Launch Logger module */
    $logger = new ConsoleLogger($output);
    /* Write content of the structure on the output file */
        if (file_put_contents($output_file, Yaml::dump($this->getApplication()->data)) === false) {
            $logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $output_file)));

            return -1;
        }
    }
}
