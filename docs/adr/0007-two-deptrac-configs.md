# 7. Two Deptrac configs: contexts and layers

Date: 2026-11-20

## Status

Accepted

## Context

The architecture has two independent sets of rules:

- Contexts: Ingestion, Enrichment, Matching and Delivery do not depend on each other,
  only on `Shared/Contract` and `Shared/Kernel` (ADR 0002).
- Layers: inside every context, Domain is plain PHP without Symfony and Doctrine,
  and dependencies point inwards.

Every class sits on both axes at once. Deptrac checks dependencies only between declared layers;
undeclared classes, including vendor ones, are "uncovered" and not checked.
One config covering both axes needs a layer for every context × layer pair
(about twenty) and a ruleset that mixes both concerns.

## Decision

Two configs, each answering one question, both run by `make deptrac`:

- `deptrac.contexts.yaml`: context isolation. Vendor classes are not declared, so contexts may use
  any library. A `Platform` layer collects every other `App\` class (`Kernel`, `Schedule`,
  `Shared/Infrastructure`, `Shared/UI`), so contexts cannot depend on them unnoticed.
- `deptrac.domain.yaml`: layer rules. Symfony and Doctrine are declared layers, and Domain
  may not depend on them, attributes included.

Each config has its own cache file.

## Consequences

- Each config reads in a minute. A new context touches only `deptrac.contexts.yaml`: one layer,
  one ruleset line and the `Platform` exclusion; `deptrac.domain.yaml` picks it up by path.
- A failure names its config, so it is clear whether a context boundary or a layer rule broke.
- Two runs instead of one; seconds in practice.
- `Platform` may depend on every context, so leftover skeleton directories
  (`src/Controller`, `src/Entity`, `src/Repository`) are a loophole and get removed.
- The layer config checks only Domain today. Application, Infrastructure and UI rules
  (UI depends on Application, not on Domain) are added in Module 1.
