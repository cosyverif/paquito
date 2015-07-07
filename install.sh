#!/bin/bash

# Get Composer and install the modules
curl -sS https://getcomposer.org/installer | php -c php.ini
php -c php.ini composer.phar install

# Get Paquito source code
mkdir Paquito-src/ # /!\ Change the destination if this directory changes its name (in the configuration file of Paquito)
git clone -b docker https://github.com/CosyVerif/paquito
mv paquito/* Paquito-src
rm -Rf paquito/
