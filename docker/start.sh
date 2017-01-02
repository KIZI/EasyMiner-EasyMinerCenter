#!/bin/bash

if [[ $HTTP_SERVER_NAME = "" || $HTTP_SERVER_NAME = ":8894" || $HTTP_SERVER_NAME = "\$HTTP_SERVER_ADDR:8894" ]]; then
    echo "ERROR: You have to set up $HTTP_SERVER_NAME variable!"
    exit 1
fi

export DBPREFIX="$(pwgen 6 1)_"

sed -i -- "s/easyminer-backend/$HTTP_SERVER_NAME/g" /var/www/html/easyminercenter/app/config/config.local.neon
sed -i -- "s/emc_/$DBPREFIX/g" /var/www/html/easyminercenter/app/config/config.local.neon

php /root/db.php $DBPREFIX

apache2-foreground