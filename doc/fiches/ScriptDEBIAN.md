Script création du paquet DEBIAN pour le programme hello world:
===============================================================


voici le script de creation de ce paquet :

il contient toutes les commandes nécessaire à la creation du paquet (arboresence , compilation ,construction paquet).



**#!/bin/bash**

# Si le nombre d'arguments est inférieur à 1

if [[ $# -lt 1 ]] ; then
echo "Usage: $0 <directory>" && exit 1
fi

mkdir deb-$1

cd deb-$1

mkdir -p DEBIAN/ usr/bin usr/share/hello-world

mkdir -p

touch DEBIAN/{control,preinst,postinst,prerm,postrm}

chmod 755 DEBIAN/{preinst,postinst,prerm,postrm}

echo "Package: deb-$1

Version: 0.1

Section: base

Priority: optional

Architecture: all

Depends: tcc

Maintainer: Toto

Description: Our best software ;)

Homepage:

" > DEBIAN/control

cd ../$1/src/

g++ main.cc -o hello-world

mv hello-world ../../deb-$1/usr/bin/

cp program.c ../../deb-$1/usr/share/hello-world

cd ../../

**Creation paquet**

**#Mettez-vous dans le dossier qui contient votre paquet. Ensuite, tapez cette commande en tant que root** :

**$ dpkg-deb --build deb-$1**



comme ce paquet contient une dependance à l'exécution (variable Depends du fichier controle) qui est le compilateur Tiny c , il faudra préalablement l'installer sur sa machine pour pouvoir l'executer.


Pour l'installer, tapez :

**$ sudo dpkg -i nom_du_paquet.deb**

commande d'execution: **$ hello-world** (qui est le nom de l'executable).

