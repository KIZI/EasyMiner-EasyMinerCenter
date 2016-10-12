#!/bin/bash

sed -i -- "s/easyminer-backend/$HTTP_SERVER_NAME/g" /var/www/html/easyminercenter/app/config/config.local.neon

apache2-foreground