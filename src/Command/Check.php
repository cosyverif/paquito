<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Check extends Command
{
    /* Clés de premier niveau */
    public $first_level_keys = array('Name', 'Version', 'Homepage', 'Description', 'Copyright', 'Maintainer', 'Authors', 'BuildDepends', 'Packages');
    /* Distributions prises en charge */
    public $dist = array('Debian', 'Archlinux', 'Fedora');
    /* Versions prises en charge */
    public $v_dist = array('All', 'Stable', 'Testing', 'Wheezy', 'Jessy');
    /* Types de paquets */
    public $typePaquet = array('binary', 'library', 'source', 'arch_independant');

    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Check validity of a YaML file')
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

        /* Analyse de la taille de la structure (qui doit posséder les 10 champs du premier niveau attendus) */
        if (count($struct) != 10) {
            $logger->err("le nombre d'éléments du fichier de configuration est incorrect");

            return -1;
        }

        /* Recherche parmi les clés de premier niveau connues celles qui n'apparaissent pas dans la structure */
        foreach ($GLOBALS['first_level_keys'] as $value) {
            /* Si la clé courante n'existe pas dans la structure */
            if (!array_key_exists($value, $struct)) {
                $logger->err("Le champ $value n'existe pas");

                return -1;
            }
            if ($value == 'BuildDepends') {
                /* "BuildDepends" contient quelque chose */
                if (!empty($struct[$value])) {
                    /* Stocke le contenu de "BuildDepends" */
                    $Depends = $struct[$value];

                    /* Le contenu de "BuildDepends" n'est pas un tableau */
                    if (!is_array($Depends)) {
                        $logger->err("Le contenu doit étre sous forme d'un tableau de compilers");

                        return -1;
                    }

                    /* Pour chaque "compiler" */
                    foreach ($Depends as $cle => $value) {
                        /* Le contenu du "compiler" courant est vide (il doit
                         * pourtant contenir au minimum le nom de la dépendance) */
                        if (empty($value)) {
                            $logger->err("Le champ $cle est vide");

                            return -1;
                        }
                        /* Le contenu du "compiler" courant n'est pas un tableau */
                        if (!is_array($value)) {
                            $logger->err("le contenu doit étre sous forme d'une liste contenant au minimum le nom commun de la dépendance");

                            return -1;
                        }
                        /* Stocke la valeur du "compiler" courant */
                        $compiler = $value;
                        /* Pour chaque champ du "compiler" courant (les champs étant les distributions) */
                        foreach ($compiler as $cle => $value) {
                            /* Si le champ courant est un tableau */
                            if (is_array($value)) {
                                /* Stocke le nom de la distribution courante */
                                $distrib = $value;
                                /* Pour chaque champ de la distribution courante (autrement dit ses versions) */
                                foreach ($distrib as $cle => $value) {
                                    /* Le nom de la distribution n'est pas connu ou la distribution
                                     * concernée n'est pas prise en charge */
                                    if (!in_array($cle, $GLOBALS['dist'])) {
                                        $logger->err('erreur dans le nom de la distribution');

                                        return -1;
                                    }
                                    /* Stocke le contenu de la distribution courante */
                                    $version = $value;
                                    /* Aucune information n'a été renseignée pour la distribution courante */
                                    if (empty($version)) {
                                        $logger->err("Le champ $cle doit contenir au moin le champ all");

                                        return -1;
                                    }
                                    /* Le contenu de la distribution courante n'est pas un tableau */
                                    if (!is_array($version)) {
                                        $logger->err("Le champ $cle doit étre un tableau");

                                        return -1;
                                    }
                                    /* Pour chaque version de la distribution courante */
                                    foreach ($version as $cle => $val) {
                                        /* Il y a qu'une seule clé (qui doit être "All", ce qui sous-entend que toutes
                                         * les versions de la distribution courante ont le même nom de dépendance) */
                                        if (count($version) == 1) {
                                            /* La clé n'est pas "All" */
                                            if ($cle != 'All') {
                                                $logger->err('Le nom du champ doit étre obligatoirement All');

                                                return -1;
                                            }
                                        }
                                        /* Si la version courante n'est pas connue ou non prise en charge */
                                        if (!in_array($cle, $GLOBALS['v_dist'])) {
                                            $logger->err('erreur dans le nom de la version');

                                            return -1;
                                        }
                                        /* La version courante ne contient pas de nom de dépendance */
                                        if (empty($val)) {
                                            $logger->err("Le champs $cle est vide");

                                            return -1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                /* Le champ de premier niveau courant est "Packages" */
            } elseif ($value == 'Packages') {
                /* Aucun paquet n'a été formulé */
                if (empty($struct[$value])) {
                    $logger->err("Le champ $value ne doit pas étre vide il doit contenir les packages qu'on veut créer");

                    return -1;
                } else {
                    /* Stocke la structure contenant l'ensemble des paquets */
                    $Packages = $struct[$value];
                    /* La structure des paquets n'est pas un tableau  */
                    if (!is_array($Packages)) {
                        $logger->err("Le contenu doit étre sous forme d'un tableau ou chaque champ est un nom de package qu'on veut construire");

                        return -1;
                    }
                    /* Pour chaque paquet */
                    foreach ($Packages as $cle => $val) {
                        /* Aucune donnée relative au paquet courant n'a été formulée */
                        if (empty($val)) {
                            $logger->err("Le champ $cle est vide");

                            return -1;
                        }
                        /* La structure du paquet courant n'est pas un tableau */
                        if (!is_array($val)) {
                            $logger->err("le champ $cle doit contenir comme valeur un tableau avec différents champs");

                            return -1;
                        }
                        /* Stocke les champs du paquet courant */
                        $champs = $val;
                        /* Pour chaque champ du paquet courant */
                        foreach ($champs as $cle => $val) {
                            if ($cle == 'Type') {
                                /* Aucun type n'est donné au paquet courant */
                                if (empty($val)) {
                                    $logger->err("Le champ $cle est vide");

                                    return -1;
                                }
                                /* Le type n'est pas connu */
                                if (!in_array($val, $GLOBALS['typePaquet'])) {
                                    $logger->err('Mauvais type de paquet');

                                    return -1;
                                }
                            } elseif ($cle == 'Files') {
                                /* Aucun fichier n'est donné pour le paquet courant */
                                if (empty($val)) {
                                    $logger->err("Le champ $cle est vide");

                                    return -1;
                                }
                                /* Les fichiers ne sont pas représentés sous forme d'un fichier */
                                if (!is_array($val)) {
                                    $logger->err("Le champ $cle doit étre un tableau contenant les fichiers du paquets à créer");

                                    return -1;
                                }
                            }
                            /* Le champ courant est "RunTimeDepends", "BeforeBuild" ou "AfterBuild" */
                            if ($cle == 'RunTimeDepends' || $cle == 'BeforeBuild' || $cle == 'AfterBuild') {
                                /* Le champ courant ne contient pas un tableau */
                                if (!empty($val) && !is_array($val)) {
                                    $logger->err("Le champ $cle doit étre un tableau");

                                    return -1;
                                }
                                /* Stocke le contenu du champ actuel */
                                $Table = $val;
                                /* Pour chacune des dépendances ("RunTimeDependency") ou
                                 * commandes ("BeforeBuild" ou "AfterBuild") */
                                foreach ($Table as $cle => $val) {
                                    /* La dépendance ou la commande courante n'a aucune valeur  */
                                    if (empty($val)) {
                                        $logger->err("Le champ $cle est vide");

                                        return -1;
                                    }
                                    /* La valeur de la commande/dépendance n'est pas un tableau */
                                    if (!is_array($val)) {
                                        $logger->err("Le contenu doit être sous forme d'une liste contenant au minimum le nom commun de");

                                        return -1;
                                    }
                                    $tab = $val;

                                    foreach ($tab as $cle => $value) {
                                        // cas des distribution

                                        if (is_array($value)) {
                                            $distrib = $value;

                                            foreach ($distrib as $cle => $value) {
                                                if (!in_array($cle, $GLOBALS['dist'])) {
                                                    $logger->err('erreur dans le nom de la distribution');

                                                    return -1;
                                                } //if array key
                                                $version = $value;
                                                if (empty($version)) {
                                                    $logger->err("le champs $cle doit contenir au moin le champ all");

                                                    return -1;
                                                } //fin empty
                                                if (!is_array($version)) {
                                                    $logger->err("le champ $cle doit étre un tableau");

                                                    return -1;
                                                } //fin is_array

                                                foreach ($version as $cle => $val) {

                                                    //si la taille du tableau version =1 la clé doit obligatoirement étre all : commune à toutes

                                                    if (count($version) == 1) {
                                                        if ($cle != 'All') {
                                                            $logger->err('le nom du champ doit étre obligatoirement All');

                                                            return -1;
                                                        }
                                                    }// fin count

                                                    if (!in_array($cle, $GLOBALS['v_dist'])) {
                                                        $logger->err('erreur dans le nom de la version');

                                                        return -1;
                                                    } //if array key
                                                    if (empty($val)) {
                                                        $logger->err("le champs $cle est vide");

                                                        return -1;
                                                    } //if empty
                                                } // foreach version
                                            }// foreach distrib
                                        } //is array
                                    } //foreach run
                                } //foreach Runtime
                            }// FIN Runtime


                            //	if($cle=="BeforeBuild") {


                            //	}
                        } //fin foreach $champs
                    } //fin foreach  $packages
                } //fin else
            } // fin  package
        } //fin fonction check
    }
}
