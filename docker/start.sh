#!/bin/bash

sed -i -- "s/easyminer-backend/$HTTP_SERVER_NAME/g" /var/www/html/easyminercenter/app/config/config.local.neon
php /root/db.php

apache2-foreground