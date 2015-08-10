#!/bin/bash

#create the directory of the repository
mkdir -p /var/www/lighttpd/centos/stable/{i386,x86_64}

# Entropy (GPG wants enough entropy to create a key)
rngd -r /dev/urandom

 #run lighttpd
 systemctl start lighttpd.service

 # Generate a key
 #create ~/.gnupg if it doesn’t exist
 [[ -d ~/.gnupg ]] || mkdir -p -m 700 ~/.gnupg
#start the gpg-agent daemon
 gpg-agent --daemon --use-standard-socket --pinentry-program /usr/bin/pinentry-curses
 #generate-key
 gpg --gen-key 
cd /var/www/lighttpd/centos
#export public key in file
#this file is necessary in order to allow us to import it in database RPM
gpg --export -a > RPM-GPG-KEY

cp RPM-GPG-KEY  /etc/pki/rpm-gpg/
#import the key into the RPM database
rpm --import RPM-GPG-KEY
#Add the GPG signing details to your rpm environment
echo "%_signature gpg" > ~/.rpmmacros
echo "%_gpg_name sarah" >> ~/.rpmmacros
cd stable
							    
#create the repository
 createrepo .

