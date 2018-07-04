FROM centos:7

# Setup
RUN yum -y install epel-release wget
RUN cd /tmp && wget http://jp2.php.net/get/php-7.2.7.tar.gz/from/this/mirror -O php-7.2 && tar zxvf php-7.2

# Dependency packages installation
RUN yum -y install git gcc gcc-c++ make libxml2-devel libicu-devel openssl-devel

# A PHP installation
RUN cd /tmp/php-7.2.7 && \
    ./configure --enable-maintainer-zts --enable-pcntl --enable-intl --enable-zip --enable-pdo --enable-sockets --with-openssl && \
    make && \
    make install

# A pthreads installation
RUN yum -y install autoconf
RUN cd /tmp && git clone https://github.com/krakjoe/pthreads.git && cd pthreads && \
    phpize && \
    ./configure && \
    make && \
    make install

# Add an extension to php.ini
RUN echo extension=pthreads.so >> /usr/local/lib/php.ini
