2020-09-01 08:41

# running php upgrade inspect see: https://github.com/silverstripe/silverstripe-upgrader
cd /var/www/ss3/upgrades/ecommerce-root
php /var/www/ss3/upgrader/vendor/silverstripe/upgrader/bin/upgrade-code inspect /var/www/ss3/upgrades/ecommerce-root/geoip/src  --root-dir=/var/www/ss3/upgrades/ecommerce-root --write -vvv
Writing changes for 0 files
Running post-upgrade on "/var/www/ss3/upgrades/ecommerce-root/geoip/src"
[2020-09-01 20:41:10] Applying ApiChangeWarningsRule to Geoip.php...
[2020-09-01 20:41:10] Applying UpdateVisibilityRule to Geoip.php...
Writing changes for 0 files
✔✔✔