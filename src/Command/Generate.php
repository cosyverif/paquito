<?php

namespace Paquito\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\ArrayInput;

class Generate extends Command
{
	private $logger;
	private $struct;
    private $directory;
	private $dockerfile;

	protected function configure()
	{
		$this
			->setName('generate')
			->setDescription('Generate a package')
			->addArgument(
				'directory',
				InputArgument::REQUIRED,
				'Name of the directory which contains the sources and the paquito.yaml file'
			)
            ->addArgument(
                'target',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Distribution on which normalize paquito.yaml'
            );
	}

	/* Launches generation of each package
	 * @param $package_struct : 'Packages' field of a distribution */
	protected function launcher($package_struct, $target_distrib, $target_version)
    {
		foreach ($package_struct as $key => $value)
        {
            echo "Lancement genereation $target_distrib - $target_version\n";
			switch ($this->getApplication()->conf[$target_distrib]['Package_manager']) {
                case 'APT':
                    $this->make_deb($key, $value, $target_distrib, $target_version);
                break;
                
                case 'ABS':
                    $this->make_archlinux($key, $value, $target_distrib, $target_version);
                break;
                
                case 'RPM':
				    $this->make_rpm($key, $value, $target_distrib, $target_version);
				break;
                
                default:
                    $logger->error();
			}
            echo "\n";
		}
	}
    

	/* Launches Docker and get its result (the package) at the end
	 * @param $distribution : Name of the distribution, followed by a ':' and its name version
	 * @param $file : Name of the file which will be got (the package) */
	protected function docker_launcher($distribution, $file) {
		$command = "docker run --name paquito -v ".$this->directory.":/paquito -v /etc/localtime/:/etc/localtime:ro -i $distribution bash /paquito/Docker_paquito.sh";
        echo $command."\n";
		system($command, $out);
		$this->_system('docker stop paquito > /dev/null');
        
		// If the output code is more than 0 (error)
        // Failed case
		if($out) {
			$this->_system('docker rm paquito > /dev/null');
			$this->logger->error($this->getApplication()->translator->trans('generate.command', array('%command%' => $command, '%code%' => $out)));
			exit(-1);
		} else {
			//$this->_system('docker cp paquito:/paquito/$file '.$this->directory.'packages');
			$this->_system('docker rm paquito > /dev/null');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
        // TODO: Normalize input
		$input_directory = $input->getArgument('directory');
        $input_target = $input->getArgument('target');
		$local = $input->getOption('local');
        
        //Precaution : if a slash miss at the end of the directory, add it
        if(substr($input_directory, -1) != '/')
            $input_directory .= '/';

        $YAML_paquito = $input_directory."paquito.yaml";
        
        $this->directory = $input_directory;
		
        // Get the references of prune
		$command = $this->getApplication()->find('prune');
		$array_input = new ArrayInput(array('command' => 'prune',
                                            'input' => $YAML_paquito,
                                            'target' => $input_target,
                                            '--local' => $local)
        );
		$command->run($array_input, $output);

        // Logger Module
        $this->logger = new ConsoleLogger($output);
        
        $this->struct =& $this->getApplication()->data;
        
        //$this->_system("rm -r ".$input_directory."packages");
        $this->_system("mkdir -p ".$input_directory."packages");
        
        // "--local" or "-l" is set => launch the packaging process
        if($local) {
           $this->launcher($this->struct['Packages'], $this->getApplication()->dist_name, $this->getApplication()->dist_version);
        }
        
        // Launch the packaging process for each distribution
        else
        {
            // print_r($this->getApplication()->data);
            foreach($this->struct['To_build'] as $dist => $tab_vers)
            {
                if(!isset($this->getApplication()->conf[$dist]['Version'])) {
                    $this->launcher($this->struct['To_build'][$dist]['Packages'], $dist, null); // A REVOIR
                } else {
                    foreach($tab_vers as $version => $node_version) {
                        // Launches the package generation for the distribution currently treated
                        $this->launcher($node_version['Packages'], $dist, $version);
                    }
                }
			}
        }
	}

	function move_files(&$buffer, $dest_directory, $file_node)
	{
		// Array to store the permissions to apply in post-installation commands
		$array_perm = array();
		
        // Security precaution : if it misses a slash at the end of the $dest_directory variable, add this slash
		if (substr($dest_directory, -1) != '/') {
			$dest_directory .= '/';
		}

		foreach($file_node as $destination => $arr_source)
        {
            // Case #1 : the source is a directory
            // We suppose that the destination is also a directory (rely on check stage)
            if(substr($arr_source['Source'], -1) == '/')
            {
                $buffer .= "mkdir -p ".$dest_directory.$destination."\n"; // Create recursively the directory
                $this->_rcopy($buffer, $value['Source'], $dest_directory.$destination, $array_perm, $value['Permissions']); // Copy recursively the directory
            }
            
        	// Case #2 : the source is a file
			else
            {
                $filename = '';
                if(substr($destination, -1) == '/') { // We just copy the file into the directory
                    $explode_array = explode('/', ltrim($arr_source['Source'], '/'));
                    $filename = end($explode_array); // if the file is contain in a directory
                    $destination = ltrim($destination, '/');
                } else {
                    // Rename & copy the file into the directory
                    $explode_array = explode('/', ltrim($destination, '/'));
                    $filename = end($explode_array);
                    $explode_array_sz = count($explode_array);
                 
                    if($explode_array_sz > 1) {
                        unset($explode_array[$explode_array_sz-1]);
                        $destination = implode('/', $explode_array).'/';
                    }
                }
                $buffer .= "mkdir -p ".$dest_directory.$destination."\n";
                $buffer .= "cp $arr_source[Source] ".$dest_directory.$destination.$filename."\n";
                $array_perm[$destination.$filename] = $arr_source['Permissions'];
            }
		}
		return $array_perm;
	}
    
    /* Recursively copy files from one directory to another
	 * IMPORTANT The $array_perm argument is an array (owned
	 * by the move_file() function) passed by reference */
	function _rcopy(&$buffer, $src, $dest, &$array_perm, $perm){
		// If source is not a directory stop processing
		if (!is_dir($src)) {
			return false;
		}

		/* Open the source directory to read in files
		 * IMPORTANT The backslash before DirectoryIterator is
		 * to resolve namespace problem with this class */
		$i = new \DirectoryIterator($src);
		foreach ($i as $file)
        {
			if ($file->isFile())
            {
                $buffer .= "cp $src/$file $dest/\n";

				// Remove the package directory of the string
				$explode_array = explode('/', ltrim($dest, '/'));
				array_shift($explode_array);
				$key = ltrim(implode('/', $explode_array).'/', '/');
				$array_perm[$key.$file] = $perm;
			}
            
            // isDot vérifie si l'élement est un dossier caché
            else if (!$file->isDot() && $file->isDir()) {
				$this->_rcopy($buffer, "$src/$file", "$dest/$file", $array_perm, $perm);
			}
		}
	}

	/* Generate a string which contain a list of dependencies for a package
	 * IMPORTANT: Can manage groups of packages in Archlinux
	 * @param $struct : Bit of the YAML structure which contains the dependencies
	 * @param $id : Changes the writing format of the returned string
	 * 0 -> Dependencies separated with spaces
	 * 1 -> Dependencies in quotes and separated with spaces */
	protected function generate_list_dependencies($struct, $id)
	{
		$list = null;
        
		// If there are dependencies
		if (!empty($struct)) {
			// If the current is an Archlinux (where there are package groups)
			/*if ($this->getApplication()->dist_name == 'Archlinux') {
				// Get a list of package groups (like "base-devel") 
				$groups = rtrim(shell_exec("pacman -Qg | awk -F ' ' '{print $1}' | sort -u | sed -e ':a;N;s/\\n/ /;ba'"));
				// Transforms the string of package groups in an array (easier to use) 
				$groups = explode(" ", $groups);
			}*/
            
			// Concatenate all build dependencies on one line
			foreach ($struct as $value) {
				/*if ($this->getApplication()->dist_name == 'Archlinux')
                {
					// If the dependencie is in fact a group 
					if (in_array($value, $groups)) {
						// Get the list of packages which compose the group 
						$p_groups = rtrim(shell_exec("pacman -Qgq $value | sed -e ':a;N;s/\\n/ /;ba'"));
						$p_groups = explode(" ", $p_groups);
						// Foreach package of the group 
						foreach ($p_groups as $p_value)
                            $list .= ($id == 0 ? ' '.$p_value : " '".$p_value."'");
					}
				} else {*/
                    $list .= ($id == 0 ? ' '.$value : " '".$value."'");
                //}
			}
		}

		// Delete superfluous element (space)
		return ltrim($list, ' ');
	}


	// fwrite() with checking mechanisms
	protected function _fwrite($fd, $str, $file)
	{
		if (fwrite($fd, $str) === false) {
			$this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $file)));
			exit(-1);
		}
	}

	// system() with checking mechanisms
	protected function _system($command)
	{
		system($command, $out);
		if($out) {
			$this->logger->error($this->getApplication()->translator->trans('generate.command', array('%command%' => $command, '%code%' => $out)));
			exit(-1);
		}
	}

    /* For further information about how deb package work :
       Check : https://wiki.debian.org/HowToPackageForDebian
               https://www.debian.org/doc/manuals/maint-guide/ */
	protected function make_deb($package_name, $struct_package, $target_distrib, $target_version)
	{
		if ($struct_package['Type'] == 'binary') {
			if ($this->getApplication()->dist_arch == 'x86_64')
				$package_arch = 'amd64';
			else
				$package_arch = 'i386';
		} else {
			$package_arch = 'all';
		}

		$dirname = $package_name.'_'.$this->struct['Version'].'_'.$package_arch;

        $buffer = "#!/bin/bash\n";
        $buffer .= "\ncd /paquito\n";
        $buffer .= "\n#Setup environment to build the package\n";
        $buffer .= "apt-get update\n";

		$array_field = array('Source' => $package_name,
			                 'Section' => 'unknown',
			                 'Priority' => 'optional',
			                 'Maintainer' => $this->struct['Maintainer']);

        $buffer .= "apt-get -y install build-essential dh-make apt-utils ";
		//  The "Build-Depends" must be placed before fields like "Package" or "Depends" (else this field is not recognized)
        if (isset($struct_package['Build']['Dependencies']))
        {
			// This variable will contains the list of dependencies (to build)
            $list_dep = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0);
			$array_field['Build-Depends'] = str_replace(' ', ', ', $list_dep);
            $buffer .= $list_dep;
		}
        $buffer .= "\n";
        
        /* If there are pre-build commands */
		/* To come back in actual directory if a "cd" command is present in pre-build commands */
        $buffer .= "\n#Execute commands\n";
        $buffer .= "TEMP_PWD=$(pwd)\n";
		
		if (!empty($struct_package['Build']['Commands'])) {
			foreach ($struct_package['Build']['Commands'] as $value) {
				/* "cd" commands don't work (each shell_exec() has its owns
				 * shell), so it has to translates in chdir() functions */
				if (preg_match('/cd (.+)/', $value, $matches))
                    $buffer .= "cd $matches[1]\n";
				else
                    $buffer .= "$value\n";
			}
		}
		
        // To come back in usual directory if a "cd" command was present in pre-build commands
        $buffer .= "cd \$TEMP_PWD\n";
        
        /* Move the files specified in the configuration file and store the returned array of permissions (for post-installation) */
		$buffer .= "\n#Move files to the debian folder\n";
        $post_permissions = $this->move_files($buffer, $dirname, $struct_package['Files']);
        
		/* IMPORTANT : The fields "Standards-Version", "Homepage"... are placed after "Build-Depends", "Source"... because
		 * the Debian package wants a specific placing order (else there is an error) */
		$array_field['Standards-Version'] = '3.9.5';
		$array_field['Homepage'] = $this->struct['Homepage']."\n";
		$array_field['Package'] = $package_name;
		$array_field['Architecture'] = $package_arch;
		$array_field['Description'] = $this->struct['Summary']."\n ".$this->struct['Description'];
		
        if(isset($struct_package['Runtime']['Dependencies']))
			$array_field['Depends'] = str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0));

		// Create and open the file "control" (in write mode)
        $buffer .= "\n#Start configuring for packaging the source\n";
        $buffer .= "mkdir -p $dirname/debian/source\n";
        $buffer .= "\n#Write control file\n";
        $buffer .= "cat << _EOF_ > $dirname/debian/control\n";
		
        // For each field that will contains the file "control"
		foreach ($array_field as $key => $value)
            $buffer .= "$key: $value\n";
        
		// Add a line at the end (required)
        $buffer .= "\n";
        $buffer .= "_EOF_\n";
        
        $buffer .= "\n#Write format file\n";
        $buffer .= "echo '3.0 (native)' > $dirname/debian/source/format\n";
        
        $buffer .= "\n#Write compat file\n";
        $buffer .= "echo '9' > $dirname/debian/compat\n";
        
        $buffer .= "\n#Write rule file\n";
        $buffer .= "echo -e '#!/usr/bin/make -f\\nDPKG_EXPORT_BUILDFLAGS = 1\\ninclude /usr/share/dpkg/default.mk\\n%:\\n\\tdh $@\\noverride_dh_usrlocal:' > $dirname/debian/rules\n";
        $buffer .= "chmod 0755 $dirname/debian/rules\n";
        
        $buffer .= "\n#Write changelog file\n";
        $buffer .= "echo -e \"$package_name (".$this->struct['Version'].") unstable; urgency=low\\n\\n  * Initial Release.\\n\\n -- ".$this->struct['Maintainer']."  ".date('r')."\" > $dirname/debian/changelog\n";

		/* Create and open the file "*.install" (in write mode). This is the
		 * file which specifies what is the files of the project to packager */
        $buffer .= "\n#Write install script\n";
        $buffer .= "cat << _EOF_ > $dirname/debian/$package_name.install\n";
		foreach($post_permissions as $f_key => $f_value)
            $buffer .= ltrim($f_key, '/').' '.ltrim(dirname($f_key), '/')."\n";
            
        $buffer .= "_EOF_\n";
        
		if (isset($struct_package['Install']['Pre'])) {
            $buffer .= "\n#Write pre-install script\n";
            $buffer .= "cat << _EOF_ > $dirname/debian/preinst\n";
            $buffer .= "#!/bin/bash\n";
			
            foreach ($struct_package['Install']['Pre'] as $value)
                $buffer .= "$value\n";
                
            $buffer .= "_EOF_\n";
            
			// The file "preinst" has to have permissions between 755 and 775
            $buffer .= "chmod 0755 $dirname/debian/preinst\n";
		}

		if (count($post_permissions) || isset($struct_package['Install']['Post'])) {
            $buffer .= "\n#Write post-install script\n";
            $buffer .= "cat << _EOF_ > $dirname/debian/postinst\n";
			
            if (isset($struct_package['Install']['Post'])) {
				foreach ($struct_package['Install']['Post'] as $key => $value)
                    $buffer .= "$value\n"; // Write each command
			}
            
			if (count($post_permissions)) {
                $buffer .= "#!/bin/bash\n";
				foreach ($post_permissions as $key => $value) // !!!! TODO: Peut etre que $value est mal formaté !!!!
                    $buffer .= "chmod $value $key\n";
			}
            
            $buffer .= "_EOF_\n";
			
            // The file "postinst" has to have permissions between 755 and 775
            $buffer .= "chmod 0755 $dirname/debian/postinst\n";
		}

		// The command dpkg-buildpackage must be executed in the package directory
        $buffer .= "\n#Start building the package\n";
        $buffer .= "cd $dirname\n";
        
		// Creates the DEB package
        $buffer .= "dpkg-buildpackage -us -uc\n";
        $buffer .= "cd \$TEMP_PWD\n";
        
        // Clean
        $buffer .= "\n#Cleaning\n";
        $buffer .= "rm -r $dirname\n";
        $buffer .= "rm -f $dirname.changes ".$package_name.'_'.$this->struct['Version'].'.dsc '.$package_name.'_'.$this->struct['Version'].".tar.xz\n";
        $buffer .= "ls\n";
        
        // Move to the right folder
        $buffer .= "\n#Move\n";
        $buffer .= "mv $dirname.deb packages/\n";
        
		// Write and closes the dynamic script
        $this->dockerfile = fopen($this->directory.'Docker_paquito.sh', 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Start the generation in a the correct container
        $container_final = $this->getApplication()->conf[$target_distrib]['Container'];
        if($target_version != null) {
            $container_final .= ':'.strtolower($target_version);
        }
		$this->docker_launcher($container_final, "$dirname.deb");

		unlink($this->directory.'Docker_paquito.sh');
	}

    /* For further information about how pkg package work
       Check : https://wiki.archlinux.fr/Standard_paquetage
               https://wiki.archlinux.fr/PKGBUILD */
	protected function make_archlinux($package_name, $struct_package, $target_distrib, $target_version)
	{
		// TODO Adapter pour les librairies et les autres types
		if ($struct_package['Type'] != 'binary')
			$package_arch = 'all';
		else
			$package_arch = $this->getApplication()->dist_arch;

        $exclude_dependencies = "'base-devel'";
        $dirname = $package_name.'_'.$this->struct['Version'].'_'.$package_arch; // Defines the directory where will be stored sources and final package
        
        /* VERY IMPORTANT : When Archlinux is in a Docker, it seems to have some problems with GPG/PGP
		 * keys. So, lines are added (compared to Debian or Centos) in dynamic script to avoid these problems
		 * -> The problem seems to be the package database (of Pacman) in Docker which could be too old */
        $buffer = "#!/bin/bash\n";
        $buffer .= "cd /paquito\n";
        $buffer .= "\n#Setup environment to build the package\n";
        $buffer .= "pacman -Sy\n"; // Update list of packages
        $buffer .= "pacman -S --noconfirm --needed openssl pacman\npacman-db-upgrade\n"; // upgrade and download openssl (used by cucrl, which is pacman dependency)
       
		/*  Dirmngr is a server for managing and downloading certificate revocation
		 *  lists (CRLs) for X.509 certificates and for downloading the certificates themselves.
		 *  It is used here to avoid the "invalid of corrupted package (PGP signature)" error (see
		 *  Docker_launcher() function) */ 
        $buffer .= "dirmngr </dev/null\n";
        
		// (In the Docker) Creates a keyring (trusted keys), refresh this with the remote server and checks keys
        $buffer .= "pacman-key --init && pacman-key --refresh-keys && pacman-key --populate archlinux\n";
        $buffer .= "pacman -S --noconfirm --needed base-devel ";
        
		$array_field = array(
			'# Maintainer' => $this->struct['Maintainer'],
			'pkgname' => $package_name,
			'pkgver' => $this->struct['Version'],
			'pkgrel' => 1,
            'pkgdesc' => '"'.$this->struct['Summary'].'"',
			'arch' => "('$package_arch')",
			'url' => '"'.$this->struct['Homepage'].'"',
			'license' => "('".$this->struct['Copyright']."')",
			'install' => "$package_name.install", );

		if (isset($struct_package['Build']['Dependencies'])) {
            $l_dependencies = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 1);
            $l_dependencies = str_replace($exclude_dependencies, "", $l_dependencies);
			$array_field['makedepends'] = "($l_dependencies)";
            
            /* Install all dependencies specified in paquito.yaml
                --needed skip the reinstallation of existing packages */
                //print_r($struct_package['Build']['Dependencies']);
            $buffer .= $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0);
		}
        $buffer .= "\npacman-db-upgrade\n";
        
        // If any "cd" command is present in pre-build commands, we need to know where we are actually
        $buffer .= "\n#Execute commands\n";
        $buffer .= "TEMP_PWD=$(pwd)\n";
        
		/* If there are pre-build commands */
		/* IMPORTANT The build() function is not used because the pre-commands work directly
		 * in the src/ directory (of the package). It is simpler to launch commands directly */
		if (!empty($struct_package['Build']['Commands'])) {
			foreach ($struct_package['Build']['Commands'] as $key => $value)
                $buffer .= "$value\n";
		}
        
        // Go back in the usual directory
        $buffer .= "cd \$TEMP_PWD\n";
        
        // Moves the files of the src/ directory in the package directory
        $buffer .= "\n#Move files to the pkg directory\n";
		$post_permissions = $this->move_files($buffer, $dirname.'/src/', $struct_package['Files']);
        
        $buffer .= "\n#Write PKGBUILD file\n";
        $buffer .= "mkdir -p $dirname/src\n";
        
		if (isset($struct_package['Runtime']['Dependencies'])) {
            $l_dependencies = str_replace($exclude_dependencies, "", $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 1));
            $array_field['depends'] = '('.$l_dependencies.')';
        }

		// (In the Docker) Writes the file PKGBUILD
        $buffer .= "cat << _EOF_ > $dirname/PKGBUILD\n";
		
		/* For each field that will contains the file PKGBUILD */
		foreach ($array_field as $key => $value)
            $buffer .= "$key=$value\n";
            
        $buffer .= "\npackage() {\n";
        
        // TODO : Rewrite the following section
		foreach ($struct_package['Files'] as $key => $value) {
			/* The destination file will be in a sub-directory */
			if (strrpos($key, '/') !== false) {
				/* Splits the path in a array */
				$explode_array = explode('/', ltrim($key, '/'));
				/* Removes the name of the file */
				unset($explode_array[count($explode_array) - 1]);
				/* Transform the array in a string */
				$directory = implode('/', $explode_array);
				/* Writes the "mkdir" command in the package() function */
                $buffer .= "\tmkdir -p \\\$pkgdir/$directory/\n";
			}
            
			/* The last character is a slash (in others words, the given path is a directory) */
			if (substr($key, -1) == '/')
				$dest = $key.basename($value['Source']);
			else
				$dest = $key;
            
			// Write the "cd" command in the package() function
            $buffer .= "\tcp --preserve ".ltrim($dest, '/')." \\\$pkgdir/".ltrim($key, '/')."\n";
		}
        $buffer .= "}\n";
        $buffer .= "_EOF_\n";

		/* If there are pre/post-install commands */
		if (isset($struct_package['Install']) || count($post_permissions)) {
			// Writes the file *.install (for pre/post commands)
            $buffer .= "\n#Write .install file\n";
            $buffer .= "cat << _EOF_ > $dirname/$package_name.install\n";
            
			// If there are pre-install commands
			if (isset($struct_package['Install']['Pre'])) {
                $buffer .= "pre_install() {\n";
                
				// Write each command
				foreach ($struct_package['Install']['Pre'] as $value)
                    $buffer .= "\t$value\n";
				
                $buffer .= "}\n\n";
			}

			// If there are post-install commands
			if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
				$buffer .= "post_install() {\n";
                
				if (isset($struct_package['Install']['Post'])) {
					// Write each command
					foreach ($struct_package['Install']['Post'] as $key => $value)
                        $buffer .= "\t$value\n";
				}
                
                // if count(...) = 0 the following code will not be executed => don't need to test ;)
				foreach ($post_permissions as $key => $value)
                    $buffer .= "\tchmod $value $key\n";

				// Close the post-installation section
                $buffer .= "}\n";
			}
            $buffer .= "_EOF_\n";
		}

        $buffer .= "\n#Change owner for makepkg\n";
        $buffer .= "/bin/chown -R nobody $dirname\n"; // Changes owner of the package directory (to allow the creation of the package)
        
        //  SUDO FIX : http://bit-traveler.blogspot.fr/2015/11/sudo-error-within-docker-container-arch.html
        $buffer .= "\n#SUDO FIX FOR DOCKER\n";
        $buffer .= "cat << _EOF_ > /etc/security/limits.conf\n";
        $buffer .= "*\t-\trtprio\t0\n";
        $buffer .= "@audio\t-\trtprio\t65\n";
        $buffer .= "@audio\t-\tnice\t-10\n";
        $buffer .= "@audio\t-\tmemlock\t40000\n";
        $buffer .= "_EOF_\n";
        
        $buffer .= "\n#Start building the .pkg\n";
        $buffer .= "cd $dirname\n"; // Moves in the package directory
        
		/* Launches the creation of the package */
		/* IMPORTANT : The makepkg command is launched with nobody user because since February
		 * 2015, root user cannot use this command */
        $buffer .= "sudo -u nobody makepkg -f\n";
        
        // Move operation
        $buffer .= "\n#Moving to the right folder\n";
        $buffer .= "mv /paquito/$dirname/$package_name-".$this->struct['Version'].'-1-'.$package_arch.".pkg.tar.xz /paquito/packages\n";
        
        // Clean operation
        $buffer .= "\n#Cleaning\n";
        $buffer .= "rm -r /paquito/$dirname\n";
        
		// Write the dynamic script
        $this->dockerfile = fopen($this->directory.'Docker_paquito.sh', 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Starts the generation in the container
        $container_final = $this->getApplication()->conf[$target_distrib]['Container'];
        if($target_version != null) {
            $container_final .= ':'.strtolower($target_version);
        }
		$this->docker_launcher($container_final, $package_name.'-'.$this->struct['Version'].'-1-'.$package_arch.'.pkg.tar.xz');
		
        // Deletes the dynamic script
	    unlink($this->directory.'Docker_paquito.sh');
	}

    /* For further information about how rpm package work
       Check : http://doc.fedora-fr.org/wiki/La_cr%C3%A9ation_de_RPM_pour_les_nuls_:_Cr%C3%A9ation_du_fichier_SPEC_et_du_Paquetage
               https://fedoraproject.org/wiki/How_to_create_an_RPM_package */
	protected function make_rpm($package_name, $struct_package, $target_distrib, $target_version)
	{
		// TODO Adapter pour les librairies et les autres types
		if ($struct_package['Type'] != 'binary')
			$package_arch = 'all';
		else
			$package_arch = $this->getApplication()->dist_arch;
        
        // Create buffer which will handle the generate script
        $buffer = "#!/bin/bash\n";
        $buffer .= "cd /paquito\n";
        $buffer .= "\n#Setup environment to build the package\n";
        $buffer .= "yum -y install rpmdevtools rpm-build "; // Install all needed dependencies once
        
		if (isset($struct_package['Build']['Dependencies'])) {
            $list_dep = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0);
            
			$array_field['BuildRequires'] = $list_dep;
            $buffer .= $list_dep;
		}
        $buffer .= "\n";
        $buffer .= "rpmdev-setuptree\n"; // Create the directories for building packages (/home/ dir)
        
        // If any "cd" command is present in pre-build commands, we need to know where we are actually
        $buffer .= "\n#Execute commands\n";
        $buffer .= "TEMP_PWD=$(pwd)\n";
        
        /* If there are pre-build commands */
		/* IMPORTANT The build() function is not used because the pre-commands work directly
		 * in the src/ directory (of the package). It is simpler to launch commands directly */
		if (!empty($struct_package['Build']['Commands'])) {
			foreach ($struct_package['Build']['Commands'] as $key => $value)
                $buffer .= "$value\n"; // Execute each commandœ
		}
        
		// Go back to the usual directory
        $buffer .= "cd \$TEMP_PWD\n";
            
        // Moves the files in the src/ directory of the package directory
        $buffer .= "\n#Move files to the RPM folder\n";
		$post_permissions = $this->move_files($buffer, "~/rpmbuild/BUILD/", $struct_package['Files']);

		// Write the "p.spec" file
        $buffer .= "\n#Write the spec file\n";
        $buffer .= "cat << _EOF_ > ~/rpmbuild/SPECS/$package_name.spec\n";
        
		// For each field that will contains the file "p.spec"
        // Create array_field which will handle the description file for the packages
		$array_field = array(
			//'#Maintainer' => $this->struct['Maintainer'],
			'Name' => $package_name,
			'Version' => $this->struct['Version'],
			'Release' => '1%{?dist}',
			'Summary' => $this->struct['Summary'],
			'License' => $this->struct['Copyright'],
			'URL' => $this->struct['Homepage'],
            //'Source0' => $this->struct['Git'],
			'Packager' => 'Paquito',
		);
        
		foreach ($array_field as $key => $value)
            $buffer .= "$key: $value\n";
            
        // Dépendences
        $buffer .= "\nBuildRequires: $list_dep\n";
        if (isset($struct_package['Runtime']['Dependencies']))
			$buffer .= "Requires: ".$this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0)."\n";
            
		// Write the description
        $buffer .= "\n%description\n".$this->struct['Description']."\n";
        
		// %install section - TODO : Faire wildcard   
		$buffer .= "\n%install\n\trm -rf %{buildroot}\n";

		// The file section uses macros to include files
		$spec_files = array(array('/usr/bin' => 'bin'),
                            array('/usr/share' => 'data'),
                            array('/usr/share/doc' => 'defaultdoc'),
                            array('/usr/share/man' => 'man'),
                            array('/usr/include' => 'include'),
                            array('/usr/lib' => 'lib'),
                            array('/usr/sbin' => 'sbin'),
                            array('/var' => 'localstate'),
                            array('/etc' => 'sysconf')
        );
        
		// List of files to include
		$spec_files_add = array();

        // TODO : Rewrite the following section
		foreach ($struct_package['Files'] as $key => $value)
        {
			/* The destination file will be in a sub-directory */
			if (strrpos($key, '/') !== false) {
				/* Splits the path in a array */
				$explode_array = explode('/', ltrim($key, '/'));
				/* Removes the name of the file */
				unset($explode_array[count($explode_array) - 1]);
				/* Transforms the array in a string */
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
				// Writes the "mkdir" command in the %install section
                $buffer .= "\tmkdir -p %{buildroot}/$directory/\n";
			}
            
			/* The last character is a slash (in others words, the given path is a directory) */
			if (substr($key, -1) == '/') {
				$dest = $key.basename($value['Source']);
			} else {
				$dest = $key;
			}
			
            // Writes the "cp" command in the %install section
            $buffer .= "\tcp --preserve ".ltrim($dest, '/')." %{buildroot}/".ltrim($key, '/')."\n";
		}
        
        // %files and %pre/%post sections
        $buffer .= "\n%files\n";
		foreach ($spec_files_add as $value)
            $buffer .= "\t$value\n";

		// If there are pre/post-install commands
		if (isset($struct_package['Install']) || count($post_permissions)) {
            
			// If there are pre-install commands
			if (isset($struct_package['Install']['Pre'])) {
				$buffer .= "\n%pre\n";

				foreach ($struct_package['Install']['Pre'] as $value)
                    $buffer .= "\t$value\n"; // Write each command
			}

			// If there are post-install commands
			if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
				$buffer .= "\n%post\n";
                
				if (isset($struct_package['Install']['Post'])) {
					foreach ($struct_package['Install']['Post'] as $value)
                        $buffer .= "\t$value\n";
				}
				
				// if count(...) = 0 the following code will not be executed => don't need to test ;)
				foreach ($post_permissions as $key => $value)
                    $buffer .= "\tchmod $value $key\n";
			}
		}
        $buffer .= "_EOF_\n";

		// Launch the creation of the package
        $buffer .= "\n#Start building the package\n";
        $buffer .= "rpmbuild -ba ~/rpmbuild/SPECS/$package_name.spec\n";
        
        // Copy the generated package into the right folder
        $buffer .= "\n#Copy the .rpm file to packages\n";
        $buffer .= "cp -r ~/rpmbuild/RPMS/$package_arch/* /paquito/packages\n";

		// Open & write the dynamic script
        $this->dockerfile = fopen($this->directory.'Docker_paquito.sh', 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Start the generation with Docker
        $container_final = $this->getApplication()->conf[$target_distrib]['Container'];
        if($target_version != null) {
            $container_final .= ':'.strtolower($target_version);
        }
		$this->docker_launcher($container_final, '/root/rpmbuild/RPMS/'.$this->getApplication()->dist_arch."/$package_name-".$this->struct['Version'].'-1.el'.substr($this->getApplication()->dist_version, 0, 1).".centos.$package_arch.rpm");
		
        // Delete the dynamic script
	    unlink($this->directory.'Docker_paquito.sh');
	}
}
