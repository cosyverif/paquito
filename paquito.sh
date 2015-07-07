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

php -d open_basedir= $m_phar $m_posix /usr/share/paquito.phar $*
