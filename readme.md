## Other commands

php bin/console make:entity
php bin/console make:migration

php bin/console lexik:jwt:generate-keypair --overwrite
php bin/console cache:clear
composer install
php bin/console doctrine:migrations:migrate
symfony server:start

php bin/console debug:router

## To download events

php bin/console app:download-events
php bin/console app:download-theatre-repertuar
php bin/console app:download-cinema-repertuar

## to enable xdebug

edit in php.ini

zend_extension="C:\xampp\php\ext\php_xdebug.dll"
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003
xdebug.client_host = 127.0.0.1

and reset server symfony

## for front

npm run dev

---
