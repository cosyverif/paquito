Idées de développement
======================

* Ajouter un champ **Repositery** dans paquito.yaml. Il sera à la racine (avec **Maintainer**...) et servira à ajouter automatiquement un dépôt à la distribution cible (**Repositery** aura comme champs **Debian**, **Centos** et **Archlinux**), afin d'accéder aux paquets qu'il propose. Les dépôts peuvent être des dépôts connus (EPEL pour Centos) ou privés.
	* Envisager de personnaliser l'installation d'un dépôt (c'est-à-dire qu'au lieu d'installer le dépôt par la méthode codée en dur/par défaut dans Paquito, on installe le dépôt grâce des sous-champs de commandes).
* Les distributions et leurs versions sont codées en dur dans le code de Paquito. Envisager que le fichier **/etc/paquito/conf.yaml** puisse contenir toutes les définitions de ces distributions.
	* Prévoir un mot-clé **latest** que l'on attribuera à une version (Wheezy, Jessie) et qui aura pour effet de considérer la version comme pour la plus récente pour la distribution en question. On pourra alors utiliser le mot-clé **latest** comme un alias dans paquito.yaml, et cela simplifiera la mise-à-jour de Paquito lorsqu'une nouvelle version sortira.
* Ajouter la gestion d'un proxy (il se peut très bien que Paquito s'exécute derrière un proxy)
