#Documentation of paquito 2015:

In this documentation we will present you  **paquito project** , we divided the content into different steps:

* Presentation of paquito (Objective) .
* Dependencies needed to use paquito .
*
*
*


###Presentation of paquito 2015:

Paquito is a build firm that try to answer to the following problem :
**How from source code available on a software repository , get to build packages and put them in repositories dedicated to them ?**

The objective being to facilitate the creation of packages for Debian , Centos and Archlinux distributions (according to different versions: Testing ,Stable ..etc  and architectures: 32bits ,64 bits ...etc ) , and that using one and same configuration file which later will be translated into the various versions .

Paquito also offers to the developer a return for errors , and a way to check and test the generated packages (through the generation of test packages), to ensure they are functional , that they can be placed in repositories dedicated to them .

Paquito also offers , the support of multi packages (generation of plurality of packages at the same time : these packages are built from different sources ), through the field **Packages** in the Configuration File **paquito.yaml** (see the documentation of the configuration file) .

The infrastructure used in this project consists of the following steps:

* Compilation and generation of packages for various distributions Debian ,Archlinux and Centos, from the same configuration file .
* Generation of test packages , for testing whether packages created in the previous steps are functional .
* Put the packages in the repositories dedicated to them

