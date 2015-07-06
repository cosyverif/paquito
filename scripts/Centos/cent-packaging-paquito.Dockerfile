FROM centos:6

RUN yum -y update
RUN yum -y install openssl
RUN yum -y install php git curl rpmdevtools rmp-build php-process
RUN ls 
RUN git clone -b docker https://github.com/CosyVerif/paquito.git
WORKDIR paquito/
RUN ls
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev 
WORKDIR src/
RUN ls  -all
RUN git clone -b docker https://github.com/CosyVerif/paquito.git
RUN mkdir Paquito-src/
RUN cp -R paquito/* Paquito-src/
RUN rm -Rf paquito/
RUN ls -all
RUN php -c ../php.ini Main.php generate pconf_paquito.yml
RUN ls -al
