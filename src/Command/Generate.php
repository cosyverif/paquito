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
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate a package')
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
    $struct = $this->getApplication()->data;
    /* Launch Logger module */
        $logger = new ConsoleLogger($output);

    /* The file /etc/os-release contains the informations about the distribution (where is executed this program)*/
    $array_ini = parse_ini_file('/etc/os-release');
    /* Get the name of the distribution */
    $dist = ucfirst($array_ini['ID']);
        switch ($dist) {
    case 'Debian':
        $package_system = 'apt-get -y install';
        break;
    case 'Arch':
        /* TODO Install on Archlinux the package "filesystem" */
        $dist = 'Archlinux';
        $package_system = 'pacman --noconfirm -S';
        break;
    case 'Centos':
        $package_system = 'yum -y install';
        break;
    default:
        $logger->error($this->getApplication()->translator->trans('prune.exist'));

        return -1;
    }
    /* Get the architecture of the current machine */
    /* TODO La fonction posix_name() ne fonctionnera pas sous Archlinux si l'extension de PHP "posix" (extension=posix.so) est commentée dans le fichier /etc/php/php.ini
     * Sous CentOS, il faut télécharger le paquet php-process pour utiliser les fonctions POSIX */
    $arch = posix_uname();
        $arch = $arch['machine'];
    /* This variable will contains the list of dependencies (to build) */
    $list_buildepend = $this->generate_list_dependencies($struct['BuildDepends']);
#	/* Get all build dependencies */
#	if (!empty($struct['BuildDepends'])) {
#		/* Concatenate all build dependencies on one line */
#		foreach ($struct['BuildDepends'] as $value) {
#			$list_buildepend .= " " . $value['Common'] ;
#		}
#	}
    /* For each package */
    foreach ($struct['Packages'] as $key => $value) {
        $struct_package = $value;
        if ($dist == 'Debian') {
            if ($value['Type'] == 'binary') {
                if ($arch['machine'] == 'x86_64') {
                    $arch = 'amd64';
                } else {
                    $arch = 'i386';
                }
            } else {
                $arch = 'all';
            }

            $dirname = $key.'_'.$struct['Version'].'_'.$arch;
            /* Create the directory of the package */
        $this->_mkdir($dirname);
            /* Create the directory "DEBIAN/" (which is required) */
        $this->_mkdir($dirname.'/DEBIAN');

            /* This variable will contains the list of dependencies (to run) */
            $list_rundepend = str_replace(' ', ', ', $this->generate_list_dependencies($struct_package['RunTimeDepends']));
            $array_field = array('Package' => "$key",
                    'Version' => $struct['Version'],
                    'Section' => 'unknown',
                    'Priority' => 'optional',
                    'Maintainer' => $struct['Maintainer'],
                    'Architecture' => $arch,
                    'Depends' => $list_rundepend,
                    'Homepage' => $struct['Homepage'],
                    'Description' => $struct['Summary']."\n ".$struct['Description'], );
            /* Create and open the file "control" (in write mode) */
            $handle = fopen($dirname.'/DEBIAN/control', 'w');
            /* For each field that will contains the file "control" */
            foreach ($array_field as $key => $value) {
                if (fwrite($handle, "$key: $value\n") === false) {
                    echo "Erreur d'écriture dans le fichier".$key.'/DEBIAN/control'."\n";

                    return -1;
                }
            }
            /* Add a line at the end (required) */
            if (fwrite($handle, "\n") === false) {
                echo "Erreur d'écriture dans le fichier".$key.'/DEBIAN/control'."\n";

                return -1;
            }
            fclose($handle);
            /* To come back in actual directory if a "cd" command is present in pre-build commands */
            $pwd = getcwd();
            /* If there are pre-build commands */
            if (!empty($struct_package['BeforeBuild'])) {
                /* Execute each command */
                foreach ($struct_package['BeforeBuild'] as $key => $value) {
                    /* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
                    if (preg_match('/cd (.+)/', $value['Common'], $matches)) {
                        chdir($matches[1]);
                    } else {
                        echo shell_exec($value['Common']);
                    }
                }
            }
            /* To come back in usual directory if a "cd" command was present in pre-build commands */
            chdir($pwd);
            /* Move the files specified in the configuration file */
            $this->move_files($dirname, $struct_package['Files']);
            /* Create the DEB package */
            echo shell_exec("dpkg-deb --build $dirname");
        } elseif ($dist == 'Archlinux') {
            /* The package type is not a binary */
            /* TODO Adapter pour les librairies et les autres types */
            if ($value['Type'] != 'binary') {
                $arch = 'all';
            }
            $name = $key;
            $dirname = $key.'-'.$struct['Version'].'-'.$arch;
            /* Create the directory of the package */
        $this->_mkdir($dirname);
            /* Create the directory "src/" (which contains the sources) */
        $this->_mkdir($dirname.'/src/');
            /* Translation to Archlinux syntax */
            $list_buildepend = "'".str_replace(' ', "'", $list_buildepend)."'";
            /* This variable will contains the list of dependencies (to run) */
            $list_rundepend = "'".str_replace(' ', "'", $this->generate_list_dependencies($struct_package['RunTimeDepends']))."'";
            $array_field = array('#Maintainer' => $struct['Maintainer'],
                    'pkgname' => "$name",
                    'pkgver' => $struct['Version'],
                    'pkgrel' => 1,
                    'arch' => $arch,
                    'depends' => "($list_rundepend)",
                    'makedepends' => "($list_buildepend)",
                    'url' => $struct['Homepage'],
                    'license' => "('$struct[Copyright]')",
                    'pkgdesc' => "'$struct[Summary]'", );
            /* Create and open the file "control" (in write mode) */
            $handle = fopen($dirname.'/PKGBUILD', 'w');
            /* For each field that will contains the file "control" */
            foreach ($array_field as $key => $value) {
                if (fwrite($handle, "$key=$value\n") === false) {
                    echo "Erreur d'écriture dans le fichier".$key.'/PKGBUILD'."\n";

                    return -1;
                }
            }
            /* To come back in actual directory if a "cd" command is present in pre-build commands */
            $pwd = getcwd();
            /* If there are pre-build commands */
            /* IMPORTANT The build() function is not used because the pre-commands work directly in the src/ directory (of the package), and
             * several unexpected files will be included in the package. It is simpler to use Paquito. */
            if (!empty($struct_package['BeforeBuild'])) {
                /* Execute each command */
                foreach ($struct_package['BeforeBuild'] as $key => $value) {
                    /* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
                    if (preg_match('/cd (.+)/', $value['Common'], $matches)) {
                        chdir($matches[1]);
                    } else {
                        echo shell_exec($value['Common']);
                    }
                }
            }
            /* To come back in usual directory if a "cd" command was present in pre-build commands */
            chdir($pwd);

            /* Write the "cd" commands in the package() function */
            /* TODO Faire wildcard */
            if (fwrite($handle, "\npackage() {\n") === false) {
                echo "Erreur d'écriture dans le fichier".$key.'/PKGBUILD'."\n";

                return -1;
            }
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
                    if (fwrite($handle, "\tmkdir -p \$pkgdir/$directory/\n") === false) {
                        echo "Erreur d'écriture dans le fichier".$key.'/PKGBUILD'."\n";

                        return -1;
                    }
                }
                /* The last character is a slash (in others words, the given path is a directory) */
                /* IMPORTANT We use this function instead of is_dir() because is_dir() works only in directories
                 * mentioned by the open_basedir directive (in php.ini) */
                if (substr($key, -1) == '/') {
                    $dest = $key.basename($value);
                } else {
                    $dest = $key;
                }
                if (fwrite($handle, "\tcp --preserve ".ltrim($dest, '/')." \$pkgdir/".ltrim($key, '/')."\n") === false) {
                    echo "Erreur d'écriture dans le fichier".$key.'/PKGBUILD'."\n";

                    return -1;
                }
            }
            if (fwrite($handle, "}\n") === false) {
                echo "Erreur d'écriture dans le fichier".$key.'/PKGBUILD'."\n";

                return -1;
            }
            /* Move the files in the src/ directory of the package directory */
            $this->move_files($dirname.'/src/', $struct_package['Files']);
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
        } elseif ($dist == 'Centos') {
            /* The package type is not a binary */
            /* TODO Adapter pour les librairies et les autres types */
            if ($value['Type'] != 'binary') {
                $arch = 'all';
            }
            $name = $key;
            $dirname = "$key-$struct[Version]-$arch";
        /* Creates the directories for building package (always in the home directory) */
        echo shell_exec('rpmdev-setuptree');

            /* This variable will contains the list of dependencies (to run) */
            $list_rundepend = $this->generate_list_dependencies($struct_package['RunTimeDepends']);
            $array_field = array('#Maintainer' => $struct['Maintainer'],
                    'Name' => $name,
                    'Version' => $struct['Version'],
            'Release' => '1%{?dist}',
            'Summary' => $name,
                    'License' => "$struct[Copyright]",
            'URL' => $struct['Homepage'],
            #'BuildRoot' => getcwd()."/$dirname/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)",
            'BuildRequires' => $list_buildepend,
            'Requires' => $list_rundepend,
        );
            /* Create and open the file "control" (in write mode) */
            $handle = fopen("$_SERVER[HOME]/rpmbuild/SPECS/p.spec", 'w');
            /* For each field that will contains the file "control" */
            foreach ($array_field as $key => $value) {
                if (fwrite($handle, "$key: $value\n") === false) {
                    echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                    return -1;
                }
            }
        /* Write the description */
        if (fwrite($handle, "\n%description\n$struct[Summary]\n") === false) {
            echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

            return -1;
        }

            /* To come back in actual directory if a "cd" command is present in pre-build commands */
            $pwd = getcwd();
            /* If there are pre-build commands */
            /* IMPORTANT The build() function is not used because the pre-commands work directly in the src/ directory (of the package), and
             * several unexpected files will be included in the package. It is simpler to use Paquito. */
            if (!empty($struct_package['BeforeBuild'])) {
                /* Execute each command */
                foreach ($struct_package['BeforeBuild'] as $key => $value) {
                    /* "cd" commands don't work (each shell_exec() has its owns shell), so it has to translates in chdir() functions */
                    if (preg_match('/cd (.+)/', $value['Common'], $matches)) {
                        chdir($matches[1]);
                    } else {
                        echo shell_exec($value['Common']);
                    }
                }
            }
            /* To come back in usual directory if a "cd" command was present in pre-build commands */
            chdir($pwd);

        /* Write the "cd" commands in the package() function */
            /* TODO Faire wildcard */
            if (fwrite($handle, "\n%install\n") === false) {
                echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                return -1;
            }
        /* The file section uses macros to include files */
        $spec_files = array(
            array('/usr/bin' => 'bindir'),
            array('/usr/share' => 'datadir'),
            array('/usr/share/doc' => 'defaultdocdir'),
            array('/usr/share/man' => 'mandir'),
            array('/usr/include' => 'includedir'),
            array('/usr/lib' => 'libdir'),
            array('/usr/sbin' => 'sbindir'),
            array('/var' => 'localstatedir'),
            array('/etc' => 'sysconfdir'), );
        /* List of files to include */
        $spec_files_add = array();

            #"bindir" => "/usr/bin",
            #"datadir" => "/usr/share",
            #"defaultdocdir" => "/usr/share/doc",
            #"includedir" => "/usr/include",
            #"libdir" => "/usr/lib",
            #"localstatedir" => "/var",
            #"mandir" => "/usr/share/man",
            #"sbindir" => "/usr/sbin",
            #"sysconfdir" => "/etc");
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
                                $spec_files_add[] = '%{_'.$tab[$val].'}/*';
                            } else {
                                $spec_files_add[] = '%{_'.$tab[$val].'}'.$path.'/*';
                            }
                        }
                    }

                    /* Write the "mkdir" command in the package() function */
                    if (fwrite($handle, "\tmkdir -p \$RPM_BUILD_ROOT/$directory/\n") === false) {
                        echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                        return -1;
                    }
                }
                /* The last character is a slash (in others words, the given path is a directory) */
                /* IMPORTANT We use this function instead of is_dir() because is_dir() works only in directories
                 * mentioned by the open_basedir directive (in php.ini) */
                if (substr($key, -1) == '/') {
                    $dest = $key.basename($value);
                } else {
                    $dest = $key;
                }
                if (fwrite($handle, "\tcp --preserve ".ltrim($dest, '/')." \$RPM_BUILD_ROOT/".ltrim($key, '/')."\n") === false) {
                    echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                    return -1;
                }
            }
            /* Move the files in the src/ directory of the package directory */
        $this->move_files("$_SERVER[HOME]/rpmbuild/BUILD/", $struct_package['Files']);

            if (fwrite($handle, "\n%files\n") === false) {
                echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                return -1;
            }
            foreach ($spec_files_add as $value) {
                if (fwrite($handle, "$value\n") === false) {
                    echo "Erreur d'écriture dans le fichier $_SERVER[HOME]/rpmbuild/SPECS/p.spec\n";

                    return -1;
                }
            }
        /* Launch the creation of the package */
        shell_exec('rpmbuild -ba ~/rpmbuild/SPECS/p.spec');
            fclose($handle);
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
        /* Copy each file in the directory of the current package */
        /* TODO Faire wildcard */
        foreach ($struct as $key => $value) {
            /* The destination file will be in a sub-directory */
            if (strrpos($key, '/') !== false) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($key, '/'));
                /* Remove the name of the file */
                unset($explode_array[count($explode_array) - 1]);
                /* Transform the array in a string */
                $directory = implode('/', $explode_array);
        /* Create recursively the directories */
                if (!mkdir($dest_directory.'/'.$directory.'/', 0777, true) && !is_dir($dest_directory.'/'.$directory.'/')) {
                    echo("Echec lors de la création des répertoires...\n");

                    return -1;
                }
            }
            if (is_dir($dest_directory.$key)) {
                /* Split the path in a array */
                $explode_array = explode('/', ltrim($value, '/'));
                /* Copy the file in the directory package */
                if (!copy($value, $dest_directory.$key.$explode_array[count($explode_array) - 1])) {
                    echo "La copie $value du fichier a échouée\n";

                    return -1;
                }
            } else {
                /* Copy the file in the directory package */
                if (!copy($value, $dest_directory.$key)) {
                    echo "La copie $value du fichier a échouée\n";

                    return -1;
                }
            }
        }
    }

    protected function generate_list_dependencies($struct)
    {
        $list = null;
    /* If there are dependencies */
    if (!empty($struct)) {
        /* Concatenate all build dependencies on one line */
        foreach ($struct as $value) {
            $list .= ' '.$value['Common'];
        }
    }
    /* Delete superfluous element (space) */
    return $list = ltrim($list, ' ');
    }

    /* This function is like mkdir() but checks also if this last fails */
    protected function _mkdir($name)
    {
        /* is_dir() is here to controls if the directory existed */
        if (!mkdir($name) && !is_dir($name)) {
            die('Echec lors de la création des répertoires...');
        }
    }
}
