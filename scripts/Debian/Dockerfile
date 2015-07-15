FROM debian

RUN apt-get update
RUN apt-get -y install php5-cli git curl g++ build-essential dh-make
#ADD paquito.tar.gz paquito/
RUN git clone https://github.com/CosyVerif/paquito
WORKDIR paquito/
#RUN bash install.sh
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install
RUN mkdir Paquito-src/
RUN git clone https://github.com/CosyVerif/paquito
RUN cp -R paquito/* Paquito-src
RUN rm -Rf paquito/
RUN ls -l Paquito-src
RUN php src/Main.php generate pconf_paquito.yml
