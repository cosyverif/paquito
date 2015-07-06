FROM base/archlinux

COPY paquito-0.2-1-x86_64.pkg.tar.xz . 
RUN ls -l
RUN pacman -Sy
RUN pacman-key --init && pacman-key --refresh-keys
RUN pacman -S --noconfirm php git curl gcc openssl sudo base-devel tcc
RUN pacman-db-upgrade
RUN pacman -U --noconfirm paquito-0.2-1-x86_64.pkg.tar.xz
RUN git clone -b testing https://github.com/CosyVerif/paquito.git
RUN git clone https://github.com/saucisson/hello-world 
RUN cp paquito/src/pconf_HelloWorld.yml hello-world/
WORKDIR hello-world/
ADD src-test/ src-test/
ADD src-test-user/ src-test-user/
RUN paquito generate-test pconf_HelloWorld.yml
RUN ls -l
