# JobRadar

Job aggregator and matcher for PHP/Symfony roles, built as a hands-on project in AI-assisted software engineering.

JobRadar collects vacancies from public job board APIs, uses an LLM to extract structured attributes (tech stack, seniority, remote policy, allowed countries and time zones, salary, relocation) and ranks vacancies against a candidate profile.

> **Status:** early setup (Module 0). Nothing is runnable yet — see [Roadmap](#roadmap).

## Why this project exists

The goal is not only the product but the process: a full AI-assisted SDLC in which coding agents (Claude Code, Codex, GitHub Copilot) work under engineering discipline:

- specs before code (spec-driven development);
- implementation plans reviewed by a human before any code is written;
- tests as executable acceptance criteria for agents;
- deterministic quality gates — static analysis, architecture rules, mutation testing — instead of trusting model output;
- AI and human code review on every pull request;
- architecture decision records and an AI work log with real metrics.

## Architecture

A modular monolith with four bounded contexts that communicate only through asynchronous messages.

| Context | Responsibility |
| --- | --- |
| Ingestion | Source adapters, raw storage, deduplication, scheduling |
| Enrichment | LLM-based attribute extraction, embeddings |
| Matching | Rule-based scoring plus semantic similarity, match explanation |
| Delivery | REST API, daily digest, MCP server for AI agents |

Details will live in `docs/architecture.md`.

## Tech stack

- PHP 8.4, Symfony, Doctrine ORM
- PostgreSQL with pgvector, Redis
- Symfony Messenger with RabbitMQ, Symfony Scheduler
- Symfony AI (Platform, Agent, Store), MCP PHP SDK
- PHPUnit, PHPStan (max level), PHP-CS-Fixer, Rector, Deptrac, Infection
- Docker Compose, GitHub Actions

## Getting started

> Available once Module 0 is complete.

```bash
git clone https://github.com/<your-username>/jobradar.git
cd jobradar
make up      # start the Docker stack
make check   # code style, static analysis, architecture rules, tests
```

## How AI agents work in this repository

| Path | Purpose |
| --- | --- |
| `AGENTS.md` | Shared instructions for all coding agents |
| `CLAUDE.md` | Claude Code specifics; imports `AGENTS.md` |
| `specs/` | Feature specifications, implementation plans, task lists |
| `docs/adr/` | Architecture Decision Records |
| `docs/ai-log/` | Work log: what agents did, where they failed, which rules or checks changed as a result |

Every change goes through a pull request into `master`. Merging is always a human decision.

## Roadmap

- [ ] **Phase A — Foundation:** environment, quality toolchain, context engineering for agents
- [ ] **Phase B — Feature cycle:** spec-driven development, TDD with agents, quality gates, AI code review in CI
- [ ] **Phase C — Scale:** agents in legacy code, reliability and failure handling, skills, subagents and MCP, issue-to-PR delegation
- [ ] **Phase D — AI in the product:** LLM enrichment, evals, MCP server, v1.0 release

## Data sources

Vacancies come from public job board APIs such as [Arbeitnow](https://arbeitnow.com/blog/job-board-api/) and [Remotive](https://remotive.com/remote-jobs/api). Every listing links back to its original posting, and each source's terms of use (attribution, request limits) are part of the feature specification.