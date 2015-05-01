<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\ArrayInput;

class Generate extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate a package')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Name of a YaML file'
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
        /* Get the path and the name of the input file */
	$input_file = $input->getArgument('input');
	/* Get the references of the command parse() */
	$command = $this->getApplication()->find('prune');
	/* Declare the arguments in a array (arguments have to be given like this) */
	$arguments = array(
		'command' => 'prune',
		'input'    => $input_file,
	);
	$array_input = new ArrayInput($arguments);
	/* Run command */
	$command->run($array_input, $output);

	/* Get the structure of the YaML file (which was parsed) */
	$struct = $this->getApplication()->data;
	/* Launch Logger module */
        $logger = new ConsoleLogger($output);

	/* This array will contains the new structure */
	$new_struct = array() ;
	/* The file /etc/os-release contains the informations about the distribution (where is executed this program)*/
	$array_ini = parse_ini_file("/etc/os-release") ;
	/* Get the name of the distribution */
	$dist = ucfirst($array_ini['ID']) ;
	$dist = "Debian" ; 
	switch ($dist) {
	case 'Debian':
     	    $package_system = "apt-get -y install" ;
   	    break;
	case 'Arch':
	    /* TODO Install on Archlinux the package "filesystem" */
            $dist = 'Archlinux';
     	    $package_system = "pacman --noconfirm -S" ;
            break;
        case 'Fedora':
     	    $package_system = "yum -y install" ;
            break;
        default:
            $logger->error($this->getApplication()->translator->trans('prune.exist'));

            return -1;
	}

	$arch = posix_uname() ;
	if ($arch['machine'] == "x86_64") {
		$arch = "amd64" ;
	} else {
		$arch = "i386" ;
	}
	
	/* This variable will contains the list of dependencies (to build) */
	$list_buildepend = $this->generate_list_dependencies($struct['BuildDepends']) ;
	/* Get all build dependencies */
	if (!empty($struct['BuildDepends'])) {
		/* Concatenate all build dependencies on one line */
		foreach ($struct['BuildDepends'] as $value) {
			$list_buildepend .= " " . $value['Common'] ;
		}
	}
	/* For each package */
	foreach($struct['Packages'] as $key => $value) {
		$struct_package = $value ;
		if ($dist == "Debian") {
			if ($value['Type'] == "binary") {
				$package_arch = $arch ;
			} else {
				$package_arch = "all" ;
			}

			$dirname = $key.'_'.$struct['Version'].'_'.$package_arch ;
			/* Create the directory of the package */
			if (!mkdir($dirname) && !is_dir($dirname)) {
				die('Echec lors de la création des répertoires...');
			}

			/* Create the directory "DEBIAN/" (which is required) */
			if (!mkdir($dirname.'/DEBIAN') && !is_dir($dirname.'/DEBIAN')) {
				echo('Echec lors de la création des répertoires...');
			}

			/* This variable will contains the list of dependencies (to run) */
			$list_rundepend = str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['RunTimeDepends'])) ;
			$array_field = array('Package' => "$key",
					'Version' => $struct['Version'],
					'Section' => 'unknown',
					'Priority' => 'optional',
					'Maintainer' => $struct['Maintainer'],
					'Architecture' => $package_arch,
					'Depends' => $list_rundepend,
					'Homepage' => $struct['Homepage'],
					'Description' => $struct['Summary']."\n ".$struct['Description']) ;
			/* Create and open the file "control" (in write mode) */
			$handle = fopen($dirname.'/DEBIAN/control', "w");
			/* For each field that will contains the file "control" */
			foreach($array_field as $key => $value) {
				if (fwrite($handle, "$key: $value\n") === false) {
					echo "Erreur d'écriture dans le fichier".$key.'/DEBIAN/control'."\n";

					return -1;
				}
			}
			/* Add a line at the end (required) */
			if (fwrite($handle, "\n") === false) {
				echo "Erreur d'écriture dans le fichier".$key.'/DEBIAN/control'."\n";

				return -1;
			}
			fclose($handle);
			/* To come back in actual directory if a "cd" command is present in pre-build commands */
			$pwd = getcwd() ;
			/* If there are pre-build commands */
			if (!empty($struct_package['BeforeBuild'])) {
				/* Execute each command */
				foreach ($struct_package['BeforeBuild'] as $key => $value) {
					/* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
					if (preg_match ('/cd (.+)/', $value['Common'], $matches)) {
						chdir($matches[1]) ;
					} else {
						echo shell_exec($value['Common']);
					}
				}
			}
			/* To come back in usual directory if a "cd" command was present in pre-build commands */
			chdir($pwd) ;
			/* Move the files specified in the configuration file */
			$this->move_files($dirname, $struct_package['Files']) ;
			/* Create the DEB package */
			echo shell_exec("dpkg-deb --build $dirname") ;
		}
	}


	$this->getApplication()->data = $new_struct ;
	/* Optionnal argument (output file, which will be parsed) */
	$output_file = $input->getArgument('output');
	/* If the optionnal argument is present */
	if ($output_file) {
		/* Get references of the command write() */
		$command = $this->getApplication()->find('write');
		/* Declare the arguments in a array (arguments has to gave like this) */
		$arguments = array(
			'command' => 'write',
			'output'    => $output_file,
		);
		$array_input = new ArrayInput($arguments);
		/* Run command */
		$command->run($array_input, $output);
	}
    }

    protected function move_files($dest_directory, $struct)
    {
	    /* Copy each file in the directory of the current package */
	    /* TODO Faire wildcard */
	    foreach($struct as $key => $value) {
		    /* The destination file will be in a sub-directory */
		    if (strrpos($key, '/') !== FALSE) {
			    /* Split the path in a array */
			    $explode_array = explode('/', ltrim($key, '/')) ;
			    /* Remove the name of the file */
			    unset($explode_array[count($explode_array) - 1]);
			    /* Transform the array in a string */
			    $directory = implode('/', $explode_array) ;
			    /* Create recursively the directories */
			    if (!mkdir($dest_directory.'/'.$directory.'/', 0777, true) && !is_dir($dest_directory.'/'.$directory.'/')) {
				    echo("Echec lors de la création des répertoires...\n");
				    return -1 ;
			    }
		    }
		    if (is_dir($dest_directory.$key)) {
			    /* Split the path in a array */
			    $explode_array = explode('/', ltrim($value, '/')) ;
			    /* Copy the file in the directory package */
			    if (!copy($value, $dest_directory.$key.$explode_array[count($explode_array)-1])) {
				    echo "La copie $value du fichier a échouée\n";

				    return -1;
			    }
		    } else {
			    /* Copy the file in the directory package */
			    if (!copy($value, $dest_directory.$key)) {
				    echo "La copie $value du fichier a échouée\n";

				    return -1;
			    }
		    }
	    }
    }

    protected function generate_list_dependencies($struct)
    {
	$list = null ;
	/* If there are dependencies */
	if (!empty($struct)) {
		/* Concatenate all build dependencies on one line */
		foreach ($struct as $value) {
			$list .= " " . $value['Common'] ;
		}
	}
	/* Delete superfluous element (space) */
	return $list = ltrim($list, ' ');
    }
}
