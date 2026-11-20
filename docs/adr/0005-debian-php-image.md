# 5. Debian-based PHP image

Date: 2026-11-20

## Status

Accepted

## Context

The PHP image is where our code runs and where extensions are compiled from source
(`redis`, and `amqp` on top of the C library `librabbitmq`). The official PHP images come
in two flavours: Debian, with glibc, and Alpine, with musl.

musl is smaller but behaves differently from glibc in places that matter here:
`iconv` with `//TRANSLIT` fails in the official Alpine images, locale support is minimal,
and prebuilt Linux binaries usually target glibc. Phase D may bring native AI tooling,
which is almost always built for glibc.

## Decision

The official PHP-FPM image on Debian 13 (trixie), pinned to a patch version in
`docker/php/Dockerfile` (currently `php:8.4.27-fpm-trixie`).

Third-party services where we compile nothing and run official images over the network
(nginx, Redis, RabbitMQ) may use their official Alpine images.

## Consequences

- Compiled extensions and Linux binaries work without musl-specific issues.
- The image is larger. That does not matter for a development image with cached layers.
- PostgreSQL also runs on trixie, so both images share the same base system.
- Revisit for a production image, where size and attack surface matter more;
  multi-stage builds on Debian slim come first there.
