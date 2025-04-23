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

## for local jwt and oauth

lexik_jwt_authentication:
secret_key: "%env(resolve:JWT_SECRET_KEY)%"
public_key: "%env(resolve:JWT_PUBLIC_KEY)%"
pass_phrase: "%env(JWT_PASSPHRASE)%"
token_ttl: 20368000
user_identity_field: email
--
knpu_oauth2_client:
clients:
google:
type: google
client_id: "%env(OAUTH_GOOGLE_CLIENT_ID)%"
client_secret: "%env(OAUTH_GOOGLE_CLIENT_SECRET)%"
redirect_route: connect_google_check
redirect_params: {}

## for front

npm run dev

---
