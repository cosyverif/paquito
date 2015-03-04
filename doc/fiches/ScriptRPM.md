Script création du paquet RPM pour le programme hello 
===========================================================
world:
=======

Construction de l'arborescence du RPM:
-----------------------------------------

avec la commande :

**$ rpmdev-setuptree** .

on aura l'arboresence suivante:

**rpmbuild/BUILD ,rpmbuid/SOURCES, rpmbuild/SPECS , rpmbuild/RPMS, rpmbuild/SRPMS**.


Fichier SPEC:
----------------

Pour avoir un modéle de fichier SPEC :
**$ rpmdev-newspec nom_du_spec**

on placera ce fichier dans le repertoire SPECS.

**%define**  _topdir /home/rpmbuild
**Name**:           hello-world
**Version**:        1.1
**Release**:        1%{?dist}
**Summary**:        afficher un simple hello-world

**License**:        GPLv2+
**URL**:            http:/github.com/saucisson
**Source0**:        %{name}.tar.bz2


**Requires**:       tcc

**%description**
Ce programe est un simple affichage de hello world

**%prep**
cd %{_topdir}/BUILD
mkdir %{name}-%{version}
cd %{name}-%{version}
tar jxvf %{_topdir}/SOURCES/%{name}.tar.bz2

**%build**
g++ %{name}-%{version}/%{name}/src/main.cc -o hello-world


**%install**
rm -rf $RPM_BUILD_ROOT
install -Dp -m 755 %{name}-%{version}/%{name}/src/program.c $RPM_BUILD_ROOT/usr/share/hello-world/program.c
install -Dp -m 755 hello-world $RPM_BUILD_ROOT/usr/bin/hello-world


**%clean**
rm -rf $RPM_BUILD_ROOT

**%files**
%defattr(-, root, root, -)
/usr/bin/hello-world
/usr/share/hello-world/program.c

**%changelog**

