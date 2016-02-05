Paquito (dev branch)
====================

This version of Paquito aim to be less platform-restricted. It can handle generation of package for multiple LINUX based operating system. `/etc/paquito/conf.yaml` file must be define.

SUGGESTIONS
-----------
* Define a global ConsoleLogger for the application, instead of create one for each command


TO DO
-----
* [FIX issue #62 - GPG keys with Docker in Archlinux](https://github.com/CosyVerif/paquito/issues/62)
* [FIX issue #55 - Print error message to /usr/local](https://github.com/CosyVerif/paquito/issues/62)
* Normalize : Check if every version has a dependency or at least "All" field is define
* Define a proper definition of conf.yaml
* Define a proper definition of version ans alias distribution
* Search info about lsb_release
* Install on Archlinux the package filesystem

For Developers
--------------
During development, run `php src/Main.php` to run the application.

Paquito heavily rely on existing tools and framework. If you want
to take part of the development process, you should take a look
at the following links :

* [PHP The Right Way](http://www.phptherightway.com/)
* [Composer](https://getcomposer.org/)
* [Box](http://box-project.org/)
* [PHP-CS-Fixer](http://cs.sensiolabs.org/)
* [Symfony Console](http://symfony.com/fr/doc/current/components/console/introduction.html)
* [Symfony Logger](http://symfony.com/doc/current/components/console/logger.html)
* [Symfony YAML](http://symfony.com/fr/doc/current/components/yaml/introduction.html)
* [Symfony Translation](http://symfony.com/doc/current/components/translation/index.html)
* [Docker](https://docs.docker.com/)

Credits - Contributeurs
-----------------------
Le projet Paquito à été créé en 2014 dans le cadre du projet [CosyVerif](https://github.com/CosyVerif), développé par le groupe [MeFoSyLoMa](http://www.mefosyloma.fr/).

L'évolution et la maintenance du projet est dans le cadre d'un projet de fin d'année de la formation Master 1 Informatique de l'[Université Pars 13](https://www.univ-paris13.fr/).

[Damien Mehala](mailto:damien.mehala@me.com) / David Rukata / Malik Hadadi / Mandana Ahmadi / Ronald Choundong / Sassa Benamoura / Souhila Zeddam

License
-------
The MIT License (MIT)

Copyright (c) 2015 CosyVerif

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

