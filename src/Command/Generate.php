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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Get the path and the name of the input file */
        $input_file = $input->getArgument('input');
        /* Get the references of the command parse() */
        $command = $this->getApplication()->find('prune');
        /* Declare the arguments in a array (arguments have to be given like this) */
        $arguments = array(
            'command' => 'prune',
            'input' => $input_file,
        );
        $array_input = new ArrayInput($arguments);
        /* Run command */
        $command->run($array_input, $output);

        /* Get the structure of the YaML file (which was parsed) */
        $this->struct = $this->getApplication()->data;
        /* Launch Logger module */
        $this->logger = new ConsoleLogger($output);

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

	function move_files($dest_directory, $struct)
	{
			/* Array to store the permissions to apply in post-installation commands */
			$array_perm = array();
			/* Security precaution : if it misses a slash at the end of the $dest_directory variable, add this slash  */
			if (substr($dest_directory, -1) != '/') {
					$dest_directory .= '/';
			}

			/* Copy each file in the directory of the current package */
			/* TODO Faire wildcard */
			foreach ($struct as $key => $value) {

					/* If the source doesn't exist */
					if (! file_exists($value['Source'])) {
							$this->logger->error($this->getApplication()->translator->trans('generate.missing', array('%path%' => $value['Source'])));

							exit(-1);
					}

					/* If the source is a directory */
					if (is_file($value['Source'])) {
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

					/* If the destination directory would exist */
					if(file_exists($dest_directory.$key)) {
							/* A file has already the name of the directory that we want create (on Linux, a directory is a file !)
							 * Else, that means the directory is already created */
							if (!is_dir($dest_directory.$key)) {
									$this->logger->error($this->getApplication()->translator->trans('generate.dirfile', array('%dir%' => $dest_directory.$key)));

									exit(-1);
							}
							$this->logger->warning($this->getApplication()->translator->trans('generate.direxist', array('%dir%' => $dest_directory.$key)));	
					} else {
							/* Create recursively the directories (if doesn't exist)
							 * IMPORTANT : the PHP mkdir() function can recursively create directories, but here we create manually
							 * directories in order to detect if there is a file which has the same name than a directory (so this
							 * directory cannot be created). */
							$r_dir = rtrim($dest_directory, '/');
							/* For each directory of the recursive chain */
							foreach (explode('/', $key) as $r_path) {
									$r_dir .= "/$r_path";
									/* If one of the chain directories is a file (so the directory cannot be created) */
									if (is_file($r_dir)) {
											$this->logger->error($this->getApplication()->translator->trans('generate.rmkdir', array('%dir%' => $r_dir)));

											exit(-1);
									}
									/* Create gradually the directories */
									$this->_mkdir($r_dir);
							}
					}

					if (is_dir($value['Source'])) {
							/* Copy the file in the directory package */
							$this->_rcopy($value['Source'], $dest_directory.$key, $array_perm, $value['Permissions']);
					} else { /* If the source is a file */
						/* Copy the file in the directory package */
						$this->_copy($value['Source'], $dest_directory.$key.$filename);
						$array_perm[$key.$filename] = $value['Permissions'];
					}
			}
			return $array_perm;
	}

    /* @param $id :
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

			/* If the destination directory does not exist create it */
			if (!is_dir($dest)) {
					$this->_mkdir($dest);
			}

			/* Open the source directory to read in files
			 * IMPORTANT The backslash before DirectoryIterator is
			 * to resolve namespace problem with this class */
			$i = new \DirectoryIterator($src);
			foreach ($i as $file) {
					if ($file->isFile()) {
							$this->_copy("$src/$file", "$dest/$file");

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

    /* This function is like mkdir() but with checking mecanims */
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

    protected function _fwrite($fd, $str, $file)
    {
        if (fwrite($fd, $str) === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => $file)));

            exit(-1);
        }
    }

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
            if ($this->getApplication()->dist_arch == 'x86_64') {
                $package_arch = 'amd64';
            } else {
                $package_arch = 'i386';
            }
        } else {
            $package_arch = 'all';
        }

        $dirname = $package_name.'_'.$this->struct['Version'].'_'.$package_arch;
        /* Create the directory of the package */
        $this->_mkdir($dirname);
        /* Create the directory "debian/" (which is required) */
        $this->_mkdir($dirname.'/debian');
        /* Create the directory "debian/source/" (to limit errors) */
		$this->_mkdir($dirname.'/debian/source');

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
				/* Install the packages required by the Buildtime dependencies */
				foreach(explode(' ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)) as $p_value) {
						/* The option "--needed" of pacman skip the reinstallation of existing packages (already installed) */
						$this->_system("apt-get --yes install $p_value");
				}
		}
		$array_field['Standards-Version'] = '3.9.5';
		$array_field['Homepage'] = $this->struct['Homepage']."\n"; # It has to has a line between the "Homepage" field and the "Package" field
		$array_field['Package'] = $package_name;
		$array_field['Architecture'] = $package_arch;
		$array_field['Description'] = $this->struct['Summary']."\n ".$this->struct['Description'];
		if (isset($struct_package['Runtime']['Dependencies'])) {
				/* This variable will contains the list of dependencies (to run) */
				$list_rundepend =  str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0));
				$array_field['Depends'] = "$list_rundepend";
		}

        /* Create and open the file "control" (in write mode) */
        $handle = fopen($dirname.'/debian/control', 'w');
        /* For each field that will contains the file "control" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key: $value\n", "$dirname/debian/control");
        }
        /* Add a line at the end (required) */
        $this->_fwrite($handle, "\n", "$dirname/debian/control");
        fclose($handle);

        /* To come back in actual directory if a "cd" command is present in pre-build commands */
        $pwd = getcwd();
        /* If there are pre-build commands */
        if (!empty($struct_package['Build']['Commands'])) {
            /* Execute each command */
            foreach ($struct_package['Build']['Commands'] as $value) {
                /* "cd" commands don't work (each shell_exec() has its owns
                 * shell), so it has to translates in chdir() functions */
                if (preg_match('/cd (.+)/', $value, $matches)) {
						if (!chdir($matches[1])) {
								$this->logger->error($this->getApplication()->translator->trans('generate.chdir', array('%dst%' => $matches[1])));

								exit(-1);
						}
                } else {
                    $this->_system($value);
                }
            }
        }
        /* To come back in usual directory if a "cd" command was present in pre-build commands */
        chdir($pwd);
        /* Move the files specified in the configuration file and store the returned array of permissions (for post-installation) */
        $post_permissions = $this->move_files($dirname, $struct_package['Files']);


		if (file_put_contents("$dirname/debian/source/format", '3.0 (native)') === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => "$dirname/debian/source/format")));

            exit(-1);
		}

		if (file_put_contents("$dirname/debian/compat", '9') === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => "$dirname/debian/compat")));

            exit(-1);
		}

		if (file_put_contents("$dirname/debian/rules", "#!/usr/bin/make -f\nDPKG_EXPORT_BUILDFLAGS = 1\ninclude /usr/share/dpkg/default.mk\n%:\n\tdh $@\noverride_dh_usrlocal:") === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => "$dirname/debian/rules")));

            exit(-1);
		}
	chmod("$dirname/debian/rules", 0755);

		if (file_put_contents("$dirname/debian/changelog", "$package_name (".$this->struct['Version'].") unstable; urgency=low\n\n  * Initial Release.\n\n -- ".$this->struct['Maintainer']."  ".date('r')) === false) {
            $this->logger->error($this->getApplication()->translator->trans('write.save', array('%output_file%' => "$dirname/debian/changelog")));

            exit(-1);
		}

	
		/* Create and open the file "*.install" (in write mode). This is the
		 * file which specifies what is the files of the project to packager */
        $handle = fopen("$dirname/debian/$package_name.install", 'w');
		foreach($post_permissions as $f_key => $f_value) {
				$this->_fwrite($handle, ltrim($f_key, '/').' '.ltrim(dirname($f_key), '/')."\n", "$dirname/debian/$package_name.install");
		}
        fclose($handle);

        if (isset($struct_package['Install']['Pre'])) {
            $handle_pre = fopen("$dirname/debian/preinst", 'w');
            $this->_fwrite($handle_pre, "#!/bin/bash\n\n", "$dirname/debian/preinst");
            foreach ($struct_package['Install']['Pre'] as $value) {
                $this->_fwrite($handle_pre, "$value\n", "$dirname/debian/preinst");
            }
            fclose($handle_pre);
            /* The file "preinst" has to have permissions between 755 and 775 */
            chmod("$dirname/debian/preinst", 0755);
        }

        if (count($post_permissions) || isset($struct_package['Install']['Post'])) {
            $handle_post = fopen("$dirname/debian/postinst", 'w');
			if (isset($struct_package['Install']['Post'])) {
					/* Write each command */
					foreach ($struct_package['Install']['Post'] as $key => $value) {
							$this->_fwrite($handle_post, "$value\n", "$dirname/debian/postinst");
					}
			}
            if (count($post_permissions)) {
                $this->_fwrite($handle_post, "#!/bin/bash\n\n", "$dirname/debian/postinst");
                foreach ($post_permissions as $key => $value) {
                    $this->_fwrite($handle_post, "chmod $value $key\n", "$dirname/debian/postinst");
                }
            }
            fclose($handle_post);
            /* The file "postinst" has to have permissions between 755 and 775 */
            chmod("$dirname/debian/postinst", 0755);
		}

		/* The command dpkg-buildpackage must be executed in the package directory */
		chdir($dirname);
        /* Create the DEB package */
		$this->_system("dpkg-buildpackage -us -uc");
        chdir($pwd);
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

        $dirname = $package_name.'-'.$this->struct['Version'].'-'.$package_arch;
        /* Create the directory of the package */
        $this->_mkdir($dirname);
        /* Create the directory "src/" (which contains the sources) */
        $this->_mkdir($dirname.'/src/');

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
		# DELETE $list_buildepend = $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 1);
		$array_field['makedepends'] = '('.$this->generate_list_dependencies($struct_package['Build']['Dependencies'], 1).')';
		/* Install the packages required by the Buildtime dependencies */
		foreach(explode(' ', $this->generate_list_dependencies($struct_package['Build']['Dependencies'], 0)) as $p_value) {
				/* The option "--needed" of pacman skip the reinstallation of existing packages (already installed) */
				$this->_system("pacman -Sy --noconfirm --needed $p_value");
		}
	}
	if (isset($struct_package['Runtime']['Dependencies'])) {
		/* This variable will contains the list of dependencies (to run) */
		$list_rundepend = $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 1);
		$array_field['depends'] = "($list_rundepend)";
	}


        /* Create and open the file "control" (in write mode) */
        $handle = fopen($dirname.'/PKGBUILD', 'w');
        /* For each field that will contains the file "control" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key=$value\n", "$dirname/PKGBUILD");
        }
        /* To come back in actual directory if a "cd" command is present in pre-build commands */
        $pwd = getcwd();
        /* If there are pre-build commands */
        /* IMPORTANT The build() function is not used because the pre-commands work directly in the src/ directory (of the package), and
         * several unexpected files will be included in the package. It is simpler to use Paquito. */
        if (!empty($struct_package['Build']['Commands'])) {
            /* Execute each command */
            foreach ($struct_package['Build']['Commands'] as $key => $value) {
                /* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
                if (preg_match('/cd (.+)/', $value, $matches)) {
						if (!chdir($matches[1])) {
								$this->logger->error($this->getApplication()->translator->trans('generate.chdir', array('%dst%' => $matches[1])));

								exit(-1);
						}
                } else {
                    $this->_system($value);
                }
            }
        }
        /* To come back in usual directory if a "cd" command was present in pre-build commands */
        chdir($pwd);

        /* Write the "cd" commands in the package() function */
        /* TODO Faire wildcard */
        $this->_fwrite($handle, "\npackage() {\n", "$dirname/PKGBUILD");
        foreach ($struct_package['Files'] as $key => $value) {
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
        $this->_fwrite($handle, "}\n", "$dirname/PKGBUILD");

        /* Move the files in the src/ directory of the package directory */
        $post_permissions = $this->move_files($dirname.'/src/', $struct_package['Files']);

        /* If there are pre/post-install commands */
        if (isset($struct_package['Install']) || count($post_permissions)) {
            $handle_script = fopen("$dirname/$package_name.install", 'w');
            /* If there are pre-install commands */
            if (isset($struct_package['Install']['Pre'])) {
                /* Write the pre-installation section */
                $this->_fwrite($handle_script, "pre_install() {\n", "$dirname/$package_name.install");
                /* Write each command */
                foreach ($struct_package['Install']['Pre'] as $value) {
                    $this->_fwrite($handle_script, "\t$value\n", "$dirname/$package_name.install");
                }
                $this->_fwrite($handle_script, "}\n\n", "$dirname/$package_name.install");
            }

            /* If there are post-install commands */
            if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
                /* Write the post-installation section */
                $this->_fwrite($handle_script, "post_install() {\n", "$dirname/$package_name.install");
                if (isset($struct_package['Install']['Post'])) {
                    /* Write each command */
                    foreach ($struct_package['Install']['Post'] as $key => $value) {
                        $this->_fwrite($handle_script, "\t$value\n", "$dirname/$package_name.install");
                    }
                }
                if (count($post_permissions)) {
                    foreach ($post_permissions as $key => $value) {
                        $this->_fwrite($handle_script, "\tchmod $value $key\n", "$dirname/$package_name.install");
                    }
                }
                /* Close the post-installation section */
                $this->_fwrite($handle_script, "}\n", "$dirname/$package_name.install");
            }
            fclose($handle_script);
        }

        /* Change owner of the package directory (to allow the creation of the package) */
        $this->_system("/bin/chown -R nobody $dirname");
        /* Move in the package directory */
        chdir($dirname);
        /* Launch the creation of the package */
        /* IMPORTANT The makepkg command is launched with nobody user because since February 2015, root user cannot use this command */
        $this->_system('sudo -u nobody makepkg');
        #echo shell_exec('makepkg');
        fclose($handle);
        /* To come back in usual directory (to write the output file in the right place */
        chdir($pwd);
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
        /* Creates the directories for building package (always in the home directory) */
        echo shell_exec('rpmdev-setuptree');

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
				$this->_system("yum -y install $p_value");
		}
	}
	if (isset($struct_package['Runtime']['Dependencies'])) {
		/* This variable will contains the list of dependencies (to run) */
		$array_field['Requires'] = $this->generate_list_dependencies($struct_package['Runtime']['Dependencies'], 0);
	}

        /* Create and open the file "p.spec" (in write mode) */
        $handle = fopen("$_SERVER[HOME]/rpmbuild/SPECS/p.spec", 'w');
        /* For each field that will contains the file "p.spec" */
        foreach ($array_field as $key => $value) {
            $this->_fwrite($handle, "$key: $value\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
        }
        /* Write the description */
        $this->_fwrite($handle, "\n%description\n".$this->struct['Summary']."\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");

        /* To come back in actual directory if a "cd" command is present in pre-build commands */
        $pwd = getcwd();
        /* If there are pre-build commands */
        /* IMPORTANT The build() function is not used because the pre-commands work directly in the src/ directory (of the package), and
         * several unexpected files will be included in the package. It is simpler to use Paquito. */
        if (!empty($struct_package['Build']['Commands'])) {
            /* Execute each command */
            foreach ($struct_package['Build']['Commands'] as $key => $value) {
                /* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
                if (preg_match('/cd (.+)/', $value, $matches)) {
						if (!chdir($matches[1])) {
								$this->logger->error($this->getApplication()->translator->trans('generate.chdir', array('%dst%' => $matches[1])));

								exit(-1);
						}
                } else {
                    $this->_system($value);
                }
            }
        }
        /* To come back in usual directory if a "cd" command was present in pre-build commands */
        chdir($pwd);

        /* Write the %install section */
        /* TODO Faire wildcard */
        $this->_fwrite($handle, "\n%install\n\trm -rf %{buildroot}\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
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
                $this->_fwrite($handle, "\tmkdir -p \$RPM_BUILD_ROOT/$directory/\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
            }
            /* The last character is a slash (in others words, the given path is a directory) */
            /* IMPORTANT We use this function instead of is_dir() because is_dir() works only in directories
             * mentioned by the open_basedir directive (in php.ini) */
            if (substr($key, -1) == '/') {
                $dest = $key.basename($value['Source']);
            } else {
                $dest = $key;
            }
            $this->_fwrite($handle, "\tcp --preserve ".ltrim($dest, '/')." \$RPM_BUILD_ROOT/".ltrim($key, '/')."\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
        }
        /* Move the files in the src/ directory of the package directory */
        $post_permissions = $this->move_files("$_SERVER[HOME]/rpmbuild/BUILD/", $struct_package['Files']);

        $this->_fwrite($handle, "\n%files\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
        foreach ($spec_files_add as $value) {
            $this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
        }

        /* If there are pre/post-install commands */
        if (isset($struct_package['Install']) || count($post_permissions)) {

            /* If there are pre-install commands */
            if (isset($struct_package['Install']['Pre'])) {
                /* Write the %pre section */
                $this->_fwrite($handle, "\n%pre\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
                /* Write each command */
                foreach ($struct_package['Install']['Pre'] as $value) {
                    $this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
                }
            }

            /* If there are post-install commands */
            if (isset($struct_package['Install']['Post']) || count($post_permissions)) {
                /* Write the %post section */
                $this->_fwrite($handle, "\n%post\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
                if (isset($struct_package['Install']['Post'])) {
                    /* Write each command */
                    foreach ($struct_package['Install']['Post'] as $value) {
                        $this->_fwrite($handle, "\t$value\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
                    }
                }
                if (count($post_permissions)) {
                    foreach ($post_permissions as $key => $value) {
                        $this->_fwrite($handle, "\tchmod $value $key\n", "$_SERVER[HOME]rpmbuild/SPECS/p.spec");
                    }
                }
            }
        }

        /* Launch the creation of the package */
        $this->_system('rpmbuild -ba ~/rpmbuild/SPECS/p.spec');
        fclose($handle);
    }
}
