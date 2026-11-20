# 4. pgvector instead of a separate vector database

Date: 2026-11-20

## Status

Accepted

## Context

Enrichment stores an embedding for each vacancy, and Matching uses semantic similarity
for scoring and "similar vacancies". The expected scale is tens of thousands of vacancies.
Similarity queries are combined with ordinary filters: country, remote policy, status.

Options considered:

- A dedicated vector database (Qdrant, Weaviate and others): built for large scale,
  but one more service, data synchronisation between two stores, and no transactions across them.
- pgvector in the existing PostgreSQL 17.

## Decision

pgvector in PostgreSQL, using the `pgvector/pgvector` image with a pinned version.
Vectors live in the `enrichment` schema.

## Consequences

- One database: embeddings are written in the same transaction as the attributes,
  and vector search is combined with SQL filters in one query.
- No extra service to run, back up or keep in sync.
- The extension must exist in every database, including the test one. Today
  `docker/postgres/init/01-extensions.sql` enables it in the main database and in `template1`,
  which new databases inherit. The script runs only when the volume is created, so an existing
  volume needs `CREATE EXTENSION` by hand; a Doctrine migration can take over when the first
  vector column appears.
- The embedding model and vector dimension are not chosen yet (Phase D). Changing the model later
  means re-embedding every vacancy.
- Revisit if the data grows by orders of magnitude or vector queries start to slow down
  the transactional workload.
