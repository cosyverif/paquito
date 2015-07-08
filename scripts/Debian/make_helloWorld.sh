#!/bin/bash

# ----- DEBIAN -----
# Packaging Paquito
docker build -t debian:paquito - < deb-packaging-paquito.Dockerfile 
# We have to run a image to create a container (and get the package)
ID=$(docker run -t debian:paquito /bin/cat /etc/hostname)
ID=${ID::-1} # Remove the last char (return)
docker cp $ID:/paquito/paquito_0.2_amd64.deb create-HelloWorld/

# Packaging HelloWorld
cd create-HelloWorld/
tar -zcvf deb-tar-HelloWorld.tar.gz Dockerfile paquito_0.2_amd64.deb src-test*
cd ..
docker build -t debian:paquito - < create-HelloWorld/deb-tar-HelloWorld.tar.gz
ID=$(docker run -t debian:paquito /bin/cat /etc/hostname)
ID=${ID::-1} # Remove the last char (return)
docker cp $ID:/hello-world/helloworld_1.2_amd64.deb test-HelloWorld/
docker cp $ID:/hello-world/helloworld_1.2_x86_64-test.deb test-HelloWorld/

# Test HelloWorld
cd test-HelloWorld/
tar -zcvf deb-tar-Test.tar.gz Dockerfile composer.json helloworld_1.2_amd64.deb helloworld_1.2_x86_64-test.deb
cd ..
docker build -t debian:paquito - < test-HelloWorld/deb-tar-Test.tar.gz
