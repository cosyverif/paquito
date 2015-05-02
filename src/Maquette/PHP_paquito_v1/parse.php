<?php
use Symfony\Component\Yaml\Yaml ;

function parse($filename) {
	if(! is_file($filename)) {
		echo "L'élément $filename n'existe pas ou n'est pas un fichier régulier\n" ;
		return -1 ;
	} else if (! is_readable($filename)) {
		echo "Droits insuffisants pour lire le fichier $filename\n" ;
		return -1 ;
	}
	# Parse le fichier et retourne son contenu sous forme d'un tableau (hashmap)
	return Yaml::parse(file_get_contents($filename));
}
?>
