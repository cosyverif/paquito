FROM base/archlinux

COPY paquito-0.2-1-x86_64.pkg.tar.xz . 
RUN pacman -Sy
RUN pacman-key --init && pacman-key --refresh-keys
RUN pacman -S --noconfirm php git curl gcc openssl sudo base-devel 
RUN pacman-db-upgrade
RUN pacman -S --noconfirm tcc
RUN pacman -U --noconfirm paquito-0.2-1-x86_64.pkg.tar.xz
RUN git clone https://github.com/sarahhadbi/hello-world
RUN cp hello-world/pconf_HelloWorld.yml .
RUN cp -R hello-world/src /src
RUN ls -al
# ----------------------
ADD src-test-user/ src-test-user/
RUN ls -al 
RUN paquito generate-test pconf_HelloWorld.yml
RUN ls -l
