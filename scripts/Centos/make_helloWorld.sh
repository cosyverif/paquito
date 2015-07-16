#!/bin/bash

# ----- DEBIAN -----
# Packaging Paquito
docker build -t centos:paquito - < cent-packaging-paquito.Dockerfile 
# We have to run a image to create a container (and get the package)
ID=$(docker run -t centos:paquito /bin/cat /etc/hostname)
ID=${ID::-1} # Remove the last char (return)
docker cp $ID:/root/rpmbuild/RPMS/x86_64/paquito-0.2-1.el6.x86_64.rpm create-HelloWorld/

# Packaging HelloWorld
cd create-HelloWorld/
tar -zcvf cent-tar-HelloWorld.tar.gz Dockerfile paquito-0.2-1.el6.x86_64.rpm src-test*
cd ..
docker build -t centos:paquito - < create-HelloWorld/cent-tar-HelloWorld.tar.gz
ID=$(docker run -t centos:paquito /bin/cat /etc/hostname)
ID=${ID::-1} # Remove the last char (return)
docker cp $ID:/root/rpmbuild/RPMS/x86_64/helloworld-1.2-1.el6.x86_64.rpm test-HelloWorld/
docker cp $ID:/root/rpmbuild/RPMS/x86_64/helloworld-test-1.2-1.el6.x86_64.rpm test-HelloWorld/

# Test HelloWorld
cd test-HelloWorld/
tar -zcvf cent-tar-Test.tar.gz Dockerfile  helloworld-1.2-1.el6.x86_64.rpm helloworld-test-1.2-1.el6.x86_64.rpm tcc-9999-115.1.x86_64.rpm installation-Bats.sh
cd ..
docker build -t centos:paquito - < test-HelloWorld/cent-tar-Test.tar.gz
