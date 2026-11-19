DC       := docker compose
PHP      := $(DC) exec -T php
CONSOLE  := $(PHP) bin/console
COMPOSER := $(PHP) composer
ARGS     ?=

.PHONY: up install check test test-db cs cs-fix stan deptrac rector rector-fix

up:
	$(DC) up -d --wait
	$(COMPOSER) install --no-interaction --no-progress

install:
	$(COMPOSER) install --no-interaction --no-progress

# Quality gate: must pass before every PR. Prerequisites run left to right
# (cheapest first) and stop at the first failure.
check: cs stan deptrac test

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

rector:
	$(CONSOLE) cache:warmup --env=dev --quiet
	$(PHP) vendor/bin/rector process --dry-run --no-progress-bar

rector-fix:
	$(CONSOLE) cache:warmup --env=dev --quiet
	$(PHP) vendor/bin/rector process --no-progress-bar
