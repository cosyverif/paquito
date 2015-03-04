Script création du paquet ARCHLINUX pour le programme hello world:
==================================================================


#!/bin/bash

echo "pkgname=arch-$1

pkgver=0.9

pkgrel=1

pkgdesc=Our tiny sofware

arch=(any)

depends=('tcc').


build() {

	cd $1/src/
	
	g++ main.cc -o hello-world
	
	chmod +x hello-world
	
        cd ../..
        
	mkdir -p arch-$1/{usr/share/$1,usr/bin}
	
	mv $1/src/hello-world arch-$1/usr/bin/
	
	cp $1/src/program.c arch-$1/usr/share/$1/
	
}

" > PKGBUILD

makepkg
