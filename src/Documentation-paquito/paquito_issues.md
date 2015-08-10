#Paquito issues :

In this documentation we will set out the various problems that remain to be solved in paquito :

* Problem of dependencies in archlinux .
* AUR packages .
* Sign packages in archlinux


###Problem of dependencies in archlinux :

When we generate a package using archlinux distribution , we faced with a problem :

The system refuses to create the archlinux package , because it requires to installing  **Runtime dependencies** before the construction of the package (see paquito github :**issue16** ) .

To resolve this problem we reflect on two solutions :

* Use makepkg options : 
`makepkg -s`, which install missing dependencies usign pacman .

`makepkg -r` which remove dependencies installed by makepkg .

see the documentation (https://www.archlinux.org/pacman/makepkg.8.html) .

This solution doen't work with **local generation of packages** (use --local option : generate packages with using machines) , because since this year we can not use the function **makepkg** (to generate archlinux packages in machines or virtual machines ) **as root** (we use **nobody** user : see the function **Generate.php**) , but to use the options above we have to be root , and this it's impossible .

But in the generation usign dockers , this solution is possible for the moment , because in docker we can always use the command **makepkg** as root to generate archlinux packages .

* Use the functions **Generate.php** and **Generate_test.php** , in which we launch commands for the installation of the **Runtime dependencies** .

this solution work with **local generation of packages** (use --local option : generate packages with using machines) , because it work with these php functions (Generate.php ..) , but in the generation using **dockers** , this solution doen't exists because , we don't use php to create packages (we use shell commands throught **Dockerfile scripts**) , for this we use the first solution for the generation of packages in dockers .
 

###AUR packages :

Currently our paquito project manages the creation of archlinux packages through the **makepkg** command , and they are installed using **pacman** command, and they are put in pacman repository .

It will think of a solution to install the eventual archlinux package dependencies that are available on AUR repository .


###Sign packages in archlinux:

The signing of archlinux packages is does at their creation , we try to create signed archlinux packages with using the option **--sign** in the command **makepkg** ( see the documentation https://www.archlinux.org/pacman/makepkg.8.html) , but it faildes , the error message displayed was :

`the id-key is not available in the keyring`.

We tried to solve this problem without result ( see the documentation of creation archlinux repository in the paquito github : to see how to generate and import the gpgkey) .

So , we create archlinux repository , which contains no signed packages , the field **Siglevel** in the file **"/etc/pacman.conf"** , determines if it signed repository or not ( we can create archlinux repository without sign packages : the possibility to sign packages in archlinux is recent ) .

