<?php
function normalize($struct) {
	/* Note: La variable $newStruct contiendra la nouvelle structure résultant de la normalisation */
	foreach($struct as $cle => $val) {
		if($cle == "BuildDepends") {
			/* Le champ "BuildDepends" n'existe pas */
			if(empty($val)) {
				/* La variable $val est vide mais elle est quand même assignée à la clé "BuildDepend"
				 * car le fichier YaML de Paquito doit nécessairement posséder cette clé */
				$newStruct[$cle] = $val ;
			/* Le champ "BuildDepends" existe */
			} else {
				$Build = $val ;
				/* Pour chacun des "compilers" existants */
				foreach($Build as $cle => $val) {
					/* Nom du "compiler" courant */
					$compiler = $cle ;
					$tab = $val ;
					/* Il n'y a pas de cas particuliers (c'est-à-dire que le nom de
					 * la dépendance est pareil pour toutes les distributions) */
					if(count($tab) == 1) {
						$newStruct["BuildDepends"][$compiler]["Common"] = $tab[0] ;
						/* Pour chacune des dépendances */
						//foreach($tab as $cle => $val) {
							//$val : valeur de la dépendance
							/* Ajout du mot-clé "Common" en tant que clé */
							//$newStruct["BuildDepends"][$compiler]["Common"] = $val ;
						//}
					/* Si des cas particuliers sont mentionnés (c'est à dire qu'il y a une
					 * différence de dépendances pour une autre distribution) */
					} else {
						$j = 1 ;
						/* Pour chacun des éléments (comprenant le nom supposément commun de la
						 * dépendance ainsi que les variations par distribution) */
						foreach($tab as $cle => $val) {
							/* Le premier cas particulier aura un traitement différent (ajout du
							 * mot-clé "Common" et réorganisation de la structure en conséquence) */
							if($j)  {
								/* La variable $val représente ici le nom supposément commun de la dépendence */
								$newStruct["BuildDepends"][$compiler]["Common"] = $val ;
								$j-- ;
							} else {
								/* La variable $val représente ici un tableau qui recense les cas particuliers */
								$t = $val ;
								/* Pour chacune des distributions dont le nom de la dépendance diffère */
								foreach($t as $cle => $val) { 
									$newStruct["BuildDepends"][$compiler][$cle] = $val ;
								}
							}
						}
					}
				}
			}
		/* Les autres champs (de 1er niveau) ne seront pas modifiés */
		} else if($cle == "Packages") {
			/* La variable $glob contient un tableau mentionnant les paquets qu'on souhaite créer */
			$glob = $val ;
			/* Pour chaque paquet */
			foreach($glob as $cle => $val) {
				/* La variable $package contient le nom du paquet */
				$package = $cle ;
				/* Contient les champs fils relatifs au paquet courant */
				$tab = $val ;
				/* Pour chacun des champs du paquet courant */
				foreach($tab as $cle => $val) {
					/* La clé désigne un champ "Type" ou "Files" */
					if($cle == "Type" || $cle == "Files") {
						/* Ces deux clés n'ont pas besoin d'être normalisées */
						$newStruct["Packages"][$package][$cle] = $val ;
					/* Les autres clés */
					} else {
						/* Stocke le nom du champ courant */
						$champ = $cle ;
						/* Si le champ courant ("RunTimeDependency", "BeforeBuild"
						 * ou "AfterBuild") ne contient rien */
						if(empty($val)) {
							/* La variable $val est vide mais elle est quand même assignée à la clé courante
							 * car le fichier YaML de Paquito doit nécessairement posséder cette clé */
							$newStruct["Packages"][$package][$cle] = $val ;
						} else {
							/* Stocke le contenu du champ actuel */
							$Table = $val ;
							/* Pour chacune des dépendances ("RunTimeDependency") ou
							 * commandes ("BeforeBuild" ou "AfterBuild") */
							foreach($Table as $cle => $val) {
								/* Nom du "runtime"/"command" courant */
								$elem = $cle ;
								$tab = $val ;
								/* S'il n'y a pas de cas particuliers (c'est-à-dire que le nom de la
								 * dépendance ou que la commande est pareil pour toutes les distributions) */
								if(count($tab) == 1) {
									$newStruct["Packages"][$package][$champ][$elem]["Common"] = $tab[0] ;
								} else {
									$j = 1 ;
									/* Pour chacun des éléments (comprenant le nom supposément commun de la
									 * dépendance ou la commande commune ainsi que les variations par distribution) */
									foreach($tab as $cle=>$val) {
										/* Le premier cas particulier aura un traitement différent (ajout du
										 * mot-clé "Common" et réorganisation de la structure en conséquence) */
										if($j) {
											/* La variable $val représente ici le nom
											 * supposément commun de la dépendence ou la commande */
											$newStruct["Packages"][$package][$champ][$elem]["Common"] = $val ;
											$j-- ;
										} else {
											/* La variable $val représente ici un tableau
											 * qui recense les cas particuliers */
											$t = $val ;
											/* Pour chacune des distributions dont
											 * le nom de la dépendance/commande diffère */
											foreach($t as $cle => $val) { 
												$newStruct["Packages"][$package][$champ][$elem][$cle]=$val;
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
			$newStruct[$cle] = $val ;
		} 
	}
	return $newStruct ;
}
?>
