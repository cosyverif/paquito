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
	private $dockerfile;

	protected function configure()
	{
		$this
			->setName('generate')
			->setDescription('Generate a package')
			->addArgument(
				'input',
				InputArgument::REQUIRED,
				'Name of the directory which contains the sources and the paquito.yaml file'
			)
			->addArgument(
				'output',
				InputArgument::OPTIONAL,
				'Name of a YaML file'
			);
	}

	/* Launches generation of each package
	 * @param $package_struct : 'Packages' field of a distribution */
	protected function launcher($package_struct) {
		/* For each package */
		foreach ($package_struct as $key => $value) {
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
	}

	/* Launches Docker and get its result (the package) at the end
	 * @param $distribution : Name of the distribution, followed by a ':' and its name version
	 * @param $file : Name of the file which will be got (the package)
	 * This function is like _system() but if there is an error it will stop and delete
	 * the container before to return the error and stop Paquito */
	protected function docker_launcher($distribution, $file) {
		$command = "docker run --name paquito -v ".getcwd().":/paquito -v /etc/localtime/:/etc/localtime:ro -i $distribution bash /paquito/Docker_paquito.sh";
		system($command, $out);
		$this->_system('docker stop paquito > /dev/null');
		/* If the output code is more than 0 (error) */
		if($out) {
			$this->_system('docker rm paquito > /dev/null');
			$this->logger->error($this->getApplication()->translator->trans('generate.command', array('%command%' => $command, '%code%' => $out)));
			exit(-1);
		} else { /* The command has succeeded */
			$this->_system("docker cp paquito:$file .");
			$this->_system('docker rm paquito > /dev/null');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$input_file = $input->getArgument('input');
		$local = $input->getOption('local');
		
        /* Get the references of the command parse() */
		$command = $this->getApplication()->find('prune');
		$array_input = new ArrayInput(array('command' => 'prune',
                                            'input' => $input_file,
                                            '--local' => $local)
        );
		$command->run($array_input, $output);

        // Logger Module
        $this->logger = new ConsoleLogger($output);

		/* Get the structure of the YaML file (which was parsed) */
		$this->struct = $this->getApplication()->data;

        // Check if docker-engine is installed
        // Only work for debian ATM
        // TODO : Support other platform
        /*if(shell_exec('dpkg-query -l | grep -c docker-engine') <= 0) {
            $this->logger->error("Docker-engine is not installed");
            exit(-1);
        }*/

		/* If the "--local" option is not set, so there are several YAML structure to use */
		if(!$local) {
			/* For each distribution */
			foreach($this->struct['Distributions'] as $dist => $tab_ver) {
				/* For each version */
				foreach($tab_ver as $ver => $tab_archi) {
					/* For each architecture */
					foreach($tab_archi as $arch => $tab_package) {
						$this->getApplication()->dist_name = $dist;
						$this->getApplication()->dist_version = $ver;
						if ($arch == '64') {
							$this->getApplication()->dist_arch = 'x86_64';
						} else {
							$this->getApplication()->dist_arch = 'i386';
						}
						/* Launches the package generation for the distribution currently treated */
						$this->launcher($this->struct['Distributions'][$dist][$ver][$arch]['Packages']);
					}
				}
			}
		} else { /* The generation will be adapted with the current configuration */
			/* Launches the package generation for the current distribution */
			$this->launcher($this->struct['Packages']);
		}

		//  Optionnal argument (output file, which will be parsed)
		$output_file = $input->getArgument('output');
		if ($output_file) {
			/* Get references of the command write() */
			$command = $this->getApplication()->find('write');
			$array_input = new ArrayInput(array('command' => 'write',
                                                'output' => $output_file)
			);
			$command->run($array_input, $output);
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

		foreach($file_node as $destination => $arr_source) {
            // Case #1 : the source is a directory
            if(substr($arr_source['Source'], -1) == '/')
            {
                
            }
            
        	// Case #2 : the source is a file
			else
            {
				/* If the file will be renamed in its destination */	
				if (substr($destination, -1) != '/') {
					$explode_array = explode('/', ltrim($key, '/'));
					/* Get the new name */
					$filename = end($explode_array);
					/* If the moved file will be in a directory */
					if (count($explode_array) > 1) {
						/* Remove the new name to keep only the destination path */
						unset($explode_array[count($explode_array) - 1]);
						/* Transform the array in a string (which is the destination path) */
						$key = implode('/', $explode_array).'/';
					}
				} else { /* The destination file name will be the same than the source */
					$explode_array = explode('/', ltrim($value['Source'], '/'));
					/* Get the name */
					$filename = end($explode_array);
				}
			}

			/* Create recursively the directories (if doesn't exist) */
			$this->_fwrite($this->dockerfile, "mkdir -p $dest_directory/$key\n", 'Docker_paquito.sh');

			if (substr($value['Source'], -1) == '/') {
				/* The file will be copied in the directory package (recursively) */
				$this->_rcopy($value['Source'], $dest_directory.$key, $array_perm, $value['Permissions']);
			} else { /* If the source is a file */
				/* The file will be copied in the directory package */
				$this->_fwrite($this->dockerfile, "cp $value[Source] $dest_directory$key$filename\n", 'Docker_paquito.sh');
				$array_perm[$key.$filename] = $value['Permissions'];
			}
		}
		return $array_perm;
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
			if ($this->getApplication()->dist_name == 'Archlinux') {
				/* Get a list of package groups (like "base-devel") */
				$groups = rtrim(shell_exec("pacman -Qg | awk -F ' ' '{print $1}' | sort -u | sed -e ':a;N;s/\\n/ /;ba'"));
				/* Transforms the string of package groups in an array (easier to use) */
				$groups = explode(" ", $groups);
			}
            
			// Concatenate all build dependencies on one line
			foreach ($struct as $value) {
				if ($this->getApplication()->dist_name == 'Archlinux')
                {
					/* If the dependencie is in fact a group */
					if (in_array($value, $groups)) {
						/* Get the list of packages which compose the group */
						$p_groups = rtrim(shell_exec("pacman -Qgq $value | sed -e ':a;N;s/\\n/ /;ba'"));
						$p_groups = explode(" ", $p_groups);
						/* Foreach package of the group */
						foreach ($p_groups as $p_value)
                            $list .= ($id == 0 ? ' '.$p_value : " '".$p_value."'");
					}
				} else {
                    $list .= ($id == 0 ? ' '.$value : " '".$value."'");
                }
			}
		}
		// Delete superfluous element (space)
		return ltrim($list, ' ');
	}

	/* Recursively copy files from one directory to another
	 * IMPORTANT The $array_perm argument is a array (owned
	 * by the move_file() function) passed by reference */
	function _rcopy($src, $dest, &$array_perm, $perm){
		/* If source is not a directory stop processing */
		if (!is_dir($src)) {
			return false;
		}

		/* The destination directory will be created */
		$this->_fwrite($this->dockerfile, "mkdir $dest\n", 'Docker_paquito.sh');

		/* Open the source directory to read in files
		 * IMPORTANT The backslash before DirectoryIterator is
		 * to resolve namespace problem with this class */
		$i = new \DirectoryIterator($src);
		foreach ($i as $file) {
			if ($file->isFile()) {
				$this->_fwrite($this->dockerfile, "cp $src/$file $dest/$file\n", 'Docker_paquito.sh');

				/* Remove the package directory of the string */
				$explode_array = explode('/', ltrim($dest, '/'));
				array_shift($explode_array);
				$key = ltrim(implode('/', $explode_array).'/', '/');
				$array_perm[$key.$file] = $perm;
			} else if (!$file->isDot() && $file->isDir()) {
				$this->_rcopy("$src/$file", "$dest/$file", $array_perm, $perm);
			}
		}
	}

	/* This function is like _fwrite() but with checking mechanisms */
	protected function _fwrite($fd, $str, $file)
	{
		if (fwrite($fd, $str) === false) {
			$this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $file)));
			exit(-1);
		}
	}

	/* This function is like _system() but with checking mechanisms */
	protected function _system($command)
	{
		system($command, $out);
		/* If the output code is more than 0 (error) */
		if($out) {
			$this->logger->error($this->getApplication()->translator->trans('generate.command', array('%command%' => $command, '%code%' => $out)));

			exit(-1);
		}
	}

	protected function make_debian($package_name, $struct_package)
	{
		if ($struct_package['Type'] == 'binary') {
			/* In Debian, the 64 bits is called "amd64" (not "x86_64") */
			if ($this->getApplication()->dist_arch == 'x86_64') {
				$package_arch = 'amd64';
			} else {
				$package_arch = 'i386';
			}
		} else {
			$package_arch = 'all';
		}

		$dirname = $package_name.'_'.$this->struct['Version'].'_'.$package_arch;

        // DEBUG
        $t0 = microtime(true);

        $buffer = "#!/bin/bash\n";
        $buffer .= "apt-get update\n";
        $buffer .= "cd /paquito\n";
        $buffer .= "mkdir -p ".$dirname."/debian/source\n";

		$array_field = array('Source' => $package_name,
			                 'Section' => 'unknown',
			                 'Priority' => 'optional',
			                 'Maintainer' => $this->struct['Maintainer']);

		//  The "Build-Depends" must be placed before fields like "Package" or "Depends" (else this field is not recognized)
        if (isset($struct_package['Build']['Dependencies']))
        {
			// This variable will contains the list of dependencies (to build)
            $list_dep = str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0));
			$array_field['Build-Depends'] = $list_dep;
            $buffer .= "apt-get -y install ".$list_dep."\n";
            // $this->_fwrite($this->dockerfile, "apt-get --yes install $list_dep\n", 'Docker_paquito.sh');
		}
        
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
        $buffer .= "cat << _EOF_ > ".$dirname."/debian/control\n";
		
        // For each field that will contains the file "control"
		foreach ($array_field as $key => $value)
            $buffer .= $key.": ".$value."\n";
        
		// Add a line at the end (required)
        $buffer .= "\n";
        $buffer .= "_EOF_\n";
        
        /* If there are pre-build commands */
		/* To come back in actual directory if a "cd" command is present in pre-build commands */
        $buffer .= "TEMP_PWD=$(pwd)\n";
		
		if (!empty($struct_package['Build']['Commands'])) {
			foreach ($struct_package['Build']['Commands'] as $value) {
				/* "cd" commands don't work (each shell_exec() has its owns
				 * shell), so it has to translates in chdir() functions */
				if (preg_match('/cd (.+)/', $value, $matches))
                    $buffer .= "cd ".$matches[1]."\n";
				else
                    $buffer .= $value."\n";
			}
		}
		
        // To come back in usual directory if a "cd" command was present in pre-build commands
        $buffer .= "cd \$TEMP_PWD\n";
        
		/* Move the files specified in the configuration file and store the returned array of permissions (for post-installation) */
		$post_permissions = $this->move_files($buffer, $dirname, $struct_package['Files']);

        $buffer .= "echo '3.0 (native)' > $dirname/debian/source/format\n";
        $buffer .= "echo '9' > $dirname/debian/compat\n";
        $buffer .= "echo -e '#!/usr/bin/make -f\\nDPKG_EXPORT_BUILDFLAGS = 1\\ninclude /usr/share/dpkg/default.mk\\n%:\\n\\tdh $@\\noverride_dh_usrlocal:' > $dirname/debian/rules\n";
        $buffer .= "chmod 0755 $dirname/debian/rules\n";
        $buffer .= "echo -e \"$package_name (".$this->struct['Version'].") unstable; urgency=low\\n\\n  * Initial Release.\\n\\n -- ".$this->struct['Maintainer']."  ".date('r')."\" > $dirname/debian/changelog\n";

		/* Create and open the file "*.install" (in write mode). This is the
		 * file which specifies what is the files of the project to packager */
        $buffer .= "cat << _EOF_ > $dirname/debian/$package_name.install\n";
		foreach($post_permissions as $f_key => $f_value)
            $buffer .= ltrim($f_key, '/').' '.ltrim(dirname($f_key), '/')."\n";
            
        $buffer .= "_EOF_\n";
        
		if (isset($struct_package['Install']['Pre'])) {
            $buffer .= "cat << _EOF_ > $dirname/debian/preinst\n";
            $buffer .= "#!/bin/bash\n";
			
            foreach ($struct_package['Install']['Pre'] as $value)
				$this->_fwrite($handle_pre, "$value\n", "$dirname/debian/preinst");
                
            $buffer .= "_EOF_\n";
            
			// The file "preinst" has to have permissions between 755 and 775
            $buffer .= "chmod 0755 $dirname/debian/preinst\n";
		}

		if (count($post_permissions) || isset($struct_package['Install']['Post'])) {
            $buffer .= "cat << _EOF_ > $dirname/debian/postinst\n";
			
            if (isset($struct_package['Install']['Post'])) {
				foreach ($struct_package['Install']['Post'] as $key => $value)
                    $buffer .= "$value\n"; // Write each command
			}
            
			if (count($post_permissions)) {
                $buffer .= "#!/bin/bash\n";
				foreach ($post_permissions as $key => $value)
                    $buffer .= "chmod $value $key\n";
			}
            
            $buffer .= "_EOF_\n";
			
            // The file "postinst" has to have permissions between 755 and 775
            $buffer .= "chmod 0755 $dirname/debian/postinst\n";
		}

		// The command dpkg-buildpackage must be executed in the package directory
        $buffer .= "cd $dirname\n";
        
		// Creates the DEB package
        $buffer .= "dpkg-buildpackage -us -c\n";
        $buffer .= "cd \$TEMP_PWD\n";
        
        $t1 = microtime(true);
        echo "Temps d'execution : ".number_format($t1-$t0, 5)." ms\n";
        
		// Write and closes the dynamic script
        $this->dockerfile = fopen("Docker_paquito.sh", 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Start the generation in a the correct container
		$this->docker_launcher('debian:'.lcfirst($this->getApplication()->dist_version), "/paquito/$dirname.deb");

		unlink('Docker_paquito.sh');
	}

	protected function make_archlinux($package_name, $struct_package)
	{
		// TODO Adapter pour les librairies et les autres types
		if ($struct_package['Type'] != 'binary')
			$package_arch = 'all';
		else
			$package_arch = $this->getApplication()->dist_arch;

		// Defines the directory where will be stored sources and final package
		$dirname = $package_name.'-'.$this->struct['Version'].'-'.$package_arch;
        
        /* VERY IMPORTANT : When Archlinux is in a Docker, it seems to have some problems with GPG/PGP
		 * keys. So, lines are added (compared to Debian or Centos) in dynamic script to avoid these problems
		 * -> The problem seems to be the package database (of Pacman) in Docker which could be too old */
        $buffer = "#!/bin/bash\n";
        $buffer .= "cd /paquito\n";
        $buffer .= "mkdir -p $dirname/src\n";
        $buffer .= "pacman -Sy\n"; // Update list of packages
        $buffer .= "pacman -S --noconfirm --needed openssl pacman\npacman-db-upgrade\n"; // upgrade and download openssl (used by cucrl, which is pacman dependency)
       
		/* (In the Docker) Dirmngr is a server for managing and downloading certificate revocation
		 *  lists (CRLs) for X.509 certificates and for downloading the certificates themselves.
		 *  It is used here to avoid the "invalid of corrupted package (PGP signature)" error (see
		 *  Docker_launcher() function) */ 
        $buffer .= "dirmngr </dev/null\n";
        
		// (In the Docker) Creates a keyring (trusted keys), refresh this with the remote server and checks keys
        $buffer .= "pacman-key --init && pacman-key --refresh-keys && pacman-key --populate archlinux\n";
        
		$array_field = array(
			'# Maintainer' => $this->struct['Maintainer'],
			'pkgname' => "$package_name",
			'pkgver' => $this->struct['Version'],
			'pkgrel' => 1,
			'arch' => $package_arch,
			'url' => $this->struct['Homepage'],
			'license' => "('".$this->struct['Copyright']."')",
			'pkgdesc' => "'".$this->struct['Summary']."'",
			'install' => "('$package_name.install')", );

		if (isset($struct_package['Build']['Dependencies'])) {
			$array_field['makedepends'] = '('.$this->generate_list_dependencies($struct_package['Build']['Dependencies'], 1).')';
            
            /* Install all dependencies specified in paquito.yaml
                --needed skip the reinstallation of existing packages */
            $buffer .= 'pacman -S --noconfirm --needed '.$this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)."\npacman-db-upgrade\n";
		}
        
		if (isset($struct_package['Runtime']['Dependencies']))
			$array_field['depends'] = '('.$this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 1).')';

		// (In the Docker) Writes the file PKGBUILD
        $buffer .= "cat << _EOF_ > $dirname/PKGBUILD\n";
		
		/* For each field that will contains the file PKGBUILD */
		foreach ($array_field as $key => $value)
            $buffer .= "$key=$value\n";
            
        $buffer .= "_EOF_\n";
        
        // If any "cd" command is present in pre-build commands, we need to know where we are actually
        $buffer .= "TEMP_PWD=$(pwd)\n";
        
		/* If there are pre-build commands */
		/* IMPORTANT The build() function is not used because the pre-commands work directly
		 * in the src/ directory (of the package). It is simpler to launch commands directly */
		if (!empty($struct_package['Build']['Commands'])) {
			foreach ($struct_package['Build']['Commands'] as $key => $value)
                $buffer .= "$value\n";
		}
        
        // Go back in the usual directory
        $buffer .= "cd \$TEMPS_PWD\n";
        
		// Writes again in the file PKGBUILD (to do the package() function)
        $buffer .= "cat << _EOF_ >> $dirname/PKGBUILD\n";
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
        
		// Moves the files of the src/ directory in the package directory
		$post_permissions = $this->move_files($buffer, $dirname.'/src/', $struct_package['Files']);

		/* If there are pre/post-install commands */
		if (isset($struct_package['Install']) || count($post_permissions)) {
			// Writes the file *.install (for pre/post commands)
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

        $buffer .= "/bin/chown -R nobody $dirname\n"; // Changes owner of the package directory (to allow the creation of the package)
        $buffer .= "cd $dirname\n"; // Moves in the package directory
        
		/* Launches the creation of the package */
		/* IMPORTANT : The makepkg command is launched with nobody user because since February
		 * 2015, root user cannot use this command */
        $buffer .= "sudo -u nobody makepkg -f\n";
        
		// Write the dynamic script
        $this->dockerfile = fopen('Docker_paquito.sh', 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Starts the generation in the container
		$this->docker_launcher('base/archlinux', "/paquito/$dirname/".$package_name.'-'.$this->struct['Version'].'-1-'.$package_arch.'.pkg.tar.xz');
		
        // Deletes the dynamic script
		unlink('Docker_paquito.sh');
	}

	protected function make_centos($package_name, $struct_package)
	{
		// TODO Adapter pour les librairies et les autres types
		if ($struct_package['Type'] != 'binary')
			$package_arch = 'all';
		else
			$package_arch = $this->getApplication()->dist_arch;

		$dirname = $package_name - $this->struct['Version'].'-'.$package_arch;

		/* Opens the dynamic script that Docker will use
		$this->dockerfile = fopen("Docker_paquito.sh", 'w');*/

        // Create buffer which will handle the generate script
        $buffer = "#!/bin/bash\n";
        $buffer .= "yum -y install rpmdevtools rpm-build\n"; // Install needed packages to build new packages
        $buffer .= "rpmdev-setuptree\n"; // Create the directories for building packages (/home/ dir)
        $buffer .= "cd /paquito\n";
        
        // Create array_field which will handle the description file for the packages
		$array_field = array(
			'#Maintainer' => $this->struct['Maintainer'],
			'Name' => $package_name,
			'Version' => $this->struct['Version'],
			'Release' => '1%{?dist}',
			'Summary' => $package_name,
			'License' => $this->struct['Copyright'],
			'URL' => $this->struct['Homepage'],
			'Packager' => 'Paquito',
		);
        
		if (isset($struct_package['Build']['Dependencies'])) {
            $list_dep = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0);
            
			$array_field['BuildRequires'] = $list_dep;
            $buffer .= "yum -y install $list_dep\n"; // Install multiple dependencies at once
		}
        
		if (isset($struct_package['Runtime']['Dependencies']))
			$array_field['Requires'] = $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0);

		// Writes the file "p.spec"
        $buffer .= "cat << _EOF_ > ~/rpmbuild/SPECS/p.spec\n";
        
		// For each field that will contains the file "p.spec"
		foreach ($array_field as $key => $value)
            $buffer .= "$key: $value\n";
            
		// Write the description
        $buffer .= "\n%description\n$this->struct['Summary']\n";
        $buffer .= "_EOF_\n";
        
		// If any "cd" command is present in pre-build commands, we need to know where we are actually
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
        
		// Writes again in the file "p.spec" (%install section)
        $buffer .= "cat << _EOF_ >> ~/rpmbuild/SPECS/p.spec\n";
        
		/* TODO Faire wildcard */
		$this->_fwrite($this->dockerfile, "\n%install\n\trm -rf %{buildroot}\n", 'Docker_paquito.sh');

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
		foreach ($struct_package['Files'] as $key => $value) {
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
                $buffer .= "\tmkdir -p \\\$RPM_BUILD_ROOT/$directory/\n";
			}
            
			/* The last character is a slash (in others words, the given path is a directory) */
			if (substr($key, -1) == '/') {
				$dest = $key.basename($value['Source']);
			} else {
				$dest = $key;
			}
			
            // Writes the "cp" command in the %install section
            $buffer .= "\tcp --preserve ".ltrim($dest, '/')." \\\$RPM_BUILD_ROOT/".ltrim($key, '/')."\n";
		}
        $buffer .= "_EOF_\n";
        
		// Moves the files in the src/ directory of the package directory
		$post_permissions = $this->move_files($buffer, "$_SERVER[HOME]/rpmbuild/BUILD/", $struct_package['Files']);

		// Write again in the file "p.spec" (%files and %pre/%post sections)
        $buffer .= "cat << _EOF_ >> ~/rpmbuild/SPECS/p.spec\n";
        
		// Write the %files section
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
        $buffer .= "rpmbuild -ba ~/rpmbuild/SPECS/p.spec\n";

		// Write the dynamic script
        $this->dockerfile = fopen('Docker_paquito.sh', 'w');
        $this->_fwrite($this->dockerfile, $buffer, 'Docker_paquito.sh');
		fclose($this->dockerfile);
        
		// Start the generation with Docker
		$this->docker_launcher('centos:centos'.substr($this->getApplication()->dist_version, 0, 1), '/root/rpmbuild/RPMS/'.$this->getApplication()->dist_arch."/$package_name-".$this->struct['Version'].'-1.el'.substr($this->getApplication()->dist_version, 0, 1).".centos.$package_arch.rpm");
		
        // Delete the dynamic script
		unlink('Docker_paquito.sh');
	}
}
