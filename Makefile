DC      := docker compose
PHP     := $(DC) exec -T php
CONSOLE := $(PHP) bin/console
ARGS    ?=

.PHONY: up test test-db

up:
	$(DC) up -d --wait

test: test-db
	$(PHP) vendor/bin/phpunit --no-progress $(ARGS)

test-db:
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists --no-interaction
	$(CONSOLE) doctrine:migrations:migrate --env=test --allow-no-migration --no-interaction