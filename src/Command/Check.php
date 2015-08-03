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
    public $logger = null;
    /* First level keys (of the Paquito configuration files) */
    public $keys_first = array('Name', 'Version', 'Homepage', 'Description', 'Summary', 'Copyright', 'Maintainer', 'Authors', 'Packages');
    public $keys_first_min = array('Name', 'Version', 'Description', 'Summary', 'Copyright', 'Maintainer', 'Packages');
    public $keys_package = array('Type', 'Files', 'Build', 'Install', 'Runtime', 'Test');
    public $keys_package_min = array('Type', 'Files');
    /* Package types */
    public $keys_type = array('binary', 'library', 'source', 'arch_independant');
    /* Known distributions */
    public $key_dist = array('Debian', 'Archlinux', 'Centos');
    /* Known versions (Debian) */
    public $versions = array(
        'Debian' => array('All', 'Stable', 'Testing', 'Wheezy', 'Jessie'), /* Debian */
        'Archlinux' => array('All'), /* Debian */
        'Centos' => array('All', '6.6', '7.0'), ); /* CentOS */

    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Check validity of a YaML file')
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

		/* Check a structure of files */
	protected function check_files($fieldbase, $struct) {
		foreach ($struct as $f_key => $f_value) {
			/* If there is not source path (in others words the field is empty) */
		#	if (empty($f_value)) {
		#		$fieldbase = implode(' -> ', $fieldbase);
		#		$this->logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $f_key, '%path%' => $fieldbase." -> $f_key")));
#
#				exit(-1);
#			}
			/* If the file will have specifics permissions */
			if (is_array($f_value)) {
				/* Analysis of the file structure (when there is the permissions) */
				$this->check_field($fieldbase, $f_value, array('Source', 'Permissions'), array('Source', 'Permissions'));
				/* The permissions have to be numbers (octal) */
				if (!preg_match('/^[0-7]{3,4}$/', $f_value['Permissions'])) {
					$fieldbase = implode(' -> ', $fieldbase);
					$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $fieldbase.' -> Permissions', '%value%' => $f_value['Permissions'])));

					exit(-1);
				} else if (preg_match('/^[0-7]{4}$/', $f_value['Permissions'])) { /* If the permissions mask of a file owns a special bit, warns the user */
					$this->logger->warning($this->getApplication()->translator->trans('check.bit', array('%file%' => $f_key, '%permissions%' => $f_value['Permissions'])));

				}
			}
		}
	}


    /* fieldbase: Path of the superiors fields
     * struct: Structure that contains the superior field
     * array_comparer: Array of standards fields of the superior field
     * array_min: Array of the fields which must appear in the superior field */
    protected function check_field($fieldbase, $struct, $array_comparer, $array_min)
	{
        /* Total number of expected fields. This number will be decremented for each expected
         * fields found. If more than 0, error (bacause it misses one or more required fields) */
        $fieldmin = count($array_min);
		$fieldbase = implode(' -> ', $fieldbase);
		/* For each field */
		foreach ($struct as $key => $value) {
			/* If the name of the current field doesn't part of the superior field */
            if (!in_array($key, $array_comparer)) {
                $this->logger->error($this->getApplication()->translator->trans('check.field', array('%value%' => $key, '%path%' => $fieldbase." -> $key")));

                exit(-1);
            } elseif (empty($value)) {
                $this->logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key, '%path%' => $fieldbase." -> $key")));

                exit(-1);
            }
            if (in_array($key, $array_min)) {
                --$fieldmin;
            }
        }
        if ($fieldmin) {
            $this->logger->error($this->getApplication()->translator->trans('check.missing', array('%field%' => $fieldbase)));

            exit(-1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get the path and the name of the input file */
        $input_file = $input->getArgument('input');
        /* Get the references of the command parse() */
        $command = $this->getApplication()->find('parse');
        /* Declare the arguments in a array (arguments have to be given like this) */
        $arguments = array(
            'command' => 'parse',
            'input' => $input_file,
        );
        $array_input = new ArrayInput($arguments);
        /* Run command */
        $command->run($array_input, $output);

        /* Get the structure of the YaML file (which was parsed) */
        $struct = $this->getApplication()->data;
        /* Launch Logger module */
        $this->logger = new ConsoleLogger($output);

        /* Analysis of the root structure */
        $this->check_field(array('Root'), $struct, $this->keys_first, $this->keys_first_min);
		/* Checks if the string giving the maintainer is well formed */
		if(! preg_match('/^[A-Z][A-Za-z- ]*[A-Za-z] +<[A-Za-z0-9][A-Za-z0-9._%+-]*[A-Za-z0-9]@[A-Za-z0-9][A-Za-z0-9.-]*[A-Za-z0-9]\.[A-Za-z]{2,4}>$/', $struct['Maintainer'])) {
			$this->logger->error($this->getApplication()->translator->trans('check.maintainer', array('%value%' => $struct['Maintainer'], '%path%' => "Root -> Maintainer")));

			exit(-1);
		}
		/* Remove superfluous spaces (replaces several spaces by one space) */
		$this->getApplication()->data['Maintainer'] = preg_replace('/\s+/'," ", $this->getApplication()->data['Maintainer']);
        /* For each package */
        foreach ($struct['Packages'] as $key => $value) {
            /* Analysis of the structure to each package */
            $this->check_field(array('Root','Packages', $key), $value, $this->keys_package, array());
            /* ----- TYPE ----- */
            /* If the type of package is unknown */
            if (!in_array($value['Type'], $this->keys_type)) {
                $this->logger->error($this->getApplication()->translator->trans('check.package', array('%value%' => $value['Type'], '%path%' => "Root -> Packages -> $key -> Type")));

                exit(-1);
            }
            /* ----- FILES ----- */
			/* For each file */
			$this->check_files(array('Root','Packages', $key, 'Files'), $value['Files']);
            /* ----- BUILD and RUNTIME ----- */
            /* Analysis of the "build" structure */
            if (isset($value['Build'])) {
                $this->check_field(array('Root','Packages', $key, 'Build'), $value['Build'], array('Dependencies', 'Commands'), array());
            }
            /* Analysis of the "run" structure */
            if (isset($value['Runtime'])) {
                $this->check_field(array('Root','Packages', $key, 'Runtime'), $value['Runtime'], array('Dependencies'), array('Dependencies'));
            }
            $key_dependencies = array('Build', 'Runtime','Test');
            /* For the "build" and "run" and "test" dependencies */
            for ($i = 0; $i < 3; ++$i) {
                /* For each dependency */
		    if(isset($value[$key_dependencies[$i]]['Dependencies'])) {
			    foreach ($value[$key_dependencies[$i]]['Dependencies'] as $d_key => $d_value) {
				    /* If the dependency is empty */
				    if (empty($d_value)) {
					    $this->logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $d_key, '%path%' => "Root -> Packages -> $key -> $key_dependencies[$i] -> Dependencies -> $d_key")));

					    exit(-1);
				    }
				    /* If the dependency is different depending on the distributions */
				    if (is_array($d_value)) {
					    /* Analysis of the "dependency" structure (where it has to have names of the distributions) */
					    $this->check_field(array('Root','Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key), $d_value, $this->key_dist, array());
					    /* For each name of ditribution */
					    foreach ($d_value as $v_key => $v_value) {
							/* Analysis of the "specific distribution dependency" structure (where it has to
							 * have versions of the distributions and "All") */
							/* IMPORTANT : The distibution may not have any dependencies (<none>) OR has a
							 * common dependency for all versions, so $v_value will not be an array */
							if(is_array($v_value)) {
								/* If the array is non-associative (so the user has specified several
								 * common dependencies for all versions of the distribution) */
								if (array_key_exists(0, $v_value)) {
									/* For each common dependency checks if the value is not an array */
									foreach($v_value as $v_dependency) {
										/* If the value is in fact an array (this is an error) */
										if (is_array($v_dependency)) {
											$field = implode(' -> ', array('Root', 'Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $v_key));
											$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%value%' => '', '%path%' => $field, '%field%' => $v_key)));
										}
									}
								} else { /* The distribution structure contains the standard structure (like for example "All", "Wheezy", "Jessie"...) */
									$this->check_field(array('Root','Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $v_key), $v_value, $this->versions[$v_key], array('All'));
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
				    } else { /* If the dependency is the same for all distributions */
					    if ($d_value != '*') {
							$field = implode(' -> ', array('Root', 'Packages', $key, $key_dependencies[$i], 'Dependencies', $d_key, $d_value));
							$this->logger->error($this->getApplication()->translator->trans('check.incorrect', array('%field%' => $d_key, '%value%' => $d_value, '%path%' => $field)));

						    exit(-1);
					    }
				    }
			    }
		    }
            }
            /* ----- INSTALL ----- */
            /* Analysis of the "install" structure */
            if (isset($value['Install'])) {
                $this->check_field(array('Root','Packages', $key, 'Install'), $value['Install'], array('Pre', 'Post'), array());
            }
            /* ----- TEST ----- */
            if (isset($value['Test'])) {
                $this->check_field(array('Root','Packages', $key, 'Test'), $value['Test'], array('Files','Dependencies', 'Commands'), array());
								if (isset($value['Test']['Files'])) {
									/* For each test file */
									$this->check_files(array('Root','Packages', $key, 'Test', 'Files'), $value['Test']['Files']);
								}
            }
        }

        /* Optionnal argument (output file, which will be parsed) */
        $output_file = $input->getArgument('output');
        /* If the optionnal argument is present */
        if ($output_file) {
            /* Get references of the command write() */
            $command = $this->getApplication()->find('write');
            /* Declare the arguments in a array (arguments has to gave like this) */
            $arguments = array(
                'command' => 'write',
                'output' => $output_file,
            );
            $array_input = new ArrayInput($arguments);
            /* Run command */
            $command->run($array_input, $output);
        }
    }
}
