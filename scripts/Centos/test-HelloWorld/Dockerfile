FROM centos:6

# composor : to install phpunit
COPY helloworld-1.2-1.el6.x86_64.rpm .
COPY helloworld-test-1.2-1.el6.x86_64.rpm .
COPY tcc-9999-115.1.x86_64.rpm .
RUN yum -y update
RUN ls -auil
# php-xml : phpunit/phpunit requires ext-dom * 
RUN yum -y install openssl php git php-process php-xml 
RUN rpm -i tcc-9999-115.1.x86_64.rpm
# install phpunit via composer
RUN chmod -R 777 /var/www
WORKDIR /var/www/
ADD composer.json /var/www/
RUN curl -sS https://getcomposer.org/installer | php -d open_basedir= -d extension=phar.so -d extension=openssl.so
RUN ls -al
RUN php -d open_basedir= -d extension=phar.so -d extension=openssl.so -d extension=zip.so composer.phar install --no-dev
ENV PATH $PATH:/var/www/vendor/bin/
WORKDIR /
RUN rpm -i  helloworld-1.2-1.el6.x86_64.rpm
RUN rpm -i  helloworld-test-1.2-1.el6.x86_64.rpm
RUN ls -l
