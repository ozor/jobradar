# 1. Record architecture decisions

Date: 2026-11-20

## Status

Accepted

## Context

JobRadar is built with coding agents (Claude Code, Codex, Copilot). 
Agents and new contributors see the code but not the reasons behind it: 
why the PHP image is Debian rather than Alpine, why host ports bind to 127.0.0.1, why Deptrac has two configs.
Without the reasons, an agent may "simplify" a deliberate decision, and a reviewer has nothing to point to.

## Decision

I record architecturally significant decisions as Architecture Decision Records, following Michael Nygard's format:
https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions

- One decision per file in `docs/adr/`, named `NNNN-short-title.md`, numbered sequentially.
- Sections: Status, Context, Decision, Consequences.
- Status is one of: Proposed, Accepted, Deprecated, Superseded by ADR-NNNN.
- An accepted ADR is not rewritten. A changed decision gets a new ADR that supersedes the old one.
- ADRs go through pull requests, like code.

## Consequences

- Agents get the reasons, not only the rules. AGENTS.md can link to an ADR instead of repeating it.
- Writing an ADR takes time, so we write one only for decisions that are costly to reverse or easy to undo by accident.
- Decisions made in Module 0 (Debian base image, localhost-only ports, two Deptrac configs) are recorded retroactively in Module 1.
