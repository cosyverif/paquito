#!/bin/bash

php -d open_basedir= -d extension=phar.so -d extension=posix.so /usr/share/paquito.phar
