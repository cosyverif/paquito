#Developer documentation:

In paquito project 2015 we took care of creating packages for Debian , Redhat and Archlinux  distributions (versions and architectures) from one (and the same) configuration file (**paquito.yaml** file) , for this  we divided the work in several steps :


* Verification
* Normalisation
* Pruning 
* Generation_of_packages
* Test_packages

Each step is represented by a php function , and each php function is represented by command , for this we used **symfony console** that can create usable console commands ,to facilitate project management .

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
###Observation:
The field **Dependencies** doen't exists in all Centos versions after pruning , because there is no dependence in the field **Runtime** of the configuration file for Centos (**"\<none>"**) . 

###Generation_of_packages:

This step is reponsible of the creation of packages for Debian , Archlinux and Centos distributions (versions and architectures ) .
According to distribution of the machine where the generation is launched , at the end we will have a package suitable for this distribution (version and architecture) .

This step is represented by the function **Generate.php** and the console command **generate** .
