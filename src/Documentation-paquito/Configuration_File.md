Configuration File of Paquito 2015:
===================================

Observations:
-------------
* The format used to write the configuration file is the yaml format and the name of the configuration file must always be **paquito.yaml** .
* All the first level fields must not be empty ,otherwise it will display an error .
* Package building is done for Debian , Archlinux and Centos distribution .
* The configuration file lets us , the support of multi packages, through the field **Packages** we will detail later , this field allows us to declare several different packages to build .
* if there is a field of the Configuration file which is empty ( we don't need this field) , we don't put this field in the configuration file , otherwise it will be an error . 

Organization of the configuration file:
---------------------------------------

**Name** : the name of your program ,you must provide a name ,if the field is empty, it will be an error .

**Version** : the version of you program , you have to provide a version , if the field is empty , it will be an error .
**Homepage** : it's the home page of you're project  , if you leave this field empty , you will have an error .

**Summary** : it's a short description of what your program do , if you leave this field empty , you will have an error .

**Description** :it's a more detailed description of what your program do , if you leave it empty , you will have an error .

**Copyright**: it's 'the license of your program , for example : gpl, gpl2, gpl3 ... ect,if you leave this field empty , you will have an error .

**Maintainer**: it's the name of the person who is responsible of maintaining the package ; It must follow the following pattern:  **name<email>** ,if not it will be an error , and if you leave this field empty it's will be another error .

**Authors** : this is a list of developers  name ,that participated on your project ; It must follow the following pattern : **name <email>** if not it will be an error , and if you leave this field empty it's will be another error.

**Packages** :  this is an array of packages you want to build . 
Each element of this array is a **package-name field** will contain , all the necessary fields to build the package , for example: package name ,  package type,  package files , dependencies ect ..
This field must not be empty ,it must be at least a package to build , if not it will be an error  .
This is an example of structure of this field :

**Packages :**
    **package1-name:**
	**Type:**
	**Files:**
	**Runtime:**
	**Build:**
    **package2-name:**
       **Type:**
       **Files:**
       **Build**

**package-name** : is the name of package you want to build , for exemple :**paquito** ,   the name must be lowercase .

**Observation**  : all fields below are fields of **package-name** field, it's the necessary elements to build the package .

    **Type** : type of package you want to build , it must be one of the following types :  **binary** , **source** , **arch_independant** , **library** , if not it will be an error , and if this field is empty it will be another error.

    **Files** : this field will contain an array of files of the packages , each element of  this array is a file  of the package .
Each  field represent **one file of the package** contain at least the **source** and the **destination** , is presented in two different ways:

 **1)**

**destination1** : **source1** .
**destination1**  is the path to the destination file in  the  build package , **source1** is the path to retrieve the source file .

**example :** 
**Files:**
    **/usr/bin/paquito : /src/paquito.sh**

**Observation:**
If the source and the destination are the same  for example : **/usr/bin/paquito : /usr/bin/paquito** , we can write it  **without the source** like this:
**/usr/bin/paquito :** 

**2)** 

**destination1** : it's an array of two elements
      **Source**: is a path to the source file : **source1**
      **Permissions** : rights to files , in the first case this field doent'n exist , we will give the rights by default

**example:**
**/usr/bin/paquito:**
       **Source : /src/paquito.sh**
       **Permissions : 755**

**Observations:**

In the two cases  if the source or the destination are ( directory || file ) the representation is different :
* the source and the destination are directories : **/usr/bin/ : /path-to-our-directory/** ( we have to add **"/"** at the end of the path, when it's directory) .
* the source and the destination are files and we want ton rename the destination file : **/usr/bin/paquito : /src/paquito.sh** ( we rename paquito.sh **paquito** in the file of package).
* the source and the destination are files and we keep the same name for the destination file : **/usr/bin/ : /src/paquito.sh**

    **Build** : this field contain everything needed before building the package .
If there is nothing need before building the package , we don't put this field on the configuration file , because if we add empty field , it will be an error .
It's an array containing the following elements :
 
      **Dependencies**: this is where we declare all dependencies for construction .

It's an array of dependencies , each element of this array is the name of the dependence,and each **field dependence**  can be represent in different ways:

* if the name of dependence is the same for all distributions ( versions and architectures) the content of **field dependence** will be **"*"** , for example :
**Dependencies:**
      **gcc : "*"**
* if the name of dependence is different ,the **field dependence** contain an array , each element of this array is the name of distribution : the name have to be one of this following names **"Debian or Archlinux or Centos"** , if not it will have an error , and each **field distribution** can be represent in different ways :

   * If **field dependence** (name of the dependance) is the same in one of each distribution ,we don't have to write it (don't write the dependence name for this distribution) .
   * If one of this distribution don't contain the dependence we replace by **"<none>"** .

**for example :**
we have php5 dependence for construction , and Centos don't need any php dependence , and name of php dependence in Debian is the same "php5".

**Dependencies :**
    **php5:**
       **Archlinux: php**
       **Centos: "<none>"**

   * if the **field dependence** (name of dependance) is not the same for all versions of the same distribution ,the content of **field distribution** will be an array ,   elements of this array are different according to the distribution , for Debian possible elements are : **All, Stable, Testing, Wheezy, Jessie** , for Archlinux : **All** (all archlinux versions are rolling release) , for Centos :**All, 6.6, 7.0** , the field **All** contain the name of dependence which is the same for all versions , to specialize version the field will be the name of the version  for exemple : there is gcc dependence , and the name of this dependence is gcc-1.2 for **Testing version** of Debian distribution .

**Dependencies :**
  **gcc:**
     **Debian:**
       **All: gcc**
       **Testing : gcc-1.2**

 
      **Commands**: it's a list of commands we have to execute before build a package , for exemple :
If our package is a binary package and we need to compile the program , we write in this field all the necessary commands to the compilation , each command is element of list.
If there is no command to execute , we don't put this field in the configuration field, because if we write : **Command :** and we leave this field empty , it's an error .

**example:**
**Commands:**
       **-cd src/**
       **- g++ main.cc -o hello-world**
       **- cd ..**

    **Runtime**: this field contains dependencies  execution.
If there is no necessary dependence for the package installation , we don't put this field on the configuration file , because if we add the empty file , it will be an error .
It's an array containing one element which is the **field Dependencies** .
The description of this field is the same with the **field Dependencies of Build field** (see above) .

    **Install** : this field contrain pre-install and post-install commands , it's an array of this two elements , and each elements is a list of commands to execute , if there is no pre-install and post-install commands , we don't write this field , because if we write it and we leave it empty , it will be an error .
      **Pre**: list of commands to execute before the installation of the package we build (pre-install), if there is no commands to execute , we don't write this field beacuse if we write it and we leave it empty (without commands) , it will be an error .
      **Post** : list of commands to execute after the installation of the package we build (post-install), if there is no commands to execute we don't write this field , because if we write it and we leave it empty , it will be an error .

**example**:
**Install:**
    **Pre:**
       **- echo "begin of the installation \n"**
    **Post:**
       **- echo "end of the installation \n"**
       **- echo "bye \n"**

    **Test**: this field allow the user of paquito to give tests , to apply the test set on the package , in order to verify if everything is right .
If the user of paquito don't give any tests , don't put this field on the configuration file , because if you write an empty field in the configuration file , it will  be an error , in this case (no tests given by user) only default test given by paquito will be apply .
It's an array containing the following fields :

      **Files** : it will be the tests files we will apply on the package , the representation of this field is exactly the same with the **field Files** of paquito (see above) : **destination-in-package : path-to-source** .
      **Dependencies**: it's the dependencies we need to execute the tests , the representation of this field is exactly the same with the **field Dependencies** of paquito (see above) , if there is no dependence don't put this field in the configuration file , if not it will be an error.
      **Commands**: it's a list of commands , which will executed at the installation of the test package , to apply test set on the package . 

  
