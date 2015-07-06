FROM debian

ADD paquito_0.2_amd64.deb .
RUN apt-get update
RUN apt-get -y install php5 git curl g++
RUN dpkg -i paquito_0.2_amd64.deb
RUN git clone -b testing https://github.com/CosyVerif/paquito.git
RUN git clone https://github.com/saucisson/hello-world 
RUN cp paquito/src/pconf_HelloWorld.yml hello-world/
WORKDIR hello-world/
ADD src-test/ src-test/
ADD src-test-user/ src-test-user/
RUN paquito generate-test pconf_HelloWorld.yml
RUN ls -l
