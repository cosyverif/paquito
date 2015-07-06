<?php

//tester l'affichage de l'éxecutable


class ExecutableFauxTest extends PHPUnit_Framework_TestCase {


public function testHello()  {

		//global $argv;

		$exec='hello-world';
		//$exec=$argv[2];
		$valeur=rtrim(shell_exec("$exec"));
		$this->assertEquals("HelloWorld",$valeur) ;

				
}

}




?>

