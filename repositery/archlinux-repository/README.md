# Création d'un  dépôt archlinux :


##Dépendances pour la création du dépôt  archlinux:

installer : gnupg, rng-tools , lighttpd


##Manipuler les clés gpg:


Générer la clé:

```bash
gpg --full-gen-key

```

Exporter la clé:

```bash
gpg --armor --output public.key --export <user-id>

```

**public.key**: le nom de la clé public (donner un nom quelconque exemple : **ARCH-GPG-KEY**) .

**user-id** : can be specified with your key ID, fingerprint, a part of your name or email address.


Enregistrer la clé public sur le serveur de clé pour qu'elle puisse étre
retrouver plus facilement:

```bash
gpg  --keyserver pgp.mit.edu --send-keys <key-id>

```

**<key-id>** : on l'obtient avec la commande :

```bash
gpg --list-key

```

Si la clé est sur le serveur on l'importe en utilisant cette commande :

```bash
pacman-key -r keyid

```

Sinon:

```bash
pacman-key --add /path/to/key

```

Obtenir la signature de la clé (printfinger):

```bash
pacman-key -f keyid

```

Mettre à jour les clés:

```bash
pacman-key --refresh-keys

```

Lister les clés du trousseau:

```bash
pacman-key -l

```


##Signature des paquets archlinux:

On doit signer les paquets archlinux à leur construction (impossible de le
faire en dehors de la construction) :

On Ajoute le champ `validpgpkeys` dans **PKGBUILD** : qui contiendra le printfinger de la clé
Pour créer le paquet on lance la commande:

```bash
 makepkg --sign

```

qui va créer un paquet archlinux signé .


##Serveur (créer le dépôt ):

* Créer le répertoire du dépôt :

Executer la commande suivante :

```bash
mkdir /srv/http/archlinux

```

* Ajouter des paquets dans le dépôt :

Placer les paquets dans le dépôt , dans notre cas c'est `/srv/http/archlinux`

Executer la commande :

```bash
repo-add  /srv/http/archlinux/repo.db.tar.gz /srv/http/archlinux/*.pkg.tar.xz

```


####Supprimer des paquets du dépôt:**

* Executer la commande suivante : 

```bash
repo-remove /srv/http/archlinux/repo.db.tar.gz  nom-du-paquet

```

**exemple** : (pour le paquet paquito)

```bash
repo-remove /srv/http/archlinux/repo.db.tar.gz paquito

```

* Puis un rm sur le paquet .


##lighttpd:

Lancer lighttpd:

```bash
systemctl start lighttpd.service

```


##Coté client:


* Modifier le fichier `/etc/pacman.conf` pour ajouter le depot:


```bash
[nom du repo]
Level :TrustAll(verifie la signature: n'accepte que les paquets signés) ou Optional (ignore la signature:accepte les paquets non signer)
Server = file:///path/to/repository

```

**exemple:**


Accepter les paquets sans signature :

```bash
[repo]
SigLevel = Optional
Server = http://132.227.76.24:8082/archlinux

```

* Si les paquets sont signés (création d'un dépot signé :`Level :TrustAll`  , ajouter la clé :


```bash
pacman-key --add /path/to/downloaded/keyfile

```

**exemple :**

```bash
pacman-key --add http://132.227.75.24:8082/archlinux/ARCH-GPG-KEY

```

la clé est au niveau du répertoire archlinux.


* Signer localement la clé importée:

```bash
pacman-key --lsign-key keyid

```

**keyid**: identifiant de la clé importée on le récupére en tapant la commande :

```bash
gpg --list-key

```

* Mettre à jour :

```bash
pacman -Sy

```

* On pourra installer les paquets de ce dépot avec la commande:

```bash
pacman -S nom_du_paquet

```
