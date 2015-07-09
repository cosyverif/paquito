FROM debian

ADD paquito_0.2_amd64.deb .
RUN apt-get update
RUN apt-get -y install php5-cli git curl g++ build-essential dh-make
RUN dpkg -i paquito_0.2_amd64.deb
# --- Partie à virer (récupère le pconf_Helloworld.yml pour l'instant détenu dans Paquito) ---
RUN git clone -b debian-proper https://github.com/CosyVerif/paquito.git
RUN git clone https://github.com/saucisson/hello-world 
RUN cp paquito/src/pconf_HelloWorld.yml hello-world/
# ----------------------
WORKDIR hello-world/
ADD src-test/ src-test/
ADD src-test-user/ src-test-user/
RUN paquito generate pconf_HelloWorld.yml
