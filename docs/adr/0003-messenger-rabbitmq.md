# 3. Symfony Messenger with RabbitMQ

Date: 2026-11-20

## Status

Accepted

## Context

Contexts must not call each other (ADR 0002), so they need messaging. The pipeline includes slow,
paid and unreliable steps: external job board APIs and LLM calls in Enrichment.
These steps need retries, and a failure in one context must not block the others.

Options considered:

- Synchronous calls between contexts: simplest, but couple the contexts and make one LLM timeout
  fail the whole import.
- Messenger with the Doctrine transport: no extra service, but a queue in the main database
  and polling instead of push.
- Messenger with Redis: Redis is already in the stack, but it is a cache first,
  and its delivery guarantees and tooling for queues are weaker.

## Decision

Symfony Messenger with RabbitMQ (AMQP transport, `ext-amqp`) for all messages between contexts.

- Each event is a message class in `src/Shared/Contract`.
- Every handler is idempotent: redelivery must not create duplicates or repeat side effects.
- Retries with exponential backoff, then a failure transport (designed in Module 7).

## Consequences

- A slow or failing step is retried in the background and does not block the others;
  each context can get its own worker.
- One more service in the stack, and `ext-amqp` compiled in the PHP image (ADR 0005).
- Idempotency is mandatory and needs a test for every handler.
- Writing to the database and publishing an event are not atomic yet; the transactional outbox
  is an open question for Module 7.
- Revisit if the operational cost of RabbitMQ outweighs its benefits, for example if the
  pipeline stays small enough for the Doctrine transport.
