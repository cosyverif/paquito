Fiche technique pour la création d'un paquet RPM:
==================================================

Introduction:
-------------

Un paquet **RPM** est construit à partir d'un fichier **SPEC**. Ce fichier génère, en plus du **RPM**, un paquet **SRPM** (Source RPM). Ce paquet SRPM est très important car il permet en l'installant de récupérer le fichier SPEC et les sources du programme ayant servis pour la construction du RPM associé.

Le fichier **SPEC** décrit toutes les informations relatives au paquet RPM, comme son nom, sa version, sa description, sa licence, etc. Il indique aussi et surtout le déroulement des étapes pour arriver à constituer le paquet RPM. S'il s'agit par exemple de constituer un paquet fournissant un programme compilé à partir de sources, il faudra indiquer l'archive contenant les sources,les fameuses étapes liées à la compilation des sources: configuration, compilation, installation. Enfin le fichier SPEC se termine par la liste des fichiers qu'il doit empaqueter et par un journal contenant l'historique des évolutions apportées au paquet (le changelog).

Le fichier **SPEC** pour plus de clarté est divisé en sections:


* Une **entête** contenant les informations sur le paquet (nom, version, description, licence, groupe etc)
* Une section préparant les **sources**
* Une section concernant la **compilation**
* Une section pour **l'installation des fichiers**
* Une section de **nettoyage**
* Une ou plusieurs sections contenant des **script à exécuter**
* Une section **listant les fichiers** que le paquet doit contenir
* Une section contenant le **changelog**

Les outils nécessaires:
------------------------

Sur une distribution RedHat/Fedora, il existe deux paquets importants pour la construction de RPM:

    **rpmdevtools**
    **rpm-build**

Le paquet **rpmdevtools** contient des outils en rapport avec l'environnement de construction, alors que **rpm-build** contient la commande "rpmbuild" qui sert à construire les paquets RPM. De plus rpmdevtools contient des squelettes de fichiers SPEC dans le répertoire **/etc/rpmdevtools**.

A noter que si vous utilisez vim, celui-ci est capable de proposer directement un squelette minimal quand vous éditez pour la première fois un fichier .spec.

Pour installer ces paquets:

**# yum install rpmdevtools rpm-build**

Il faut maintenant constituer un environnement de construction, avec notre utilisateur il suffit pour cela de taper la commande:

**$ rpmdev-setuptree**

Ce qui crée une arborescence dans le répertoire ~/rpmbuild. Les répertoires ainsi créés sont les suivants:

    **BUILD** qui sert pour la construction
    **SOURCES** qui contient les archives des sources 
    **SPECS** qui contient les fichiers SPEC
    **RPMS** qui contient les paquets RPMs construits
    **SRPMS** qui contient les paquets SRPMS

L’entête du fichier SPEC:

**Name**:           nom du fichier .spec qui sera le nom du paquet
**Version**:        version du logiciel que vous empaquetez
**Release**:        version de votre paquetage la version par
                defaut est : :1%{?dist}
**Summary**:        brève description du logiciel empaqueté.
**Group** :         groupe d'applications auquel appartiendra
                 votre paquet.
**License**:        La licence du programme.
**URL:**            Site web du logiciel.
**Source0**:        URL complète de téléchargement des sources du logiciel.
**BuildRoot**:      La valeur par defaut est :%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n) et
ne devrait pas être modifiée.

**BuildRequires** :  liste de dépendances qui ne sont nécessaires qu'à la constitution du programme (compilateur, entêtes de bibliothèques, outils divers).

**Requires**:	liste de dépendances qui elles ne sont 
		uniquement nécessaires qu'au fonctionnement 
		du programme final (bibliothèques partagées, 
		programmes tiers, etc). 



**REMARQUE** : 

On peut accéder à la valeur de chacune des variables déclarées dans l’entête à travers %{nom de la variable }

**exemple:**
Nom:	hello-world
Pour accéder à la valeur de Nom donc pour avoir hello-world: **%%{nom}**.
il s'agit en fait de macro que nous pouvons aussi définir avec la commande **%define**.
 
**exemple** :
Si on reprend l'exemple précédant ça donnera:
%define name hello-world
Nom:	**%{name}**

**Section description**:

**%description**
description du logiciel empaqueter.

**Section préparant les sources**:

Cette étape consiste principalement à décompresser l'archive contenant les sources du programme.
l'archive du programme devra se trouver au préalable dans **~/rpmbuild/SOURCES** , elle sera décompresser dans le répertoire : **~/rpmbuild/BUILD/nom-du-paquet** (variable Name).

**%prep** 
liste des commandes qui permettent de réaliser cette étape.

**Section concernant la compilation**:

Cette section contient les étapes de configuration des sources et de compilation. Elle est donc vide ou inutilisée quand il s'agit de programmes non compilés comme des scripts par exemple.

Voici son contenu:

**%build**
Commandes de compilation.


**Section pour l'installation des fichiers**:

Il s'agit ici de déplacer les fichiers issues des étapes précédentes dans leurs répertoires de destination. Ce sont ces fichiers qui constituent notre programme à distribuer sous forme de paquet RPM. Pour plus de sécurité, il n'est pas envisageable d'installer ces fichiers réellement sur le système lors de la construction du RPM. D'une part parce que ceci pourrait rendre le système instable, et d'autre part parce que notre utilisateur n'en a pas forcement les droits. Pour palier à ce problème un répertoire temporaire est créé lors de la construction d'un paquet RPM. Ce répertoire sert de racine simulant le système de fichier futur dans lequel seront placés les fichiers du paquet. Ce répertoire est accessible via la variable $RPM_BUILD_ROOT.

**exemple**:

si on a générer un exécutable hello-world après la compilation le contenu de ce champs sera:

**%install**
**rm -rf $RPM_BUILD_ROOT**
**install -Dp -m 755 hello-world $RPM_BUILD_ROOT/usr/bin/hello-world**.


**Section de nettoyage**

Cette section permet de nettoyer le répertoire temporaire, et se résume souvent aux lignes suivantes:

**%clean**
**rm -rf $RPM_BUILD_ROOT**

Il est vivement conseillé de nettoyer le répertoire en cas de nouvel tentative de construction du paquet RPM.


**Section des scripts à exécuter**:(facultative)

Il est possible et des fois obligatoire, d’exécuter des scripts lors de la manipulation du RPM. Par exemple pour créer un utilisateur dédié au programme, etc. Ces petits bouts de scripts (ou scriptlets) peuvent être écrits en différents langage mais bien souvent il s'agit de shell script en Bash.

Les sections pouvant contenir ces scripts sont:

    **%pre**: avant l'installation, avant de décompresser les fichiers du RPM
    **%preun**: avant la désinstallation, avant de supprimer les fichiers du RPM
    **%post**: après l'installation, les fichiers du RPMs sont déjà déployés sur le systèmes
    **%postun**: après la désinstallation, les fichiers du RPMs ne sont plus sur le système

**Section listant les fichiers**:

Cette section a pour objectif de lister les fichiers qui sont présents dans le RPM. Chaque fichier est précisés avec son répertoire complet, indiquant ainsi l'emplacement final une fois le RPM installé. 

Si on reprend l'exemple précédent:

**%files**
**%defattr(-, root, root, -)**
**/usr/bin/hello-world**

La macro **%defattr** permet de préciser les attributs de nos fichiers, comme les permissions et propriétaires.

**Section contenant le changelog**:

Cette section contient le journal des modifications effectuées sur le fichier SPEC, aussi appelé changelog.

La construction du RPM:
-----------------------

Une fois le fichier SPEC est complet ,les sources du programme (archive) sont placées dans le répertoire ~/rpmbuild/SOURCES et notre fichier spec dans ~/rpmbuild/SPECS. La construction, à l'aide de rpmbuild, s'exécute avec la commande suivante:

**$ rpmbuild -ba ~/rpmbuild/SPECS/nom_du_fichier_SPEC.spec**
     
Logiquement, à la fin du processus de construction, si tout se passe bien, un paquet **RPM nomPaquet-version-architecture.x86_64.rpm** sera présent dans **~/rpmbuild/RPMS/x86_64**, ainsi que la version **Source RPM** dans **~/rpmbuild/SRPMS**
