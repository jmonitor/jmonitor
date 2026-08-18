---
name: changelog-entry
description: Use before writing or editing any line under `## [Unreleased]` in CHANGELOG.md — the conciseness rules, and the log of entries that were sent back as too long.
---

# Changelog entries

Whether a change deserves an entry at all is decided in `.claude/CLAUDE.md`
("Changelog"). This skill is only about how the line is written.

**One sentence. What the user notices, nothing else.**

## Rules

- One sentence per entry, no second clause. Aim under 15 words.
- Cut the tail: if the sentence still stands without its last clause, that
  clause goes. This is the mistake to expect — the urge to add where the user
  lands, what now happens instead, why it broke.
- No mechanism, no cause, no class or file name, no "so that…". That belongs in
  the PR body or the commit message.
- The user's vocabulary (what they see in the app), not the diff's.
- Suffix `(cloud)` / `(self-hosted)` only when the line applies to one edition.

## Sent back as too long

Kept as they come, verbatim, so the same tail is not written twice.

**2026-08-17** — admin host redirect loop:

> ~~Opening the admin panel without admin rights no longer loops the browser on
> redirects: **the user is sent back to the dashboard**~~
>
> Opening the admin panel without admin rights no longer loops the browser on
> redirects.

The tail restates the fix as mechanism. That the loop is gone is the whole news;
where the browser ends up is not the user's problem.
