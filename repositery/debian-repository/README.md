# Création d'un dépôt Debian :

##SERVEUR (machine sur laquelle le dépôt est créé ):

#### Pour ajouter un paquet au dépôt (depuis le répertoire du dépôt) :

Depuis le répertoire du dépôt qui est : `/var/www/debian` , on tape la commande
suivante :

```bash
reprepro -vb . includedeb jessie /chemin_vers/un/paquet/deb

```

reprepro se charge de faire signer les paquets (demande la phrase de passe).

#### Pour supprimer un paquet du dépôt (depuis le répertoire du dépôt) :

```bash
reprepro -vb /var/www/debian/ remove jessie nomDuPaquetSansDeb

```

#### Signature des paquets:

Sachant que reprepro s'occupe de faire signer les paquets ,cette étape est nécessaire que si on veut signer les paquets,
sans les ajouter dans un dépôt pour cela :

* On installe d'abord le paquet **dpkg-sig**:

```bash
apt-get install dpkg-sig

```
* Puis on signe les paquets avec la commande suivante :

```bash
dpkg-sig --sign builder nomdupaquet.deb

```   
Evidemment faudra taper la passpharse pour pouvoir le signer 


## CLIENT :

* Ajouter le nouveau dépôt :

```bash
echo "deb  http://132.227.76.24:8080/ jessie main" >> /etc/apt/sources.list 

```

* Pour télécharger la clé du dépôt (stockée sur la machine où le dépôt se trouve) :

```bash
wget -O - http://132.227.76.24:8080/repository_paquito.gpg.key | apt-key add -

```

* On met à jour le dépôt :

```bash
apt-get update

```

*  Verifier que le paquet ajouter au dépôt existe:

```bash
apt-cache policy nom_du_paquet(sans le .deb)

```


## Signer les paquets automatiquement:

Sachant que reprepro s'occupe de faire signer les paquets automatiquement à
chaque ajout d'un paquet dans le dépôt , en utilisant la commande suivante:

```bash
reprepro -vb . includedeb jessie /chemin:vers/un/paquet/deb

```

Pour pouvoir entrer la passPhrase automatiquement à chaque fois qu'on utilise
cette commande :

On doit d'abord installer le paquet expect :

```bash
yum install expect

```

On donne les droit d'execution au fichier **debian-sign.exp** : 

```bash
chmod 755 debian-sign.exp

```

On execute ce fichier en lui passant en argument le chemin vers le répertoire
contenant les paquets à ajouter au dépôt:


```bash
./debian-sign.exp /chemin_vers_un_répertoire_précédemment_créé_contenant_les paquets_à_ajouter_et_à_signer) .

```

Dans mon exemple le mot de passe donné à la clé est: **PAQUITO**

Pour finir on crée une crontab pour pouvoir executer ce script toute les heurs
et on redirige les logs vers le fichier `/var/log/sign-debian.log` :

Pour ajouter une crontab

```bash
crontab -e 

```
Ajouter la ligne suivante dans le fichier:

```bash
00 * * * * /chemin_vers_debian-sign.exp /répertoire_contenant_les_paquets_à_signer_et_à_ajouter_au_dépôt >> /var/log/sign-debian.log 2>&1

```
