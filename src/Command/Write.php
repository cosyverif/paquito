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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input_file = $input->getArgument('input');
        if ($input_file) {
            $command = $this->getApplication()->find('parse');
            $array_input = new ArrayInput(array('command' => 'parse',
                                                'input' => $input_file)
            );
            $command->run($array_input, $output);
        }
        
        $output_file = $input->getArgument('output');
        $logger = new ConsoleLogger($output);
        
        if (file_put_contents($output_file, Yaml::dump($this->getApplication()->data)) === false) {
            $logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $output_file)));
            exit(-1);
        }
    }
}
