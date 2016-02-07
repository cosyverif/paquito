<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\ArrayInput;

class Prune extends Command
{
	public $logger = null;

	protected function configure()
	{
		$this
			->setName('prune')
			->setDescription('Prune a structure')
			->addArgument(
				'input',
				InputArgument::REQUIRED,
				'Name of the directory which contains the sources and the paquito.yaml file'
			)
			->addArgument(
				'output',
				InputArgument::OPTIONAL,
				'Name of a YaML file'
			)
			;
	}

	// Defines the applications variables $dist_name, $dist_version and $dist_arch
	protected function getDist()
    {
        // /etc/os-release contains informations about the distribution (name, version and architecture)
        // TODO : Search info about lsb_release
		if (is_file('/etc/os-release'))
        {
			$array_ini = parse_ini_file('/etc/os-release');
			
            // Get the name of the distribution
			$this->getApplication()->dist_name = ucfirst($array_ini['ID']);
			
            switch ($this->getApplication()->dist_name) {
			case 'Debian':
				preg_match('/[a-z]+/', $array_ini['VERSION'], $match);
				$this->getApplication()->dist_version = ucfirst($match[0]);
				break;
			
            case 'Arch':
				/* TODO Install on Archlinux the package "filesystem" */
				$this->getApplication()->dist_name = 'Archlinux';
				break;
			
            case 'Centos':
				preg_match('/[0-9](\.[0-9])?/', $array_ini['VERSION'], $match);
				$this->getApplication()->dist_version = $match[0];
				if (strlen($this->getApplication()->dist_version) == 1) {
					$this->getApplication()->dist_version = $this->getApplication()->dist_version.'.0';
				}
				break;
			
            default:
                // Vraiment generer une erreur ?
				$logger->error($this->getApplication()->translator->trans('prune.exist'));
				exit(-1);
			}
		}
        
        else
        {
            // Let's find a specific file for the distribution

			// Archlinux
			if (is_file('/etc/arch-release')) {
				/* IMPORTANT : We don't need to read this file because Archlinux
				 * has only one version and is only available on 64bits */
				$this->getApplication()->dist_name = 'Archlinux';
			} else if (is_file('/etc/centos-release')) {
				$this->getApplication()->dist_name = 'Centos';
				
                // Read the content of the file /etc/centos-release
				if (($version = file_get_contents('/etc/centos-release')) === FALSE) {
					$logger->error($this->getApplication()->translator->trans('prune.read', array('%file%' => '/etc/centos-release')));
					return -1;
				}
                
				// Get the version of the Centos distribution
				preg_match('/[0-9](\.[0-9])?/', $version, $match);
				$this->getApplication()->dist_version = $match[0];
			}
		}
        
		/* Get the architecture of the current machine */
		$this->getApplication()->dist_arch = posix_uname();
		$this->getApplication()->dist_arch = $this->getApplication()->dist_arch['machine'];
	}

	/* Prune a 'Packages' node with current distribution ($dist_name),
	 * version ($dist_version) and architecture ($dist_arch)
	 * @param $pkg_node : 'Packages' node */
	protected function prune_node($pkg_node) {
		// my_distribution should be const
        $my_distribution = array('Name' => $this->getApplication()->dist_name,
                                 'Version' => $this->getApplication()->dist_version,
                                 'architecture' => $this->getApplication()->dist_arch);
                                 
		$pruned_pkg_node = $pkg_node;
        $key_dependencies = array('Build', 'Runtime', 'Test');
        
		foreach ($pkg_node as $pkg_name => $value) {
			$cur_pkg =& $pkg_node[$pkg_name];
            
			// For each field (in others words 'Build', 'Runtime' and 'Test')
			for ($i = 0; $i < 3; ++$i) {
                
                if(!isset($cur_pkg[$key_dependencies[$i]]))
                    continue;

                $cur_dep =& $cur_pkg[$key_dependencies[$i]]['Dependencies'];
                
                $cur_pruned_dep =& $pruned_pkg_node[$pkg_name][$key_dependencies[$i]]['Dependencies'];
                $cur_pruned_dep = array();
                
                // If there are dependencies in the field Build/Runtime/Test
				if (isset($cur_dep)) { 
					// Remove the 'Dependencies' node in the pruned package node
					unset($pruned_pkg_node['Packages'][$pkg_name][$key_dependencies[$i]]['Dependencies']);
                    
					// For each node in 'Dependencies' node
					foreach($cur_dep as $dep_name => $d_value)
                    {
                        // A problem may occur w/ All
                        if(isset($cur_dep[$dep_name][$my_distribution['Name']]['All']))
                            $dep_for_my_dist = $cur_dep[$dep_name][$my_distribution['Name']]['All'];
                        else
                            $dep_for_my_dist = $cur_dep[$dep_name][$my_distribution['Name']]['Version'];
                            
                        if($dep_for_my_dist != "<none>")
                            array_push($cur_pruned_dep, $dep_for_my_dist);
                    }
                }
                
                // the distribution don't need any dependencies => erase the dependencies node
                // TODO : Check if it work
                if(empty($cur_pruned_dep))
                    unset($cur_pruned_dep);
				/*if (empty($new_struct['Packages'][$key][$key_dependencies[$i]])) {
					unset($new_struct['Packages'][$key][$key_dependencies[$i]]);
				}*/
                
            }
		}
        //print_r($pruned_pkg_node);
		return $pruned_pkg_node;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$input_file = $input->getArgument('input');
		$local = $input->getOption('local');
		
        // Get references of the command parse()
        //
		$command = $this->getApplication()->find('normalize');
		$array_input = new ArrayInput(array('command' => 'normalize',
                                            'input' => $input_file,
                                            '--local' => $local)
        );
		$command->run($array_input, $output);

        $logger = new ConsoleLogger($output);

		// If the "--local" option is not set, so we generate pruned YAML for each distrib
		if (!$local)
        {
			$YAML_conf = $this->getApplication()->conf;
			
            foreach($YAML_conf as $distribution => $arr_version) {
				foreach($arr_version as $cur_version => $arr_architecture) {
					foreach($arr_architecture as $cur_architecture) {
						$this->getApplication()->dist_name = $distribution;
						$this->getApplication()->dist_version = $cur_version;
						$this->getApplication()->dist_arch = $cur_architecture;
                        
                        // Add a node for each distributions/versions/architecture
                        $this->getApplication()->data['Distributions'][$distribution][$cur_version][$cur_architecture]['Packages'] = $this->prune_node($this->getApplication()->data['Packages']);
					}
				}
			}
            
			// Removes the original field 'Packages', now it's useless <= is it usefull to unset ? For memory purpose I guess */
			unset($this->getApplication()->data['Packages']);
		}
        
        else
        {
			$this->getDist();
            $this->getApplication()->data['Packages'] = $this->prune_node($this->getApplication()->data['Packages']);
		}

		// Optionnal argument
		$output_file = $input->getArgument('output');
		if ($output_file) {
			// Get references of the command write()
			$command = $this->getApplication()->find('write');
			$array_input = new ArrayInput(array('command' => 'write',
                                                'output' => $output_file)
            );
			$command->run($array_input, $output);
		}
	}
}
