# EvaOAuth 2.0 acceptance evidence

Acceptance pass executed on 2026-09-27 against commit `dc6ed6f` on branch
`feature/evaoauth-2.0`. Local runtime: PHP 8.5.11 (Homebrew), Composer 2.10.3, macOS (darwin).
Every `[PASS]` below cites a test, source file, executable example, documentation statement or an
observed command/CI run. Claims carried over from earlier reports were re-derived from code and
re-executed; nothing here is inherited from a previous review.

**Release status: BLOCKED.** The three live-provider gates (GitHub, Google, Flickr OAuth1) were not
performed, because no OAuth application credentials or provider account sessions were available on
the acceptance machine and the maintainer chose to skip them. Everything else in the release gate
list is `[PASS]`. See [Live provider verification](#live-provider-verification-not-performed).

## Observed commands

| Command | Where | Observed result |
| --- | --- | --- |
| `composer install --no-interaction --prefer-dist` | fresh `git clone` of the branch into a clean directory | PASS: 32 packages resolved from the committed lock, autoload generated |
| `composer verify` | same clean clone, PHP 8.5.11 | PASS: 131 tests / 1037 assertions, PHPStan level 6 no errors, PHPCS 0 issues, `./composer.json is valid`, `No security vulnerability advisories found.` |
| `composer validate --strict` | same clean clone | PASS: `./composer.json is valid` |
| `composer audit --locked --abandoned=fail` | same clean clone | PASS: no advisories, no abandoned packages |
| `composer update --prefer-lowest --prefer-stable` | second clean clone | PASS: resolved league/oauth1-client `1.11.0`, league/oauth2-client `2.9.1`, guzzlehttp/guzzle `7.15.2`, guzzlehttp/psr7 `2.13.0`, psr/http-message `2.0`, psr/http-client `1.0.2`, psr/log `3.0.0`, phpunit `11.5.50`, phpstan `2.1.0` |
| `composer verify` | lowest-dependency clone, PHP 8.5.11 | PASS: 131 tests / 1037 assertions, all five stages |
| `composer verify` | CI runs `36306265436` (`dc6ed6f`) and `36306377223` (`59f7a74`), five jobs each | PASS: PHP 8.2, 8.3, 8.4, 8.5 locked and PHP 8.2 lowest, all `success`; lowest job reports `OK (131 tests, 1037 assertions)` |
| `composer archive --format=zip` | release candidate worktree | PASS: 45 files, see [Distribution archive](#distribution-archive) |
| `composer test -- --filter DocumentationTest` | local | PASS: all 9 PHP fences in each README executed against mocked HTTP |

CI evidence for the code and documentation state at `59f7a74`: run
<https://github.com/AlloVince/EvaOAuth/actions/runs/36306377223> (head SHA
`59f7a74b5eef8d91e578d0a1c6e4d8a80998ec4c`, started 2026-09-27T08:29:56Z, conclusion `success`, all
five jobs). The identical matrix was green one commit earlier on the code change itself, run
`36306265436` on `dc6ed6f`, where the lowest job reports `OK (131 tests, 1037 assertions)`.
`composer audit` results are time-dependent and must be repeated immediately before tagging, and the
release commit needs its own green run for its own SHA.

## Capability checklist

Paths without a directory prefix are under `lib/`; test methods live in `tests/Modern/` of the
source repository, which is not part of the distribution archive.

| Status | Capability | Evidence |
| --- | --- | --- |
| [PASS] | PHP 8.2–8.5 support | `composer.json` requires `~8.2.0 \|\| ~8.3.0 \|\| ~8.4.0 \|\| ~8.5.0`; CI jobs for each version green in runs `36306265436` and `36306377223`; no version-specific syntax in `lib/` |
| [PASS] | OAuth2 Authorization Code + S256 PKCE | `OAuth2Test::testCodePkceIdentityAndReplay` asserts the wire request (endpoint, `Content-Type`, `Accept`, grant fields, verifier) and the SHA-256 challenge relationship; `HardenedOAuth2Provider::getRandomPkceCode` uses `random_bytes` |
| [PASS] | State validation, one use, browser bound | `OAuth2Test::testBrowserAndConfigurationBinding`, `testMalformedCallbackConsumed`, `testConcurrentAttemptsHaveIndependentVerifiers`; `OAuth::doExchange` consumes through `StateStore` before any exchange |
| [PASS] | Refresh grant | `OAuth2Test::testRefreshRetentionRotationAndNoVerifier`, `testRefreshKeepsEpochExpiredSemantics`; Google refresh snippet executed by `DocumentationTest` |
| [PASS] | OAuth1 three-legged flow | `OAuth1Test::testCompleteFlickrFlowAndReplay` (request token → consent → access token → identity) and `testConcurrentAttemptsKeepTheirOwnSecrets` |
| [PASS] | OAuth1 signature correctness | `OAuth1Test::testRfc5849PhotoSignatureVector` (RFC 5849 §3.4.1.1 example) plus independent reference-signature recomputation in the flow and form tests; `Engine/OAuth1Signature.php` |
| [PASS] | GitHub provider | `Provider/GitHub.php`; code-flow test; both README GitHub fences executed by `DocumentationTest` against a real secure PHP session |
| [PASS] | Google provider | `Provider/Google.php`; `OAuth2Test::testGoogleMappingAndOfflineAuthorization`, `testIssuerValidation`; UserInfo `sub` mapped by `Provider/Google::identity` |
| [PASS] | Flickr provider | `Provider/Flickr.php`; `OAuth1Test::testCompleteFlickrFlowAndReplay`; `flickr.test.login` mapped to id/username with optional email/avatar absent |
| [PASS] | Unified `Token` | `Token.php`; `CoreTest::testTokenRedactionAndExplicitPersistence`; `OAuth2Test::testTokenValidationAndPersistence`, `testExpiryBoundarySemantics`; JSON/debug redaction, `__serialize` rejection |
| [PASS] | Unified `Identity` / `AuthorizationResult` | `Identity.php`, `AuthorizationResult.php`; asserted in the GitHub, Google, Flickr and generic-provider tests and in the README snippets |
| [PASS] | Provider extension without protocol code | `tests/Modern/Fixture/DemoProvider.php` + `ProviderExtensionTest::testProviderOnlyDeclaresConfigurationAndIdentityMapping` (only `__construct` and `identity()` are overridden) and `testNewProviderRunsTheSharedFlowWithoutProtocolCode` |
| [PASS] | Exception model | `Exception/` (five fixed subclasses, constant messages, no previous exception) asserted by `OAuth2Test::testStrictTokenValidation`, `testMapperExceptionsAreWrapped`, `OAuth1Test::testMalformedExchangeResponsesAreSanitized` |
| [PASS] | PSR-18 / PSR-3 injection | `OAuth::__construct`, `Http/Transport.php`; `DocumentationTest` injects a PSR-18 mock and a PSR-3 logger and executes the authorized-request and logging snippets |
| [PASS] | Origin-restricted authorized requests | `Http/UrlPolicy.php`; `OAuth2Test::testResourceOriginPolicy` (8 forbidden URL shapes), `OAuth1Test::testUnsupportedNormalizationRejectedBeforeSending` |
| [PASS] | Bounded, expiring, atomic state store | `State/BoundedStateStore.php`, `SessionStateStore.php`; `OAuth2Test::testSessionStateStoreRequiresActiveSession`, `OAuth1Test::testTokenMismatchBrowserBindingAndRedirectBinding` |
| [PASS] | Callback shape, issuer, unknown extensions | `OAuth2Test::testUnknownCallbackParametersIgnored`, `testIssuerValidation`, `testPathBearingIssuerRequiresExactCallbackMatch`, `testIssuerRejectedWhenProviderHasNoIssuer`, `testIssuerRejectsForbiddenUrlComponents` (11 rejected issuer URLs) |
| [PASS] | README executability | `DocumentationTest::testEveryReadmePhpFenceExecutes` — 9 fences per README, both languages, executed unchanged (only the `<?php` tag is stripped) |

## Design review gates

| Gate | Verdict | Evidence |
| --- | --- | --- |
| Debug useful **and** safe | [PASS] | `OAuth2Test::testTraceIdentifiesProviderStageAndCategory` asserts the exact key set, provider alias, stage and failure category for a denied callback, a replayed state, a provider HTTP error, an identity failure and a transport error, and asserts the serialized trace contains no URL, credential, code or error text; `OAuth1Test::testSuccessfulFlowLogsExcludeEveryCredential`; `OAuth2Test::testNoRedirectAndSafeTraceAndTransportException`; `DocumentationTest` asserts the success schema |
| Token/provider binding decision | [PASS] | `OAuth2Test::testTokenBindingSurvivesCredentialRotationButNotProviderIdentityChange` — secret rotation, endpoint/scope/option changes keep a stored token usable; a different client id, callback URI or provider class does not; `OAuth2Test::testBrowserAndConfigurationBinding` still rejects a configuration change mid-flow; `testWrongTokenBinding` covers `refresh()`, `user()` and `request()`; decision documented in `docs/ARCHITECTURE.md`, both READMEs and `UPGRADING.md` |
| `callback()` failure semantics | [PASS] | `OAuth2Test::testIdentityFailureConsumesCallbackAndRecoversWithExchangeThenUser` — a failed identity request consumes the callback, the replay is refused, and `exchange()` + `user()` recovers; documented as a deliberate one-shot design in `docs/ARCHITECTURE.md` with the recovery pattern in both READMEs |
| Provider extension simplicity | [PASS] | `ProviderExtensionTest` (above) plus the README "Add an OAuth2 provider" section, whose example is executed by `DocumentationTest` |
| README 5-minute usability | [PASS] | First screen is a single runnable file: session → `new OAuth([...])` → `authorize()` → 302 → `callback()` → `$result->token` / `$result->user->id|name|email`; both READMEs execute it verbatim; OAuth1 and OAuth2 share that API and are documented as the same boundary; security internals (binding hashes, state-store mechanics, engine adapters) stay in `docs/ARCHITECTURE.md` |

## Trace schema reference

Emitted at `debug` level, metadata only, values from fixed allowlists:

```text
oauth.http.response {provider, stage, method, status, duration_ms}
oauth.http.failure  {provider, stage, method, category: "transport", duration_ms}
oauth.failure       {provider, stage, category, duration_ms}
```

`provider` is the registry alias, `stage` ∈ {`authorize`, `request_token`, `token_exchange`,
`token_refresh`, `identity`, `resource_request`, `callback`}, `category` ∈ {`callback`,
`configuration`, `provider`, `transport`, `unsupported`}, `method` is an allowlisted verb or `OTHER`.
`oauth.failure` is emitted once per public call that ends in an `OAuthException`; its category comes
from the exception class, never from its message. No URL, host, header, body, query value, provider
message or exception chain is recorded.

## Distribution archive

`composer archive --format=zip` on `dc6ed6f` produced 45 files:

- runtime PHP from `lib/` only (33 files at the package root, matching the `Eva\EvaOAuth\` PSR-4 prefix);
- `composer.json` (no hard-coded `version`), `LICENSE`;
- user documentation: `README.md`, `README_CN.md`, `UPGRADING.md`, `CHANGELOG.md`, `SECURITY.md`,
  `docs/ARCHITECTURE.md`, `docs/ADR-001.md`, `docs/ACCEPTANCE.md`, `docs/RELEASING.md`,
  `docs/FUTURE.md`.

Excluded and verified absent: `tests/`, `tmp/`, `.cache/`, `.github/`, `composer.lock`,
`phpunit.xml.dist`, `phpstan.neon`, `phpcs.xml.dist`, `.gitattributes`, `TASK.md`,
`docs/TASK_REVIEW.md`, `.DS_Store`, `.phpunit.result.cache`. A recursive scan of the extracted
archive for private keys, `gh*` tokens, real client ids and test credentials found nothing. The
archive also revealed that `composer archive` includes untracked working-tree files, which is why the
stray `RELEASE_ASSESSMENT.md` review draft was moved out of the repository before packaging.

## Live provider verification (not performed)

These gates are **not** passed. No live authorization was executed, so no statement about real
provider behaviour is claimed anywhere in this repository.

| Gate | Status | What is missing |
| --- | --- | --- |
| GitHub OAuth2 (authorize → consent → callback → token → identity, plus one user cancellation) | NOT PERFORMED | an OAuth app client id/secret and an interactive GitHub session |
| Google OAuth2 (authorize → callback → token → identity, refresh if a refresh token is issued, `sub` mapping, one refusal) | NOT PERFORMED | an OAuth client id/secret, a configured consent screen and an interactive Google session |
| Flickr OAuth1 (request token → authorization → verifier → access token → identity) | NOT PERFORMED | an API key/secret and an interactive Flickr session |

A local HTTPS harness (self-signed certificate on `127.0.0.1:8443`, outside this repository) was
prepared to run the README flow unchanged and verified to route `/login/{provider}` and
`/callback/{provider}`, start a strict secure session and redirect to
`github.com/login/oauth/authorize`; it was not exercised against any provider because no credentials
were provided. Mock-based tests cannot substitute for these checks: they prove protocol
correctness, not a real app registration, consent screen, redirect delivery or account policy.

To close these gates, register a throwaway OAuth app per provider with the callback URI
`https://<host>/callback/<provider>`, put the credentials in the environment outside the repository,
run the harness, and record only non-secret outcomes (`id`, `name`, whether `email` is null, refresh
behaviour, and the observed failure class of a cancelled callback). Never commit credentials, tokens
or callback query strings.

## Remaining release gates

1. [BLOCKED] GitHub, Google and Flickr live verification (above).
2. [PASS] `composer verify`, `composer validate --strict`, `composer audit --locked --abandoned=fail`
   in a clean checkout — re-run immediately before tagging because audit findings are time-dependent.
3. [PASS] CI matrix and lowest-dependency job — runs `36306265436` and `36306377223`; re-run if the
   release commit changes any file.
4. [PASS] Documentation set: `CHANGELOG.md`, `SECURITY.md`, `UPGRADING.md`, `docs/ACCEPTANCE.md`
   (this file), `docs/ARCHITECTURE.md`, `docs/ADR-001.md`, `docs/RELEASING.md`, `docs/FUTURE.md`.
5. [ ] Maintainer actions still outstanding: create tag `2.0.0`, publish the GitHub Release from
   `CHANGELOG.md`, confirm Packagist shows `2.0.0`, and install it into a fresh consumer project with
   `composer require evaengine/eva-oauth:^2.0` to confirm the tag (not a dev branch) resolves and
   that the README's minimal authorize/callback example runs there.

## Verification boundary

Protocol tests assert real PSR request methods, endpoints, headers, form fields and signatures against
mocked responses, not live services. `DocumentationTest` evaluates the actual extracted PHP of both
READMEs; fixtures enter only through the public PSR-18 injection point, and a real PHP session is
started, closed, reopened and regenerated. PHP CLI executes header calls but does not prove browser
redirect delivery, cookie or proxy deployment, consent screens or provider uptime. Google endpoints
and scopes were re-checked against the official web-server guide and discovery metadata; that is a
documentation check, not a live login.
