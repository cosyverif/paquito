#!/bin/bash

modules=$(php -m)

# If the module "Phar" is already loaded (because it is compiled in PHP)
if [[ ! $modules =~ "Phar" ]] ; then
		m_phar='-d extension=phar.so'	
fi

# If the module "posix" is already loaded (because it is compiled in PHP)
if [[ ! $modules =~ "posix" ]] ; then
		m_posix='-d extension=posix.so'	
fi

# Launch docker service
# We suppose docker is installed 
# Perform some very rudimentary platform detection and launch docker service
# TODO: What happen' if lsb_release do not exist ?
if lsb_release -v "$@" > /dev/null 2>&1; then
        lsb_dist="$(lsb_release -si | tr '[:upper:]' '[:lower:]')"

        case "$lsb_dist" in
            debian|centos)
                    sudo service docker start;;
                    
            arch)
                    sudo systemctl start docker;;
                    
            *)
                    echo -e "Veillez a démarrer le deamon Docker avant d\'exécuter Paquito\nConsultez: https://docs.docker.com/engine/installation/linux/\n\n";;
        esac
fi

php -d open_basedir= $m_phar $m_posix /usr/share/paquito.phar $*
