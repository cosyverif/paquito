<?php

//tests d'installation du paquet 


class InstallationTest extends PHPUnit_Framework_TestCase {


	public function testFiles() {

		//global $argv;

	
     		//$a=$argv[2];
		//shell_exec("dpkg -i $a");
		$this->assertFileExists('/usr/bin/hello-world');
        $this->assertFileExists('/usr/share/hello-world/program.c');

	}
	public function testPermissions() {

		//$perm=substr(sprintf('%o', fileperms('/usr/bin/hello-world')), -4);
		//$this->assertEquals($perm,'755');
		$this->assertTrue(is_executable('/usr/bin/hello-world'));
		$this->assertTrue(is_readable('/usr/share/hello-world/program.c'));

	}

}


?>
