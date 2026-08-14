# Releasing

Maintainer procedure. Contributors never tag or bump a version — they only add
a line under `## [Unreleased]` in `CHANGELOG.md` (see `CONTRIBUTING.md`).

A release is a self-hosted image version, published by
`.github/workflows/docker-publish.yml` on `v*` tags. The cloud edition is
continuously deployed and does not follow these versions.

## Steps

1. Curate `## [Unreleased]` in `CHANGELOG.md`: rewrite the accumulated lines so
   they read as release notes, and move anything a self-hoster must do by hand
   (new env var, long migration, changed default) under an `### Upgrade notes`
   heading at the top of the entry, before the other sections. The self-hosted
   README points readers at that heading, so it has to be there when it matters.
   These lines are also what the dashboard's "What's new" card shows once the tag
   is out, each edition seeing only the lines that apply to it.
2. Rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD`, using the date you are
   tagging, and add a fresh empty `## [Unreleased]` above it.
3. Update the link definitions at the bottom of the file: point `[Unreleased]`
   at `compare/vX.Y.Z...HEAD` and add a `[X.Y.Z]` line for the new tag.
4. Commit, then `git tag vX.Y.Z && git push origin master --tags`.
5. Watch the run. It re-checks that the changelog has an entry for the tag
   before building, pushes the multi-arch image to Docker Hub and ghcr, then
   opens the GitHub release with that changelog section as its body.

## Choosing the tag

Use plain `vX.Y.Z` tags. A prerelease tag (`v1.0.0-rc.1`) produces no `latest`
image tag, which breaks the `${JMONITOR_VERSION:-latest}` default of the
self-hosted Compose stack.

## If the run fails

The release is created last, after the image push succeeds, so a failure there
leaves the image published: create the release by hand rather than re-tagging.
A failure in the changelog check happens within seconds of the run starting,
before any build — fix the changelog, delete the tag, tag again.
