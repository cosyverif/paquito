Fiche technique (projet de l'année dernière)
============================================

CosyVerif/deb-base/scripts
--------------------------

**buildeb** : 

* Le script actuel n'est capable de concevoir que des paquets sans architecture  (c'est-à-dire des paquets qui contiennent des fichiers de configuration, des fichiers textes, des scripts...)
* Pour concevoir le paquet Debian désiré, il faut remplir et compléter le fichier **build_config.yml**.
* L'exécution de **buildeb** est divisée en 9 étapes :
    1. Interprétation des options fournis en ligne de commande
    2. Lecture du fichier de configuration (c'est-à-dire **build_config.yml** grâce au script **config_parser.pl**)
    3. Recherche du Makefile
    4. Création de logs (2 journaux, le contenu de l'un est la sortie standard des différentes commandes, l'autre contient la sortie de **debuild ** [l'outil qui construit les paquets])
    5. Tâches avant les opérations de construction (*BEFOREBUILD*)
    6. Création des Tarballs (c'est-à-dire les archives qui contiennent le code source) (seulement pour les paquets contenant des librairies ou des paquets binaires)
    7. Gestion du fichier **copyright** (il doit être présent pour répondre à la politique de Debian)
    8. Construction des paquets
        1. Création des fichiers Debian (avec **dh_make**)
        2. Modification des fichiers Debian
        3. Création du paquet source
        4. Construction du paquet
    9. Lancement après les opérations de construction


**config_parser.pl** : Interprète le fichier **build_config.yml** pour y récupérer les informations.

* Doit se trouver dans le même répertoire que le script **buildeb**

**correct_lintian.pl** : Détecte les éventuelles erreurs et vérifie la conformité du paquet Debian par rapport à la politique de Debian.

* Ce script n'est pas nécessaire pour la procédure de création de paquets. Il est cependant hautement recommandé de l'utiliser afin d'obtenir des paquets correctement formés.
* Doit se trouver dans le même répertoire que le script **buildeb**


CosyVerif/images/
-----------------
 
**create_buildcosy** : Prépare 3 systèmes : un esclave dédié à Jenkins, un dépôt pour les paquets Debian et une image "minimale" pour créer et tester les paquets.

* L'esclave Jenkins et le dépôt sont des machines virtuelles (vm), tandis que le système de test de paquets est une image Docker.
* Quelques paramètres sont disponibles pour ajuster la configuration :
    * L'architecture est par défaut en 64 bits (*amd64*), mais peut être réglée à *i386* (**-a <archi\>**, **--arch=ARCH**, **--architecture=ARCH**)
    * Le format pour les deux machines virtuelles (**-f <format\>**, **--format=FORMAT**, **--vm-format=FORMAT**, **--format-vm=FORMAT**)
* S'aide du script **create_imagevm** pour créer les 3 systèmes

**create_imagevm** : Créé des images Docker ou des disques virtuels

* Un ensemble de paramètres (certains obligatoires) sont disponibles pour ajuster la configuration :
    * Le script peut créer des images Docker ou des disques virtuels (**-t <type\>**, **--type=TYPE**)
    * L'architecture est par défaut en 64 bits (*amd64*), mais peut être réglée à *i386* (**-a <archi\>**, **--arch=ARCH**, **--architecture=ARCH**)
    * L'image/disque peut être pré-configuré grâce aux branches, qui sont des assemblages de plusieurs fichiers Dockerfile pour former une configuration distinct (**-b <branch\>**, **--branch=BRANCH**)
    * Si c'est un disque virtuel qui doit être créé, il est possible de choisir son format (**-f <format\>**, **--format=FORMAT**, **--vm-format=FORMAT**, **--format-vm=FORMAT**)
    * Une image Docker peut être utilisée comme base, ce qui reviendra à l'agrémenter d'une configuration supplémentaire (**-F <image\>**, **--from=IMAGE**)
    * Il est possible de demander l'installation supplémentaire de paquets (**-p <packages\>**, **--install=PACKAGES**, **--extra-packages=PACKAGES**, **--packages=PACKAGES**)
        * Il est aussi possible d'en retirer (**--remove-packages=PACKAGES**)
        * Certains paquets peuvent se situer dans un dépôt non-renseigné dans le système. Il est toutefois possible d'en ajouter (**-R <repo\>**, **--repository=REPO**, **--repo=REPO**, **--cosy-repo=REPO**)
 * Ce script (ainsi que les fichiers Dockerfile) est calibré essentiellement pour Debian !

CosyVerif/images/dockerfiles
----------------------------
**{build,cosyverif}** : *Modifie le noyau, configure et créé un utilisateur pour une utilisation pour CosyVerif*

* Ajoute le support des architectures i386 et amd64. Les paquets des architectures ajoutées pourront désormais être disponibles (après un **apt-get update**)
* Installe un noyau personnalisé grâce à un paquet créé en fonction d'une configuration donnée (un fichier kernel.config est donné, qui est pris en compte par **make oldconfig**).
	* **make-kpkg** permet de créer un paquet d'un noyau personnalisé
	* La version du noyau téléchargé est celle du paquet **linux-source**, autrement dit la version téléchargée est la version courante (contrairement au noyau compilé par le fichier **CosyVerif/images/with-kernel**)
* EXPLIQUER FAKEROOT ???
* Ajoute l'utilisateur **cosyverif**

**clean** : *Nettoie l'image d'un certain nombre de données relative aux paquets et leur gestion (cache des listes de paquets, configuration des paquets, dépendances inutiles...)*

* Supprime les paquets installés comme dépendances et devenus inutiles avec **apt-get autoremove**
* Supprime tout le cache avec **apt-get clean**
* **debconf** est un outil permettant de pré-configurer les paquets avant même leur installation. Pour cela, il guide l'utilisateur à l'aide de questions (l'interface "non-interactive" permet d'automatiser la configuration puisqu'il les questions seront remplies avec le choix par défaut). Les réponses déjà données aux questions sont conservées dans une base de donnée (dans **/var/log/debconf**) qui elle-même supprimée ici.

**install-extrapackages** : *Installe un ou plusieurs paquets supplémentaires grâce à l'ajout d'un dépôt supplémentaire. Des mots-clés (à remplacer avec **sed**) sont prévus pour les paquets et le dépôt*

**remove-packages** : *Supprime complètement (logiciel + ses fichiers de configuration) un paquet ainsi que ses dépendances inutilisées. Les paquets sont spécifiés à l'aide d'un mot-clé (à remplacer avec **sed**).*

**with-buildeb** : *Installe tous les paquets utiles pour l'aide à la création de paquets DEB* (outils Debian)

* **devscripts** : Ensemble de scripts pour faciliter la vie des mainteneurs de paquets Debian
* **dh-make** : Permet de prendre un paquet source standard (ou une source originale) et de le convertir dans un format permettant de construire des paquets Debian.
Après une courte série de questions, dh_make fournira alors un ensemble de modèles qui, après quelques modifications mineures, permettront de créer un paquet Debian. 
* **build-essential** : Méta-paquet pour installer divers compilateurs et librairies
* **debhelper** : Ensemble de programmes que l'on peut utiliser dans un fichier **debian/rules** pour automatiser des tâches intervenant fréquemment dans la création de paquets Debian. Des programmes sont fournis pour installer divers fichiers dans le paquet, compresser des fichiers, corriger les droits d'accès, intégrer le paquet au système de menu Debian, debconf, doc-base, etc. La plupart des paquets Debian utilisent **debhelper** au cours de leur création.
* **perl** : Le langage (interprété) de script Perl
* **sed** : La commande Unix **sed**
* **mawk** : La commande Unix **awk**
* **fakeroot** : Permet de lancer une commande dans un environnement simulant les privilèges du super-utilisateur pour la manipulation des fichiers.  
* **lintian** : Lintian dissecte les paquets Debian et essaye de trouver les bugs et les violations de politique.
* **coreutils** : Coreutils (ou GNU Core Utilities) est un paquetage du projet GNU contenant de nombreux outils basiques tels que **cat**, **ls**, et **rm** nécessaires pour les systèmes d'exploitation de type Unix
* **libyaml-tiny-perl** : Module PERL pour la lecture et l'écriture de fichiers YAML

**with-cosy** : *Créé l'utilisateur **cosy** (avec répertoire personnel mais sans mot de passe) et inclut le paquet **passwd** (pour configurer ultérieusement le mot de passe)*

**with-jenkins-slave** : *Prépare le container à être esclave d'un serveur Jenkins et à attendre ses ordres pour lancer d'autres containers*

* Installe le paquet **slave-cosy** (depuis un dépôt privé) ainsi que Docker (depuis le dépôt *testing*)
* Créé l'utilisateur **jenkins** et l'ajoute dans le groupe de Docker (afin qu'il puisse l'utiliser même sans être administrateur)

**with-kernel** : *Installe une version personnalisée du noyau (à sa version 3.15.5, 32 ou 64 bits) ainsi qu'un bootloader*

* **udhcpc** : Client DHCP léger, adapté pour l'embarqué
* **iproute2** : Ensemble d'outils de contrôle du réseau et du trafic
* **xz-utils** : Utilitaires de manipulation du format compressé XZ
* **extlinux** : Fournit les chargeurs d'amorçage (bootloaders) pour ext2/3/4 et btrfs. SYSLINUX est un ensemble de chargeurs d'amorçage qui fonctionnent avec des systèmes de fichiers ext2/3/4, btrfs, FAT, NTFS, avec des serveurs réseau utilisant un micrologiciel PXE ou des CD-ROM.

**with-repo** : *Configure le système pour mettre en place un dépôt de paquets Debian*

* L'obtention d'un paquet (avec **apt**, **yum**...) se fait à l'aide du protocole HTTP. Un dépôt de paquets n'est donc rien d'autre qu'un serveur Web (avec une gestion des clés pour l'intégrité).
* Pour mettre en place le dépôt, quelques paquets sont installés :
    * **gnupg** : Outil pour sécuriser les communications et le stockage de données. Il peut être utilisé pour chiffrer des données et créer des signatures numériques.
    * **devscripts** : Ensemble de scripts pour faciliter la vie des mainteneurs de paquets Debian
    * **dput** : Outil d'uploading sur le dépôt d'un ou plusieurs paquets Debian
    * **coreutils** : Coreutils (ou GNU Core Utilities) est un paquetage du projet GNU contenant de nombreux outils basiques tels que **cat**, **ls**, et **rm** nécessaires pour les systèmes d'exploitation de type Unix
    * **mini-dinstall** : Démon d'ajout et de mises-à-jour de paquets Debian dans un dépôt
    * **apache2** : Le serveur Web Apache
    * **rng-tools** : Outil d'aide à la génération de nombres pseudo-aléatoire
* Tous les outils installés par les paquets sus-cités sont configurés par le Dockerfile après leur installation.


**without-recommends** : *Configure APT pour qu'il évite autant que possible d'installer des paquets recommandés ou suggérés, ceci pour économiser de l'espace disque*

* La variable d'environnement **DEBIAN_FRONTEND** est utilisée avec **debconf** : réglée à **noninteractive**, à chaque installation de paquets, **debconf** choisira les réponses par défaut aux questions posés par l'installateur, sans que l'utilisateur n'intervienne.
* Notes sur la distinction entre les paquets recommandés et les paquets suggérés :
    * *Recommandé* : Installe le paquet B car le mainteneur du paquet A juge que les utilisateurs n'utiliseront pas A si B n'est pas installé.
    * *Suggéré* : Installe le paquet B car celui-ci améliore le contenu du paquet A
