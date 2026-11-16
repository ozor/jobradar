# AGENTS.md

This is a Symfony project. Check `composer.json` for the exact Symfony/PHP version
in use, and read `symfony.lock` to see which recipes ran. Don't assume Doctrine,
Twig, API Platform, Messenger, or Lock are installed unless one of those says so.

## Ask before generating

If the task doesn't specify, ask rather than guess:

- Persistence: Doctrine ORM, Doctrine ODM, or none?
- Interface: server-rendered (Twig), API (Serializer, maybe API Platform), or both?
- Auth: SecurityBundle, and which authenticator?

If you can't ask (no interactive channel), state the assumption you're making and
pick the smallest option (e.g. no persistence layer) rather than scaffolding a
full stack nobody asked for.

## Adding features: Flex, not hand-wiring

- Never add, remove or upgrade composer dependencies unless the task explicitly asks for it.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients, schedulers),
  check whether a Symfony component covers it. If it does and it isn't installed,
  propose it in the plan and wait for approval instead of installing it.
- When a package is approved, install it with
  `docker compose exec php composer require <package>` and let the Flex recipe
  register the bundle and generate its config. Don't hand-edit `config/bundles.php`.

## Conventions

Follow https://symfony.com/doc/current/best_practices.html to write idiomatic
Symfony:

- Use PHP attributes for framework metadata, and not only on controllers:
  `#[Route]`, `#[MapRequestPayload]`, `#[IsGranted]` on actions, `#[Assert\...]`
  on properties, `#[AsCommand]`, `#[AsEventListener]`, `#[AsMessageHandler]`, and
  `#[AsAlias]` / `#[AsTaggedItem]` / `#[Autoconfigure]` on services. No YAML or
  XML routing.
- Rely on autowiring and autoconfiguration. Type-hint constructor arguments and
  let the container resolve them. Where a type-hint can't express it, stay in the
  class with `#[Autowire]` (parameters, env vars, expressions) or `#[Target]` (one
  of several implementations of an interface). A YAML service definition is the
  last resort, not the first.
- Controllers extend `AbstractController`, stay thin, and delegate to services.
- Use the framework for what it already does: Form for server-rendered forms,
  Validator for validation, Serializer for JSON, Messenger for async work,
  Security (voters, authenticators) for access control, Twig `path()`/`url()`
  instead of hardcoded URLs.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand. 
  If they aren’t installed, propose them in the plan.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion (propose it if not installed). 
  A hand-built flag or lock file looks fine in review and is usually wrong under concurrency.

## Everyday workflow

- - Everything runs in Docker. Start the stack with `docker compose up -d --wait`.
  Run commands inside the php container: `docker compose exec php bin/console ...`,
  `docker compose exec php composer ...`. Never use `symfony serve`, the Symfony CLI
  or a local PHP binary.
- HTTP is served by nginx; the host port comes from `NGINX_PORT` in `.env`.
- When something fails, read `var/log/dev.log`.
- If `maker-bundle` is installed, prefer `docker compose exec php bin/console make:*` with every argument
  passed up front and `--no-interaction` where supported: makers prompt on a
  terminal by default, which hangs a non-interactive shell. If a maker still
  needs interactive input, hand-write the code instead.
- If Doctrine ORM is installed, schema changes go through migrations
  (`docker compose exec php bin/console make:migration`, 
  then `docker compose exec php bin/console doctrine:migrations:migrate`), never
  `docker compose exec php bin/console doctrine:schema:update` or hand-written SQL.
- `.env` is committed and holds defaults only. Real secrets belong in `.env.local`
  (git-ignored) or the secrets vault (`docker compose exec php bin/console secrets:set`), read via
  `%env(...)%`.
- Do not add composer dependencies unless the task explicitly asks for one.

## Testing

Run everything through the Makefile (it wraps `docker compose exec -T php`);
start the stack first with `make up`.

- `make test` runs PHPUnit. Pass extra arguments with `ARGS`:
  `make test ARGS="--testsuite Functional"`, `make test ARGS="--filter HealthControllerTest"`.
- `make test` first runs `make test-db`, which creates the `jobradar_test`
  database if it is missing and applies migrations to it. Don't create it by hand.
- Tests run against the real Postgres, Redis and RabbitMQ from `compose.yaml`.
- Layout (one testsuite each in `phpunit.xml.dist`):
  - `tests/Unit`: plain `TestCase`, no kernel, no I/O.
  - `tests/Integration`: `KernelTestCase`, real services from the container.
  - `tests/Functional`: `WebTestCase`, HTTP requests through the kernel.
  Mirror the `src/` path, e.g. `src/Shared/UI/Http/HealthController.php` is tested in
  `tests/Functional/Shared/UI/Http/HealthControllerTest.php`.

A feature isn't done until it has a test that exercises it the way a caller would,
an HTTP request for a controller or a service call for a service, not just
"it didn't throw."

## Code style and quality gates

`make check` runs `cs`, `stan`, `deptrac` and `test` in that order, stops at the
first failure, and must pass before every PR (budget: under 60 seconds).

- `make cs` / `make cs-fix`: PHP-CS-Fixer with `@PER-CS` + `@Symfony` and
  `declare(strict_types=1)` required in every PHP file. Global classes, functions
  and constants are written fully qualified (`\Throwable`, `\sprintf`), not imported.
- `make stan`: PHPStan at level `max` with strict-rules and the Symfony and
  Doctrine extensions. There is no baseline and `@phpstan-ignore` is not allowed:
  fix the code. If the fix would change behaviour, stop and ask.
- `make deptrac`: architecture rules, two configs:
  - `deptrac.contexts.yaml`: Ingestion, Enrichment, Matching and Delivery must not
    depend on each other, only on `Shared/Contract` and `Shared/Kernel`.
  - `deptrac.domain.yaml`: no `*/Domain/*` class may depend on `Symfony\` or
    `Doctrine\`, attributes included (no `#[ORM\...]` or `#[Assert\...]` in Domain).
  Don't weaken a rule or add skip_violations to make a check pass.
- `make rector` (dry-run) / `make rector-fix`: Rector with PHP 8.4 and Symfony
  sets. Not part of `make check`; apply only as a separate change a human asked for.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `docker compose exec php bin/console about`: versions, environment, paths.
- `docker compose exec php bin/console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `docker compose exec php bin/console lint:container`, plus `lint:twig templates/` and
  `lint:yaml config/` where those packages are installed: validate before running.
- Read the installed source and docblocks under `vendor/`.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs).
