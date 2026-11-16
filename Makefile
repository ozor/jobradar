DC      := docker compose
PHP     := $(DC) exec -T php
CONSOLE := $(PHP) bin/console
ARGS    ?=

.PHONY: up test test-db cs cs-fix stan deptrac

up:
	$(DC) up -d --wait

test: test-db
	$(PHP) vendor/bin/phpunit --no-progress $(ARGS)

test-db:
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists --no-interaction
	$(CONSOLE) doctrine:migrations:migrate --env=test --allow-no-migration --no-interaction

cs:
	$(PHP) vendor/bin/php-cs-fixer check --diff --show-progress=none

cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix --show-progress=none

stan:
	$(CONSOLE) cache:warmup --env=dev --quiet
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=1G

deptrac:
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.contexts.yaml --cache-file=var/cache/deptrac.contexts.cache --no-progress
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.domain.yaml --cache-file=var/cache/deptrac.domain.cache --no-progress
