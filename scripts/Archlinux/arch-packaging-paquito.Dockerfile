FROM base/archlinux

RUN pacman -Sy
RUN pacman-key --init && pacman-key --refresh-keys
# Il peut y avoir sous Archlinux une erreur de signature, la commande précédente résout le problème
RUN pacman -S --noconfirm php git curl gcc openssl sudo 
RUN pacman -S --noconfirm base-devel
# Il faut installer sous Archlinux le paquet "openssl" lorsqu'on récupère un contenu avec GitHub ou avec CURL, sinon
# il y aura l'erreur : "git-remote-https: symbol lookup error: /usr/lib/libcurl.so.4: undefined symbol: SSL_CTX_set_alpn_protos"
# Sous Archlinux, pour que makepkg fonctionne pleinement il faut installer le paquet base-devel (qui installera notamment l'environnement fakeroot)
RUN pacman-db-upgrade
# Pour corriger l'erreur : failed to initialize alpm library (database is incorrect version: /var/lib/pacman/
RUN ls 
RUN git clone https://github.com/CosyVerif/paquito
WORKDIR paquito/
RUN curl -sS https://getcomposer.org/installer | php -d extension=phar.so -c php.ini
RUN php -c php.ini composer.phar install --no-dev 
RUN mkdir Paquito-src/

RUN git clone  https://github.com/CosyVerif/paquito
RUN cp -R paquito/* Paquito-src/
RUN rm -Rf paquito/
RUN php -c php.ini src/Main.php generate pconf_paquito.yml
RUN ls -l
