#Developer documentation:

In paquito project 2015 we took care of creating packages for Debian , Redhat and Archlinux  distributions (versions and architectures) from one (and the same) configuration file (**paquito.yaml** file) , for this  we divided the work in several steps :


* Verification
* Normalisation
* Pruning 
* Generation_of_packages
* Test_packages

Each step is represented by a php function , and each php function is represented by command , for this we used **symfony console** that can create usable console commands ,to facilitate project management .

We have two versions of paquito :

* the first version is one that is available on the **master** branch : this corresponds to the generation of packages using **machines** (Debian , Archlinux , Centos ) , this version works perfectly .
* the second version is one that is available on the **issue60** branch : this corresponds to the generation of packages using **dockers** (dockers images) .

####Observation :
Refer to the documentation of the configuration file to understand the structures shown below .

###Verification : 

After having loaded the configuration file (parse the configuration file paquito.yaml and convert it on php array) ,verification step is initiated to:
* Ensure that the configuration file contains all the required fields.
* He respects the format described in the documentation of the configuration file , in case of error it will be transmitted as an error message describing the error occurred

This step is represented by the function **Check.php** and the console command **check**.

**example:**

```yaml
Name:
Version: 1.3
Homepage: paquito.fr
Description: program of packaging
Authors:
    - sarah <sarah@gmail.com>
    - corentin <corentin@gmail.com> 

```
the error will be:

``` the field Name is empty ```

###Normalisation:

To facilitate the filling of  the configuration file by user , there are shortcuts in this file , normalisation is to remove these shortcuts for developer ,to technically facilitate the access to some fields of the file .

This step is represented by the function **Normalize.php** and the console command **normalize** .

**example_1:**

We find one of the shortcuts at the field **Files** of the configuration file .

```yaml
Files:
   /usr/bin/paquito: src/paquito.sh
   
```
In this example the file has no rights, for this we will give him default rights (**755**) .
After normalisation it will represented like this :

```yaml
Files:
   /usr/bin/paquito:
      Source: src/paquito.sh
      Permissions: 755
      
```
**example_2:**

We find another shortcut at the field **Dependencies** of the configuration file .
For example if we have one dependence  **gcc** , and this dependence is the same for all distributions (versions and architectures ) , we will use the shortcut **"*"** .

It will represented like this :

```yaml
Build:
   Dependencies:
      gcc: "*"

```
After normalisation we delete this shortcut , and it will be like this :

```yaml
Build:
   Dependencies:
      gcc:
         Debian:
            All: gcc
         Archlinux:
            All: gcc
         Centos:
            All: gcc
            
```

**example_3**:

There is another shortcut in the field **Dependecies** of the configuration file  , for example we have a dependence which is **tcc** and this dependence is different for some distributions , but for the same distribution ,there is no specialization for versions (the same dependence name for all versions of the distribution) .

It will represented like this :

```yaml
Runtime:
   Dependencies:
      tcc:
         Archlinux: tcc-1.2
         Centos: tcc-2.2
         
```
After normalisation it will be like this :

```yaml
Runtime
   Dependencies:
      tcc:
         Debian:
            All: tcc
         Archlinux:
            All: tcc-1.2
         Centos:
            All: tcc-2.2

```

###Pruning:

Is to remove all the specific information in the configuration file ,to keep only the information concerning the version of the package distribution that wants to build .

This step is represented by the function **Prune.php** and the console command **prune** .

**example** :

We have a dependence which is **base-devel** and this dependence doen't exist in Centos distribution , in Debian distribution she exists but she have another name **build-essential** .

It will represented like this (in the configuraton file) :

```yaml
Runtime:
   Dependencies:
      base-devel:
         Debian: build-essential
         Centos: "<none>"
        
```
After pruning it will be like this :

####for debian stable:

```yaml
Runtime:
   Dependencies:
      build-essential
     
```
####for Archlinux :

```yaml
Runtime:
   Dependencies:
      base-devel
      
```
####Observation:
The field **Dependencies** doen't exists in all Centos versions after pruning , because there is no dependence in the field **Runtime** of the configuration file for Centos (**"\<none>"**) . 


###Generation_of_packages:

This step is reponsible of the compilation (if needed) , and the creation of packages for Debian , Archlinux and Centos distributions (versions and architectures ) .

In the first version of paquito ( **master branch** ) , this function  compiles the program ( if the program needs to be compile ) , and create packages according to distribution of the machine where the generation is launched , at the end we will have a package suitable for this distribution (version and architecture) .

In the second version of paquito (**issue60 branch**) , this function is responsible of launching dockers , and the compilation and the creation of packages will be does in these dockers . 

This step is represented by the function **Generate.php** and the console command **generate** .


###Test_packages:

In this step we will test the package created in the previous step , to ensure that it is consistent and functional , so it can be put into the repository dedicated to him .

This step is available for the first version of paquito (**master branch**) , we have not yet processed the case where you launch the tests in dockes .

This step is represented by the function **Generate_test.php** and the console command **generate-test** .
When we execute the command **generate-test** we will create two packages :

* the first package will be the package created in the previous step (at the begining the command **generate-test** calls the command **generate** ) . 
* the second package will be the test package ,with which test the previous package .

There are two types of tests :

* default test which is always executed at the installation of test package , it is the file **installation.bats** which will be created at the lauch of the command **generate-test** ( this file will be contained in the test package) , this file tests that the files of packages in the field **Files** of the configuration file exist , and these files have the right for execution .
* tests provided by user , who will want to test different things in his program (use the field **Test** of the configuration file paquito.yaml) .

We have to use scripting language to write these tests .
These tests will be always executed at the installation of the test package .

To write the default test we used  **Bats** , for this we have added in the file **Generation_test.php** installation of Bats ,so it will be installed at the execution of command **generate-test** ( if you want to write tests in Bats, you will not need to install it because it will be installed through the command **generate-test**).

#####Bats:
Is a TAP-compliant testing framework for Bash , a Bats test file is a Bash script with special syntax for defining
test cases .

