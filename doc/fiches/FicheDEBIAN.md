Fiche technique pour la création d'un package debian :
======================================================

Introduction:
-------------

Un paquet Debian est constitué de deux parties notables :
 
1.Un dossier contenant le nom du paquet, ses dépendances, etc (répertoire DEBIAN). 
2.Les fichiers du programme de l'autre(répertoire /usr/bin).

L'arborescence d'un paquet Debian se présente sous cette forme :

Citation

- dossier_du_paquet/

--- DEBIAN/

----- control

----- preinst

----- postinst

----- prerm

----- postrm

--- usr/

----- bin/

------- votre_programme

Le fichier control:
-------------------

 Tout d’abord il faut créer un dossier du nom de votre programme (ou un autre ? ça sera le dossier qui contiendra le package). Créer ensuite dans ce dernier un dossier nommé "DEBIAN" (tout en majuscules).
 dans ce dossier, ajoutez un fichier nommé "control".

le contenu du fichier control sera:

**Package** : le nom de votre paquet. C'est par exemple celui utilisé pour apt-get, lorsque vous écrivez apt-get install firefox.
**Version** : la version de votre programme.
Section : .
**Priority** : l'importance de votre paquet pour le système. La plupart du temps, votre programme sera noté "optional".
Architecture : les architectures avec lesquelles votre programme est compatible. La plupart du temps, on choisira "all".
**Depends** : les dépendances de votre programme.
Maintainer : le nom et l'email de celui qui s'occupe de la création du .deb.  Le format est "Votre Nom <votre@email>".
Description : la description de votre paquet.
Homepage : l'adresse de votre site internet, si vous en avez un.

Les scripts d'installation/suppression:
---------------------------------------

Vous pouvez, si c'est nécessaire (c'est facultatif), ajouter des scripts qui seront exécutés avant/après l'installation/la suppression du paquet. Ils auront pour nom :

**preinst** : exécuté avant l'installation
**postinst** : exécuté après l'installation
**prerm** : exécuté avant la suppression
**postrm** : exécuté après la suppression
Vous pouvez vous en servir pour déplacer des dossiers, afficher des instructions à l'écran..
Vos scripts doivent avoir une permission de 755 :

**$ sudo chmod 755 postrm**

Le contenu du paquet:
---------------------

 Placez-vous à la racine de votre paquet, où se trouve le dossier DEBIAN, et créez un dossier **usr/**, puis un dossier **bin/** à l'intérieur du premier. Le principe est le même pour les autres: considérez la racine du paquet comme la racine du disque. Prenons un exemple : votre programme contient un bibliothèque dynamique libcarotte.so. Elle devra normalement être placée dans un dossier **usr/lib** dans le paquet, pour se retrouver dans **/usr/lib** une fois le .deb installé.


Compilation et installation:
----------------------------

Si tout est prêt, on va pouvoir transformer tout ça en un paquet .deb .
Mettez-vous dans le dossier qui contient votre paquet. Ensuite, tapez cette commande en tant que root :

**$ sudo dpkg-deb --build nom_du_paquet**

...où nom_du_paquet est le nom du dossier qui contient le dossier DEBIAN et tout le reste.
Un fichier .deb du nom du dossier devrait avoir été créé dans le répertoire courant. Pour l'installer, tapez :

**$ sudo dpkg -i nom_du_paquet.deb**

Pour désinstaller le paquet, vous utiliserez la commande :

**$ sudo apt-get remove nom_du_paquet**
