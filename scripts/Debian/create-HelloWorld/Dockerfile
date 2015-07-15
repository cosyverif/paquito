FROM debian

ADD paquito_0.2_amd64.deb .
RUN apt-get update
RUN apt-get -y install php5-cli git curl g++ build-essential dh-make
RUN dpkg -i paquito_0.2_amd64.deb
# --- Partie à virer (récupère le pconf_Helloworld.yml pour l'instant détenu dans Paquito) ---
RUN git clone https://github.com/sarahhadbi/hello-world
RUN cp hello-world/pconf_HelloWorld.yml .
RUN cp -R hello-world/src /src
RUN ls -al
# ----------------------
ADD src-test-user/ src-test-user/
RUN paquito generate-test pconf_HelloWorld.yml
