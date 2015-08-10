#!/bin/bash

# Create the directory of the repository
mkdir -p /var/www/debian/{conf,incoming,temp}

# Entropy (GPG wants enough entropy to create a key)
echo "HRNGDEVICE=/dev/urandom" >> /etc/default/rng-tools
/etc/init.d/rng-tools start

# Generate a key
gpg --gen-key 
gpg --armor --export sarah@gmail.com --output > /var/www/debian/repository_paquito.gpg.key

# Add virtualhost to Lighttpd
echo -e '$HTTP["host"] =~ "^.+$" {\n\tserver.document-root = "/var/www/debian"\n\tserver.errorlog = "/var/log/lighttpd/debian-repo/error.log"\n\taccesslog.filename = "/var/log/lighttpd/debian-repo/access.log"\n\tserver.error-handler-404 = "/e404.php"\n}' >> /etc/lighttpd/lighttpd.conf

# Create the directory in order to allow Lighttpd to log
mkdir /var/log/lighttpd/debian-repo
chown www-data /var/log/lighttpd/debian-repo/
/etc/init.d/lighttpd restart

# Configure reprepro
# create file conf/distributions
echo -e "Origin: apt.gmail.com\nLabel: apt repository\nCodename: jessie\nArchitectures: i386 amd64\nComponents: main\nDescription: debian package repo\nSignWith: yes" > /var/www/debian/conf/distributions
# create file conf/incoming
echo -e "Name: incoming\nIncomingDir: /var/www/debian/incoming\nTempDir: /var/www/debian/temp\nAllow: jessie" > /var/www/debian/conf/incoming
cd /var/www/debian
reprepro -V export
reprepro -V processincoming incoming
