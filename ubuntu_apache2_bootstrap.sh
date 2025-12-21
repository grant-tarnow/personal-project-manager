#! /bin/bash

apt update
apt install sqlite3 php libapache2-mod-php apache2 apache2-doc -y

mkdir /var/www/ppm
chown -R $USER:$USER /var/www/ppm

touch /etc/apache2/sites-available/ppm.conf
cat > /etc/apache2/sites-available/ppm.conf << 'EOF'
<VirtualHost *:80>
    ServerName ppm
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/ppm/public
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

cp --recursive public/ model/ ppm/ view/ util/ /var/www/ppm/
mkdir --parents /var/www/ppm/db
cp db/README.md /var/www/ppm/db/

chown -R www-data:www-data /var/www/ppm/db

a2dissite 000-default
a2ensite ppm
systemctl reload apache2

