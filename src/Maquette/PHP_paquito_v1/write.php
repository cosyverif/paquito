<?php
use Symfony\Component\Yaml\Yaml ;

function write($struct, $filename) {
	if(file_put_contents($filename, Yaml::dump($struct)) === FALSE) {
		echo "Erreur lors de l'écriture dans le fichier $filename\n" ;
		return -1 ;
	}
}
?>
