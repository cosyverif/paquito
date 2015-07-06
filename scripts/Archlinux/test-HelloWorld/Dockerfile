FROM base/archlinux

ADD composer.json .
COPY helloworld-1.2-1-x86_64.pkg.tar.xz .
COPY helloworld-test-1.2-1-x86_64.pkg.tar.xz .
RUN pacman -Sy
RUN pacman-key --init && pacman-key --refresh-keys
RUN pacman -S --noconfirm php git curl gcc openssl sudo base-devel tcc
RUN pacman-db-upgrade
RUN curl -sS https://getcomposer.org/installer | php -d open_basedir= -d extension=phar.so -d extension=openssl.so
RUN php -d open_basedir= -d extension=phar.so -d extension=openssl.so -d extension=zip.so composer.phar install --no-dev 
ENV PATH $PATH:/vendor/bin/
RUN pacman --noconfirm -U helloworld-1.2-1-x86_64.pkg.tar.xz
RUN pacman --noconfirm -U helloworld-test-1.2-1-x86_64.pkg.tar.xz
