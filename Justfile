install:
    composer install --no-interaction
    bin/console doctrine:database:create
    bin/console doctrine:schema:update --force
    bin/console doctrine:fixtures:load --no-interaction
    APP_ENV=test bin/console doctrine:database:create
    APP_ENV=test bin/console doctrine:schema:update --force
start:
    symfony serve
test:
    just install
    vendor/bin/phpunit --fail-on-incomplete
    vendor/bin/phpstan
