# docker build -t easyminer-frontend  https://github.com/KIZI/EasyMiner-EasyMinerCenter.git#:docker
# docker run -d -p 8894:80 --name easyminer-frontend easyminer-frontend

FROM php:5.6.21-apache

MAINTAINER kizi "stanislav.vojir@gmail.com"

WORKDIR /var/www/html

RUN apt-get update && \
    apt-get install -y pwgen git php5-curl php5-xsl php5-mysql mcrypt php5-mcrypt libmcrypt-dev libxslt-dev libcurl4-openssl-dev zziplib-bin zlib1g-dev && \
    docker-php-ext-install -j$(nproc) curl xsl mysql mcrypt zip pdo pdo_mysql sockets mysqli && \
    a2enmod headers && \
    a2enmod rewrite && \
    a2enmod proxy_http
    
RUN git clone --recursive https://github.com/KIZI/EasyMiner-EasyMinerCenter.git#v2.4 easyminercenter && \
    chmod -R 777 easyminercenter/log && \
    chmod -R 777 easyminercenter/temp && \
    chmod 777 easyminercenter/www/images/users && \
    touch easyminercenter/app/config/config.local.neon && \
    chmod 666 easyminercenter/app/config/config.local.neon
    
ADD easyminer.conf /etc/apache2/sites-enabled
ADD proxy.conf /etc/apache2/mods-enabled
ADD db.php /root
ADD start.sh /root

WORKDIR easyminercenter

RUN php -r "readfile('https://getcomposer.org/installer');" | php && \
    php composer.phar update && \
    chmod 775 /root/start.sh && \
    mkdir temp/pmmlImports/cloud && \
    chmod 777 temp/pmmlImports/cloud

ADD config.local.neon /var/www/html/easyminercenter/app/config
    
EXPOSE 80

CMD ["/root/start.sh"]