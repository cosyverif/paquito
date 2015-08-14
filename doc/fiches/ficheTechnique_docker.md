Fiche technique (Docker)
========================

Docker sur une Debian 7.8
-------------------------

### Installation

Docker requiert un noyau Linux plus évolué que celui que possède Debian 7.8. Il existe cependant une procédure sur le site officiel de Docker pour résoudre ce problème :

* Ajouter le dépôt des paquets rétroportés (Backports) parmi les adresses de dépôts :

    ```$ echo "deb http://http.debian.net/debian wheezy-backports main" >> /etc/apt/sources.list```
    
* Mettre à jour la liste des paquets disponibles :

    ```$ aptitude update```
    
* Installer le paquet linux-image-amd64 dans sa version backports :

    ```$ aptitude install -t wheezy - backports linux - image - amd64```

* Installer Docker à l’aide d’un script disponible sur le site officiel :

    ```$ curl - sSL https :// get . docker . com / | sh```

* Si on souhaite utiliser Docker avec un compte utilisateur (Docker est normalement utilisable qu'avec le compte **root**) :

    ```$ usermod - aG docker your - user```
    
### Proxy

Modifier le fichier **/etc/default/docker** puis adapter la ligne suivante :

```# If you need Docker to use an HTTP proxy , it can also be specified here```

```# export http_proxy="http://127.0.0.1:3128/"```

Pour appliquer les modifications, Docker doit être redémarré :

```$ /etc/init.d/docker restart```

### Obtenir une image

Chercher un dépôt contenant le mot "debian" :

```$ docker search debian```

Télécharger la dernière version d’une image en renseignant seulement le nom du dépôt :

```$ docker pull debian``` (Si on ne précise aucun tag, alors le tag **latest** est automatiquement choisi)

Télécharger une image précise à partir d’un couple dépôt/tag :

```$ docker pull debian:7.8```

Problèmes connus
----------------

### [Centos 7] Module br_netfilter not found

```Running modprobe bridge nf_nat br_netfilter failed with message: modprobe: WARNING: Module br_netfilter not found.\n, error: exit status 1```

Ce problème survient lors du démarrage de Docker sous Centos, l'empêchant du coup de fonctionner. Il est provoqué par l'exécution de 'firewalld', un service propre à Redhat/Centos s'appuyant sur netfilter (iptables).

Pour que Docker puisse fonctionner, il est nécessaire que firewalld soit arrêté et désactivé :

```
 $ systemctl stop firewalld
   Redirecting to /bin/systemctl stop  firewalld.service
 $ systemctl disable firewalld
```
Docker pourra désormais démarrer sans encombres.

### [Archlinux] /var/run/docker.sock: no such file or directory

SOus Archlinux, le message suivant apparait lorsque l'on tente une quelconque commande de manipulation d'image/de container :

```
Post http:///var/run/docker.sock/v1.19/containers/create?name=paquito: dial unix /var/run/docker.sock: no such file or directory. Are you trying to connect to a TLS-enabled daemon without TLS?
Post http:///var/run/docker.sock/v1.19/containers/paquito/stop?t=10: dial unix /var/run/docker.sock: no such file or directory. Are you trying to connect to a TLS-enabled daemon without TLS?
Error: failed to stop containers: [paquito]
```

Ceci est dû au fait qu'Archlinux ne lance pas automatiquement à l'installation et au démarrage du système ses services. Ce message d'erreur signifie donc que Docker n'est pas démarré.

Pour démarrer Docker :

```$ systemctl start docker```

Pour lancer Docker au démarrage du système :

```
$ systemctl enable docker
  Created symlink from /etc/systemd/system/multi-user.target.wants/docker.service to /usr/lib/systemd/system/docker.service.
```
