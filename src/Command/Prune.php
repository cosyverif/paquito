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

	/* Defines the applications variables $dist_name, $dist_version and $dist_arch
	 * to inquire Paquito of the distribution where it runs (locally !!!) */
	protected function getDist() {
		/* The file /etc/os-release contains the informations about the distribution (what distribution,
		 * version and architecture is executed Paquito). Tests if this file exists */
		if (is_file('/etc/os-release')) {
			$array_ini = parse_ini_file('/etc/os-release');
			/* Get the name of the distribution */
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
				$logger->error($this->getApplication()->translator->trans('prune.exist'));

				exit(-1);
			}
		} else { /* If the file /etc/os-release doesn't exists */
			/* We try to find the specific file for a distribution */

			/* If the /etc/arch-release exists, so our distribution is Archlinux */
			if (is_file('/etc/arch-release')) {
				/* IMPORTANT : We don't need to read this file because Archlinux
				 * has not version (it is a rolling release) and is only a 64 bits
				 * architecture */
				$this->getApplication()->dist_name = 'Archlinux';
			} else if (is_file('/etc/centos-release')) {
				$this->getApplication()->dist_name = 'Centos';
				/* Read the content of the file /etc/centos-release */
				if (($version = file_get_contents('/etc/centos-release')) === FALSE) {
					$logger->error($this->getApplication()->translator->trans('prune.read', array('%file%' => '/etc/centos-release')));

					return -1;
				}
				/* Get the version of the Centos distribution */
				preg_match('/[0-9](\.[0-9])?/', $version, $match);
				$this->getApplication()->dist_version = $match[0];
			} 
		}
		/* Get the architecture of the current machine */
		$this->getApplication()->dist_arch = posix_uname();
		$this->getApplication()->dist_arch = $this->getApplication()->dist_arch['machine'];
	}

	/* Prunes a 'Packages' structure with current distribution ($dist_name),
	 * version ($dist_version) and architecture ($dist_arch)
	 * @param $struct : 'Packages' structure */
	protected function prune_structure($struct) {
		/* Copy the initial structure of the configuration file. The new structure will be modified */
		$new_struct['Packages'] = $struct;
		/* For each package */
		foreach ($struct as $key => $value) {
			$key_dependencies = array('Build', 'Runtime', 'Test');
			/* For each field (in others words 'Build', 'Runtime' and 'Test') */
			for ($i = 0; $i < 3; ++$i) {
				/* If there are dependencies in the field Build/Runtime/Test */
				if (isset($struct[$key][$key_dependencies[$i]]['Dependencies'])) { 
					/* To clear the follow code */
					$depend_struct = $struct[$key][$key_dependencies[$i]]['Dependencies'];
					/* It has to remove the 'Dependencies' structure in the new structure, to have new 'Dependencies' structure */
					unset($new_struct['Packages'][$key][$key_dependencies[$i]]['Dependencies']);
					/* For each dependency */
					foreach ($depend_struct as $d_key => $d_value) {
						/* If there is a field having the name of the current distribution */
						if (isset($depend_struct[$d_key][$this->getApplication()->dist_name])) {
							if ($this->getApplication()->dist_name != 'Archlinux') {
								/* The version is referenced (by her name, like for example "wheezy" for Debian ; the version number for CentOS) */
								if (array_key_exists($this->getApplication()->dist_version, $depend_struct[$d_key][$this->getApplication()->dist_name])) {
									$src_field = $this->getApplication()->dist_version;
								} else {
									/* To prepare the next condition and avoid that the function array_key_exists() works
									 *  with an empty value ($result may be empty, when there is only the field 'All') */
									$result = array_search($this->getApplication()->dist_version, $this->getApplication()->alias_distributions[$this->getApplication()->dist_name]);
									/* The version is referenced (by the branch name, like for example "testing") */
									if (!empty($result) && array_key_exists($result, $depend_struct[$d_key][$this->getApplication()->dist_name])) { 
										$src_field = $result;
									} else { /* The version of the current distribution is not specified, the general case of the distribution ("All") applies */
										$src_field = 'All';
									}
								}
							} else { /* The distribution is Archlinux */
								/* Archlinux doesn't have versions (rolling release), the content of the field "All" always applies */
								$src_field = 'All';
							}
							/* If the source field contains a array (in others words, the field contains several dependencies) */
							if (is_array($depend_struct[$d_key][$this->getApplication()->dist_name][$src_field])) {
								/* The dependencies are organized in list and added with others dependencies */
								foreach ($depend_struct[$d_key][$this->getApplication()->dist_name][$src_field] as $dependency) {
									$new_struct['Packages'][$key][$key_dependencies[$i]]['Dependencies'][] = $dependency ;
								}
							} else {
								$new_struct['Packages'][$key][$key_dependencies[$i]]['Dependencies'][] = $depend_struct[$d_key][$this->getApplication()->dist_name][$src_field];
							}
						}
					}
				}
				/* Sometimes, the "Build"/"Runtime"/"Test" section can contains only one dependency (any
				 * other keyword). This dependency, for a specific distribution, can be erased (<none>
				 * keyword) so the section is empty. If the section is empty, we delete this section */
				if (empty($new_struct['Packages'][$key][$key_dependencies[$i]])) {
					unset($new_struct['Packages'][$key][$key_dependencies[$i]]);
				}
			}
		}
		return $new_struct;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/* Get path and name of the input file */
		$input_file = $input->getArgument('input');
		/* Get presence of the "--local" option */
		$local = $input->getOption('local');
		/* Get references of the command parse() */
		$command = $this->getApplication()->find('normalize');
		/* Declare the arguments in a array (arguments has to gave like this) */
		$arguments = array(
			'command' => 'normalize',
			'input' => $input_file,
			'--local' => $local,
		);
		$array_input = new ArrayInput($arguments);
		/* Run command */
		$command->run($array_input, $output);

		/* Get structure of YaML file (which was parsed and checked) */
		$struct = $this->getApplication()->data;
		/* Launch Logger module */
		$logger = new ConsoleLogger($output);

		/* If the "--local" option is not set, so there are several pruned YAML structure */
		if (! $local) {
			/* Get the structure of the YaML file (which was parsed) */
			$conf = $this->getApplication()->conf;
			/* For each distribution */
			foreach($conf as $dist => $tab_ver) {
				/* For each version */
				foreach($tab_ver as $ver => $tab_archi) {
					/* For each architecture */
					foreach($tab_archi as $archi) {
						$this->getApplication()->dist_name = $dist;
						$this->getApplication()->dist_version = $ver;
						$this->getApplication()->dist_arch = $archi;
						/* When the generation is not local (for several distributions/versions/architectures), the
						 * pruned structure for the current configuration is stored in the root field named
						 * 'Distributions' then the name of the distribution -> the version name -> the architecture */
						$this->getApplication()->data['Distributions'][$dist][$ver][$archi] = $this->prune_structure($struct['Packages']);
					}
				}
			}
			/* Removes the original field 'Packages', now useless */
			unset($this->getApplication()->data['Packages']);
		} else { /* The prune will be only for the current distribution (where is launched Paquito) */
			/* Gets the local distribution, version and architecture */
			$this->getDist();
			/* The new pruned 'Packages' structure returned by prune_structure()
			 * function is merged with the YAML structure */
			$this->getApplication()->data = array_merge($this->getApplication()->data, $this->prune_structure($struct['Packages']));
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
