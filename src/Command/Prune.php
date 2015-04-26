<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Prune extends Command
{
    /* Traduction versions Debian */
    public $dv_dist = array('Stable' => 'Wheezy', 'Testing' => 'Jessy');

    protected function configure()
    {
        $this
            ->setName('prune')
            ->setDescription('Prune a structure')
            ->addArgument(
                'structure',
                InputArgument::REQUIRED,
                'Data structure of a YaML file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $struct = $input->getArgument('structure');
        $logger = new ConsoleLogger($output);
        $new_struct = array();
        /* Récupérer le nom de la distribution (le "2> /dev/null" est pour éviter de récupérer le message
         * d'erreur "No LSB modules are available.", qui peut éventuellement apparaitre) */
        /* Notes techniques : la fonction trim() est utilisée pour retirer le retour chariot (qui semble être ajouté par shell_exec(),
         * l'option -r de sed est pour activer les expressions régulières avancées et pour s'affranchir de mettre des antislashes aux
         * parenthèses...  */
        $dist = trim(shell_exec('lsb_release -a 2> /dev/null | grep "Distributor ID" | sed -nr \'s/^.+	([A-Za-z ]+)$/\1/p\''));
        #$dist = "Debian" ; // DEBUG
        switch ($dist) {
        case 'Debian':
            $ver = ucfirst(trim(shell_exec('lsb_release -a 2> /dev/null | grep "Codename:" | grep -Eo "([A-Za-z]+)$"')));
            #$ver = "Wheezy" ;
            break;
        case 'Arch Linux':
            $dist = 'Archlinux';
            $ver = 'rolling';
            break;
        case 'Fedora':
            break;
        default:
            $logger->err('Distribution inconnue');

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
                            $new_struct['BuildDepends'][$compiler]['Common'] = $tab[0];
                        } else {
                            /* La distribution est cité et a donc une dépendance différente */
                            if (array_key_exists($dist, $tab)) {
                                /* La distribution est Debian */
                                if ($dist == 'Debian') {
                                    /* La version est référencée (par son nom, comme par exemple "wheezy") */
                                    if (array_key_exists($ver, $val['Debian'])) {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][$ver];
                                        /* La version est référencée (par le nom de branche, comme par exemple "testing") */
                                    } elseif (array_key_exists(array_search($ver, $GLOBALS['dv_dist']), $val['Debian'])) {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][array_search($ver, $GLOBALS['dv_dist'])];
                                        /* La version de la distribution en cours d'exécution n'est pas spécifiée, le cas général de la distribution ("All") s'applique donc */
                                    } else {
                                        $new_struct['BuildDepends'][$compiler]['Common'] = $val['Debian'][0]['All'];
                                    }
                                    /* La distribution est Archlinux */
                                } elseif ($dist == 'Archlinux') {
                                    /* Archlinux n'ayant pas de versions, le contenu du champ "All" s'applique systématiquement */
                                    $new_struct['BuildDepends'][$compiler]['Common'] = $val['Archlinux'][0]['All'];
                                } elseif ($dist == 'Fedora') {
                                    #TODO
                                }
                                /* La distribution n'est pas citée, le cas général ("Common") s'applique donc */
                            } else {
                                $new_struct['BuildDepends'][$compiler]['Common'] = $tab[0];
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
                                        $new_struct['Packages'][$package][$champ][$elem] = $tab['Common'];
                                    } else {
                                        if (array_key_exists($dist, $tab)) {
                                            if ($dist == 'Debian') {
                                                /* La version est référencée (par son nom, comme par exemple "wheezy") */
                                                if (array_key_exists($ver, $tab['Debian'])) {
                                                    $new_struct['Packages'][$package][$champ][$elem] = $tab['Debian'][$ver];
                                                    /* La version est référencée (par le nom de branche, comme
                                                     *  par exemple "testing") */
                                                } elseif (array_key_exists(array_search($ver, $GLOBALS['dv_dist']), $tab['Debian'])) {
                                                    $new_struct['Packages'][$package][$champ][$elem] = $tab['Debian'][array_search($ver, $GLOBALS['dv_dist'])];
                                                    /* La version de la distribution en cours d'exécution
                                                     *  n'est pas spécifiée, le cas général de la distribution ("All")
                                                     *  s'applique donc */
                                                } else {
                                                    $new_struct['Packages'][$package][$champ][$elem] = $tab['Debian']['All'];
                                                }
                                                /* La distribution est Archlinux */
                                            } elseif ($dist == 'Archlinux') {
                                                /* Archlinux n'ayant pas de versions, le contenu du champ "All" s'applique systématiquement */
                                                $new_struct['Packages'][$package][$champ][$elem] = $tab['Archlinux']['All'];
                                            } elseif ($dist == 'Fedora') {
                                                #TODO
                                            }
                                            /* La distribution n'est pas citée, le cas général ("Common") s'applique donc */
                                        } else {
                                            $new_struct['Packages'][$package][$champ][$elem] = $tab['Common'];
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

        return $new_struct;
    }
}
