Documents et Documentation
==========================

Ce dossier contient les dossiers suivants :

* `comptes-rendus` : les comptes-rendus des réunions,
  rédigés en format [Markdown](http://fr.wikipedia.org/wiki/Markdown),
  et dont les noms de fichiers sont de la forme `YYYY-MM-DD.md`
  correspondant à la date de la réunion;
* `presentations` : les présentations pour les réunions,
  rédigés en format [Markdown](http://fr.wikipedia.org/wiki/Markdown),
  et dont les noms de fichiers sont de la forme `YYYY-MM-DD.md`
  correspondant à la date de la réunion;
* `fiches` : les fiches techniques,
  rédigées en format [Markdown](http://fr.wikipedia.org/wiki/Markdown),
  et dont les noms de fichiers correspondent au thème de la fiche.

Attention, il existe plusieurs variantes de syntaxe Markdown.
Nous utiliserons la variante spécifique à `pandoc`.

*On ne stocke dans le dépôt que les sources, pas les fichiers générés !*

Comment générer du HTML ou PDF à partir du Markdown ?
-----------------------------------------------------

Tout d'abord, installez l'outil [pandoc](http://johnmacfarlane.net/pandoc/),
disponible sous forme de package dans de nombreuses distributions.

Le script `make-doc.sh` génère, pour chaque fichier `.md` trouvé dans
les dossiers listés ci-dessus, une version HTML et une version PDF.

La commande utilisée pour générer du HTML est :

    pandoc --from=markdown \
           --to=html5 \
           --self-contained \
           --output=<fichier>.html \
           <fichier>.md

Pour les présentations, il faut utiliser l'une des sorties proposées.
Certaines nécessitent la présence d'un fichier CSS au bon endroit.

    pandoc --from=markdown \
           --to=slidy \
           --self-contained \
           --output=<fichier>.html \
           <fichier>.md

La commande utilisée pour générer du PDF est :

    pandoc --from=markdown \
           --to=latex \
           --output=<fichier>.pdf \
           <fichier>.md

De même, pour générer un PDF de présentation, la commande est donnée
ci-dessous. Attention, le style utilisé par défaut n'affiche pas les
numéros des slides, ce qui va à l'encontre des règles fixées.

    pandoc --from=markdown \
           --to=beamer \
           --output=<fichier>.pdf \
           <fichier>.md

