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
                'Name of the directory which contains the sources and the paquito.yaml file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get path and name of the input file */
        $basename = $input->getArgument('input');
		/* Get presence of the "--local" option */
        $local = $input->getOption('local');
		/* Launch Logger module */
		$logger = new ConsoleLogger($output);

		/* Security precaution : if it misses a slash at the end of the $dest_directory variable, add this slash  */
		if (substr($basename, -1) != '/') {
				$basename .= '/';
		}

		/* The file not exists */
        if (!is_file("$basename"."paquito.yaml")) {
            $logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => "$basename"."paquito.yaml")));

            exit(-1);
        } elseif (!is_readable("$basename"."paquito.yaml")) { /* If the file is not readable */
            $logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' => "$basename"."paquito.yaml")));

            exit(-1);
        }
        # Parse the file and return its content like a array (hashmap)
        $this->getApplication()->data = Yaml::parse(file_get_contents("$basename"."paquito.yaml"));

		/* If the "--local" option is not set */
		if (! $local) {
			/* The configuration file of Paquito not exists */
			if (!is_file('/etc/paquito/conf.yaml')) {
				$logger->error($this->getApplication()->translator->trans('parse.exist', array('%basename%' => '/etc/paquito/conf.yaml')));

				exit(-1);
			} elseif (!is_readable('/etc/paquito/conf.yaml')) { /* If the file is not readable */
				$logger->error($this->getApplication()->translator->trans('parse.right', array('%basename%' =>  '/etc/paquito/conf.yaml')));

				exit(-1);
			}
			# Parse the file and return its content like a array (hashmap)
			$this->getApplication()->conf = Yaml::parse(file_get_contents('/etc/paquito/conf.yaml'));
		}

		/* Change the current directory to the project directory */
		chdir($basename);
    }
}
