Parseurs
========

Bash (jshon)
------------
<a href="http://kmkeen.com/jshon/" target="_blank">http://kmkeen.com/jshon/</a>

Jshon est un parseur du format JSON à destination du shell (Bash). C'est un exécutable écrit en C.

### Procédure d'installation
* Installer la librairie C *jansson* (<a href="http://www.digip.org/jansson/" target="_blank">http://www.digip.org/jansson/</a>)
* Télécharger sur le site officiel l'archive *tar.gz*.
* Exécuter le fichier **Makefile**, qui génère l'exécutable **jshon**

### Utilisation sur un exemple (paquito.json)

Afficher le contenu (de manière indentée) du fichier JSON donné en entrée :

> *$ ./jshon < paquito.json*

Afficher les clés contenues au niveau de la racine :

> *./jshon -k < paquito.json*

Afficher les clés contenues à partir du chemin PACKAGES -> 0 (position 0 du tableau -> 1er paquet) :

> *./jshon -e PACKAGES -e 0 -k < paquito.json*

Afficher la donnée pointée par la clé PACKAGENAME

> *./jshon -e PACKAGES -e 0 -e PACKAGENAME < paquito.json*

Afficher la donnée (décodée) pointée par la clé PACKAGENAME

> *./jshon -e PACKAGES -e 0 -e PACKAGENAME -u < paquito.json*

Afficher la 2nd dépendance (à la construction) de la variance Archlinux du 1er paquet

> *./jshon -e PACKAGES -e 0 -e ARCHLINUX -e BUILDDEPENDS -e 1 -u < paquito.json*