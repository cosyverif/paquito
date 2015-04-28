<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class Normalize extends Command
{
    public $newStruct = array() ; 

    protected function configure()
    {
        $this
            ->setName('normalize')
            ->setDescription('Normalize a YaML file')
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
        /* Get path and name of the input file */
	$input_file = $input->getArgument('input');
	/* Get references of the command parse() */
	$command = $this->getApplication()->find('check');
	/* Declare the arguments in a array (arguments has to gave like this) */
	$arguments = array(
		'command' => 'check',
		'input'    => $input_file,
	);
	$array_input = new ArrayInput($arguments);
	/* Run command */
	$command->run($array_input, $output);

	/* Get structure of YaML file (which was parsed and checked) */
	$struct = $this->getApplication()->data;

        /* Note: The variable $newStruct will owns the new structure (which becomes of the normalization) */
        foreach ($struct as $key => $val) {
            if ($key == 'BuildDepends') {
                /* Le champ "BuildDepends" n'existe pas */
                if (empty($val)) {
                    /* La variable $val est vide mais elle est quand même assignée à la clé "BuildDepend"
                     * car le fichier YaML de Paquito doit nécessairement posséder cette clé */
                    $this->newStruct[$key] = $val;
                    /* Le champ "BuildDepends" existe */
                } else {
		    $this->check_builddependency($val, 'BuildDepends');
                }
                /* Les autres champs (de 1er niveau) ne seront pas modifiés */
            } elseif ($key == 'Packages') {
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
                        /* La clé désigne un champ "Type" ou "Files" */
                        if ($key == 'Type' || $key == 'Files') {
                            /* Ces deux clés n'ont pas besoin d'être normalisées */
                            $this->newStruct['Packages'][$package][$key] = $val;
                            /* Les autres clés */
                        } else {
                            /* Stocke le nom du champ courant */
                            $champ = $key;
                            /* Si le champ courant ("RunTimeDependency", "BeforeBuild"
                             * ou "AfterBuild") ne contient rien */
                            if (empty($val)) {
                                /* La variable $val est vide mais elle est quand même assignée à la clé courante
                                 * car le fichier YaML de Paquito doit nécessairement posséder cette clé */
                                $this->newStruct['Packages'][$package][$key] = $val;
                            } else {
                                /* Stocke le contenu du champ actuel */
                                $Table = $val;
                                /* Pour chacune des dépendances ("RunTimeDependency") ou
                                 * commandes ("BeforeBuild" ou "AfterBuild") */
                                foreach ($Table as $key => $val) {
				    /* Nom du "runtime"/"command" courant */
                                    $elem = $key;
                                    $tab = $val;
                                    /* S'il n'y a pas de cas particuliers (c'est-à-dire que le nom de la
                                     * dépendance ou que la commande est pareil pour toutes les distributions) */
                                    if (count($tab) == 1) {
                                        $this->newStruct['Packages'][$package][$champ][$elem]['Common'] = $tab[0];
                                    } else {
                                        $j = 1;
                                        /* Pour chacun des éléments (comprenant le nom supposément commun de la
                                         * dépendance ou la commande commune ainsi que les variations par distribution) */
                                        foreach ($tab as $key => $val) {
                                            /* Le premier cas particulier aura un traitement différent (ajout du
                                             * mot-clé "Common" et réorganisation de la structure en conséquence) */
                                            if ($j) {
                                                /* La variable $val représente ici le nom
                                                 * supposément commun de la dépendence ou la commande */
                                                $this->newStruct['Packages'][$package][$champ][$elem]['Common'] = $val;
                                                $j--;
                                            } else {
                                                /* La variable $val représente ici un tableau
                                                 * qui recense les cas particuliers */
                                                $t = $val;
                                                /* Pour chacune des distributions dont
                                                 * le nom de la dépendance/commande diffère */
                                                foreach ($t as $key => $val) {
                                                    $this->newStruct['Packages'][$package][$champ][$elem][$key] = $val;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $this->newStruct[$key] = $val;
            }
        }
	$this->getApplication()->data = $this->newStruct ;
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

    protected function check_builddependency($array, $field)
    {
	    /* For each of existing "compiler", "runtime"... */
	    foreach ($array as $key => $value) {
		    /* Store the name of the current "compiler"... */
		    $cd_name = $key;
		    /* Store the value of the current "compiler"... */
		    $cd_field = $value;
		    /* There are not particular cases (in others words, the command/dependency is the same for every distribution) */
		    if (count($cd_field) == 1) {
			    $this->newStruct[$field][$cd_name]['Common'] = $cd_field[0];
		    /* If there are particular cases (in others words, one or several distributions have specific command/dependency) */
		    } else {
			    $j = 1;
			    /* For each element (which has the common command/dependency and the varieties by distribution) */
			    foreach ($cd_field as $key => $value) {
				    /* The first particular case will be a different treatement (adding of the keyword "Common" and reorganization of the structure accordingly) */
				    if ($j) {
					    /* The variable $value represents here the common command/dependency */
					    $this->newStruct[$field][$cd_name]['Common'] = $value;
					    $j--;
				    } else {
					    /* The variable $value represents here a array which owns the particular cases */
					    $t = $value;
					    /* For each of the distributions where the name of the command/distribution differs */
					    foreach ($t as $key => $value) {
						    $this->newStruct[$field][$cd_name][$key] = $value;
					    }
				    }
			    }
		    }
	    }
    }
}
