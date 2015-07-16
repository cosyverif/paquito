FROM base/archlinux

COPY helloworld-1.2-1-x86_64.pkg.tar.xz .
COPY helloworld-test-1.2-1-x86_64.pkg.tar.xz .
RUN pacman -Sy
RUN pacman-key --init && pacman-key --refresh-keys
RUN pacman -S --noconfirm git curl openssl sudo base-devel
RUN pacman-db-upgrade
RUN pacman -S --noconfirm tcc
RUN git clone https://github.com/sstephenson/bats.git
RUN ./bats/install.sh /usr/local
RUN pacman --noconfirm -U helloworld-1.2-1-x86_64.pkg.tar.xz
RUN pacman --noconfirm -U helloworld-test-1.2-1-x86_64.pkg.tar.xz


