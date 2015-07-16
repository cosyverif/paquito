FROM centos:6

COPY helloworld-1.2-1.el6.x86_64.rpm .
COPY helloworld-test-1.2-1.el6.x86_64.rpm .
COPY tcc-9999-115.1.x86_64.rpm .
RUN yum -y update
RUN ls -al
RUN yum -y install openssl git  
RUN rpm -i tcc-9999-115.1.x86_64.rpm
RUN rpm -i  helloworld-1.2-1.el6.x86_64.rpm
RUN rpm -i  helloworld-test-1.2-1.el6.x86_64.rpm
RUN ls -l
