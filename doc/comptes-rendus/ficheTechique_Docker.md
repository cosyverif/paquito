Fiche technique (Docker)
========================

Docker sur une Debian 7.8
-------------------------

### Installation

Docker requiert un noyau Linux plus évolué que celui que possède Debian 7.8. Il existe cependant une procédure sur le site officiel de Docker pour résoudre ce problème :

* Ajouter le dépôt des paquets rétroportés (Backports) parmi les adresses de dépôts :

    *$ echo "deb http://http.debian.net/debian wheezy-backports main" >> /etc/apt/sources.list*
    
* Mettre à jour la liste des paquets disponibles :

    *$ aptitude update*
    
* Installer le paquet linux-image-amd64 dans sa version backports :

    *$ aptitude install -t wheezy - backports linux - image - amd64*

* Installer Docker à l’aide d’un script disponible sur le site officiel :

    *$ curl - sSL https :// get . docker . com / | sh*

* Si on souhaite utiliser Docker avec un compte utilisateur (Docker est normalement utilisable qu'avec le compte **root**) :

    *$ usermod - aG docker your - user*
    
### Proxy

Modifier le fichier **/etc/default/docker** puis adapter la ligne suivante :

*\# If you need Docker to use an HTTP proxy , it can also be specified here*

*\# export http_proxy="http://127.0.0.1:3128/"*

Pour appliquer les modifications, Docker doit être redémarré :

*$ /etc/init.d/docker restart*

### Obtenir une image

Chercher un dépôt contenant le mot "debian" :

*$ docker search debian*

Télécharger la dernière version d’une image en renseignant seulement le nom du dépôt :

*$ docker pull debian* (Si on ne précise aucun tag, alors le tag **latest** est automatiquement choisi)

Télécharger une image précise à partir d’un couple dépôt/tag :

*$ docker pull debian:7.8*
