<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class Normalize extends Command
{
    private $normalizeYAML = array();

	protected function configure()
	{
		$this
			->setName('normalize')
			->setDescription('Normalize paquito.yaml and conf.yaml')
			->addArgument(
				'input',
				InputArgument::REQUIRED,
				'Name of the directory which contains the sources and the paquito.yaml file'
			)
			->addArgument(
				'output',
				InputArgument::OPTIONAL,
				'Name of a YaML file'
			);
	}

    protected function normalize_file_node(&$file_node)
    {
        foreach ($file_node as $src => $val)
        {
			// src : dest
			if (!is_array($val)) {
                $file_node[$src] = array('Source' => (empty($val) ? $src : $val),
                                         'Permissions' => 755);
			}
		}
    }

	protected function normalize_dependencies_node(&$dependencies_node)
    {
        $supported_distributions = $this->getApplication()->conf['Distribution']; //Should be const
        
        foreach ($dependencies_node as $dep_name => $val)
        {
            // Check if the current dependence is to exclude
            if(in_array($dep_name, $this->getApplication()->conf['Exclude_dependence'])) {
                unset($dependencies_node[$dep_name]);
            }
                
            // dep_name: * <- shortcut
			if ($val == '*') {
                $dependencies_node[$dep_name] = array();
                
                // Generate dependencies node for all distributions depending on the system conf
                foreach($supported_distributions as $distribution)
                    $dependencies_node[$dep_name][$distribution] = array('All' => $dep_name);
                    
			} else {
                /* dep_name:
                                distrib1: dep_name1
                                distrib2:
                                  version1: dep_name2
                                  version2: dep_name3 */
				$distrib_node =& $val;
                $YAML_distributions = array_keys($distrib_node);
                
                foreach($supported_distributions as $dist_name)
                {
                    // User not define dependencies for this distribution -> generate it
                    if(!in_array($dist_name, $YAML_distributions))
                        $dependencies_node[$dep_name][$dist_name] = array('All' => $dep_name);
                        
                    else {
                        //distrib: dependence -> distrib: All: dependence
                        if(!is_array($distrib_node[$dist_name]))
                            $dependencies_node[$dep_name][$dist_name] = array('All' => $distrib_node[$dist_name]);
                            
                        else{
                            // User define dependencies for each version of a distribution
                            // TODO : Check if every version has a dependency or at least "All" field is define
                            // Verifier que check ne fasse pas remonter une eerreur
                        }
                    }
                }
            }
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$input_file = $input->getArgument('input');
		$local = $input->getOption('local');
		
        // Get references of the command check()
        // Check the integrity of paquito.yaml before
		$command = $this->getApplication()->find('check');
		$array_input = new ArrayInput(array('command' => 'check',
                                            'input' => $input_file,
                                            '--local' => $local)
        );
		
        $command->run($array_input, $output);

        $normalizeYAML =& $this->getApplication()->data;
        $packages_node =& $normalizeYAML['Packages'];
        
        foreach($packages_node as $pkg_name => $value)
        {
            // Normalize Files Node -> LVL 1
            $this->normalize_file_node($packages_node[$pkg_name]['Files']);
            
            // Normalize Dependencies Node -> LVL 2
            $node_to_check = array('Build', 'Runtime', 'Test');
            for($i = 0; $i < 3; $i++)
            {
                if(isset($packages_node[$pkg_name][$node_to_check[$i]])) {
                    $this->normalize_dependencies_node($packages_node[$pkg_name][$node_to_check[$i]]['Dependencies']);
                    
                    if($i == 2) //Test node
                        $this->normalize_file_node($packages_node[$pkg_name]['Test']['Files']);
                }
            }
        }

		// Optionnal argument
        $output_file = $input->getArgument('output');
		if ($output_file) {
			/* Get references of the command write() */
			$command = $this->getApplication()->find('write');
			$array_input = new ArrayInput(array('command' => 'write',
                                                'output' => $output_file)
            );
			
            $command->run($array_input, $output);
		}
	}
}
