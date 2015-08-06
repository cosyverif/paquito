<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class Normalize extends Command
{
	public $newStruct = array();

	protected function configure()
	{
		$this
			->setName('normalize')
			->setDescription('Normalize a YaML file')
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

	protected function check_file($package,$test,$file,$tab) {

		/* parcours des fichiers*/
		foreach ($tab as $key => $val) {
			/* si les fichiers ne sont pas sous forme d'un tableau de champs Source et Permission*/
			if (!is_array($val)) {
				/* champ contenant le fichier de destination*/
				$dest = $key;
				/* champ contenant un fichier source*/
				$source = $val;
				$permission = '755';
				if($test=='Test'){
					if(!empty($val)) {
						$this->newStruct['Packages'][$package][$test][$file][$dest] = array('Source' => $source,'Permissions' => $permission);
					}
					else {
						$this->newStruct['Packages'][$package][$test][$file][$dest] = array('Source' => $dest,'Permissions' => $permission);
					}
				}
				else {
					if(!empty($val)) {
						$this->newStruct['Packages'][$package][$file][$dest] = array('Source' => $source,'Permissions' => $permission);
					}
					else {
						$this->newStruct['Packages'][$package][$file][$dest] = array('Source' => $dest,'Permissions' => $permission);
					}
				}


			} else {
				/* la structure est un tableau donc sous forme "Source , Permission", Rien ne change*/
				if($test=='Test'){
					$this->newStruct['Packages'][$package][$test][$file][$dest] =$val;
				}
				else {
					$this->newStruct['Packages'][$package][$file][$key] = $val;
				}
			}

		}
	}

	protected function check_dependencies($struct,$package,$field,$depend,$tab) {

		/*je parcours la liste des dependances*/
		foreach ($tab as $key => $val) {
			/*champ contenant le nom de la dépendance*/
			$d = $key;
			/* la dependance est la méme pour toutes les ditributions*/
			if ($val == '*') {
				/*il faut normaliser */
				$this->newStruct['Packages'][$package][$field][$depend][$d] = array(
					'Debian' => array('All' => $d),
					'Archlinux' => array('All' => $d),
					'Centos' => array('All' => $d));
			} else {
				/* tableau contenant les dépendances pour les différentes distributions*/
				$dist = $val;
				/* regarder quelle distribution manque*/
				foreach (array_keys($this->getApplication()->distributions) as $val) {
					if (!array_key_exists($val, $dist)) {
						$this->newStruct['Packages'][$package][$field][$depend][$d][$val] = array('All' => $d);
					} else { /* la distribution existe*/
						/* regarder si la valeur du champ de la distribution n'est pas égale à none */
						if ($struct['Packages'][$package][$field][$depend][$d][$val] != "<none>") {
							/* If the value to the distribution is an array (so the user has used
							 * "All" or has given a dependency according to the version)  */
							if (is_array($struct['Packages'][$package][$field][$depend][$d][$val])) {
								/* If the array is non-associative (so the user has specified several
								 * common dependencies for all versions of the distribution) */
								if (array_key_exists(0, $struct['Packages'][$package][$field][$depend][$d][$val])) {
									/* Shortcut "All" : the user has given several dependency names which will be 
									 * common to all versions of the distribution (like "All") */
									$this->newStruct['Packages'][$package][$field][$depend][$d][$val]['All'] = $dist[$val];
								} else {
									$this->newStruct['Packages'][$package][$field][$depend][$d][$val] = $dist[$val];
								}
							} else { /* Shortcut "All" : the user has given a dependency name which will be
								common to all versions of the distribution (like "All") */
								$this->newStruct['Packages'][$package][$field][$depend][$d][$val]['All'] = $dist[$val];
							}
						}
					}
				}
			}

		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* Get path and name of the input file */
		$input_file = $input->getArgument('input');
		/* Get presence of the "--local" option */
		$local = $input->getOption('local');
		/* Get references of the command check() */
		$command = $this->getApplication()->find('check');
		/* Declare the arguments in a array (arguments has to gave like this) */
		$arguments = array(
			'command' => 'check',
			'input' => $input_file,
			'--local' => $local,
		);
		$array_input = new ArrayInput($arguments);
		/* Run command */
		$command->run($array_input, $output);

		/* Get structure of YaML file (which was parsed and checked) */
		$struct = $this->getApplication()->data;

		/* Note: The variable $newStruct will owns the new structure (which becomes of the normalization) */
		foreach ($struct as $key => $val) {

			/* Les autres champs (de 1er niveau) ne seront pas modifiés */
			if ($key == 'Packages') {
				/* La variable $glob contient un tableau mentionnant les paquets qu'on souhaite créer */
				$glob = $val;
				/* Pour chaque paquet */
				foreach ($glob as $key => $val) {
					/* La variable $package contient le nom du paquet */
					$package = $key;
					/* Contient les champs fils relatifs au paquet courant */
					$tab = $val;
					/* Pour chacun des champs du paquet courant */
					foreach ($tab as $key => $val) {
						/* La clé désigne un champ  */
						if ($key == 'Type' || $key == 'Install') {
							/* Ces clés n'ont pas besoin d'être normalisées */
							$this->newStruct['Packages'][$package][$key] = $val;
							/* Les autres clés */
						}
						elseif($key=='Test') {
							/*stoque le nom du champ Test*/
							$test=$key;
							/*stoque le contenu du champ Test*/
							$tab=$val;
							foreach($tab as $key=>$val){

								if($key=='Files') {
									/*appeler la fonction check_file */
									$this->check_file($package,$test,$key,$val);
								}
								elseif($key=='Commands') { 
									/* c'est le champ Commands il reste pareil */
									$this->newStruct['Packages'][$package][$test][$key]=$val;
								}
								else { /* champ dependencies*/
									$this->check_dependencies($struct,$package,$test,$key,$val);
								}
							}
						}

						elseif ($key == 'Files') {
							/*appeler la fonction check_file */
							$test='Files';
							$this->check_file($package,$test,$key,$val);

						} else {
							/* variable contenant le champ Build ou le champ Runtime */
							$build = $key;
							/*variable contenant le contenu du champ Build ou du champ Runtime*/
							$contenu = $val;

							foreach ($contenu as $cle => $val) {

								if ($cle == 'Dependencies') {
									/*variable contenant les  champ cle et val*/
									$depend = $cle;
									$tab = $val;
									$this->check_dependencies($struct,$package,$build,$depend,$tab);

								} elseif ($cle == 'Commands') {
									/* pas de normalisation la structure reste la méme */

									$this->newStruct['Packages'][$package][$build][$cle] = $val;
								}
							}
						}
					}
				}
			}

			else {
				/* tout les autres champs restent pareil */
				$this->newStruct[$key] = $val;
			}
		}

		$this->getApplication()->data = $this->newStruct;

		/* If the "--local" option is not set */
		if (! $local) {
			/* Get the structure of the YaML file (which was parsed) */
			$struct = $this->getApplication()->conf;
			/* Empties the temporary structure (used before to keep the normalized YAML) */
			$this->newStruct = array();

			/* For each field of the root (in others words for each distribution) */
			foreach($struct as $key => $value) {
				/* It has to delete aliases versions (like "Stable", "Testing"...) to avoid to add them
				 * IMPORTANT: The array_values() function is to give good integer values in the array (else we can have jumps in values)*/
				$versions = array_values(array_diff($this->getApplication()->distributions[$key], array_keys($this->getApplication()->alias_distributions[$key])));

				/* If the creation of packages concerns all versions of the distribution */
				if (! is_array($value)) {
					/* Particular case : Archlinux has not version, so we add an "artificial" version  */
					if ($key != 'Archlinux') {
						/* For each version of the distribution */
						foreach($versions as $v) {
							/* 'All' is ignored */
							if ($v == 'All') {
								continue;
							}
							/* Add the version with all architectures */
							$this->newStruct[$key][$v] = $this->getApplication()->architectures;
						}
					} else {
						$this->newStruct[$key]['Rolling'] = $this->getApplication()->architectures;
					}
				} else { /* Only some versions are concerned */
					/* If the 'All' field is set */
					if (isset($value['All'])) {
						/* Saves her value */
						$content_all = $value['All'];
						/* If the variable is not an array, transforms it */
						if (! is_array($content_all)) {
							$content_all = array($content_all);
						}

						/* Particular case : Archlinux has not version, so we add an "artificial" version  */
						if ($key != 'Archlinux') {
							/* For each version which is not explicity specified in the configuration file */
							foreach(array_diff($versions, array_keys($value)) as $v) {
								$this->newStruct[$key][$v] = $content_all;
							}
						} else {
							$this->newStruct[$key]['Rolling'] = $content_all;
						}
						/* Remove this field (to avoid that the next foreach get 'All') */
						unset($value['All']);
					}		
					/* For each version of the distribution */
					foreach($value as $nv => $v) {
						/* If the version field contain an array (of architectures) */
						if (is_array($v)) {
							$this->newStruct[$key][$nv] = $v;
						} else { /* If there is only one architecture */
							/* The value is "*" (so all architectures) */
							if ($v == "*") {
								$this->newStruct[$key][$nv] = $this->getApplication()->architectures;
							} else {
								/* Transforms the architecture on a array */
								$this->newStruct[$key][$nv] = array($v);
							}
						}
					}
				}
			}
			$this->getApplication()->conf = $this->newStruct;
		}

		/* Optionnal argument (output file, which will be parsed) */
		$output_file = $input->getArgument('output');
		/* If the optionnal argument is present */
		if ($output_file) {
			/* Get references of the command write() */
			$command = $this->getApplication()->find('write');
			/* Declare the arguments in a array (arguments has to gave like this) */
			$arguments = array('command' => 'write', 'output' => $output_file);
			$array_input = new ArrayInput($arguments);
			/* Run command */
			$command->run($array_input, $output);
		}
	}
}
