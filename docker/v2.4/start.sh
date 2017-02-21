#!/bin/bash

export DBPREFIX="$(pwgen 6 1)_"

sed -i -- "s/emc_/$DBPREFIX/g" /var/www/html/easyminercenter/app/config/config.local.neon

php /root/db.php $DBPREFIX

apache2-foreground