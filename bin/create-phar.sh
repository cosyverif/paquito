#!/bin/sh

set -e

# Increase system limits for opened files:
ulimit -Sn 2048

export PATH="${PATH}:${PWD}/bin"
php='php -c php.ini'

# Download composer if it does not exist:
command -v "$php composer.phar" || {
  cd bin
  curl -S https://getcomposer.org/installer | php -d detect_unicode=0 -c ../php.ini
  cd ..
}
# Install tools:
$php bin/composer.phar global require fabpot/php-cs-fixer
$php bin/composer.phar global require kherge/box

# Clean generated files:
rm -rf ~/paquito
echo "Retour rm: $?"

# Install dependencies:
if [ -f composer.lock ]
then
  $php bin/composer.phar update --no-dev
echo "Retour update: $?"
else
  $php bin/composer.phar install --no-dev
echo "Retour install: $?"
fi

# Check and fix source:
$php ~/.composer/vendor/bin/php-cs-fixer \
  fix --verbose --diff --level=symfony src/
echo "Retour fix: $?"

# Build PHAR archive:
for i in 1 2 ; do
	$php -dphar.readonly=0 ~/.composer/vendor/bin/box build
	echo "Retour PHAR: $?"
done

mv paquito.phar paquito
echo "paquito has been created."
