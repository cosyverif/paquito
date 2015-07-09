FROM centos:6

ADD paquito-0.2-1.el6.x86_64.rpm .
RUN yum -y update
RUN yum -y install openssl
RUN yum -y install php git curl rpmdevtools rpm-build php-process gcc-c++
RUN rpm -i paquito-0.2-1.el6.x86_64.rpm
RUN git clone -b docker https://github.com/CosyVerif/paquito.git
RUN git clone https://github.com/saucisson/hello-world 
RUN cp paquito/src/pconf_HelloWorld.yml hello-world/
WORKDIR hello-world/
RUN ls -al
ADD src-test/ src-test/
ADD src-test-user/ src-test-user/
RUN paquito generate-test pconf_HelloWorld.yml
RUN ls -l
