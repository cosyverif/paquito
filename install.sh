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

# If the module "openssl" is already loaded (because it is compiled in PHP)
if [[ ! $modules =~ "openssl" ]] ; then
		m_openssl='-d extension=openssl.so'	
fi

# If the module "zip" is already loaded (because it is compiled in PHP)
if [[ ! $modules =~ "zip" ]] ; then
		m_zip='-d extension=zip.so'	
fi

php="php -d open_basedir= $m_phar $m_posix $m_openssl $m_zip"

# Get Composer and install the modules
curl -sS https://getcomposer.org/installer | $php
$php composer.phar install

# Get Paquito source code
mkdir Paquito-src/ # /!\ Change the destination if this directory changes its name (in the configuration file of Paquito)
git clone -b docker https://github.com/CosyVerif/paquito
mv paquito/* Paquito-src
rm -Rf paquito/
