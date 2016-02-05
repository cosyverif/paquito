<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\ArrayInput;

class Check extends Command
{
    private $logger = null;
    
    //Root Node -> lvl 1
    private $root_node = array('Name', 'Version', 'Homepage', 'Description', 'Summary', 'Copyright', 'Maintainer', 'Authors', 'Packages');
    private $root_node_required = array('Name', 'Version', 'Description', 'Summary', 'Copyright', 'Maintainer', 'Packages');
    
    //Packages Node -> lvl 2
    private $packages_node = array('Type', 'Files', 'Build', 'Install', 'Runtime', 'Test');
    private $packages_node_required = array('Type','Files');
    
    //Types Values 
    private $type_values = array('binary', 'library', 'source', 'arch_independant');
    
	protected function configure()
	{
		$this
			->setName('check')
			->setDescription('Check integrity of paquito.yaml')
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

	/* Check integrity of a Files node
	 * @param $fieldbase : Path (in the YAML file) to the analyzed field
	 * @param $file_node : Array describing a 'Files' node */
	protected function check_files_node($fieldbase, $file_node) {
		foreach ($file_node as $f_key => $f_value) {
            // If a file need specific permissions -> check integry of the field
			if (is_array($f_value)) {
				$this->check_field($fieldbase, $f_value, array('Source', 'Permissions'), array('Source', 'Permissions'));
				
                // Check if the file/directory exist
                if(!file_exists($f_value['Source'])) {
                    $fieldbase = implode(' -> ', $fieldbase);
                    $this->logger->error($this->getApplication()->translator->trans('check.inexistant', array('%path%' => $fieldbase.' -> '.$f_value['Source'])));
					exit(-1);
                }
                
                // Check if the permissions is well formated
				if (!preg_match('/^[0-7]{3,4}$/', $f_value['Permissions'])) {
					$fieldbase = implode(' -> ', $fieldbase);
					$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $fieldbase.' -> Permissions', '%value%' => $f_value['Permissions'])));
					exit(-1);
				} else if (preg_match('/^[0-7]{4}$/', $f_value['Permissions'])) { /* If the permissions mask of a file owns a special bit, warns the user */
					$this->logger->warning($this->getApplication()->translator->trans('check.bit', array('%file%' => $f_key, '%permissions%' => $f_value['Permissions'])));
				}
			}
            
            else {
                if(!file_exists($f_value)) {
                    $fieldbase = implode(' -> ', $fieldbase);
                    $this->logger->error($this->getApplication()->translator->trans('check.inexistant', array('%path%' => $fieldbase.' -> '.$f_value)));
					exit(-1);
                }
            }
		}
	}

	/* Verifies for the analyzed field :
	 * 		- all its sub-fields are an array
	 * 		- all its sub-fields are not empty
	 * 		- it owns all required sub-fields 
	 * @param $fieldbase : Path (in the YAML file) to the analyzed field
	 * @param $struct : Structure that contains the analyzed field
	 * @param $array_comparer : Array of expected fields for the analyzed field
	 * @param $array_min : Array of the fields which must appear in the structure */
	protected function check_field($fieldbase, $struct, $array_comparer, $array_min)
	{
		/* Total number of expected fields. This number will be decremented for each expected
		 * fields found. If more than 0, error (because it misses one or more required fields) */
		$fieldmin = count($array_min);
		$fieldbase = implode(' -> ', $fieldbase);
        
		foreach ($struct as $key => $value) {
			/* If the name of the current field doesn't part of the superior field */
			if (!in_array($key, $array_comparer)) {
				$this->logger->error($this->getApplication()->translator->trans('check.field', array('%value%' => $key, '%path%' => $fieldbase." -> $key")));
				exit(-1);
			} elseif (empty($value)) {
				$this->logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key, '%path%' => $fieldbase." -> $key")));
				exit(-1);
			}
            
			/* If the field name is found in the array of required fields */
			if (in_array($key, $array_min))
                --$fieldmin;
		}
        
		if ($fieldmin) {
			$this->logger->error($this->getApplication()->translator->trans('check.missing', array('%field%' => $fieldbase)));
			exit(-1);
		}
	}
    
    protected function check_dependencies($basefield, $dependancies_node)
    {
		foreach ($dependancies_node as $d_field => $d_value)
        {
			if (empty($d_value)) {
				$this->logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $d_key, '%path%' => "Root -> Packages -> $key -> $key_dependencies[$i] -> Dependencies -> $d_key")));
				exit(-1);
			}
            
            if(!is_array($d_value))
            {
                if($d_value != '*') {
                    $field = implode(' -> ', array('Root', 'Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $d_value));
					$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $d_key, '%value%' => $d_value, '%path%' => $field)));
					exit(-1);
                }
            }
            
            // If the dependency is different depending on distributions
            else
            {
				// force array($this->getApplication()->currentDistrib) ? 
				$this->check_field($basefield, $d_value, array_keys($this->getApplication()->distributions), array());

                // Check dependencies for each distribution
				foreach ($d_value as $distribution => $v_value) {
                    // If the dependencie varie between different version of a distribution
					if(is_array($v_value)) {
                        if (array_key_exists(0, $v_value))
                        {
                            foreach($v_value as $v_dependency) {
                                if (is_array($v_dependency)) {
                                    $field = implode(' -> ', array('Root', 'Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $v_key));
								    $this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%value%' => '', '%path%' => $field, '%field%' => $v_key)));
                                    exit(-1);
                                }
                            }
                        }
                        
                        else
                        {
                            /* The distribution structure contains the standard structure (like for example "All", "Wheezy", "Jessie"...) */
							$this->check_field($basefield, $v_value, $this->getApplication()->distributions[$v_key], array('All'));
							
                            /* For each field (like "All", "Wheezy", "Jessie"...) */
							foreach($v_value as $v_subkey => $v_subvalue) {
								/* If the array is non-associative (so the user has specified
								 * several dependencies for the version of the distribution) */
								if (is_array($v_subvalue) && array_key_exists(0, $v_subvalue)) {
									/* For each common dependency checks if the value is not an array */
									foreach($v_subvalue as $v_dependency) {
										/* If the value is in fact an array (this is an error) */
										if (is_array($v_dependency)) {
											$field = implode(' -> ', array('Root', 'Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $v_key, $v_subkey));
											$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%value%' => '', '%path%' => $field, '%field%' => $v_subkey)));
										}
									}
								}
							}
						}
					}
				}
			}
		}
    }

	protected function execute(InputInterface $input, OutputInterface $output)
	{
       	$input_dir = $input->getArgument('input');
		$local = $input->getOption('local');
        
        //Precaution : if a slash miss at the end of the directory, add it
        if(substr($input_dir, -1) != '/')
            $input_dir .= '/';

        $YAML_paquito = $input_dir."paquito.yaml"; 
        
		// Get the references of the command parse()
		$command = $this->getApplication()->find('parse');
		$array_input = new ArrayInput(array('command' => 'parse',
                                            'input' => $YAML_paquito,
                                            '--local' => $local)
        );
        
		// Parse the paquito.yaml and put it in data
		$command->run($array_input, $output);

        // Launch logger module
        // TODO : creer un logger general !
        $this->logger = new ConsoleLogger($output);
        
		// Get the structure of the YaML file
		$YAML_node = $this->getApplication()->data;
        
		/* Analysis of the root structure */
		$this->check_field(array("Root"), $YAML_node, $this->root_node, $this->root_node_required);
        
		// Check if the maintainer is well formatted
		if(!preg_match('/^[A-Z][A-Za-z- ]*[A-Za-z] +<[A-Za-z0-9][A-Za-z0-9._%+-]*[A-Za-z0-9]@[A-Za-z0-9][A-Za-z0-9.-]*[A-Za-z0-9]\.[A-Za-z]{2,4}>$/', $YAML_node['Maintainer'])) {
			$this->logger->error($this->getApplication()->translator->trans('check.maintainer', array('%value%' => $YAML_node['Maintainer'], '%path%' => "Root -> Maintainer")));
			exit(-1);
		}
        
		// Remove several spaces by one space - PURPOSE ? 
		$this->getApplication()->data['Maintainer'] = preg_replace('/\s+/'," ", $YAML_node['Maintainer']);
        
        // Check integrity of each package node - LVL 1 OF paquito.yaml
		foreach ($YAML_node['Packages'] as $pkg_name => $value) {
			$this->check_field(array("Root", "Packages", $pkg_name), $value, $this->packages_node, $this->packages_node_required);
            
			/* ----- TYPE ----- */
			/* If the type of package is unknown */
			if (!in_array($value['Type'], $this->type_values)) {
				$this->logger->error($this->getApplication()->translator->trans('check.package', array('%value%' => $value['Type'], '%path%' => "Root -> Packages -> $key -> Type")));
				exit(-1);
			}
            
			/* ----- FILES ----- */
			$this->check_files_node(array("Root", "Packages", $pkg_name, "Files"), $value['Files']);
            
            $key_dependencies = array();
            
			/* ----- OPTIONAL : BUILD ----- */
			if (isset($value['Build'])) {
				$this->check_field(array("Root", "Packages", $pkg_name, "Build"), $value['Build'], array('Dependencies', 'Commands'), array());
                array_push($key_dependencies, "Build");
            }
            
            /* ----- OPTION : RUN ----- */
			if (isset($value['Runtime'])) {
				$this->check_field(array("Root", "Packages", $pkg_name, "Runtime"), $value['Runtime'], array('Dependencies'), array('Dependencies'));
                array_push($key_dependencies, "Runtime");
            }
            
            /* ----- OPTIONAL : TEST ----- */
			if (isset($value['Test'])) {
				$this->check_field(array('Root','Packages', $pkg_name, 'Test'), $value['Test'], array('Files','Dependencies', 'Commands'), array());
				if (isset($value['Test']['Files']))
					$this->check_files_node(array("Root", "Packages", $pkg_name, "Test", "Files"), $value['Test']['Files']);
                    
                array_push($key_dependencies, "Test");
			}
            
            // For "build", "run" and "test" dependencies
			for ($i = 0; $i < count($key_dependencies); ++$i) {
                $current_node =& $value[$key_dependencies[$i]]['Dependencies'];
				if(isset($current_node))
                    $this->check_dependencies(array("Root", "Packages", $pkg_name, $key_dependencies[$i], "Dependencies"), $current_node);
            }
            
            /* ----- OPTIONAL : INSTALL ----- */
			if (isset($value['Install']))
				$this->check_field(array('Root','Packages', $pkg_name, 'Install'), $value['Install'], array('Pre', 'Post'), array());
            
		}

		/* If the "--local" option is not set */
		if (!$local) {
			$YAML_conf = $this->getApplication()->conf;

			/* Analysis of the root structure (which contains distribution names) */
			$this->check_field(array('Root'), $YAML_conf, array_keys($this->getApplication()->distributions), array_keys($this->getApplication()->distributions));

			foreach($YAML_conf as $distribution => $value) {
				if (is_array($value))
                {
                    $versions =& $value;
                   
					/* Checks versions of the current distribution */
					$this->check_field(array('Root', $distribution), $YAML_conf[$distribution], array_diff($this->getApplication()->distributions[$distribution], array_keys($this->getApplication()->alias_distributions[$distribution])), array());
					
					foreach($YAML_conf[$distribution] as $nom_code_version => $architecture) {
                        
                        if (is_array($architecture)) { // Multiple architecture
							foreach ($architecture as $current_architecture) {
								if (is_array($current_architecture) || !in_array($current_architecture, $this->getApplication()->architectures)) {
									$field = implode(' -> ', array('Root', $distribution, $version));
									$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $v_key, '%value%' => $v_key, '%path%' => $field)));
									exit(-1);
								}
							}
						} else { /* One architecture is specified or "*" */
							if (!in_array($architecture, $this->getApplication()->architectures) && $architecture != '*') {
								$field = implode(' -> ', array('Root', $nom_code_version, $architecture));
								$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $nom_code_version, '%value%' => $architecture, '%path%' => $field)));
								exit(-1);
							}
						}
					}
				}
                
                else
                {
					if ($value != "*") {
						$field = implode(' -> ', array('Root', $key));
						$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $key, '%value%' => $value, '%path%' => $field)));
						exit(-1);
					}
				}
			}
		}

		// Write the output - OPTIONAL
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
