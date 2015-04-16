Fiche technique (formats)
=========================

JSON : (*JavaScript Object Notation*) format de données textuelles dérivé de la notation des objets du langage JavaScript (il est reconnu nativement par ce langage puisqu'il en fait partie). Il permet de représenter de l’information structurée comme le permet XML par exemple.

* Plus adapté pour l'envoi de données
* Avantages :
    * Simplicité de mise en œuvre et d'apprentissage, la syntaxe étant réduite et non extensible
    * Léger
    * Peu verbeux, donc **lisible par un humain** et une machine
    * Types de données sont connus et simples à décrire.
* Pour utiliser un fichier JSON, il faut connaître sa structure des données
* Nativement et facilement utilisable avec Javascript, sans être limité qu'à ce langage (via des parsers) : Bash (Jshon, JSON.sh), C (JSON_checker, json-c, json-parser...), Perl (CPAN, perl-JSON-SL)...
* Voir <a href="http://json.org/" target="_blank">http://json.org/</a>

CSV : (*Comma-separated values*) format représentant des données tabulaires sous forme de valeurs séparées par des virgules.

* Non-adapté au projet car sa représentation est un tableau (qui ne permet pas par exemple de représenter un quelconque héritage)

YAML : (*YAML Ain't Markup Language*) format de représentation de données par sérialisation Unicode

* Avantages :
    * Vis-à-vis du JSON, YAML possède des types plus avancés et est plus adapté pour l'envoi de données
    * Peu verbeux, donc **lisible par un humain** et une machine
    *
    
    