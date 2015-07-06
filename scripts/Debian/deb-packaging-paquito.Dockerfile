FROM debian

RUN apt-get update
RUN apt-get -y install php5 git curl g++
RUN git clone -b testing https://github.com/CosyVerif/paquito.git
WORKDIR paquito/
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev 
WORKDIR src/
RUN git clone -b testing https://github.com/CosyVerif/paquito.git
RUN mkdir Paquito-src/
RUN cp -R paquito/* Paquito-src/
RUN rm -Rf paquito/
RUN php Main.php generate pconf_paquito.yml
