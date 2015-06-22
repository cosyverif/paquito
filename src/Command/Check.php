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
    /* First level keys (of the Paquito configuration files) */
    public $first_level_keys = array('Name', 'Version', 'Homepage', 'Description', 'Copyright', 'Maintainer', 'Authors', 'BuildDepends', 'Packages');
    /* Known distributions */
    public $dist = array('Debian', 'Archlinux', 'Centos');
    /* Known versions (Debian) */
    public $versions = array(
        array('All', 'Stable', 'Testing', 'Wheezy', 'Jessy'), /* Debian */
        array('All', '6.6', '7.0'), ); /* CentOS */
    #public $v_deb = array('All', 'Stable', 'Testing', 'Wheezy', 'Jessy');
    /* Known versions (CentOS) */
    #public $v_cent = array('All', '6.6', '7.0');
    /* Package types */
    public $typePackage = array('binary', 'library', 'source', 'arch_independant');

    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Check validity of a YaML file')
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
        $logger = new ConsoleLogger($output);

        /* Analysis of the size of the structure (which has to own the expected 10 fields of the first level) */
        if (count($struct) != 10) {
            $logger->error($this->getApplication()->translator->trans('check.number'));

            exit(-1);
        }

        /* Recherche parmi les clés de premier niveau connues celles qui n'apparaissent pas dans la structure */
        foreach ($this->first_level_keys as $value) {
            /* Si la clé courante n'existe pas dans la structure */
            if (!array_key_exists($value, $struct)) {
                $logger->error($this->getApplication()->translator->trans('check.field', array('%value%' => $value)));

                exit(-1);
            }
            if ($value == 'BuildDepends') {
                /* "BuildDepends" contient quelque chose */
                if (!empty($struct[$value])) {
                    /* Stocke le contenu de "BuildDepends" */
                    $Depends = $struct[$value];

                    /* Le contenu de "BuildDepends" n'est pas un tableau */
                    if (!is_array($Depends)) {
                        $logger->error($this->getApplication()->translator->trans('check.tab_compiler'));

                        exit(-1);
		    }
		/* Foreach "Compiler" keyword */
		    foreach($Depends as $key => $value) {
			    if (preg_match('/^Compiler([1-9][0-9]*)$/', $key) == 0) {
			    //if (ereg("^Compiler([1-9][0-9]*)$", $key)) {
			    //if (substr($key, 0, 8) != "Compiler") {
				    $logger->error($this->getApplication()->translator->trans('check.writing_compiler', array('%field%' => $key)));

				    exit(-1);
			    }
		    }
                    $this->check_command_dependency($Depends, $logger);
                }
                /* Le champ de premier niveau courant est "Packages" */
            } elseif ($value == 'Packages') {
                /* Aucun paquet n'a été formulé */
                if (empty($struct[$value])) {
                    $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $value)));

                    exit(-1);
                } else {
                    /* Stocke la structure contenant l'ensemble des paquets */
                    $Packages = $struct[$value];
                    /* La structure des paquets n'est pas un tableau  */
                    if (!is_array($Packages)) {
                        $logger->error($this->getApplication()->translator->trans('check.tab_field', array('%key%' => 'Packages')));

                        exit(-1);
                    }
                    /* Pour chaque paquet */
                    foreach ($Packages as $key => $val) {
                        /* Aucune donnée relative au paquet courant n'a été formulée */
                        if (empty($val)) {
                            $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key)));

                            exit(-1);
                        }
                        /* La structure du paquet courant n'est pas un tableau */
                        if (!is_array($val)) {
                            $logger->error($this->getApplication()->translator->trans('check.tab_field', array('%key%' => $key)));

                            exit(-1);
                        }
                        /* Stocke les champs du paquet courant */
                        $champs = $val;
                        /* Pour chaque champ du paquet courant */
                        foreach ($champs as $key => $val) {
                            if ($key == 'Type') {
                                /* Aucun type n'est donné au paquet courant */
                                if (empty($val)) {
                                    $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key)));

                                    exit(-1);
                                }
                                /* Le type n'est pas connu */
                                if (!in_array($val, $this->typePackage)) {
                                    $logger->error($this->getApplication()->translator->trans('check.package', array('%val%' => $val)));

                                    exit(-1);
                                }
                            } elseif ($key == 'Files') {
                                /* Aucun fichier n'est donné pour le paquet courant */
                                if (empty($val)) {
                                    $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key)));

                                    exit(-1);
                                }
                                /* Les fichiers ne sont pas représentés sous forme d'un fichier */
                                if (!is_array($val)) {
                                    $logger->error($this->getApplication()->translator->trans('check.tab_field', array('%key%' => $key)));

                                    exit(-1);
                                }
                            }
                            /* Le champ courant est "RunTimeDepends", "BeforeBuild" ou "AfterBuild" */
                            if ($key == 'RunTimeDepends' || $key == 'BeforeBuild' || $key == 'AfterBuild') {
                                /* "BuildDepends" contient quelque chose */
				    if (!empty($val)) {
					    /* Le champ courant ne contient pas un tableau */
					    if (!is_array($val)) {
						    $logger->error($this->getApplication()->translator->trans('check.tab_field', array('%key%' => $key)));

						    exit(-1);
					    }
					    /* Stocke le contenu du champ actuel */
					    //$Table = $val;
					    $this->check_command_dependency($val, $logger);
				    }
			    }
			}
		    }
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

    protected function check_command_dependency($array, ConsoleLogger $logger)
    {
        /* For each "compiler", "runtime" or "command" */
        foreach ($array as $key => $value) {
            /* The content of the current "compiler", "runtime"... is empty (it has to contain at least the name of the command/dependency) */
            if (empty($value)) {
                $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key)));

                exit(-1);
            }
            /* The content of the current "compiler"... is not an array */
            if (!is_array($value)) {
                $logger->error($this->getApplication()->translator->trans('check.tab_depends', array('%key%' => $key)));

                exit(-1);
            }
            /* Store the name of the current "compiler"... */
            $cd_name = $key;
            /* Store the value of the current "compiler"... */
            $cd_field = $value;
            /* For each field of the current "compiler"... (the fields contain the common command/distribution and one sub-array of the distribution) */
            foreach ($cd_field as $key => $value) {
                /* The current field is an array */
                if (is_array($value)) {
                    /* Store the name of the common distribution name */
                    $distrib = $value;
                    /* For each field of the current distribution (in others words, its versions) */
                    foreach ($distrib as $key => $value) {
                        /* Unknown distribution name or the distribution is not managed */
                        if (!in_array($key, $this->dist)) {
                            $logger->error($this->getApplication()->translator->trans('check.dist', array('%key%' => $key)));

                            exit(-1);
                        }
                        $distribution = $key;
                        /* Store the structure of the current distribution */
                        $version = $value;
                        /* No information is stored for the current distribution */
                        if (empty($version)) {
                            $logger->error($this->getApplication()->translator->trans('check.content_field', array('%key%' => $key, '%field_name%' => 'All')));

                            exit(-1);
                        }
                        /* The content of the current distribution is not an array */
                        if (!is_array($version)) {
                            $logger->error($this->getApplication()->translator->trans('check.tab_field', array('%key%' => $key)));

                            exit(-1);
                        }
                        /* For each version of the current distribution */
            foreach ($version as $key => $val) {
                /* Depending of the distribution, this ID will be defined to find the good array of versions */
                /* IMPORTANT 0 is the ID of Debian */
                $id_dist = 0;
                            /* There is only one key (which has to "All", so all versions of the current distribution have the same name of dependency) */
		if (count($version) == 1) {
                                /* The key is not "All" */
                                if ($key != 'All') {
                                    $logger->error($this->getApplication()->translator->trans('check.name_field', array('%field_name%' => 'All', '%key' => $key)));

                                    exit(-1);
                                }
                            }

                /* Verifies the versions depending on the distribution */
                if ($distribution == 'Centos') {
                    $id_dist = 1;
                }
                /* The current version is unknown or not managed */
                if (!in_array($key, $this->versions[$id_dist])) {
                    $logger->error($this->getApplication()->translator->trans('check.version', array('%key%' => $key)));

                    exit(-1);
                }

                            /* The current version doesn't contain any name of dependency */
                            if (empty($val)) {
                                $logger->error($this->getApplication()->translator->trans('check.void', array('%key%' => $key)));

                                exit(-1);
                            }
            }
                    }
                } elseif (empty($value)) {
                    $logger->error($this->getApplication()->translator->trans('check.common_empty', array('%name%' => $cd_name)));

                    exit(-1);
                }
            }
        }
    }
}
