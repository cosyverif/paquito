Fiche technique (YaML avec PHP)
===============================

PHP est un langage (dérivé du C) capable d'interpréter les fichiers au format YaML. Il faut cependant télécharger et compiler une librairie pour obtenir cette fonctionnalité.

La procédure suivante est conçue pour la distribution Debian :

* Installer le projet PEAR (qui est entre autre une bibliothèque d'extensions de PHP), les outils de compilation pour PHP ainsi que la librairie YaML (en C) :

    **aptitude install php-pear, php5-dev, libyaml-dev**

* Si un proxy est présent, configurer PEAR pour l'utiliser lors des téléchargements :

    **pear config-set http_proxy http://proxy:3128**

* Installer l'extension YaML pour PHP (PEAR se charge automatiquement de la compilation) :

    **pecl install yaml**

* Ajout dans le fichier de configuration de PHP d'une mention pour charger le module YaML :

    **echo "extension=yaml.so" >> /etc/php5/apache2/php.ini**

<u>Note</u> : Redémarrer le serveur Web !
