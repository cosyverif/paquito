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
            ->setDescription('Parse YaML file')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Name of YaML file'
            );
            /*->addArgument(
				'output',
				InputArgument::REQUIRED,
				'Array of YaML structure'
			);*/
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $YAMLFile = $input->getArgument('input');
		$local = $input->getOption('local');
		
        // Launch Logger module
		$logger = new ConsoleLogger($output);

        //TODO : Check if its a .yaml

        if (!is_file($YAMLFile)) {
            $logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => "$YAMLFile")));
            exit(-1);
        } elseif (!is_readable($YAMLFile)) {
            $logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' => "$YAMLFile")));
            exit(-1);
        }
        
        // Parse the file
        $this->getApplication()->data = Yaml::parse(file_get_contents($YAMLFile));

		if (!$local) {
			// The configuration file of Paquito not exists
			if (!is_file('/etc/paquito/conf.yaml')) {
				$logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => '/etc/paquito/conf.yaml')));
				exit(-1);
			} elseif (!is_readable('/etc/paquito/conf.yaml')) { /* If the file is not readable */
				$logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' =>  '/etc/paquito/conf.yaml')));
				exit(-1);
			}
            
			// Parse the file and return its content like a array (hashmap)
			$this->getApplication()->conf = Yaml::parse(file_get_contents('/etc/paquito/conf.yaml'));
        }
        
		/* Change the current directory to the project directory
		chdir($basename);*/
    }
}
