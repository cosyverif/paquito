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

This step is represented by the function **Check.php**

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
