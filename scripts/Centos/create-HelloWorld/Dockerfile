FROM centos:6


ADD paquito-0.2-1.el6.x86_64.rpm .
RUN yum -y update
RUN yum -y install openssl
RUN yum -y install php-cli git curl rpmdevtools rpm-build php-process gcc-c++
RUN rpm -i paquito-0.2-1.el6.x86_64.rpm
RUN git clone https://github.com/sarahhadbi/hello-world 
RUN cp hello-world/pconf_HelloWorld.yml .
RUN cp -R hello-world/src /src
RUN ls -al
ADD src-test-user/ src-test-user/
RUN paquito generate-test pconf_HelloWorld.yml
RUN ls -l
