# 2. Modular monolith with bounded contexts

Date: 2026-11-20

## Status

Accepted

## Context

JobRadar has four parts with different reasons to change: Ingestion (source APIs), Enrichment (LLM),
Matching (scoring rules) and Delivery (API, digest, MCP). One developer builds and deploys it.

Options considered:

- Microservices: independent deployment, but a network, a deployment and a database per service.
  That is operational cost with no benefit for one developer and one deployment.
- A classic layered monolith (`src/Controller`, `src/Entity`, `src/Service`): cheapest to start,
  but nothing stops Matching from querying Ingestion's tables, and the parts grow into each other.

## Decision

A modular monolith on Symfony with four bounded contexts: `src/Ingestion`, `src/Enrichment`,
`src/Matching`, `src/Delivery`.

- Contexts communicate only through asynchronous messages (ADR 0003). Message classes live in
  `src/Shared/Contract`; small framework-free primitives in `src/Shared/Kernel`.
- Each context owns one PostgreSQL schema. No queries and no foreign keys across schemas;
  references between contexts are plain IDs.
- Delivery serves its API from its own read model, built from the events it consumes.
- Boundaries are enforced by Deptrac (ADR 0007), not by convention.

## Consequences

- Any context can later be extracted into a separate service without rewriting the others.
- The database does not guarantee that an ID from another context exists. Deletions and archiving
  travel as events, and consumers clean up their own data.
- Data is duplicated across contexts, and Delivery's read model is eventually consistent:
  a vacancy may briefly appear with an old score.
- Revisit if the contexts will clearly never be split and the duplication costs more than it protects,
  or if eventual consistency becomes visible to the user.
