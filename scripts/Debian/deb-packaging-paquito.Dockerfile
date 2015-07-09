FROM debian

RUN apt-get update
RUN apt-get -y install php5-cli git curl g++
RUN ls -al
RUN git clone -b debian-proper https://github.com/CosyVerif/paquito.git
WORKDIR paquito/
RUN bash install.sh
RUN php src/Main.php generate pconf_paquito.yml
