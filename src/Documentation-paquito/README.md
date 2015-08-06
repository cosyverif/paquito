#Documentation of paquito 2015:

In this documentation we will present you  **paquito project** , we divided the content into different steps:

* Presentation of paquito  .
* Dependencies needed to use paquito .
* How to create paquito package .
* How to install paquito package
* Create packages through paquito command
* Compilation sources
* Test packages


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

##How to create package paquito :

First install **php** in you're machine (paquito was programmed in php).

To use the command **paquito** ,you have to create and install **the package paquito** . 
There are to ways to create the command **paquito** , which allow us to create packages from source programs (you will use this command to create all packages you want to build) :

* Create the paquito package locally (on machines : using **--local** option) .
* Create the paquito package using docker (virtualisation technology : without the option ) .

Package paquito in paquito (create paquito package) locally , meaning start creating package on machines for this you will follow the following steps :

* Clone the github repository of paquito :
```bash
git clone https://github.com/CosyVerif/paquito

```
* Go to the directory paquito 
```bash
cd paquito

```
* Give rights to the file **install.sh** and execute it , it will install in your machine  , all necessary php tools (php modules , composer ....) :
```bash
chmod 755 install.sh
./install.sh
```
* Exectute the function **Main.php** to create the package paquito (you will use the **--local** option because in this case we choose to create paquito package locally) :

**To create Debian package:**
```bash
php src/Main.php --local generate paquito.yaml

```
**To create Archlinux or Centos package:**
```bash
php -c php.ini src/Main.php --local generate paquito.yaml

```
At the end you will have paquito package according to the distribution on which you start creating.

####Observation:
We use **php.ini** for Archlinux and Centos , because there are some php modules that are missing in the php configuration file of these distributions .


Create package paquito in docker , for this you follow the same steps as above , the only thing that changes is :
we will not used the **--local** option .

**To create Debian package :**
```bash
php src/Main.php generate paquito.yaml

```
**To create Centos or Archlinux package :**
```bash
php -c php.ini src/Main.php generate paquito.yaml

```
##How to install paquito package:

Installation is different according to the distribution :

* **Debian:**
```bash
dpkg -i name_of_debian_package

```
* **Centos:**
```bash
rpm  -i name_of_centos_package

```
* **Archlinux:**
```bash
pacman -U name_of_archlinux_package

```

##Create packages through the paquito command :

After having to create and install the paquito package , you can use **paquito** command to create packages from source programs ,to do this follows these steps :

* Fill the configuration file **paquito.yaml** , for it to be adapted to the needs of you're program(see the documentation of configuration file) .
* Place the configuration file in the repository contains the sources of your program .
* Run the command :

**Locally (--local option)** : you will have generate paquito package locally (see above) .
```bash
paquito --local generate source-repository

```
**Docker**: you will have generate paquito package in docker (see above).
```bash
paquito generate source_repository

```
####Observation:
In the field **Files** of configuration file paquito.yaml , in which we indicate the path to access to sources files that package need , the path to the files is done from **the directory we give as parameter to the paquito command**.

**for example**:

The directory containing the sources of your program (the program you want to package ) is **src** , you will place **paquito.yaml** in this directory , and this directory contains two files : **hello.c** ,**program.c** (program sources) , the field **Files** will be fill like this :
```yaml
Files:
   /usr/bin/ : hello.c
   /usr/share : program.c
   
```
**The path is done from the directory** .

The command to create a package from the program sources ( directory **src**) will be :

**Locally (paquito package has been created locally)** :
```bash
paquito --local generate src

```

**Docker (paquito package has been created in docker )**:
```bash
paquito generate src

```
##Compilation sources :

#####Locally:

If your program needs to be compiled (you need to generate an executable),the compilation of your program sources (program that you want to package) , will be in the function **Generate.php** (see the developer_documentation) ,for this you will give the compilation commands (the commands will be executed in **Generate.php**) in the field **Commands** of the configuration file (see the documentation of configuration file ) .

**For example:**

I take the example of **hello -world** program , this program contain two files : **main.cc** and  **program.c** (see README of the program hello-world) .
We need to compile the file **main.cc** to generate the executable **hello-world** , the command of compilation will be written in **Commands** field of the configuration file , it will like this :

```yaml
Build:
   Commands:
      - g++ main.cc -o hello-world

```

**Observation**:

The path considered at the **Commands** field (in order to give the path to the file to be compiled) , is the same with the path given in the **Files** field , is the directory containing sources program and the configuration file **paquito.yaml** ( in the above example ; the directory was **src** : the path done from this directory ) .

##Test packages :

To test the created package , we use test package , to ensure that the package is functionnal , for it to be put in the repository dedicated to it .
Create of the test package is in **Generate_test.php** function  , using the command **generate-test** (see the developer_documentation which explain what this command does) . 

The command you'll use to create the test package is :

**Locally :**
```bash
paquito --local generate-test name_of_your_source_repository

```
**Docker:**
```bash
paquito generate-test name_of_your_source_repository

```

**name_of_your_source_repository** : is the repository which will contain program sources , and the  configuration file paquito.yaml .

