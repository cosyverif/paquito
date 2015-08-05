#Documentation of paquito 2015:

In this documentation we will present you  **paquito project** , we divided the content into different steps:

* Presentation of paquito  .
* Dependencies needed to use paquito .
*
*
*


##Presentation of paquito 2015:

####Description:

Paquito is a build firm that try to answer to the following problem :

**How from source code available on a software repository , get to build packages and put them in repositories dedicated to them ?**

The objective being to facilitate the creation of packages for Debian , Centos and Archlinux distributions (according to different versions: Testing ,Stable ..etc  and architectures: 32bits ,64 bits ...etc ) , and that using one and same configuration file which later will be translated into the various versions .

Paquito also offers to the developer a return for errors , and a way to check and test the generated packages (through the generation of test packages), to ensure they are functional , that they can be placed in repositories dedicated to them .

Paquito also offers , the support of multi packages (generation of plurality of packages at the same time : these packages are built from different sources ), through the field **Packages** in the Configuration File **paquito.yaml** (see the documentation of the configuration file) .

The infrastructure used in this project consists of the following steps:

* Compilation and generation of packages for various distributions Debian ,Archlinux and Centos, from the same configuration file .
* Generation of test packages , for testing whether packages created in the previous steps are functional .
* Put the packages in the repositories dedicated to them .

####Format of the configuration file :

The name of the configuration file , will be always **paquito.yaml** , for all projects that will use paquito .
The format of the configuration file paquito is the **yaml** format , because :

* it's a very human readable format .
* it's easy to write .

####Paquito programming language:

The choice of language has focused on the **php** language , because of the advantages it offers for our project :

* Has some libraries for the interpretation of yaml configuration file .
* Scripting language, object language .
* Has a number of tools and frameworks , such as **symfony** , that allow  the management of some tasks , and facilitate the work of developer .

####PHP modules used:

We used some modules of symfony ,to facilitate and accelerate the developement :

* **YAML**: provides yaml functions for the interpretation of the configuration file .
* **Console**: creates usable console commands to facilitate project management (create command for each php fonction : the command **generate** for the function **Generate.php**) .
* **Logger** : display of error messages .
* **Translator** : to translate (paquito exists in french and english) .

##Dependencies:

Paquito has some Dependencies on the construction and the execution :

Dependencies for construction are :

* Debian : packages **build-essential** and **dh-make** , necessary for building Debian packages .
* Archlinux : **base-devel** which is a packages group , necessary for building Archlinux packages .
* Centos : packages **rpm-build** and **rpmdevtools** , necessary for building Centos packages , and packages **php-xml** and **php-process** necessary to satisfy some php modules , that are not available on Centos .

Dependencies for execution are :

* Debian : we have the same dependencies for building Debian packages , as construction dependencies , there is a dependence more which is **php5-cli** , necessary because paquito project programmed in php .
* Archlinux : we have the same dependencies for building Archlinux packages , as construction dependencies , there is a dependence more which is **php-cgi** , which is the php dependence for Archlinux .
* Centos : we have the same dependencies for building Centos packages , as construction dependencies, and the same dependencies necessary to satisfy some php modules , there is a dependence more which is **php-cli** , which is the php dependence for Centos

#How to create package paquito :

To use the command **paquito** ,you have to create and install **the package paquito** . 
There are to ways to create the command **paquito** , which allow us to create packages from source program (you will use this command to create all packages you want to build) :

* Create the paquito package locally (on machines : using **--local** option) .
* Create the paquito package using docker (virtualisation technologie : without the option ) .

Package paquito in paquito (create paquito package) locally , meaning start creating package on machines for this we will follow the following steps :

* Clone the github repository of paquito :
```bash
git clone https://github.com/CosyVerif/paquito

```
* Go to the directory paquito 
```bash
cd paquito

```
