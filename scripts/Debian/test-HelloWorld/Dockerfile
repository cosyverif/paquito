FROM debian

ADD composer.json .
ADD helloworld_1.2_amd64.deb .
ADD helloworld_1.2_x86_64-test.deb .
RUN apt-get update
RUN apt-get -y install php5 git curl g++ tcc
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install --no-dev 
ENV PATH $PATH:/vendor/bin/
RUN dpkg -i helloworld_1.2_amd64.deb
RUN dpkg -i helloworld_1.2_x86_64-test.deb
