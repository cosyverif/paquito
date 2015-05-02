Fiche technique pour la création d'un paquet ARCHLINUX:
========================================================


Le principal fichier est **PKGBUILD**:base du paquet.

Fichier PKGBUILD:
-----------------

c'est un simple fichier texte  qui explique comment se déroule la création du paquet il contient deux parties:

a. une partie concernant les informations sur le paquet : nom, version ,dépendances ...ect.

b. 2ème partie : fonction **build()** qui se charge de la création de l'arborescence du paquet et de la compilation.


Contenu du fichier:
-------------------


**# Maintainer**
	
La personne qui se charge officiellement de la maintenance du paquet (format Nom <mail@domain.tld>;) .

**# Contributor**
	
La personne qui est à l'origine du paquet (même format) .

**pkgname**
	
Nom du paquet

**pkgver**
	
Version du programme ( chiffres et lettres uniquement, pour séparer on utilise des underscores (_) ).

**pkgrel**
	
Valeur spécifique à ArchLinux, elle correspond à la version du paquet (pour chaque version d'un PKGBUILD d'un même programme de la même version, on met une valeur différente, en commençant par 1). Elle gère entre autres les paquets périmés.

**pkgdesc**
	
Courte description du contenu du paquet qui sera affichée dans les détails.

**arch**
	
L'architecture où la compilation du programme est compatible (i686 ou x86_64 ou any pour les deux)

**url**
	
Site du programme

**license**
	
Licence du programme
 
**groups**
	
Si le paquet appartient à un groupe de paquets, on le précise (exemple kdebase appartient au groupe kde)

**provides**
	
Si notre paquet fournit un autre logiciel, on le précise

**depends**
	
Les dépendances du programme 

**optdepends**
	
Paquet optionnel qui rajoute en général des fonctionnalités au programme (exemple, pacman-color rajoute de la couleur dans pacman).

**makedepends**
	
Ce sont aussi des dépendances, mais celles que la compilation nécessite 

**conflicts**
	
Les paquets qui empêchent le fonctionnement de notre programme

**replaces**
	
Précise les paquets que notre programme remplace (exemple, ancienne version du programme qui porte un autre nom)

**backup**
	
Les fichiers qui seront sauvegardés à la désinstallation

**install**
	
Précise un fichier d'installation (voir la partie « Les fichiers d'installation »)

**source**
	
Le lien de l'archive contenant les sources

**md5sums**
	
Permet de vérifier l'intégralité de l'archive en fournissant son empreinte MD5

**build()**
	
Fonction qui nous permettra de préparer le paquet (compilation ainsi que placement dans le paquet final)


Les dépendances:
----------------

Pour la variable **depends**, on prend un tableau avec la liste des dépendances sous la forme ('nom du paquet archlinux'). On peut aussi préciser une version avec un comparatif (qui je rappelle sont =, >, <,<= ou encore >=) suivi d'un numéro de version (par exemple ('python>=2.6') .


La variable md5sums:
---------------------

Pour la calculer, il suffit d'utiliser la commande md5sum ainsi :

**md5sum monprogramme-1.1_1.tar.gz | cut -d ' ' -f 1**

C'est le résultat de cette commande que vous allez mettre dans la variable md5sums.

La fonction build():
---------------------

C'est la fonction **build()** qui va se charger de la création du paquet.

En général, l'ordre des commandes dans cette fonction est celui-ci :

**Fonction build**:

a.On se place dans le répertoire contenant les sources  téléchargées
b.On compile les sources 
c.On place dans le répertoire qui va être le paquet tout ce qu'il nous faudra
Fin de la fonction

Comme exemple, nous allons maintenant imaginer que notre archive tar.gz téléchargée contient ceci :

Archive tar.gz

---- bin/

---- doc/

-------- palne.6.gz

-------- index.html

---- src/

-------- main.c

-------- fonctions.c

-------- fonctions.h

---- data/

-------- COPYING

-------- images/

------------ icone.png

------------ splash.png

------------ gagne.png

------------ perdu.png

-------- conf/

------------ palne.cfg

---- Makefile


**build()**
{

    cd $srcdir/$pkgname  # on se place dans le répértoire     contenant les sources.
    
    **#srcdir:le répertoire ou l'archive sera décompresse**r
    .
    **#pkgname: répertoire de l'archive décompresser**.

    make || return 1    # compiler le makefile 
    # || return 1:cela permet de dire que si la commande a échoué, on arrête la création du paquet.


    cd ..
    
    **#pkgdir : répertoire du paquet**
    **#construction de l'arborescence du paquet**

    mkdir -p $pkgdir/usr/bin/ 
    mkdir -p $pkgdir/usr/share/palne/
    mkdir -p $pkgdir/usr/share/doc/palne/
    mkdir -p $pkgdir/usr/man/man6/
    mkdir -p $pkgdir/etc/palne/
    mkdir -p $pkgdir/usr/share/licenses/palne/
    
    **#déplacer les fichiers(ceux qui vont constituer le paquet exp: exécutable ici c'est palne) vers le répertoire du paquet**

    cp bin/palne $pkgdir/usr/bin/
    #donner les droits d’exécution pour l’exécutable
    chmod +x $pkgdir/usr/bin     
    cp doc/palne.6.gz $pkgdir/usr/man/man6/
    cp doc/index.html $pkgdir/usr/share/doc/palne/
    cp -r data/images/ $pkgdir/usr/share/palne/images/
    cp data/conf/palne.cfg $pkgdir/etc/palne/
    cp data/COPYING $pkgdir/usr/share/licenses/palne/
}

Création du paquet:
-------------------

lancez cette commande :

**makepkg**

Si tout se passe bien, il vous posera quelques questions, et vous retournerez dans votre prompt.
Si on fait un ls on aura le résultat suivant:
**$ ls**
 pkg/ PKGBUILD nom-version.tar.gz

L'archive **tar.gz** résultante est notre paquet. Pour l'installer :

**$ pacman -U nom-version.tar.gz**

Pour désinstaller le paquet :

**$ pacman -R nom_du_paquet**

### Commandes suplémentaires

Obtenir les méta-données du paquet :

**$ pacman -Qip nom_du_paquet.tar.gz**

Obtenir la liste des fichiers du paquet :

**$ pacman -Qlp nom_du_paquet.tar.gz**
