FROM php:5.6.21-apache

MAINTAINER kizi "stanislav.vojir@gmail.com"

WORKDIR /var/www/html

RUN apt-get update && \
    apt-get install -y git php5-curl php5-xsl php5-mysql mcrypt php5-mcrypt libmcrypt-dev libxslt-dev libcurl4-openssl-dev zziplib-bin zlib1g-dev && \
    docker-php-ext-install -j$(nproc) curl xsl mysql mcrypt && \
    a2enmod headers && \
    a2enmod rewrite
    
RUN git clone --recursive -b newDataService https://github.com/KIZI/EasyMiner-EasyMinerCenter.git easyminercenter && \
    chmod -R 777 easyminercenter/log && \
    chmod -R 777 easyminercenter/temp && \
    chmod 777 easyminercenter/www/images/users && \
    touch easyminercenter/app/config/config.local.neon && \
    chmod 666 easyminercenter/app/config/config.local.neon
    
ADD easyminer.conf /etc/apache2/sites-enabled

WORKDIR easyminercenter

RUN php -r "readfile('https://getcomposer.org/installer');" | php && \
    php composer.phar update
    
EXPOSE 80

CMD ["apache2-foreground"]