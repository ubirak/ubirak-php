FROM ubirak/php:7.2.0

ARG COMPOSER_AUTH

ENV PATH="$PATH:/home/php/app/vendor/bin:/home/php/app/Tests/testapp" \
    SYMFONY_DEBUG="0" \
    SYMFONY_ENV="prod"

COPY .*atoum*.php behat.yml composer.json ./
COPY features ./features
COPY entrypoint.sh ./entrypoint.sh
COPY Domain ./Domain
COPY Infra ./Infra
COPY Tests ./Tests
COPY Ui ./Ui
COPY vendor ./vendor

ENTRYPOINT ["/home/php/app/entrypoint.sh"]
