#! /bin/sh

set -e

# Increase system limits for opened files:
ulimit -Sn 2048

export PATH="${PATH}:${PWD}/bin"

# Download composer if it does not exist:
command -v composer.phar || {
  cd bin
  curl -S https://getcomposer.org/installer | php -d detect_unicode=0
  cd ..
}

# Install tools:
composer.phar global require fabpot/php-cs-fixer
composer.phar global require kherge/box

# Clean generated files:
rm -rf ~/paquito

# Install dependencies:
if [ -f composer.lock ]
then
  composer.phar update --no-dev
else
  composer.phar install --no-dev
fi

# Check and fix source:
~/.composer/vendor/bin/php-cs-fixer \
  fix --verbose --diff --level=symfony src/

# Build PHAR archive:
php -dphar.readonly=0 ~/.composer/vendor/bin/box build

mv paquito.phar paquito
echo "paquito has been created."
