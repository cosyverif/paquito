#Création d'un dépôt Centos :

##SERVEUR :

####Ajout de paquets dans le dépôt:

Lorsqu'on ajoute un paquet dans le nouveau dépôt créé qui est `/var/www/lighttpd/centos/stable` :
 
On l'ajoute au niveau des répertoires qui contiendront les paquets : **i386** ou **x86_64** .

Le paquet doit étre signer , pour signer un paquet on utilise la commande suivante:

```bash
rpm --addsign chemin_vers_le_paquet

```

On met à jour le dépôt pour qu'il prenne en compte le nouveau paquet :

```bash
createrepo --update  chemin_vers_le_dépôt

```

##CLIENT:

* Récupérer la clé du dépôt et l'ajouter à la base de donnée de RPM:

```bash
rpm --import http://132.227.76.24:8081/centos/RPM-GPG-KEY

```

* Créer un fichier dans `/etc/yum.repo.d` , dont le nom sera **nom.repo** (nom : représente un nom quelconque), qui aura la forme suivante:

```bash
 [nom-repo]
 name=centos repository
 baseurl=http://132.227.76.24:8081/centos/stable
 gpgcheck=1
 gpgkey=http://132.227.76.24:8081/centos/RPM-GPG-KEY
 enabled=1

```

* Mette à jour :

```bash
yum clean expire-cache

```

* Afficher la liste des dépôts disponibles:

```bash
yum repolist

```

On verra que le nouveau dépôt est disponible .

   
##Paquet lighttpd :
 
Pour pouvoir installer lighttpd sous centos on a d’abord besoin d'installer le paquet suivant:

```bash
yum install epel-release-6-8.noarch.rpm

```
Puis: 

```bash
yum install lighttpd

```
  
##Clés GPG                                                                                                                                                 
Pour voir la liste des clés importé dans la base de donnée RPM:

```bash
rpm -q gpg-pubkey --qf '%{NAME}-%{VERSION}-%{RELEASE}\t%{SUMMARY}\n'

```
 
Pour supprimer une clé importée:

```bash
rpm-e gpg-pubkey-numéro_clé

```
 
Pour voir la liste des clés générées sur la machine :

```bash
gpg --list-keys

```
 
Pour supprimer une des clés générés:

```bash
gpg --delete-secret-key id-key
gpg --delete-key id-key

```

## Signer les paquets automatiquement:

Pour pouvoir entrer la passPhrase automatiquement à chaque signature d'un paquet :

On doit d'abord installer le paquet expect : 

```bash
yum install expect

```

On donne les droit d'execution au fichier **rpm-sign.exp** : 

```bash
chmod 755 rpm-sign.exp

```

On execute ce fichier en lui passant en argument le chemin vers le répertoire contenant les paquets:


```bash
./rpm-sign.exp /var/www/lighttpd/centos/stable/x86_64/*.rpm

```

Dans mon exemple le mot de passe donné à la clé est: **PAQUITO**

Pour finir on crée une crontab pour pouvoir executer ce script toute les heurs , et on redirige les logs vers le fichier `var/log/sign-rpm.log` :

Pour ajouter une crontab:

```bash
crontab -e 

```

Ajouter la ligne suivante dans le fichier:

```bash
00 * * * * /chemin_vers_rpm-sign.exp  /var/www/lighttpd/centos/stable/x86_64/*.rpm >> /var/log/sign-rpm.log 2>&1

```
 
