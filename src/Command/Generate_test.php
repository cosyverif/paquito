<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\ArrayInput;

class Generate_test extends Command
{
    private $logger;
    private $struct;
    protected function configure()
    {
        $this
            ->setName('generate-test')
            ->setDescription('Generate a test package')
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'Name of a YaML file'
            )
            ->addArgument(
                'output',
                InputArgument::OPTIONAL,
                'Name of a YaML file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get the path and the name of the input file */
        $input_file = $input->getArgument('input');
        /* Get the references of the command parse() */
        $command = $this->getApplication()->find('generate');
        /* Declare the arguments in a array (arguments have to be given like this) */
        $arguments = array(
            'command' => 'generate',
            'input' => $input_file,
        );
        $array_input = new ArrayInput($arguments);
        /* Run command */
        $command->run($array_input, $output);

        /* Get the structure of the YaML file (which was parsed) */
        $this->struct = $this->getApplication()->data;
        /* Launch Logger module */
        $this->logger = new ConsoleLogger($output);

        /* The file /etc/os-release contains the informations about the distribution (where is executed this program)*/
        #$array_ini = parse_ini_file('/etc/os-release');
        /* Get the name of the distribution */
        #$this->getApplication()->dist_name = ucfirst($array_ini['ID']);
        /*switch ($this->getApplication()->dist_name) {
        case 'Debian':
            $package_system = 'apt-get -y install';
            break;
        case 'Archlinux':
            /* TODO Install on Archlinux the package "filesystem" */
            #$this->getApplication()->dist_name = 'Archlinux';
    /*	    $package_system = 'pacman --noconfirm -S';
            break;
        case 'Centos':
            $package_system = 'yum -y install';
            break;
        default:
            $this->logger->error($this->getApplication()->translator->trans('prune.exist'));

            exit(-1);
        }*/
        /* Get the architecture of the current machine */
        /* TODO La fonction posix_name() ne fonctionnera pas sous Archlinux si l'extension de PHP "posix"
         * (extension=posix.so) est commentée dans le fichier /etc/php/php.ini
         * Sous CentOS, il faut télécharger le paquet php-process pour utiliser les fonctions POSIX */
        $this->getApplication()->dist_arch = posix_uname();
        $this->getApplication()->dist_arch = $this->getApplication()->dist_arch['machine'];
        /* For each package */
        foreach ($this->struct['Packages'] as $key => $value) {
            $struct_package = $value;
            switch ($this->getApplication()->dist_name) {
           case 'Debian':
                $this->make_debian($key, $value);
	        break;
            case 'Archlinux':
                $this->make_archlinux($key, $value);
                break;
            case 'Centos':
                $this->make_centos($key, $value);
		break;
            }
        }
        /* Optionnal argument (output file, which will be parsed) */
        $output_file = $input->getArgument('output');
        /* If the optionnal argument is present */
        if ($output_file) {
            /* Get references of the command write() */
            $command = $this->getApplication()->find('write');
            /* Declare the arguments in a array (arguments has to gave like this) */
            $arguments = array(
                'command' => 'write',
                'output' => $output_file,
            );
            $array_input = new ArrayInput($arguments);
            /* Run command */
            $command->run($array_input, $output);
        }
    }

    protected function move_files($dest_directory, $struct)
    {
        /* Array to store the permissions to apply in post-installation commands */
        $array_perm = array();
        /* Copy each file in the directory of the current package */
        /* TODO Faire wildcard */
        foreach ($struct as $key => $value) {
            /* The destination file will be in a sub-directory, so we have to create each sub-directory in the destination directory */
            if (strrpos($key, '/') !== false) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($key, '/'));
                /* Remove the name of the file */
                unset($explode_array[count($explode_array) - 1]);
                /* Transform the array in a string */
                $directory = implode('/', $explode_array);
                /* Create recursively the directories */
                if (!mkdir($dest_directory.'/'.$directory.'/', 0777, true) && !is_dir($dest_directory.'/'.$directory.'/')) {
                    $this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => $dest_directory.'/'.$directory.'/')));

                    return -1;
                }
            }
            if (is_dir($dest_directory.$key)) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($value['Source'], '/'));

                /* Copy the file in the directory package */
                if (!copy($value['Source'], $dest_directory.$key.$explode_array[count($explode_array) - 1])) {
                    $this->logger->error($this->getApplication()->translator->trans('generate.copy', array('%src%' => $value['Source'], '%dst%' => $dest_directory.$key.$explode_array[count($explode_array) - 1])));

                    return -1;
                }
                $array_perm[$key.$explode_array[count($explode_array) - 1]] = $value['Permissions'];
            } else { /* If the moved file will have a new name (so there is a name at the end of the given path) */
                /* Copy the file in the directory package */
                if (!copy($value['Source'], $dest_directory.$key)) {
                    $this->logger->error($this->getApplication()->translator->trans('generate.copy', array('%src%' => $value['Source'], '%dst%' => $dest_directory.$key)));

                    return -1;
                }
                $array_perm[$key] = $value['Permissions'];
            }
        }

        return $array_perm;
    }

    /* $id :
     * 0 -> Dependencies separated with spaces
     * 1 -> Dependencies in quotes and separated with spaces */
    protected function generate_list_dependencies($struct, $id)
    {
        $list = null;
        /* If there are dependencies */
        if (!empty($struct)) {
            /* Concatenate all build dependencies on one line */
            foreach ($struct as $value) {
                switch ($id) {
                case 0:
                    $list .= ' '.$value;
                    break;
                case 1:
                    $list .= " '".$value."'";
                    break;
                }
            }
        }
        /* Delete superfluous element (space) */
        #return $list = ltrim($list, ' ');
        return ltrim($list, ' ');
    }

    /* This function is like mkdir() but checks also if this last fails */
    protected function _mkdir($name)
    {
        /* is_dir() is here to controls if the directory existed */
        if (!mkdir($name) && !is_dir($name)) {
            $this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => $name)));

            exit(-1);
        }
    }

    protected function _fwrite($fd, $str, $file)
    {
        if (fwrite($fd, $str) === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $file)));

            exit(-1);
        }
    }

   
   protected function make_debian($package_name, $struct_package)
    {
        $dirname = $package_name.'_'.$this->struct['Version'].'_'.$this->getApplication()->dist_arch.'-test';
        /* Create the directory of the package */
        $this->_mkdir($dirname);
        /* Create the directory "DEBIAN/" (which is required) */
        $this->_mkdir($dirname.'/DEBIAN');

				$array_field = array('Package' => "$package_name-test",
            'Version' => $this->struct['Version'],
            'Section' => 'unknown',
            'Priority' => 'optional',
            'Maintainer' => $this->struct['Maintainer'],
            'Architecture' => 'all', 
            #'Build-Depends' => $list_buildepend,
            'Homepage' => $this->struct['Homepage'],
            'Description' => $this->struct['Summary']."\n ".$this->struct['Description'], );
        /* Create and open the file "control" (in write mode) */
        $handle = fopen($dirname.'/DEBIAN/control', 'w');
        /* For each field that will contains the file "control" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key: $value\n", "$dirname/DEBIAN/control");
				
				}
        /* Add a line at the end (required) */
        $this->_fwrite($handle, "\n", "$dirname/DEBIAN/control");
        fclose($handle);
	
	/* on execute les tests par defaut */
	
	if(!array_key_exists('Test',$struct_package)) {
		
		$directory='usr/share/test';
		if (!mkdir($dirname.'/'.$directory.'/', 0777, true)) {
			$this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => $dirname.'/'.$directory.'/')));
		       	return -1;
		}
		
		/* copier  les tests par defauts dans le répértoire du paquet au niveau du repertoire usr/share/test */
		copy("./src-test/installation.php","./".$dirname."/".$directory."/installation.php");
		$handle_post = fopen("$dirname/DEBIAN/postinst", 'w');
		$this->_fwrite($handle_post, "#!/bin/bash\n\n", "$dirname/DEBIAN/postinst");
		$this->_fwrite($handle_post, "chmod 755 /usr/share/test/installation.php\n\n", "$dirname/DEBIAN/postinst");
		$this->_fwrite($handle_post, "phpunit --tap /usr/share/test/installation.php\n\n", "$dirname/DEBIAN/postinst");
	
	}
		/* on execute en plus des tests par defaut les tests fournis par l'utilisateur */

				else {
      
        /* Move the files specified in the configuration file and store the returned array of permissions (for post-installation) */
	$post_permissions = $this->move_files($dirname, $struct_package['Test']['Files']);
       	/* copier les tests par defaut dans leur répértoire de destination : usr/share/test */
	$directory='usr/share/test';
	copy("./src-test/installation.php","./".$dirname."/".$directory."/installation.php");
        /* If there are commands */

	$handle_post = fopen("$dirname/DEBIAN/postinst", 'w');
	$this->_fwrite($handle_post, "#!/bin/bash\n\n", "$dirname/DEBIAN/postinst");
	/* commandes du test par defaut */
	$this->_fwrite($handle_post, "chmod 755 /usr/share/test/installation.php\n\n", "$dirname/DEBIAN/postinst");
	$this->_fwrite($handle_post, "phpunit --tap /usr/share/test/installation.php\n\n", "$dirname/DEBIAN/postinst");

	if (count($post_permissions)) {
	foreach ($post_permissions as $key => $value) {
	$this->_fwrite($handle_post, "chmod $value $key\n", "$dirname/DEBIAN/postinst");
	}
	}
	/* Write each Command*/
	foreach ($struct_package['Test']['Commands'] as $key => $value) {
		$this->_fwrite($handle_post, "$value\n", "$dirname/DEBIAN/postinst");
					}
	
	fclose($handle_post);
				}
	
	/* The file "postinst" has to have permissions between 755 and 775 */
	chmod("$dirname/DEBIAN/postinst", 0755);
        
        /* Create the DEB package */
        echo shell_exec("dpkg-deb --build $dirname");
    }
 

        protected function make_archlinux($package_name, $struct_package)
    {
        $dirname = $package_name.'-'.$this->struct['Version'].'-'.$this->getApplication()->dist_arch.'-test';
        /* Create the directory of the package */
        $this->_mkdir($dirname);
        /* Create the directory "src/" (which contains the sources) */
        $this->_mkdir($dirname.'/src/');

               $array_field = array(
            '# Maintainer' => $this->struct['Maintainer'],
            'pkgname' => "$package_name-test",
            'pkgver' => $this->struct['Version'],
            'pkgrel' => 1,
            'arch' => $this->getApplication()->dist_arch,
            //'depends' => $package_name,
            'url' => $this->struct['Homepage'],
            'license' => "('".$this->struct['Copyright']."')",
            'pkgdesc' => "'".$this->struct['Summary']."'",
            'install' => "('$package_name.install')", );
        /* Create and open the file "control" (in write mode) */
        $handle = fopen($dirname.'/PKGBUILD', 'w');
        /* For each field that will contains the file "control" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key=$value\n", "$dirname/PKGBUILD");
        }
        /* To come back in actual directory if a "cd" command is present in pre-build commands */
	$pwd = getcwd();
	//echo $pwd;
	//echo "\n";
        
        /* if the field Test not exists*/
	if(!array_key_exists('Test',$struct_package)) {
		/* on execute les tests par defaut*/

		$this->_fwrite($handle, "\npackage() {\n", "$dirname/PKGBUILD");
		/* répértoire qui contiendra les tests par defaut au moment de l'installation */
		$directory='usr/share/test';
		$this->_fwrite($handle, "\tmkdir -p \$pkgdir/$directory/\n", "$dirname/PKGBUILD");
	       	$this->_fwrite($handle, "\tcp --preserve $directory/installation.php"." \$pkgdir/$directory\n", "$dirname/PKGBUILD");

		$this->_fwrite($handle, "}\n", "$dirname/PKGBUILD");
		/* créer le repertoire /usr/share/test  dans src*/
                if (!mkdir($dirname.'/src/'.$directory.'/', 0777, true)) {
                    $this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => $dirname.'/src/'.'/'.$directory.'/')));

                    return -1;
                }

	       /* copier  les tests par defauts dans le répértoire src du répértoire du paquet */
		copy("./src-test/installation.php","./".$dirname."/src/".$directory."/installation.php");
		/* executer le test dans le post-install*/	
		$handle_script = fopen("$dirname/$package_name.install", 'w');
		/* Write the post-installation section */
		$this->_fwrite($handle_script, "post_install() {\n", "$dirname/$package_name.install");
		/* donner les doits minimums */
		//$this->_fwrite($handle_script, "\tchmod 755 /usr/share/test/installation.php\n", "$dirname/$package_name.install");
		/* executer le test par defaut*/
		$this->_fwrite($handle_script, "\tphpunit --tap /usr/share/test/installation.php\n", "$dirname/$package_name.install");

                /* Close the post-installation section */
                $this->_fwrite($handle_script, "}\n", "$dirname/$package_name.install");
      
		fclose($handle_script);



	}

	
/* the field Test exists */
	/* on executera les tests par defauts et les tests fournis par l'utilisateur */
else{
	
        /* Write the "cd" commands in the package() function */
        /* TODO Faire wildcard */
        $this->_fwrite($handle, "\npackage() {\n", "$dirname/PKGBUILD");

	/* créer les répertoires des tests fournis par l'utilisateur*/
	foreach ($struct_package['Test']['Files'] as $key => $value) {
            /* The destination file will be in a sub-directory */
            if (strrpos($key, '/') !== false) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($key, '/'));
                /* Remove the name of the file */
                unset($explode_array[count($explode_array) - 1]);
                /* Transform the array in a string */
                $directory = implode('/', $explode_array);
                /* Write the "mkdir" command in the package() function */
                $this->_fwrite($handle, "\tmkdir -p \$pkgdir/$directory/\n", "$dirname/PKGBUILD");
            }
            /* The last character is a slash (in others words, the given path is a directory) */
            /* IMPORTANT We use this function instead of is_dir() because is_dir() works only in directories
             * mentioned by the open_basedir directive (in php.ini) */
            if (substr($key, -1) == '/') {
                $dest = $key.basename($value['Source']);
            } else {
                $dest = $key;
            }
            $this->_fwrite($handle, "\tcp --preserve ".ltrim($dest, '/')." \$pkgdir/".ltrim($key, '/')."\n", "$dirname/PKGBUILD");
	}
	/*copier le test par defaut dans le bon répertoire */

	$this->_fwrite($handle, "\tcp --preserve $directory/installation.php"." \$pkgdir/$directory\n", "$dirname/PKGBUILD");


	$this->_fwrite($handle, "}\n", "$dirname/PKGBUILD");

	 /* Move the files in the src/ directory of the package directory */
	$post_permissions = $this->move_files($dirname.'/src/', $struct_package['Test']['Files']);


        /* copier  les tests par defauts dans le répértoire src du répértoire du paquet */
	copy("./src-test/installation.php","./".$dirname."/src/".$directory."/installation.php");
	
	$handle_script = fopen("$dirname/$package_name.install", 'w');
     /* Write the post-installation section */
	$this->_fwrite($handle_script, "post_install() {\n", "$dirname/$package_name.install");
	/* Commandes par defaut */
	$this->_fwrite($handle_script, "\tchmod 755 /usr/share/test/installation.php\n", "$dirname/$package_name.install");
	/* executer le test par defaut*/
	$this->_fwrite($handle_script, "\tphpunit --tap /usr/share/test/installation.php\n", "$dirname/$package_name.install");
	
	if (count($post_permissions)) {
	       	foreach ($post_permissions as $key => $value) {
                        $this->_fwrite($handle_script, "\tchmod $value $key\n", "$dirname/$package_name.install");
                    }
                }

	/* Write each command */
	foreach ($struct_package['Test']['Commands'] as $value) {
		$this->_fwrite($handle_script, "\t$value\n", "$dirname/$package_name.install");
	}

       	/* Close the post-installation section */
       	$this->_fwrite($handle_script, "}\n", "$dirname/$package_name.install");
	fclose($handle_script);

		}

        /* Change owner of the package directory (to allow the creation of the package) */
        system("/bin/chown -R nobody $dirname");
        /* Move in the package directory */
        chdir($dirname);
        /* Launch the creation of the package */
        /* IMPORTANT The makepkg command is launched with nobody user because since February 2015, root user cannot use this command */
        echo shell_exec('sudo -u nobody makepkg');
        fclose($handle);
        /* To come back in usual directory (to write the output file in the right place */
        chdir($pwd);
	}

protected function make_centos($package_name, $struct_package) {
       

	$dirname = $package_name - $this->struct['Version'].'-'.$this->getApplication()->dist_arch.'-test';
        /* Creates the directories for building package (always in the home directory) */
        echo shell_exec('rpmdev-setuptree');

               $array_field = array(
            '#Maintainer' => $this->struct['Maintainer'],
            'Name' => "$package_name-test",
            'Version' => $this->struct['Version'],
            'Release' => '1%{?dist}',
            'Summary' => $package_name,
            'License' => $this->struct['Copyright'],
            'URL' => $this->struct['Homepage'],
            'Packager' => 'Paquito',
            'Requires' => $package_name,
        );
        /* The RPM packager doesn't want void fields (else error) */
       // if (strlen($list_buildepend) > 0) {
          //  $array_field['BuildRequires'] = $list_buildepend;
       // }

        /* Create and open the file "p.spec" (in write mode) */
        $handle = fopen("$_SERVER[HOME]/rpmbuild/SPECS/pTest.spec", 'w');
        /* For each field that will contains the file "p.spec" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key: $value\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
        }
        /* Write the description */
        $this->_fwrite($handle, "\n%description\n".$this->struct['Summary']."\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");

        /* To come back in actual directory if a "cd" command is present in pre-build commands */
	$pwd = getcwd();
	/* To come back in usual directory if a "cd" command was present in pre-build commands */
        chdir($pwd);

        /* Write the %install section */
        /* TODO Faire wildcard */
        $this->_fwrite($handle, "\n%install\n\trm -rf %{buildroot}\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
        /* The file section uses macros to include files */
        $spec_files = array(
            array('/usr/bin' => 'bin'),
            array('/usr/share' => 'data'),
            array('/usr/share/doc' => 'defaultdoc'),
            array('/usr/share/man' => 'man'),
            array('/usr/include' => 'include'),
            array('/usr/lib' => 'lib'),
            array('/usr/sbin' => 'sbin'),
            array('/var' => 'localstate'),
            array('/etc' => 'sysconf'), );
        /* List of files to include */
	$spec_files_add = array();

	if(!array_key_exists('Test',$struct_package)) {

		/* on execute les tests par defaut */
		/* Write the "mkdir" command in the %install  section */
		$directory='usr/share/test';
                $this->_fwrite($handle, "\tmkdir -p \$RPM_BUILD_ROOT/$directory/\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		$this->_fwrite($handle, "\tcp --preserve $directory/installation.php  \$RPM_BUILD_ROOT/$directory"."\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
	       
		foreach ($spec_files as $tab) {
                    $val = key($tab);
                    if (substr("/$directory", 0, strlen($val)) == $val) {
                        $path = substr("/$directory", strlen($val));
                        if (strlen($path) == 0) {
                            $spec_files_add[] = '%{_'.$tab[$val].'dir}/*';
                        } else {
                            $spec_files_add[] = '%{_'.$tab[$val].'dir}'.$path.'/*';
                        }
                    }
                }

		/* créer le repertoire /usr/share/test  dans le repertoire BUILD*/
                if (!mkdir("$_SERVER[HOME]/rpmbuild/BUILD/$directory", 0777, true)) {
                    $this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => "$_SERVER[HOME]/rpmbuild/BUILD/$directory")));

                    return -1;
                }

	       /* copier  les tests par defauts dans le répértoire de destination */
		copy("./src-test/installation.php","$_SERVER[HOME]/rpmbuild/BUILD/$directory/installation.php");
	      
		$this->_fwrite($handle, "\n%files\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		foreach ($spec_files_add as $value) {
		       
			$this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		}

                 /* test Command */
                $this->_fwrite($handle, "\n%post\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                $this->_fwrite($handle, "\tchmod 755 /usr/share/test/installation.php\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                $this->_fwrite($handle, "\tphpunit --tap /usr/share/test/installation.php\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                  
	}

	else {

		/* on execute les tests par defaut et les tests fournis par l'utilisateur */
	
		/* tests utilisateur */

		foreach ($struct_package['Test']['Files'] as $key => $value) {
            /* The destination file will be in a sub-directory */
            if (strrpos($key, '/') !== false) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($key, '/'));
                /* Remove the name of the file */
                unset($explode_array[count($explode_array) - 1]);
                /* Transform the array in a string */
                $directory = implode('/', $explode_array);

                foreach ($spec_files as $tab) {
                    $val = key($tab);
                    if (substr("/$directory", 0, strlen($val)) == $val) {
                        $path = substr("/$directory", strlen($val));
                        if (strlen($path) == 0) {
                            $spec_files_add[] = '%{_'.$tab[$val].'dir}/*';
                        } else {
                            $spec_files_add[] = '%{_'.$tab[$val].'dir}'.$path.'/*';
                        }
                    }
                }

                /* Write the "mkdir" command in the %install  section */
                $this->_fwrite($handle, "\tmkdir -p \$RPM_BUILD_ROOT/$directory/\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
            }
            /* The last character is a slash (in others words, the given path is a directory) */
            /* IMPORTANT We use this function instead of is_dir() because is_dir() works only in directories
             * mentioned by the open_basedir directive (in php.ini) */
            if (substr($key, -1) == '/') {
                $dest = $key.basename($value['Source']);
	    }

	    else {
                $dest = $key;
            }
            $this->_fwrite($handle, "\tcp --preserve ".ltrim($dest, '/')." \$RPM_BUILD_ROOT/".ltrim($key, '/')."\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		}

		/* les tests par defaut */
		/* Write the "mkdir" command in the %install  section */
		$directory='usr/share/test';
		$this->_fwrite($handle, "\tcp --preserve $directory/installation.php  \$RPM_BUILD_ROOT/$directory"."\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");

		
		      	/* Move the tests files user */
		$post_permissions = $this->move_files("$_SERVER[HOME]/rpmbuild/BUILD/", $struct_package['Test']['Files']);
	       	/* copier  les tests par defauts dans le répértoire de destination */
		copy("./src-test/installation.php","$_SERVER[HOME]/rpmbuild/BUILD/$directory/installation.php");

	       
		$this->_fwrite($handle, "\n%files\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		foreach ($spec_files_add as $value) {
			$this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
	       	}

                 /* test Command */
		$this->_fwrite($handle, "\n%post\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		/* commandes par defauts */

			
		$this->_fwrite($handle, "\tchmod 755 /usr/share/test/installation.php\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                $this->_fwrite($handle, "\tphpunit --tap /usr/share/test/installation.php\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
		/*commandes utilisateur*/
		
		if (count($post_permissions)) {
                    foreach ($post_permissions as $key => $value) {
                        $this->_fwrite($handle, "\tchmod $value $key\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                    }
                }

                    foreach ($struct_package['Test']['Commands'] as $value) {
                        $this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/pTest.spec");
                    }
                
               	}
        /* Launch the creation of the package */
        echo shell_exec('rpmbuild -ba ~/rpmbuild/SPECS/pTest.spec');
        fclose($handle);
}


}
