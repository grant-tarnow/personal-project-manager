#! /bin/bash

cp --recursive public/ model/ ppm/ view/ util/ /var/www/ppm/
mkdir --parents /var/www/ppm/db
cp db/README.md /var/www/ppm/db/
