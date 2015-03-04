Fiche technique expliquant les différences et les points
=========================================================
commun entre les distributions (DEBIAN ,ARCH ,RPM):
===================================================


Création , arborescence et nommage des paquets :
==================================================

Ce qui est en commun:
---------------------


* chacune des distribution à un fichier spécial : 

**Debian**: fichier **control** qui contient les informations sur le paquet.

**Archlinux**: fichier **PKGBUILD** contient les informations sur le paquet et la fonction build() pour la construction du paquet.

**RPM**: fichier SPEC contient les informations sur le paquet ainsi que  plusieurs sections pour la construction du paquet.

* Les informations au niveau des fichiers sont globalement les mêmes:
nom_paquet , version , dépendances , licence ect ...


* Chacun des paquets contient la partie data (ex:**/usr/bin**) qui contient les fichiers du programme (ex: l’exécutable). 

* Chacun des paquet est créé à partir d'une commande taper sur le terminal .

* Au niveau du nommage les paquets contiennent tous au minimum 
 **"nom_du_paquet"** ( définie dans le fichier spécial).

Ce qui diffère:
----------------

* Paquet Debian:

Le paquet sera constitué de deux partie:
une partie **control** (fichier 'control' qui est dans le répertoire **DEBIAN**) qui contient les informations sur le paquets (version ,nom, dépendances ...) , et une partie **data** (les fichiers du programme :**/usr/bin** ...) .	

création du paquet se fait de la manière suivante:
à partir du répertoire qui contient le répertoire du paquet( celui qui contient l'arborescence du paquet) on tape la commande suivante :

**$ sudo dpkg-deb --build nom_du_paquet**  (ou nom du paquet est le répertoire qui contient le contenu du paquet).

si on fait un ls sur le répertoire on aura :

nom_du_paquet(c'est le répertoire contenant l'arborescence du paquet) , nom_du_paquet.deb (le paquet).

**Remarque:**

le fichier **control** est propre à **DEBIAN** il contient les informations sur le paquet il ne se charge pas de la création du paquet(arborescence ,compilation .. on le fait avec un script simple ou manuellement) .

* Paquet ARCHLinux:

Le paquet sera constitué que de la partie **data** (les fichiers du programme) exp: **/usr/bin** (contient l'exécutable) , **/usr/share**.

création du paquet se fait grâce au fichier **PKGBUILD** , grâce à la fonction **buid()** contenu dans ce fichier .

Le fichier **PKGBUILD** contient les information sur le paquet( comme le fichier control) mais aussi la fonction build() qui gère la création du paquet .

Une fois le fichier **PKGBUILD** rempli pour créer le paquet on tape la commande suivante (au niveau du répertoire qui contient ce fichier):

**$ makepkg** 

le paquet ARCH sera créer.
Si on fait un **ls** sur ce répertoire:

**$ ls**

**PKGBUILD** , **nom_du_paquet**( repertoire qui contient l'arborescence du paquet) , **nom_du_paquet.version.tar.gz** (paquet ARCHLinux)

* Paquet Redhat:

On aura pas de répértoire pour l'arborescence du paquet mais un répertoire rpmbuild qui contient les répertoires suivants : 

**SOURCES**: contient l'archive contenant les sources du programme. 
**BUILD** : contient l'archive décompressée (celle dans SOURCES.
**RPMS**:   contient le paquet RPM binaire.
**SRPMS**:  contient le paquet RPM source.
**SPECS**:  contient le fichier SPEC .

pour créer cette arborescence on tape la commande suivante:

**$ rpmdev-setuptree**

La construction du paquet RPM est géré par le fichier **SPEC**.
Ce ficher contient les informations sur le paquet( comme le fichier control) mais surtout des sections permettant d'arriver à la création du paquet (exemple **%build** est une section du fichier SPEC qui permettra la compilation des sources).

Une fois le fichier SPEC rempli ,on se place au niveau du répertoire qui contiendra le répertoire rpmbuild et on tape la commande suivante:

**$ rpmbuild -ba rpmbuild/SPECS/nom_du_paquet.spec**

On aura deux paquets qui seront créés : **paquet binaire** (programme: **nom_paquet.version.architecture.rpm** ) qui sera contenu dans le répertoire **RPMS** et le **paquet source** qui sera contenu dans le répertoire **SRPMS**.


* le nom des variables (information sur le paquet)au niveau de chacun des fichiers (control ,PKGBUILD,SPEC) n'est pas toujours le même exemple:

variable pour le nom du paquet:
Debian: *Package*
ARCH:   *pkgname*
RPM:	*Name*

Dépendances :
==============

Ce qui est en commun:
-----------------------
Les trois distributions contiennent une variable qui contient les dépendances de notre programme(au niveau des informations sur le paquet).

Debian: fichier control

**Depends:tcc>=1.4**

ARCH: fichier PKGBUILD

**Depends=('tcc>=1.4')**

RPM: fichier SPEC

**Require:   tcc**

Ce qui diffère:
----------------
ARCH : contient deux autres dépendances

**optdepends**: Paquet optionnel qui rajoute en général des fonctionnalités au programme .

**makedepends** :Ce sont aussi des dépendances, mais celles que la compilation nécessite.


RPM: contient une autre dépendance 

**BuildRequires**: dépendances pour la compilation .
