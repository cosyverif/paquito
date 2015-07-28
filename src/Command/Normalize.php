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
    public $distribution = array('Debian','Archlinux','Centos');

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
							if(empty(!$val)) {
									$this->newStruct['Packages'][$package][$test][$file][$dest] = array('Source' => $source,'Permissions' => $permission);
							}
							else {
									$this->newStruct['Packages'][$package][$test][$file][$dest] = array('Source' => $dest,'Permissions' => $permission);
							}
					}
					else {
							if(empty(!$val)) {
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
                                $this->newStruct['Packages'][$package][$field][$depend][$d] = array('Debian' => array('All' => $d),'Archlinux' => array('All' => $d),'Centos' => array('All' => $d));
                            } else {

                                /* tableau contenant les dépendances pour les différentes distributions*/
                                $dist = $val;
							 	/* regarder quelle distribution manque*/
                                 foreach ($this->distribution as $val) {
                                        if (!array_key_exists($val, $dist)) {
                                            $this->newStruct['Packages'][$package][$field][$depend][$d][$val] = array('All' => $d);
                                        }
                                        /* la distribution existe*/
										else {
												/* regarder si la valeur du champ de la distribution n'est pas égale à none */
										
											if($struct['Packages'][$package][$field][$depend][$d][$val]!="<none>") {
													$this->newStruct['Packages'][$package][$field][$depend][$d][$val] = $dist[$val];
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
    /* Get references of the command parse() */
    $command = $this->getApplication()->find('check');
    /* Declare the arguments in a array (arguments has to gave like this) */
    $arguments = array(
        'command' => 'check',
        'input' => $input_file,
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
