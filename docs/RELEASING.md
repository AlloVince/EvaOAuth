# Releasing EvaOAuth 2.x

Release work is a maintainer action. This checklist does not claim that a version, tag, Packagist publication, remote CI run or live provider acceptance already exists.

## Preconditions

- Review [ADR](ADR-001.md), [architecture](ARCHITECTURE.md), [migration](../UPGRADING.md) and current [acceptance evidence](ACCEPTANCE.md). Resolve every pending engineering gate before tagging.
- Supported runtimes are PHP 8.2, 8.3, 8.4 and 8.5. Keep Composer's range and all four CI jobs synchronized; raising minimum PHP or changing the facade requires a migration note.
- Review every dependency change, including League/Guzzle transitive changes, against wire-level OAuth1/OAuth2 tests. Do not bypass audit, ignore advisories, add a PHPStan baseline or suppress general style failures to make a release green.
- Ensure the lock file is tracked for reproducible contributor/CI installs. It is excluded from distribution archives because this is a library; consumers resolve dependencies in their own project.

## Clean-checkout verification

Run in a clean checkout with Composer 2 and development extensions required by PHPUnit:

```sh
composer install --no-interaction --prefer-dist
composer verify
composer validate --strict
```

Verify runs `test`, `analyse`, `style`, strict Composer validation and `composer audit --locked --abandoned=fail`. Network access is needed for install/audit, not for protocol or documentation tests. Audit conclusions are time-dependent and must be repeated at release time.

Require successful remote `.github/workflows/verify.yml` jobs for all supported PHP versions and the lowest-dependency job. Do not substitute local PHP 8.5 success for PHP 8.2–8.4 results. The lowest job resolves with `--prefer-lowest --prefer-stable` and runs the same verification; it must remain a blocking check, including security audit. If declared minimum versions are vulnerable or incompatible, review and raise the affected minimum rather than disabling audit.

For manual lowest-dependency testing, use a disposable checkout so the normal lock is not overwritten:

```sh
composer update --prefer-lowest --prefer-stable --no-interaction
composer install --no-interaction
composer verify
```

## Package and documentation review

- Verify autoload resolves only `Eva\EvaOAuth\` from `lib/`; no legacy src classes or full framework dependency should be needed.
- Run `composer archive --format=zip` in a disposable checkout and inspect the resulting archive. `.gitattributes` excludes tests, temporary files, CI configuration, development configs, lock and internal task/review files (`TASK.md`, `docs/TASK_REVIEW.md`); obsolete src/examples have been deleted. Runtime PHP must come only from `lib/`, alongside Composer metadata, license and user documentation—not tests, tooling or vendored dependencies. Confirm `lib/`, Composer metadata, LICENSE, both READMEs, CHANGELOG, SECURITY, UPGRADING and final architecture/acceptance/releasing docs remain included. Also inspect for secrets and generated artifacts, and confirm no untracked file (caches, local reports, editor state) is picked up.
- Do not add a hard-coded Composer `version`; Git tags supply package versions. Validate package description, BSD-3-Clause license and declared runtime support.
- Execute `composer test -- --filter DocumentationTest` after every README PHP change. All PHP fences are exercised, not merely syntax-checked; adding/reordering a fence requires updating its test scenario. Examples use placeholders and PSR-18 mocks, never embedded credentials or a demo website.
- Manually inspect the first README screen: secure active session, GitHub authorization, callback and result must remain understandable. Confirm both language versions describe the same public API and operational responsibilities.
- Recheck official provider documentation before release, especially endpoint, scope, consent and refresh behavior. Google endpoints were checked against official discovery and web-server docs on 2026-09-17; this is not a live-login guarantee.

## Legacy cleanup completed

With explicit cleanup authorization, removed `src/`, `examples/`, `tests/EvaOAuthTest/`, `tests/Bootstrap.php`, `tests/report/`, `tmp/`, `.travis.yml`, `.coveralls.yml`, `.scrutinizer.yml`, `makefile`, `phpdoc.xml` and the obsolete `docs/.gitignore`. The 1.x implementation remains available in Git history; LICENSE, ADR and migration guidance are retained. Internal `docs/STATUS-core.md` and `docs/STATUS-oauth1.md` were removed after incorporating protocol boundaries and OAuth1 supported-request limitations into ARCHITECTURE and the READMEs. Runtime autoload uses `lib/`, and verification uses `tests/Modern/`; no legacy website is shipped. `TASK.md` and `docs/TASK_REVIEW.md` stay in the repository as internal history but are excluded from distribution archives.

## Manual integration and publication

A maintainer should use private staging app registrations to check GitHub, Google and Flickr consent, denied callbacks, session continuity, callback routing and local account resolution over HTTPS. Check Google offline refresh/re-consent and app consent-screen status. Do not put credentials or raw request traces into issues or acceptance evidence; record only non-secret outcomes. This is application/provider integration validation, not a new website deliverable.

Once clean checks, remote matrix and package review are complete, update acceptance evidence with actual commands/results and links to the successful workflow run. Review the complete release diff, approve a semantic 2.x version, then create the release commit/tag and publication through the maintainer's normal process. No release commands here authorize automated commits or pushes. Confirm the tag appears on Packagist and test installation in a fresh consumer project before announcing it. Include breaking API changes, required reauthorization cases and security responsibilities in release notes.
