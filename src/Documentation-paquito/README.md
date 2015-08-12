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

We have two versions of paquito :

* In the first version (this version is represented by the **master** branch ) , we generate packages according to the distribution of machine in which the generation is launched (according to the version and the architecture of the machine) , the generation will be does in this machine , at the end of this generation we will have package according to the version and the architecture of this machine .

The generation is made  by the function **Generate.php** (see the developer documentation) , in this function we do the compilation (if needed) and the creation of packages  .

This version work perfectly .

* In the second version  (this version is represented by the **issue60** branch ) , we generate packages using dockers , the compilation and the creation of packages is made in these dockers , the function **Generate.php** only serves to launch these dockers  , and we have two methods in this version :

   * The local method (use the **--local** option : you will see bellow ) , allow us to generate packages in dockers , but only launches docker for the version and architecture of the distribution of the machine (like the first version but we using docker).
   * The non local method ( without the **--local** option ) , also allow us to generate packages in dockers ,  but this time it launches all dockers  according to the file **/etc/paquito/conf.yaml** , in which you will specify all dockers images of the versions and architectures you want (packages for multiple distributions at same time ) .
   

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

Knowing we said previously there has two versions of paquito , the generation of paquito package is different depending on the version :

* For the first version ( create paquito package using machines ) , there is one way to generate paquito package which allow us to create packages from source programs (you will use this command to create all packages you want to build), for this you will follow the following steps :

  * Clone the github repository of paquito (repository of the **master** branch) :
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
  * Exectute the function **Main.php** to create the package paquito :

**To create Debian package:**
```bash
php src/Main.php generate .

```
**To create Archlinux or Centos package:**
```bash
php -c php.ini src/Main.php generate .

```

`.` : represent the actual directory .

At the end you will have paquito package according to the distribution on which you start creating.


* For the second version of paquito ( version of the **issue60** branch : create packages using dockers) ,there are to ways to create the command **paquito** , which allow us to create packages from source programs (you will use this command to create all packages you want to build) :


   * Create the paquito package locally ( only launches docker for the version and architecture of the distribution of the machine : using **--local** option) .
   * Create the paquito package non locally ( launches all dockers for the versions and architectures of the distributions , you specify in the file **/etc/paquito/conf.yaml** : without the option ) .

Package paquito in paquito (create paquito package) locally , for this you will follow the following steps :

* Clone the github repository of paquito (branch **issue60** ):

```bash
git clone -b issue60 https://github.com/CosyVerif/paquito.git

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
php src/Main.php --local generate .

```
**To create Archlinux or Centos package:**
```bash
php -c php.ini src/Main.php --local generate .

```
At the end you will have paquito package according to the distribution on which you start creating.


####Observation:
We use **php.ini** for Archlinux and Centos , because there are some php modules that are missing in the php configuration file of these distributions .


Create package paquito non locally (a lot of dockers ) , for this you follow the same steps as above , the only thing that changes is :

we will not used the **--local** option .

**To create Debian package :**
```bash
php src/Main.php generate .

```
**To create Centos or Archlinux package :**
```bash
php -c php.ini src/Main.php generate .

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

   * For the first version of paquito (master branch ) :
  ```bash
  paquito generate source-repository
  
  ```
  
  * For the second version of paquito ( issue60 branch ) there are two ways :

**Locally (--local option)** : you will have generate paquito package locally (see above) .
```bash
paquito --local generate source-repository

```
**Non locally** : without the **--local** option.
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

* For the first version of paquito (**master**) :
```bash
paquito generate src

```

* For the second version of paquito (**issue60**) :

   * **Locally (paquito package has been created locally )** :
  ```bash
  paquito --local generate src

  ```

   * **Non locally (paquito package has been created without --local option )**:
  ```bash
  paquito generate src


  ```
  
  
##Compilation sources :

#### In the first version of paquito (**master** branch):

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

####In the second version of paquito (**issue60** branch ):

The compilation will not be in the function **Generate.php** , but in the docker .

The function **Generate.php** will just launch the dockers  , and in these dockers we will do the compiltation and the creation of packages ( commands given throught scripts ) .

##Test packages :

To test the created package , we use test package , to ensure that the package is functionnal , for it to be put in the repository dedicated to it .
Create of the test package is in **Generate_test.php** function  , using the command **generate-test** (see the developer_documentation which explain what this command does) . 

**Observation**:

**This step is only available in the first version of paquito (master branch) , in the second version tests are not implemented** .

The command you'll use to create the test package is (unsing the first version of paquito):

```bash
paquito generate-test name_of_your_source_repository

```

**name_of_your_source_repository** : is the repository which will contain program sources , and the  configuration file paquito.yaml .

