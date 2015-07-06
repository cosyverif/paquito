<?php

//tester l'affichage de l'éxecutable

class ExecutableTest extends PHPUnit_Framework_TestCase {


public function testHello()  {

		//global $argv;

		$exec='hello-world';
		//$exec=$argv[2];
		$valeur=rtrim(shell_exec("hello-world"));
		$this->assertEquals("Hello World",$valeur) ;

				
}

}





?>

