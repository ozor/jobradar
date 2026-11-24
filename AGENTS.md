# AGENTS.md: JobRadar

JobRadar imports vacancies from public job board APIs, extracts structured
attributes with an LLM and scores vacancies against a candidate profile.
PHP 8.4, Symfony 8.1, Doctrine ORM, PostgreSQL 17 with pgvector, Redis,
RabbitMQ via Symfony Messenger.

- Design: `docs/architecture.md` (contexts, layers, data flow, open questions).
- Reasons: `docs/adr/`. Don't undo a decision recorded in an ADR. If a task
  seems to need that, stop and ask.
- Open questions in `docs/architecture.md` are not decided. If a task depends
  on one, stop and ask instead of choosing.

## Commands

Everything runs in Docker. Never use a local PHP binary, the Symfony CLI or
`symfony serve`.

- `make up`: start the stack and install Composer dependencies.
- `make install`: after pulling a changed `composer.lock`.
- `make check`: `cs`, `stan`, `deptrac`, `test` in that order; stops at the
  first failure. Must be green before you report a task as done.
- `make test ARGS="--filter HealthControllerTest"` or
  `make test ARGS="--testsuite Unit"`: a subset of tests.
- `make cs-fix`: fix code style. Don't format by hand.
- Console and Composer: `docker compose exec php bin/console ...`,
  `docker compose exec php composer ...`.
- HTTP goes through nginx on the host port `NGINX_PORT` from `.env`.
- When something fails at runtime, read `var/log/dev.log`.

## Where code goes

- Bounded contexts: `src/Ingestion`, `src/Enrichment`, `src/Matching`,
  `src/Delivery`, each split into `Domain`, `Application`, `Infrastructure`,
  `UI`. What each layer may contain and depend on: `docs/architecture.md`.
- Contexts never use each other's classes or tables. They talk only through
  message classes in `src/Shared/Contract`. Framework-free primitives shared
  by several contexts go to `src/Shared/Kernel`.
- A value object used by one context lives in that context's `Domain`. It
  moves to `src/Shared/Kernel` only when a message in `Shared/Contract`
  carries it.
- Domain is plain PHP: no Symfony and no Doctrine classes, attributes
  included. Doctrine mapping lives in Infrastructure.
- `src/Kernel.php`, `src/Schedule.php`, `src/Shared/Infrastructure` and
  `src/Shared/UI` are platform code. Contexts must not depend on them.
- `src/Controller`, `src/Entity`, `src/Repository` and the Doctrine mapping
  of `src/Entity` are skeleton leftovers. Put nothing there.
- Each context owns one PostgreSQL schema. No queries and no foreign keys
  across schemas; refer to another context's data by ID.

## Conventions the tools don't check yet

- Global functions and constants are fully qualified: `\sprintf()`,
  `\count()`, `\JSON_THROW_ON_ERROR`.
- Value objects and DTOs are `final readonly` with constructor promotion.
  Existing classes in `src/Shared` are not final yet; don't copy that.
- Money and salaries: `Brick\Money\Money` for amounts with a currency,
  `Brick\Math\BigDecimal` for other decimals. Never `float`.
- Time: inject `Psr\Clock\ClockInterface`. No argument-less
  `new \DateTimeImmutable()` in Domain or Application.
- A value object checks its invariants in the constructor and throws.
  Symfony Validator is for input at the UI boundary, never in Domain.
- Message handlers are idempotent: the same message delivered twice creates
  no duplicates and repeats no side effects (ADR 0003). Every handler has a
  redelivery test.
- A message carries the vacancy ID plus only the fields its spec names.
- Every network client (HTTP, database, Redis, AMQP, LLM) sets explicit
  connect and read timeouts.
- Symfony: attributes for routes, commands, message handlers and listeners;
  autowiring first, then `#[Autowire]` or `#[Target]`; a YAML service
  definition is the last resort. Controllers are thin and call Application.
- Before hand-writing infrastructure (locks, caches, rate limits, HTTP
  clients, schedules), check for a Symfony component. If it isn't installed,
  propose it in the plan and wait.

## Database

- Schema changes go through Doctrine migrations:
  `docker compose exec php bin/console doctrine:migrations:diff`, review the
  generated SQL, then `doctrine:migrations:migrate`. `make test` migrates the
  test database itself.
- Never `doctrine:schema:update`. Never edit a migration that is on
  `master`: add a new one.

## Tests

- Suites in `phpunit.xml.dist`:
  - `tests/Unit`: plain `TestCase`, no kernel, no I/O. Domain is tested here.
  - `tests/Integration`: `KernelTestCase` with real services.
  - `tests/Functional`: `WebTestCase`, HTTP requests through the kernel.
- Mirror the `src/` path: `src/Shared/UI/Http/HealthController.php` is
  tested in `tests/Functional/Shared/UI/Http/HealthControllerTest.php`.
- Tests run against the real PostgreSQL, Redis and RabbitMQ from
  `compose.yaml`. `make test` creates `jobradar_test` if it is missing;
  don't create it by hand.
- Test behaviour the way a caller sees it: an HTTP request for a controller,
  a method call for a service, a dispatched message for a handler.
- Don't change or delete an existing test to make it pass. If a test looks
  wrong, stop and ask.

## Quality gates

- Never weaken a check to get green: no `@phpstan-ignore`, no baseline, no
  `skip_violations`, no loosened Deptrac rule or tool config. Fix the code.
  If the fix changes behaviour, stop and ask.
- PHPStan runs at level `max` with strict rules: write exact types and array
  shapes from the start.
- Rector is not part of `make check`. Run `make rector-fix` only when asked,
  as a separate change.

## Boundaries

- Composer dependencies: add, remove or upgrade only when the task names the
  package. Install with `docker compose exec php composer require <package>`
  and let the Flex recipe write the config; don't edit `config/bundles.php`.
- `.env` holds committed defaults only. Never read or write `.env.local`,
  `.env.*.local` or `config/secrets/`.
- `.env` is the single source of configuration: don't copy its values into
  `environment:` of the `php` service. A changed `POSTGRES_*` takes effect
  only on a recreated volume, so ask before changing them.
- Published ports in `compose.yaml` bind to `127.0.0.1` (ADR 0006).
- Change `compose.yaml`, `docker/`, `Makefile`, CI and tool configs only when
  the task is about them.

## Look it up, don't guess

Framework APIs change between versions. Check the installed code, not memory:

- `bin/console about`, `debug:router`, `debug:container`,
  `debug:autowiring <name>`, `debug:config <bundle>`,
  `config:dump-reference <bundle>`, `lint:container`.
- Source and docblocks under `vendor/`; https://symfony.com/doc/current/
  (match the version in `composer.json`).
- Versions of Docker images, GitHub Actions and packages come from their
  registry or release page, not from memory. Name the source in the plan.

## Definition of Done

1. `make check` is green.
2. New behaviour has a test that fails without it.
3. The final message says what was done, what wasn't, and which assumptions
   you made.

## Git

- Branch from `master`; pull requests target `master`.
- Conventional Commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`,
  `build:`, `ci:`, `chore:`.
- Never push or merge. A human pushes, opens the pull request and merges.
