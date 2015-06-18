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
    $array_ini = parse_ini_file('/etc/os-release');
    /* Get the name of the distribution */
    $dist = ucfirst($array_ini['ID']);
        switch ($dist) {
    case 'Debian':
        preg_match('/[a-z]+/', $array_ini['VERSION'], $match);
        $ver = ucfirst($match[0]);
            break;
    case 'Arch':
        /* TODO Install on Archlinux the package "filesystem" */
            $dist = 'Archlinux';
            break;
        case 'Centos':
        preg_match('/[0-9](\.[0-9])?/', $array_ini['VERSION'], $match);
        $ver = $match[0];
        if (strlen($ver) == 1) {
            $ver = $ver.'.0';
        }
            break;
        default:
            $logger->error($this->getApplication()->translator->trans('prune.exist'));

            return -1;
        }
        foreach ($struct as $cle => $val) {
            if ($cle == 'BuildDepends') {
                if (empty($val)) {
                    $new_struct[$cle] = $val;
                } else {
                    $build = $val;
                    /* Pour chacun des "compilers" existants */
                    foreach ($build as $cle => $val) {
                        /* Nom du "compiler" courant */
                        $compiler = $cle;
                        $tab = $val;
                        /* Il n'y aucun cas particulier, le cas général s'applique donc */
                        if (count($tab) == 1) {
                            $new_struct['BuildDepends'][$compiler]['Common'] = $tab['Common'];
                        } else {
                            /* La distribution est cité et a donc une dépendance différente */
                            if (array_key_exists($dist, $tab)) {
                                /* La distribution est Debian */
                                if ($dist == 'Debian') {
                                    /* La version est référencée (par son nom, comme par exemple "wheezy") */
                                    if (array_key_exists($ver, $val['Debian'])) {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][$ver];
                                        /* La version est référencée (par le nom de branche, comme par exemple "testing") */
                                    } elseif (array_key_exists(array_search($ver, $this->dv_dist), $val['Debian'])) {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][array_search($ver, $this->dv_dist)];
                                        /* La version de la distribution en cours d'exécution n'est pas spécifiée, le cas général de la distribution ("All") s'applique donc */
                                    } else {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][0]['All'];
                                    }
                                    /* La distribution est Archlinux */
                                } elseif ($dist == 'Archlinux') {
                                    /* Archlinux n'ayant pas de versions, le contenu du champ "All" s'applique systématiquement */
                                    $new_struct['BuildDepends'][$compiler]['Common'] = $val['Archlinux']['All'];
                                    /* La distribution est CentOS */
                                } elseif ($dist == 'Centos') {
                                    /* La version est référencée (pour CentOS, toujours par son numéro de version) */
                                    if (array_key_exists($ver, $val['Centos'])) {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Centos'][$ver];
                                        /* La version de la distribution en cours d'exécution n'est pas spécifiée, le cas général de la distribution ("All") s'applique donc */
                                    } else {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Centos']['All'];
                                    }
                                }
                                /* La distribution n'est pas citée, le cas général ("Common") s'applique donc */
                            } else {
                                $new_struct['BuildDepends'][$compiler]['Common'] = $tab['Common'];
                            }
                        }
                    }
                }
            } elseif ($cle == 'Packages') {
                /* La variable $glob contient un tableau mentionnant les paquets qu'on souhaite créer */
                $glob = $val;
                /* Pour chaque paquet */
                foreach ($glob as $cle => $val) {
                    /* La variable $package contient le nom du paquet */
                    $package = $cle;
                    /* Contient les champs fils relatifs au paquet courant */
                    $tab = $val;
                    /* Pour chacun des champs du paquet courant */
                    foreach ($tab as $cle => $val) {
                        /* La clé désigne un champ "Type" ou "Files" */
                        if ($cle == 'Type' || $cle == 'Files') {
                            /* Ces deux clés n'ont pas besoin d'être modifiés */
                            $new_struct['Packages'][$package][$cle] = $val;
                            /* Les autres clés */
                        } else {
                            /* Stocke le nom du champ courant */
                            $champ = $cle;
                            /* Si le champ courant ("RunTimeDependency", "BeforeBuild"
                             * ou "AfterBuild") ne contient rien */
                            if (empty($val)) {
                                /* La variable $val est vide mais elle est quand même assignée à la clé courante
                                 * car le fichier YaML de Paquito doit nécessairement posséder cette clé */
                                $new_struct['Packages'][$package][$cle] = $val;
                            } else {
                                /* Stocke le contenu du champ actuel */
                                $Table = $val;
                                /* Pour chacune des dépendances ("RunTimeDependency") ou
                                 * commandes ("BeforeBuild" ou "AfterBuild") */
                                foreach ($Table as $cle => $val) {
                                    /* Nom du "runtime"/"command" courant */
                                    $elem = $cle;
                                    $tab = $val;
                                    /* S'il n'y a pas de cas particuliers (c'est-à-dire que le nom de la
                                     * dépendance ou que la commande est pareil pour toutes les distributions) */
                                    if (count($tab) == 1) {
                                        $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Common'];
                                    } else {
                                        if (array_key_exists($dist, $tab)) {
                                            if ($dist == 'Debian') {
                                                /* La version est référencée (par son nom, comme par exemple "wheezy") */
                                                if (array_key_exists($ver, $tab['Debian'])) {
                                                    $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Debian'][$ver];
                                                    /* La version est référencée (par le nom de branche, comme
                                                     *  par exemple "testing") */
                                                } elseif (array_key_exists(array_search($ver, $this->dv_dist), $tab['Debian'])) {
                                                    $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Debian'][array_search($ver, $this->dv_dist)];
                                                    /* La version de la distribution en cours d'exécution
                                                     *  n'est pas spécifiée, le cas général de la distribution ("All")
                                                     *  s'applique donc */
                                                } else {
                                                    $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Debian']['All'];
                                                }
                                                /* La distribution est Archlinux */
                                            } elseif ($dist == 'Archlinux') {
                                                /* Archlinux n'ayant pas de versions, le contenu du champ "All" s'applique systématiquement */
                                                $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Archlinux']['All'];
                                            } elseif ($dist == 'Centos') {
                                                /* La version est référencée (pour CentOS, toujours par son numéro de version) */
                            if (array_key_exists($ver, $val['Centos'])) {
                                $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Centos'][$ver];
                                /* La version de la distribution en cours d'exécution n'est pas
                                 * spécifiée, le cas général de la distribution ("All") s'applique donc */
                            } else {
                                $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Centos']['All'];
                            }
                                            }
                                            /* La distribution n'est pas citée, le cas général ("Common") s'applique donc */
                                        } else {
                                            $new_struct['Packages'][$package][$champ][$elem]['Common'] = $tab['Common'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $new_struct[$cle] = $val;
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
