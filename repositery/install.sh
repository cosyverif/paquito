#!/bin/bash

tmpfile=$(mktemp)
echo -e "%echo Generating a default key\nKey-Type: 1\nKey-Length: 2048\nName-Real: Inria Debian\nName-Comment: Dépôt Paquito INRIA\nName-Email: debian@inria.fr\nExpire-Date: 0\n%commit\n%echo done" > $tmpfile

# Create the directory of the repository
mkdir -p /var/www/debian/{conf,incoming}

# Entropy (GPG wants enough entropy to create a key)
echo "HRNGDEVICE=/dev/urandom" >> /etc/default/rng-tools
/etc/init.d/rng-tools start

# Generate a key
gpg --batch --gen-key $tmpfile
gpg --armor --export debian@inria.fr --output > /var/www/debian/repository_paquito.gpg.key

# Add virtualhost to Lighttpd
echo -e '$HTTP["host"] =~ "^.+$" {\n\tserver.document-root = "/var/www/debian"\n\tserver.errorlog = "/var/log/lighttpd/debian-repo/error.log"\n\taccesslog.filename = "/var/log/lighttpd/debian-repo/access.log"\n\tserver.error-handler-404 = "/e404.php"\n}' >> /etc/lighttpd/lighttpd.conf

# Create the directory in order to allow Lighttpd to log
mkdir /var/log/lighttpd/debian-repo
chown www-data /var/log/lighttpd/debian-repo/
/etc/init.d/lighttpd restart

# Configure reprepro
echo -e "Origin: apt.inria.fr\nLabel: apt repository\nCodename: jessie\nArchitectures: i386 amd64\nComponents: main\nDescription: debian package repo\nSignWith: yes" > /var/www/debian/conf/distributions
cd /var/www/debian
reprepro -V export
reprepro -V processincoming incoming
