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
	private $current_struct;
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
			)
			;
	}

	/* Launches for each package her generation
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
		/* Get the path and the name of the input file */
		$input_file = $input->getArgument('input');
		/* Get presence of the "--local" option */
		$local = $input->getOption('local');
		/* Get the references of the command parse() */
		$command = $this->getApplication()->find('prune');
		/* Declare the arguments in a array (arguments have to be given like this) */
		$arguments = array(
			'command' => 'prune',
			'input' => $input_file,
			'--local' => $local,
		);
		$array_input = new ArrayInput($arguments);
		/* Run command */
		$command->run($array_input, $output);

		/* Get the structure of the YaML file (which was parsed) */
		$this->struct = $this->getApplication()->data;
		/* Launch Logger module */
		$this->logger = new ConsoleLogger($output);

		/* If the "--local" option is not set, so there are several YAML structure to use */
		if (! $local) {
			/* For each distribution */
			foreach($this->struct['Distributions'] as $dist => $tab_ver) {
				/* For each version */
				foreach($tab_ver as $ver => $tab_archi) {
					/* For each architecture */
					foreach($tab_archi as $archi => $tab_package) {
						$this->getApplication()->dist_name = $dist;
						$this->getApplication()->dist_version = $ver;
						if ($arch == '64') {
							$this->getApplication()->dist_arch = 'x86_64';
						} else {
							$this->getApplication()->dist_arch = 'i386';
						}
						/* Launches the package generation for the distribution currently treated */
						$this->launcher($this->struct['Distributions'][$dist][$ver][$archi]['Packages']);
					}
				}
			}
		} else { /* The generation will be adapted with the current configuration */
			/* Launches the package generation for the current distribution */
			$this->launcher($this->struct['Packages']);
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

	function move_files($dest_directory, $struct)
	{
		/* Array to store the permissions to apply in post-installation commands */
		$array_perm = array();
		/* Security precaution : if it misses a slash at the end of the $dest_directory variable, add this slash  */
		if (substr($dest_directory, -1) != '/') {
			$dest_directory .= '/';
		}

		/* Copy each file in the directory of the current package */
		foreach ($struct as $key => $value) {

			/* If the source is a directory */
			if (substr($value['Source'], -1) != '/') {
				/* If the file will be renamed in its destination */	
				if (substr($key, -1) != '/') {
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

			/* Create recursively the directories (if doesn't exist)
			 * IMPORTANT : the PHP mkdir() function can recursively create directories, but here we create manually
			 * directories in order to detect if there is a file which has the same name than a directory (so this
			 * directory cannot be created). */

			$this->_fwrite($this->dockerfile, "mkdir -p $dest_directory/$key\n", 'Docker_paquito.sh');
			#$r_dir = rtrim($dest_directory, '/');
			/* For each directory of the recursive chain */
			#foreach (explode('/', $key) as $r_path) {
			#	$r_dir .= "/$r_path";
			/* The directories will be created gradually */
			#	$this->_fwrite($this->dockerfile, "mkdir $r_dir\n", 'Docker_paquito.sh');
			#}

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

	/* Generates a string which contain a list of dependencies for a package
	 * IMPORTANT: Manages groups of packages in Archlinux
	 * @param $struct : Bit of the YAML structure which contains the dependencies
	 * @param $id : Changes the writing format of the returned string
	 * 0 -> Dependencies separated with spaces
	 * 1 -> Dependencies in quotes and separated with spaces */
	protected function generate_list_dependencies($struct, $id)
	{
		$list = null;
		/* If there are dependencies */
		if (!empty($struct)) {
			/* If the current is an Archlinux (where there are package groups) */
			if ($this->getApplication()->dist_name == 'Archlinux') {
				/* Get a list of package groups (like "base-devel") */
				$groups = rtrim(shell_exec("pacman -Qg | awk -F ' ' '{print $1}' | sort -u | sed -e ':a;N;s/\\n/ /;ba'"));
				/* Transforms the string of package groups in an array (easier to use) */
				$groups = explode(" ", $groups);
			}
			/* Concatenate all build dependencies on one line */
			foreach ($struct as $value) {
				/* If the current is an Archlinux (where there are package groups) */
				if ($this->getApplication()->dist_name == 'Archlinux') {
					/* If the dependencie is in fact a group */
					if (in_array($value, $groups)) {
						/* Get the list of packages which compose the group */
						$p_groups = rtrim(shell_exec("pacman -Qgq $value | sed -e ':a;N;s/\\n/ /;ba'"));
						$p_groups = explode(" ", $p_groups);
						/* Foreach package of the group */
						foreach ($p_groups as $p_value) {
							switch ($id) {
							case 0:
								$list .= ' '.$p_value;
								break;
							case 1:
								$list .= " '".$p_value."'";
								break;
							}
						}
						continue;
					}
				}
				/* In Archlinux, this next code is not executed
				 * if the dependencie is a package */
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
		return ltrim($list, ' ');
	}

	/* This function is like copy() but with checking mecanims */
	function _copy($source, $destination)
	{
		if (file_exists($destination)) {
			if (is_dir($destination)) {
				$this->logger->error($this->getApplication()->translator->trans('generate.copyfile', array('%src%' => $source, '%dst%' => $destination)));

				exit(-1);
			}
			$this->logger->warning($this->getApplication()->translator->trans('generate.copyexist', array('%src%' => $source, '%dst%' => $destination)));	
		}
		if (!copy($source, $destination)) {
			$this->logger->error($this->getApplication()->translator->trans('generate.copy', array('%src%' => $source, '%dst%' => $destination)));

			exit -1;
		}
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

	/* This function is like mkdir() but with checking mechanisms */
	protected function _mkdir($name)
	{
		/* If the directory would exist */
		if(file_exists($name)) {
			/* A file has already the name of the directory that we want create (on Linux, a directory is a file !)
			 * Else, that means the directory is already created */
			if (!is_dir($name)) {
				$this->logger->error($this->getApplication()->translator->trans('generate.dirfile', array('%dir%' => $name)));

				exit(-1);
			}
			$this->logger->warning($this->getApplication()->translator->trans('generate.direxist', array('%dir%' => $name)));	
		} else {
			/* We have to create the directory */
			if (!mkdir($name)) {
				$this->logger->error($this->getApplication()->translator->trans('generate.mkdir', array('%dir%' => $name)));

				exit(-1);
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

		$this->dockerfile = fopen("Docker_paquito.sh", 'w');

		$this->_fwrite($this->dockerfile, "cd /paquito\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "mkdir -p $dirname/debian/source\n", 'Docker_paquito.sh');

		$array_field = array(
			'Source' => $package_name,
			'Section' => 'unknown',
			'Priority' => 'optional',
			'Maintainer' => $this->struct['Maintainer']);
		/* The "Build-Depends" must be placed before fields like "Package" or "Depends" (else this field is not recognized) */
		if (isset($struct_package['Build']['Dependencies'])) {
			/* This variable will contains the list of dependencies (to build) */
			$list_buildepend =  str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0));
			$array_field['Build-Depends'] = "$list_buildepend";
			$this->_fwrite($this->dockerfile, "apt-get update\n", 'Docker_paquito.sh');
			/* Install the packages required by the Buildtime dependencies */
			foreach(explode(' ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)) as $p_value) {
				/* Installs package */
				$this->_fwrite($this->dockerfile, "apt-get --yes install $p_value\n", 'Docker_paquito.sh');
			}
		}
		/* IMPORTANT : The fields "Standards-Version", "Homepage"... are placed after "Build-Depends", "Source"... because
		 * the Debian package wants a specific placing order (else there is an error) */
		$array_field['Standards-Version'] = '3.9.5';
		$array_field['Homepage'] = $this->struct['Homepage']."\n"; # It has to has a line between the "Homepage" field and the "Package" field
		$array_field['Package'] = $package_name;
		$array_field['Architecture'] = $package_arch;
		$array_field['Description'] = $this->struct['Summary']."\n ".$this->struct['Description'];
		if (isset($struct_package['Runtime']['Dependencies'])) {
			/* This variable will contains the list of dependencies (to run) */
			#$list_rundepend =  str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0));
			#$array_field['Depends'] = "$list_rundepend";
			$array_field['Depends'] = str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0));
		}

		/* Create and open the file "control" (in write mode) */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/debian/control\n", 'Docker_paquito.sh');
		/* For each field that will contains the file "control" */
		foreach ($array_field as $key => $value) {
			$this->_fwrite($this->dockerfile, "$key: $value\n", 'Docker_paquito.sh');
		}
		/* Add a line at the end (required) */
		$this->_fwrite($this->dockerfile, "\n", 'Docker_paquito.sh');

		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* To come back in actual directory if a "cd" command is present in pre-build commands */
		$this->_fwrite($this->dockerfile, "TEMP_PWD=$(pwd)\n", 'Docker_paquito.sh');
		/* If there are pre-build commands */
		if (!empty($struct_package['Build']['Commands'])) {
			/* Execute each command */
			foreach ($struct_package['Build']['Commands'] as $value) {
				/* "cd" commands don't work (each shell_exec() has its owns
				 * shell), so it has to translates in chdir() functions */
				if (preg_match('/cd (.+)/', $value, $matches)) {
					$this->_fwrite($this->dockerfile, "cd $matches[1]\n", 'Docker_paquito.sh');
				} else {
					$this->_fwrite($this->dockerfile, "$value\n", 'Docker_paquito.sh');
				}
			}
		}
		/* To come back in usual directory if a "cd" command was present in pre-build commands */
		$this->_fwrite($this->dockerfile, "cd \$TEMP_PWD\n", 'Docker_paquito.sh');

		/* Move the files specified in the configuration file and store the returned array of permissions (for post-installation) */
		$post_permissions = $this->move_files($dirname, $struct_package['Files']);

		$this->_fwrite($this->dockerfile, "echo '3.0 (native)' > $dirname/debian/source/format\n", 'Docker_paquito.sh');

		$this->_fwrite($this->dockerfile, "echo '9' > $dirname/debian/compat\n", 'Docker_paquito.sh');

		$this->_fwrite($this->dockerfile, "echo -e '#!/usr/bin/make -f\\nDPKG_EXPORT_BUILDFLAGS = 1\\ninclude /usr/share/dpkg/default.mk\\n%:\\n\\tdh $@\\noverride_dh_usrlocal:' > $dirname/debian/rules\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "chmod 0755 $dirname/debian/rules\n", 'Docker_paquito.sh');

		$this->_fwrite($this->dockerfile, "echo -e \"$package_name (".$this->struct['Version'].") unstable; urgency=low\\n\\n  * Initial Release.\\n\\n -- ".$this->struct['Maintainer']."  ".date('r')."\" > $dirname/debian/changelog\n", 'Docker_paquito.sh');


		/* Create and open the file "*.install" (in write mode). This is the
		 * file which specifies what is the files of the project to packager */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/debian/$package_name.install\n", 'Docker_paquito.sh');
		foreach($post_permissions as $f_key => $f_value) {
			$this->_fwrite($this->dockerfile, ltrim($f_key, '/').' '.ltrim(dirname($f_key), '/')."\n", 'Docker_paquito.sh');
		}
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		if (isset($struct_package['Install']['Pre'])) {
			$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/debian/preinst\n", 'Docker_paquito.sh');
			$this->_fwrite($this->dockerfile, "#!/bin/bash\n", 'Docker_paquito.sh');
			foreach ($struct_package['Install']['Pre'] as $value) {
				$this->_fwrite($handle_pre, "$value\n", "$dirname/debian/preinst");
			}
			$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');
			/* The file "preinst" has to have permissions between 755 and 775 */
			$this->_fwrite($this->dockerfile, "chmod 0755 $dirname/debian/preinst\n", 'Docker_paquito.sh');
		}

		if (count($post_permissions) || isset($struct_package['Install']['Post'])) {
			$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/debian/postinst\n", 'Docker_paquito.sh');
			if (isset($struct_package['Install']['Post'])) {
				/* Write each command */
				foreach ($struct_package['Install']['Post'] as $key => $value) {
					$this->_fwrite($this->dockerfile, "$value\n", 'Docker_paquito.sh');
				}
			}
			if (count($post_permissions)) {
				$this->_fwrite($this->dockerfile, "#!/bin/bash\n", 'Docker_paquito.sh');
				foreach ($post_permissions as $key => $value) {
					$this->_fwrite($this->dockerfile, "chmod $value $key\n", 'Docker_paquito.sh');
				}
			}
			$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');
			/* The file "postinst" has to have permissions between 755 and 775 */
			$this->_fwrite($this->dockerfile, "chmod 0755 $dirname/debian/postinst\n", 'Docker_paquito.sh');
		}

		/* The command dpkg-buildpackage must be executed in the package directory */
		$this->_fwrite($this->dockerfile, "cd $dirname\n", 'Docker_paquito.sh');
		/* (In the Docker) Creates the DEB package */
		$this->_fwrite($this->dockerfile, "dpkg-buildpackage -us -uc\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "cd \$TEMP_PWD\n", 'Docker_paquito.sh');
		fclose($this->dockerfile);
		/* Starts the generation with Docker */
		$this->docker_launcher('debian:'.lcfirst($this->getApplication()->dist_version), "/paquito/$dirname.deb");
		/* Deletes the dynamic script */
		unlink('Docker_paquito.sh');
	}

	protected function make_archlinux($package_name, $struct_package)
	{
		/* The package type is not a binary */
		/* TODO Adapter pour les librairies et les autres types */
		if ($struct_package['Type'] != 'binary') {
			$package_arch = 'all';
		} else {
			$package_arch = $this->getApplication()->dist_arch;
		}

		/* Defines the directory where will be stored sources and final package */
		$dirname = $package_name.'-'.$this->struct['Version'].'-'.$package_arch;

		/* Open the dynamic script that Docker will use */
		$this->dockerfile = fopen("Docker_paquito.sh", 'w');

		/* VERY IMPORTANT : When Archlinux is in a Docker, it seems to have some problems with GPG/PGP
		 * keys. So, lines are added (compared to Debian or Centos) in dynamic script to avoid these problems
		 * -> The problem seems to be the package database (of Pacman) in Docker which could be too old */

		/* (In the Docker) Changes directory for /paquito (see Docker_launcher() function) */
		$this->_fwrite($this->dockerfile, "cd /paquito\n", 'Docker_paquito.sh');
		/* (In the Docker) Creates the directory where will be stored sources and final
		 * package. In more, creates recursively /src directory */
		$this->_fwrite($this->dockerfile, "mkdir -p $dirname/src\n", 'Docker_paquito.sh');
		/* (In the Docker) Updates list of packages (to download) */
		$this->_fwrite($this->dockerfile, "pacman -Sy\n", 'Docker_paquito.sh');
		/* Upgrade "pacman" and download "openssl" (used by "curl", which is pacman's dependency)
		 * If "openssl" is missing, there will be an error (SSL_CTX) */
		$this->_fwrite($this->dockerfile, "pacman -S --noconfirm --needed openssl pacman\npacman-db-upgrade\n", 'Docker_paquito.sh');

		/* (In the Docker) Dirmngr is a server for managing and downloading certificate revocation
		 *  lists (CRLs) for X.509 certificates and for downloading the certificates themselves.
		 *  It is used here to avoid the "invalid of corrupted package (PGP signature)" error (see
		 *  Docker_launcher() function) */ 
		$this->_fwrite($this->dockerfile, "dirmngr </dev/null\n", 'Docker_paquito.sh');
		/* (In the Docker) Creates a keyring (trusted keys), refresh this with the remote server and checks keys */
		$this->_fwrite($this->dockerfile, "pacman-key --init && pacman-key --refresh-keys && pacman-key --populate archlinux\n", 'Docker_paquito.sh');

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
			/* This variable will contains the list of dependencies (to build) */
			$array_field['makedepends'] = '('.$this->generate_list_dependencies($struct_package['Build']['Dependencies'], 1).')';
			/* Install the packages required by the Buildtime dependencies */
			foreach(explode(' ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)) as $p_value) {
				/* The option "--needed" of pacman skip the reinstallation of existing packages (in others words, already installed)
				 * (In the Docker) Installs the package and upgrades format of the database (which is too old) */
				$this->_fwrite($this->dockerfile, "pacman -S --noconfirm --needed $p_value\npacman-db-upgrade\n", 'Docker_paquito.sh');
			}
		}
		if (isset($struct_package['Runtime']['Dependencies'])) {
			/* This variable will contains the list of dependencies (to run) */
			$array_field['depends'] = '('.$this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 1).')';
		}

		/* (In the Docker) Writes the file PKGBUILD */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/PKGBUILD\n", 'Docker_paquito.sh');
		/* For each field that will contains the file PKGBUILD */
		foreach ($array_field as $key => $value) {
			$this->_fwrite($this->dockerfile, "$key=$value\n", 'Docker_paquito.sh');
		}
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* (In the Docker) To come back in actual directory if a "cd" command is present in pre-build commands */
		$this->_fwrite($this->dockerfile, "TEMP_PWD=$(pwd)\n", 'Docker_paquito.sh');
		/* If there are pre-build commands */
		/* IMPORTANT The build() function is not used because the pre-commands work directly
		 * in the src/ directory (of the package). It is simpler to launch commands directly */
		if (!empty($struct_package['Build']['Commands'])) {
			/* (In the Docker) Execute each command */
			foreach ($struct_package['Build']['Commands'] as $key => $value) {
				$this->_fwrite($this->dockerfile, "$value\n", 'Docker_paquito.sh');
			}
		}
		/* (In the Docker) To come back in usual directory if a "cd" command was present in pre-build commands */
		$this->_fwrite($this->dockerfile, "cd \$TEMP_PWD\n", 'Docker_paquito.sh');

		/* (In the Docker) Writes again in the file PKGBUILD (to do the package() function) */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ >> $dirname/PKGBUILD\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "\npackage() {\n", 'Docker_paquito.sh');
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
				$this->_fwrite($this->dockerfile, "\tmkdir -p \\\$pkgdir/$directory/\n", 'Docker_paquito.sh');
			}
			/* The last character is a slash (in others words, the given path is a directory) */
			if (substr($key, -1) == '/') {
				$dest = $key.basename($value['Source']);
			} else {
				$dest = $key;
			}
			/* Write the "cd" command in the package() function */
			$this->_fwrite($this->dockerfile, "\tcp --preserve ".ltrim($dest, '/')." \\\$pkgdir/".ltrim($key, '/')."\n", 'Docker_paquito.sh');
		}
		$this->_fwrite($this->dockerfile, "}\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* (In the Docker) Moves the files of the src/ directory in the package directory */
		$post_permissions = $this->move_files($dirname.'/src/', $struct_package['Files']);

		/* If there are pre/post-install commands */
		if (isset($struct_package['Install']) || count($post_permissions)) {
			/* (In the Docker) Writes the file *.install (for pre/post commands) */
			$this->_fwrite($this->dockerfile, "cat << _EOF_ > $dirname/$package_name.install\n", 'Docker_paquito.sh');
			/* If there are pre-install commands */
			if (isset($struct_package['Install']['Pre'])) {
				/* (In the Docker) Writes the pre-installation section */
				$this->_fwrite($this->dockerfile, "pre_install() {\n", 'Docker_paquito.sh');
				/* (In the Docker) Writes each command */
				foreach ($struct_package['Install']['Pre'] as $value) {
					$this->_fwrite($this->dockerfile, "\t$value\n", 'Docker_paquito.sh');
				}
				$this->_fwrite($this->dockerfile, "}\n\n", 'Docker_paquito.sh');
			}

			/* If there are post-install commands */
			if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
				/* (In the Docker) Writes the post-installation section */
				$this->_fwrite($this->dockerfile, "post_install() {\n", 'Docker_paquito.sh');
				if (isset($struct_package['Install']['Post'])) {
					/* (In the Docker) Write each command */
					foreach ($struct_package['Install']['Post'] as $key => $value) {
						$this->_fwrite($this->dockerfile, "\t$value\n", 'Docker_paquito.sh');
					}
				}
				if (count($post_permissions)) {
					foreach ($post_permissions as $key => $value) {
						$this->_fwrite($this->dockerfile, "\tchmod $value $key\n", 'Docker_paquito.sh');
					}
				}
				/* Close the post-installation section */
				$this->_fwrite($this->dockerfile, "}\n", 'Docker_paquito.sh');
			}
			$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');
		}

		/* (In the Docker) Changes owner of the package directory (to allow the creation of the package) */
		$this->_fwrite($this->dockerfile, "/bin/chown -R nobody $dirname\n", 'Docker_paquito.sh');
		/* (In the Docker) Moves in the package directory */
		$this->_fwrite($this->dockerfile, "cd $dirname\n", 'Docker_paquito.sh');
		/* (In the Docker) Launches the creation of the package */
		/* IMPORTANT : The makepkg command is launched with nobody user because since February
		 * 2015, root user cannot use this command */
		$this->_fwrite($this->dockerfile,"sudo -u nobody makepkg -f\n", 'Docker_paquito.sh');
		#$this->_fwrite($this->dockerfile, "cd \$TEMP_PWD\n", 'Docker_paquito.sh');
		fclose($this->dockerfile);
		/* Starts the generation with Docker */
		$this->docker_launcher('base/archlinux', "/paquito/$dirname/".$package_name.'-'.$this->struct['Version'].'-1-'.$package_arch.'.pkg.tar.xz');
		/* Deletes the dynamic script */
		unlink('Docker_paquito.sh');
	}

	protected function make_centos($package_name, $struct_package)
	{
		/* The package type is not a binary */
		/* TODO Adapter pour les librairies et les autres types */
		if ($struct_package['Type'] != 'binary') {
			$package_arch = 'all';
		} else {
			$package_arch = $this->getApplication()->dist_arch;
		}

		$dirname = $package_name - $this->struct['Version'].'-'.$package_arch;

		/* Opens the dynamic script that Docker will use */
		$this->dockerfile = fopen("Docker_paquito.sh", 'w');

		/* (In the Docker) Installs needed packages to build new packages */
		$this->_fwrite($this->dockerfile, "yum -y install rpmdevtools rpm-build\n", 'Docker_paquito.sh');
		/* (In the Docker) Creates the directories for building package (always in the home directory) */
		$this->_fwrite($this->dockerfile, "rpmdev-setuptree\n", 'Docker_paquito.sh');
		/* (In the Docker) Changes directory for /paquito (see Docker_launcher() function) */
		$this->_fwrite($this->dockerfile, "cd /paquito\n", 'Docker_paquito.sh');

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
			/* This variable will contains the list of dependencies (to build) */
			$array_field['BuildRequires'] = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0);
			/* Install the packages required by the Buildtime dependencies */
			foreach(explode(' ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)) as $p_value) {
				$this->_fwrite($this->dockerfile, "yum -y install $p_value\n", 'Docker_paquito.sh');
			}
		}
		if (isset($struct_package['Runtime']['Dependencies'])) {
			/* This variable will contains the list of dependencies (to run) */
			$array_field['Requires'] = $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0);
		}

		/* (In the Docker) Writes the file "p.spec" */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ > ~/rpmbuild/SPECS/p.spec\n", 'Docker_paquito.sh');
		/* For each field that will contains the file "p.spec" */
		foreach ($array_field as $key => $value) {
			$this->_fwrite($this->dockerfile, "$key: $value\n", 'Docker_paquito.sh');
		}
		/* (In the Docker) Writes the description */
		$this->_fwrite($this->dockerfile, "\n%description\n".$this->struct['Summary']."\n", 'Docker_paquito.sh');
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* (In the Docker) To come back in actual directory if a "cd" command is present in pre-build commands */
		$this->_fwrite($this->dockerfile, "TEMP_PWD=$(pwd)\n", 'Docker_paquito.sh');
		/* If there are pre-build commands */
		/* IMPORTANT The build() function is not used because the pre-commands work directly
		 * in the src/ directory (of the package). It is simpler to launch commands directly */
		if (!empty($struct_package['Build']['Commands'])) {
			/* (In the Docker) Executes each command */
			foreach ($struct_package['Build']['Commands'] as $key => $value) {
				$this->_fwrite($this->dockerfile, "$value\n", 'Docker_paquito.sh');
			}
		}
		/* (In the Docker) To come back in usual directory if a "cd" command was present in pre-build commands */
		$this->_fwrite($this->dockerfile, "cd \$TEMP_PWD\n", 'Docker_paquito.sh');

		/* (In the Docker) Writes again in the file "p.spec" (%install section) */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ >> ~/rpmbuild/SPECS/p.spec\n", 'Docker_paquito.sh');
		/* TODO Faire wildcard */
		$this->_fwrite($this->dockerfile, "\n%install\n\trm -rf %{buildroot}\n", 'Docker_paquito.sh');

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
				/* (In the Docker) Writes the "mkdir" command in the %install section */
				$this->_fwrite($this->dockerfile, "\tmkdir -p \\\$RPM_BUILD_ROOT/$directory/\n", 'Docker_paquito.sh');
			}
			/* The last character is a slash (in others words, the given path is a directory) */
			if (substr($key, -1) == '/') {
				$dest = $key.basename($value['Source']);
			} else {
				$dest = $key;
			}
			/* (In the Docker) Writes the "cp" command in the %install section */
			$this->_fwrite($this->dockerfile, "\tcp --preserve ".ltrim($dest, '/')." \\\$RPM_BUILD_ROOT/".ltrim($key, '/')."\n", 'Docker_paquito.sh');
		}
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* Moves the files in the src/ directory of the package directory */
		$post_permissions = $this->move_files("$_SERVER[HOME]/rpmbuild/BUILD/", $struct_package['Files']);


		/* (In the Docker) Writes again in the file "p.spec" (%files and %pre/%post sections) */
		$this->_fwrite($this->dockerfile, "cat << _EOF_ >> ~/rpmbuild/SPECS/p.spec\n", 'Docker_paquito.sh');
		/* (In the Docker) Writes the %files section */
		$this->_fwrite($this->dockerfile, "\n%files\n", 'Docker_paquito.sh');
		foreach ($spec_files_add as $value) {
			$this->_fwrite($this->dockerfile, "\t$value\n", 'Docker_paquito.sh');
		}

		/* If there are pre/post-install commands */
		if (isset($struct_package['Install']) || count($post_permissions)) {

			/* If there are pre-install commands */
			if (isset($struct_package['Install']['Pre'])) {
				/* (In the Docker) Writes the %pre section */
				$this->_fwrite($this->dockerfile, "\n%pre\n", 'Docker_paquito.sh');
				/* (In the Docker) Writes each command */
				foreach ($struct_package['Install']['Pre'] as $value) {
					$this->_fwrite($this->dockerfile, "\t$value\n", 'Docker_paquito.sh');
				}
			}

			/* If there are post-install commands */
			if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
				/* (In the Docker) Writes the %post section */
				$this->_fwrite($this->dockerfile, "\n%post\n", 'Docker_paquito.sh');
				if (isset($struct_package['Install']['Post'])) {
					/* (In the Docker) Writes each command */
					foreach ($struct_package['Install']['Post'] as $value) {
						$this->_fwrite($this->dockerfile, "\t$value\n", 'Docker_paquito.sh');
					}
				}
				if (count($post_permissions)) {
					foreach ($post_permissions as $key => $value) {
						$this->_fwrite($this->dockerfile, "\tchmod $value $key\n", 'Docker_paquito.sh');
					}
				}
			}
		}
		$this->_fwrite($this->dockerfile, "_EOF_\n", 'Docker_paquito.sh');

		/* (In the Docker) Launches the creation of the package */
		$this->_fwrite($this->dockerfile, "rpmbuild -ba ~/rpmbuild/SPECS/p.spec\n", 'Docker_paquito.sh');

		fclose($this->dockerfile);
		/* Starts the generation with Docker */
		$this->docker_launcher('centos:centos'.substr($this->getApplication()->dist_version, 0, 1), '/root/rpmbuild/RPMS/'.$this->getApplication()->dist_arch."/$package_name-".$this->struct['Version'].'-1.el'.substr($this->getApplication()->dist_version, 0, 1).".centos.$package_arch.rpm");
		/* Deletes the dynamic script */
		unlink('Docker_paquito.sh');
	}
}
