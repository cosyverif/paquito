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
    /* Traduction versions Debian */
    public $dv_dist = array('Stable' => 'Wheezy', 'Testing' => 'Jessie');

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get path and name of the input file */
    $input_file = $input->getArgument('input');
    /* Get references of the command parse() */
    $command = $this->getApplication()->find('normalize');
    /* Declare the arguments in a array (arguments has to gave like this) */
    $arguments = array(
        'command' => 'normalize',
        'input' => $input_file,
    );
        $array_input = new ArrayInput($arguments);
    /* Run command */
    $command->run($array_input, $output);

    /* Get structure of YaML file (which was parsed and checked) */
    $struct = $this->getApplication()->data;
    /* Launch Logger module */
        $logger = new ConsoleLogger($output);

    /* This array will contain the new structure */
    $new_struct = array();
    /* The file /etc/os-release contains the informations about the distribution (where is executed this program)*/
    /* TODO Sous Archlinux, la fonction parse_ini_files() ne marchera pas si la variable "open_basedir" du fichier /etc/php/php.ini
     * n'inclue pas le chemin /usr/lib/ (qui est la vraie localisation du fichier "os-release" */
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
    } else {
	    if (is_file('/etc/arch-release')) {
		    $this->getApplication()->dist_name = 'Archlinux';
	    } else if (is_file('/etc/centos-release')) {
		    $this->getApplication()->dist_name = 'Centos';
		    if (($version = file_get_contents('/etc/centos-release')) === FALSE) {
			echo "erreur lecture fichier\n";
			exit(-1);
		    }
		    preg_match('/[0-9](\.[0-9])?/', $version, $match);
		    $this->getApplication()->dist_version = $match[0];
		    
	    } 
    }
	/* Get the architecture of the current machine */
	$this->getApplication()->dist_arch = posix_uname();
	$this->getApplication()->dist_arch = $this->getApplication()->dist_arch['machine'];

    /* Copy the initial structure of the configuration file. The new structure will be modified */
    $new_struct = $struct;
        foreach ($struct['Packages'] as $key => $value) {
            $key_dependencies = array('Build', 'Runtime', 'Test');
			/* For each field (in others words 'Build', 'Runtime' and 'Test') */
			for ($i = 0; $i < 3; ++$i) {
				/* If there are dependencies in the field Build/Runtime/Test */
				if (isset($struct['Packages'][$key][$key_dependencies[$i]]['Dependencies'])) { 
					/* To clear the follow code */
					$depend_struct = $struct['Packages'][$key][$key_dependencies[$i]]['Dependencies'];
					/* It has to remove the pre-dependencies structure in the new structure, to keep new "dependency" structure */
					unset($new_struct['Packages'][$key][$key_dependencies[$i]]['Dependencies']);
					/* For each dependency */
					foreach ($depend_struct as $d_key => $d_value) {
						/* If there is a field having the name of the current distribution */
						if (isset($depend_struct[$d_key][$this->getApplication()->dist_name])) {
							if ($this->getApplication()->dist_name != 'Archlinux') {
								/* The version is referenced (by her name, like for example "wheezy" for Debian ; the version number for CentOS) */
								if (array_key_exists($this->getApplication()->dist_version, $depend_struct[$d_key][$this->getApplication()->dist_name])) {
									$src_field = $this->getApplication()->dist_version;
									/* La version est référencée (par le nom de branche, comme par exemple "testing") */
								} elseif (array_key_exists(array_search($this->getApplication()->dist_version, $this->dv_dist), $depend_struct[$d_key][$this->getApplication()->dist_name])) {
									$src_field = array_search($this->getApplication()->dist_version, $this->dv_dist);
								} else { /* La version de la distribution en cours d'exécution n'est pas spécifiée, le cas général de la distribution ("All") s'applique donc */
									$src_field = 'All';
								}
							} else { /* The distribution is Archlinux */
								/* Archlinux doesn't have versions (rolling release), the content of the field "All" always applies */
								$src_field = 'All';
							}
							/* If the source field contains a array (in others words, the field contains several dependencies) */
							if (is_array($depend_struct[$d_key][$this->getApplication()->dist_name][$src_field])) {
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
        $this->getApplication()->data = $new_struct;
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
