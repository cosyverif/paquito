#!/bin/bash

set -e

# Increase system limits for opened files:
ulimit -Sn 2048

export PATH="${PATH}:${PWD}/bin"

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

# Download composer if it does not exist:
command -v "$php composer.phar" || {
  cd bin
  curl -S https://getcomposer.org/installer | $php -d detect_unicode=0
  cd ..
}
# Install tools:
# $php bin/composer.phar global require fabpot/php-cs-fixer
$php bin/composer.phar global require kherge/box

# Clean generated files:
#rm -rf ~/paquito

# Install dependencies:
if [ -f composer.lock ]
then
  $php bin/composer.phar update --no-dev
else
  $php bin/composer.phar install --no-dev
fi

# Check and fix source:
#$php ~/.composer/vendor/bin/php-cs-fixer \ 
#  fix --verbose --diff --level=symfony src/

# Build PHAR archive:
$php -dphar.readonly=0 ~/.composer/vendor/bin/box build

mv paquito.phar paquito
echo "paquito (binary) has been created."
