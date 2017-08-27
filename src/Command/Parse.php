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
            ->setDescription('Parse YAML file')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Name of YAML file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $YAMLFile = $input->getArgument('input');
		$local = $input->getOption('local');
		
        // Launch Logger module
		$logger = new ConsoleLogger($output);

        // Rudimentary check
        if (!is_file($YAMLFile)) {
            $logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => "$YAMLFile")));
            exit(-1);
        } elseif (!is_readable($YAMLFile)) {
            $logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' => "$YAMLFile")));
            exit(-1);
        }
        
        // Parse the input file
        try {
            $this->getApplication()->data = Yaml::parse(file_get_contents($YAMLFile));
        } catch (ParseException $e) {
            $logger->error("Unable to parse the YAML file");//$this->getApplication()->translator->trans('parse.exception', arra))
        }
        
		// Rudimentary check for conf.yaml
		if (!is_file($this->getApplication()->conf)) {
			$logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => '/etc/paquito/conf.yaml')));
			exit(-1);
		} elseif (!is_readable($this->getApplication()->conf)) {
			$logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' =>  '/etc/paquito/conf.yaml')));
			exit(-1);
		}
            
		// Parse the configuration file
        try {
		     $this->getApplication()->conf = Yaml::parse(file_get_contents($this->getApplication()->conf));
        } catch(ParseException $e) {
             $logger->error("Unabl to parse the YAML file");
        }
    }
}
