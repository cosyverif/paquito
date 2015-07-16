FROM debian

ADD helloworld_1.2_amd64.deb .
ADD helloworld-test_1.2_amd64.deb .
RUN apt-get update
RUN apt-get -y install git curl tcc
RUN dpkg -i helloworld_1.2_amd64.deb
RUN dpkg -i helloworld-test_1.2_amd64.deb 
