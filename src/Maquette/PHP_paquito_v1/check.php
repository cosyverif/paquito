<?php

/* Clés de premier niveau */
$first_level_keys = array("Name", "Version", "Homepage", "Description", "Copyright", "Maintainer", "Authors", "BuildDepends", "Packages") ;
/* Distributions prises en charge */
$dist = array("Debian", "Archlinux", "Fedora") ;
/* Versions prises en charge */
$v_dist = array("All", "Stable", "Testing", "Wheezy", "Jessy") ;
/* Types de paquets */
$typePaquet = array("binary", "library", "source", "arch_independant") ;
//$t = array("Compiler", "RunTime", "Command") ; # TODO

function check($struct) {
	/* Analyse de la taille de la structure (qui doit posséder les 10 champs du premier niveau attendus) */
	if(count($struct) != 10) {
		echo "le nombre d'éléments du fichier de configuration est incorrect \n" ;
		return -1 ;
	}

	/* Recherche parmi les clés de premier niveau connues celles qui n'apparaissent pas dans la structure */
	foreach($GLOBALS["first_level_keys"] as $value) {
		/* Si la clé courante n'existe pas dans la structure */
		if(!array_key_exists($value, $struct)) {
			echo "Le champ $value n'existe pas\n" ;
			return -1 ;
		}
		if($value == "BuildDepends") {
			/* "BuildDepends" contient quelque chose */
			if(!empty($struct[$value])) {
				/* Stocke le contenu de "BuildDepends" */
				$Depends = $struct[$value] ;

				/* Le contenu de "BuildDepends" n'est pas un tableau */
				if(!is_array($Depends)) {
					echo "le contenu doit étre sous forme d'un tableau de compilers \n" ;
					return -1 ;
				}

				/* Pour chaque "compiler" */
				foreach($Depends as $cle => $value) {
					/* Le contenu du "compiler" courant est vide (il doit
					 * pourtant contenir au minimum le nom de la dépendance) */ 
					if(empty($value)) {	
						echo "Le champ $cle est vide \n" ;
						return -1 ;
					}
					/* Le contenu du "compiler" courant n'est pas un tableau */
					if(!is_array($value))  {
						echo "le contenu doit étre sous forme d'une liste contenant au minimum le nom commun de la dépendance \n";
						return -1;
					}
					/* Stocke la valeur du "compiler" courant */
					$compiler = $value ;
					/* Pour chaque champ du "compiler" courant (les champs étant les distributions) */
					foreach($compiler as $cle => $value) {
						/* Si le champ courant est un tableau */
						if(is_array($value)) {
							/* Stocke le nom de la distribution courante */
							$distrib = $value ;
							/* Pour chaque champ de la distribution courante (autrement dit ses versions) */
							foreach($distrib as $cle => $value) {
								/* Le nom de la distribution n'est pas connu ou la distribution
								 * concernée n'est pas prise en charge */
								if(!in_array($cle, $GLOBALS["dist"])) {
									echo "erreur dans le nom de la distribution \n";
									return -1;
								}
								/* Stocke le contenu de la distribution courante */
								$version = $value ;
								/* Aucune information n'a été renseignée pour la distribution courante */
								if(empty($version)) {
									echo "Le champ $cle doit contenir au moin le champ all \n" ;
									return -1 ;
								}
								/* Le contenu de la distribution courante n'est pas un tableau */
								if(!is_array($version)) {
									echo "Le champ $cle doit étre un tableau \n" ;
									return -1 ;
								}
								/* Pour chaque version de la distribution courante */
								foreach ($version as $cle => $val) {
									/* Il y a qu'une seule clé (qui doit être "All", ce qui sous-entend que toutes
									 * les versions de la distribution courante ont le même nom de dépendance) */
									if(count($version) == 1) {
										/* La clé n'est pas "All" */
										if($cle != "All") {
											echo "Le nom du champ doit étre obligatoirement All \n" ;
											return -1 ;
										}
									}
									/* Si la version courante n'est pas connue ou non prise en charge */
									if(!in_array($cle, $GLOBALS["v_dist"])) {
										echo "erreur dans le nom de la version \n" ;
										return -1 ;
									}
									/* La version courante ne contient pas de nom de dépendance */
									if(empty($val)) {
										echo "Le champs $cle est vide \n" ;
										return -1 ;
									}

								}
							}
						}
					}
				}
			}
		/* Le champ de premier niveau courant est "Packages" */
		} else if($value == "Packages") {
			/* Aucun paquet n'a été formulé */
			if(empty($struct[$value])) {
				echo "Le champ $value ne doit pas étre vide il doit contenir les packages qu'on veut créer \n" ;
				return -1 ;
			} else {
				/* Stocke la structure contenant l'ensemble des paquets */
				$Packages = $struct[$value] ;
				/* La structure des paquets n'est pas un tableau  */
				if(!is_array($Packages))  {
					echo "Le contenu doit étre sous forme d'un tableau ou chaque champ est un nom de package qu'on veut construire \n" ;
					return -1 ;
				}
				/* Pour chaque paquet */
				foreach($Packages as $cle => $val) {
					/* Aucune donnée relative au paquet courant n'a été formulée */
					if(empty($val)) {
						echo "Le champ $cle est vide \n" ;
						return -1 ;
					}
					/* La structure du paquet courant n'est pas un tableau */
					if(!is_array($val)) {
						echo "le champ $cle doit contenir comme valeur un tableau avec différents champs \n" ;
						return -1 ;
					}
					/* Stocke les champs du paquet courant */
					$champs = $val ;
					/* Pour chaque champ du paquet courant */
					foreach($champs as $cle => $val) {
						if($cle == "Type") {
							/* Aucun type n'est donné au paquet courant */
							if(empty($val)) {
								echo "Le champ $cle est vide \n" ;
								return -1 ;
							}
							/* Le type n'est pas connu */
							if(!in_array($val, $GLOBALS['typePaquet'])) {
								echo "Mauvais type de paquet \n" ;
								return -1 ;
							}
						} else if($cle == "Files") {
							/* Aucun fichier n'est donné pour le paquet courant */
							if(empty($val)) {
								echo "Le champ $cle est vide \n" ;
								return -1 ;			
							}
							/* Les fichiers ne sont pas représentés sous forme d'un fichier */
							if(!is_array($val)) {
								echo "Le champ $cle doit étre un tableau contenant les fichiers du paquets à créer \n" ;
								return -1 ;
							}
						}
						/* Le champ courant est "RunTimeDepends", "BeforeBuild" ou "AfterBuild" */
						if($cle == "RunTimeDepends" || $cle == "BeforeBuild" || $cle == "AfterBuild") {
							/* Le champ courant ne contient pas un tableau */ 
							if(!empty($val) && !is_array($val)) {
								echo "Le champ $cle doit étre un tableau\n" ;
								return -1 ;
							}
							/* Stocke le contenu du champ actuel */
							$Table = $val ;
							/* Pour chacune des dépendances ("RunTimeDependency") ou
							 * commandes ("BeforeBuild" ou "AfterBuild") */
							foreach($Table as $cle => $val) {
								/* La dépendance ou la commande courante n'a aucune valeur  */
								if(empty($val)) {
									echo "Le champ $cle est vide\n" ;
									return -1 ; 			
								}
								/* La valeur de la commande/dépendance n'est pas un tableau */
								if(!is_array($val)) {
									echo "Le contenu doit être sous forme d'une liste contenant au minimum le nom commun de \n" ;
									return -1 ;
								}
								$tab=$val ;

								foreach($tab as $cle=>$value) {
									// cas des distribution

									if(is_array($value))  {
										$distrib=$value;

										foreach ($distrib as $cle=>$value) {
											//echo "$cle\n";        

											if(!in_array($cle,$GLOBALS["dist"]))  {

												echo "erreur dans le nom de la distribution \n";
												return -1;
											} //if array key
											$version=$value;
											if(empty($version)) {

												echo "le champs $cle doit contenir au moin le champ all \n " ;
												return -1;

											} //fin empty
											if(!is_array($version)) {

												echo "le champ $cle doit étre un tableau \n";
												return -1;
											} //fin is_array

											foreach ($version as $cle=>$val) {

												//si la taille du tableau version =1 la clé doit obligatoirement étre all : commune à toutes 

												if(count($version)==1) {

													if($cle!="All") {

														echo "le nom du champ doit étre obligatoirement All \n";
														return -1;

													}
												}// fin count

												if(!in_array($cle,$GLOBALS["v_dist"]))  {

													echo "erreur dans le nom de la version \n";
													return -1;
												} //if array key
												if(empty($val)) {


													echo "le champs $cle est vide \n";
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
?>
