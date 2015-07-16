FROM centos:6

RUN yum -y update
RUN yum -y install openssl
RUN yum -y install php-cli git curl rpmdevtools rpm-build php-process
RUN ls 
RUN git clone https://github.com/CosyVerif/paquito
WORKDIR paquito/
RUN ls
RUN curl -sS https://getcomposer.org/installer | php -c php.ini
RUN php -c php.ini composer.phar install --no-dev 
RUN git clone  https://github.com/CosyVerif/paquito
RUN mkdir Paquito-src/
RUN cp -R paquito/* Paquito-src/
RUN rm -Rf paquito/
RUN ls -all
RUN php -c php.ini src/Main.php generate pconf_paquito.yml
RUN ls -al
