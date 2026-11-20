# 6. Published ports bound to 127.0.0.1

Date: 2026-11-20

## Status

Accepted

## Context

The short syntax `"28080:80"` in `compose.yaml` publishes a port on every network interface
of the host. Docker writes its own iptables rules: published traffic is redirected
to the container and passes through the `FORWARD` chain, so ufw rules in `INPUT` do not apply.

The development credentials are weak and public (`app` / `app` in `.env`), and Redis has no password.
In Module 0 an agent generated the short syntax, and the plan passed review before a human noticed.

## Decision

Every published port in `compose.yaml` is bound to loopback:
`"127.0.0.1:${PORT}:<container port>"`.

Containers talk to each other over the Compose network by service name and do not need
published ports. Published ports exist only for tools on the developer's machine:
browser, `psql`, IDE database tools, the RabbitMQ management UI.

## Consequences

- PostgreSQL, Redis and RabbitMQ are not reachable from the local network or the internet,
  so the weak development credentials are acceptable.
- The protection lives in the repository, not in the configuration of one machine,
  and works the same for every developer and agent.
- The rule is easy to break by accident; Module 1 turns it into a check
  (`docker compose config` plus `jq` in `make check`).
- Keep Docker Engine up to date: older versions allowed reaching loopback-bound ports from
  neighbouring hosts through direct routing.
