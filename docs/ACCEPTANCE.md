# EvaOAuth 2.0 acceptance evidence

Final local engineering evidence: on 2026-09-18 using PHP 8.5.10, dependencies were reinstalled after removing vendor and the lock file; install and verification passed. After the final issuer-validation changes, the complete verify chain passed again with 122 tests and 885 assertions. The command results below record local execution, not a remote workflow. Earlier documentation and archive checks are identified separately. `[PASS]` means the cited test, source, example or documentation supports the capability; it does **not** imply successful remote CI, live provider login or publication. Remote matrix execution and live staging remain pending.

## Observed commands

| Command | Observed result |
| --- | --- |
| `composer install` | PASS: fresh dependencies resolved/installed on 2026-09-18, followed by an install from the resulting lock |
| `composer verify` | PASS: final complete chain, PHP 8.5.10; 122 tests, 885 assertions |
| `composer test` | PASS: PHPUnit stage of that verify run, 122 tests and 885 assertions |
| `composer analyse` | PASS: PHPStan level 6, no errors in that verify run |
| `composer style` | PASS: PHPCS reported zero issues in that verify run |
| `composer validate --strict` | PASS: Composer metadata and lock valid in the parent run |
| `composer audit --locked --abandoned=fail` | PASS: no vulnerability advisories at the parent check time |
| `composer test -- --filter DocumentationTest` | PASS: separately executed documentation checks; every PHP fence in both READMEs is exercised |

Earlier style blockers have been resolved. `phpcs.xml.dist` applies PSR-12 to `lib/` and all `tests/Modern/` without general error exclusions. `phpstan.neon` uses level 6 with only `missingType.iterableValue` disabled; no blanket ignore or baseline is used. Audit findings are time-dependent, so repeat the checks before release.

## Final integration recheck

The completed issuer validation accepts HTTPS issuer paths and compares callback issuers exactly. The final combined tree passes `composer verify`: 122 tests, 885 assertions, PHPStan with no errors, PHPCS with no issues, strict dependency validation and security audit. A transient missing-method error observed during concurrent editing is resolved; the results here apply to the completed implementation.

## Capability checklist

| Status | Capability | Reproducible evidence |
| --- | --- | --- |
| [PASS] | Modern PHP 8.x configuration | `composer.json` restricts PHP to 8.2–8.5; strict/native-type code in `lib/`; local tests and analysis on PHP 8.5.10. Other runtimes require the pending CI matrix |
| [PASS] | OAuth2 Authorization Code | `OAuth2Test::testCodePkceIdentityAndReplay`: authorization URL, token POST endpoint, content type, Accept, code, client credentials, redirect, bearer user request and parsed result |
| [PASS] | PKCE | Same test verifies actual verifier/challenge SHA-256 relationship; `testConcurrentAttemptsHaveIndependentVerifiers`; `Engine/HardenedOAuth2Provider.php` forces S256 and CSPRNG verifier |
| [PASS] | State Validation | `OAuth2Test::testBrowserAndConfigurationBinding`, `testMalformedCallbackConsumed`, code-flow replay test; `OAuth.php` validates and consumes transactions before exchange |
| [PASS] | Refresh Token | `OAuth2Test::testRefreshRetentionRotationAndNoVerifier`: refresh fields, auth, expiry, scope and retention/rotation; DocumentationTest executes Google refresh and persistence snippets |
| [PASS] | OAuth1 | `OAuth1Test::testCompleteFlickrFlowAndReplay`: temporary credentials → consent → access credentials → signed user request; asserts nonce, timestamp, method, headers, verifier encoding and independent reference signatures |
| [PASS] | OAuth1 signature correctness | `OAuth1Test::testRfc5849PhotoSignatureVector`, `testAuthorizedFormSignaturePreservesPercentEncoding`; `Engine/OAuth1Signature.php` |
| [PASS] | GitHub Provider | `Provider/GitHub.php`; OAuth2 code-flow test; both first-screen README snippets execute start/callback using an actual secure PHP session in DocumentationTest |
| [PASS] | Google Provider | `Provider/Google.php`; `OAuth2Test::testGoogleMappingAndOfflineAuthorization`; DocumentationTest verifies offline scopes, UserInfo mapping and refresh endpoint |
| [PASS] | OAuth1 Provider | `Provider/Flickr.php`; complete Flickr test and both READMEs' executed Flickr snippets; id/username mapped, optional email/avatar absent |
| [PASS] | Unified Token | `Token.php`; `CoreTest::testTokenRedactionAndExplicitPersistence`; both protocol result assertions; `OAuth2Test::testWrongTokenBinding` |
| [PASS] | Unified Identity | `Identity.php`, `AuthorizationResult.php`; GitHub/Google/Flickr callback tests and custom mapper test |
| [PASS] | Extensible Provider Model | `OAuth2Test::testGenericProviderBasicAuthenticationAndMapper`; DocumentationTest executes the README custom provider and obtains mapped identity through a full mocked callback |
| [PASS] | Safe Debug Logging | `OAuth2Test::testNoRedirectAndSafeTraceAndTransportException` rejects secret/URL leakage; DocumentationTest asserts exact success metadata keys and PSR-3 events; `Http/Transport.php` uses an allowlist |
| [PASS] | Exception Model | `Exception/` fixed subclasses; OAuth2 malformed token/callback/binding and transport tests assert rejection and sanitized exceptions |
| [PASS] | PSR HTTP / logger injection | `Http/Transport.php`, `OAuth` constructor; DocumentationTest injects PSR-18 mock and PSR-3 logger, then executes authorized PSR-7 request snippet |
| [PASS] | Security operational guidance | Both READMEs document session locking, callback secrecy, encrypted persistence, refresh races, origin policy, account linking, transport constraints and provider responsibilities |
| [PASS] | composer verify | Fresh-dependency run on 2026-09-18: all stages passed; `composer.json` verify script, `phpunit.xml.dist`, `phpstan.neon`, `phpcs.xml.dist` and `tests/Modern/` provide reproducible command/source evidence |
| [PASS] | GitHub Actions configuration | `.github/workflows/verify.yml`: install + verify on PHP 8.2/8.3/8.4/8.5, plus PHP 8.2 lowest dependencies; read-only repository permissions. This is source evidence only |
| [PENDING] | GitHub Actions execution | No remote workflow run was started or observed during this task; maintainer must attach actual successful run evidence |
| [PASS] | Migration Guide | `UPGRADING.md` maps Service, authorization, callback/token/user, Provider, Storage, debug, HTTP, exceptions and old token records; documents deliberate removals |
| [PASS] | README Quick Start | `tests/Modern/DocumentationTest.php` extracts all nine PHP fences from each README and executes them; verifies secure session options, session regeneration, login result, refresh, Flickr and custom provider/request/logging |
| [PASS] | Release documentation | `docs/RELEASING.md` contains clean checkout, matrix/lowest, archive, migration, staging and completed cleanup gates |

### Verified core integration

| Status | Behavior | Test / source evidence |
| --- | --- | --- |
| [PASS] | Response scope separator | `OAuth2Test::testResponseScopeSeparatorOnWire`; `Provider/OAuth2Provider.php`, `Provider/GitHub.php` and `Engine/OAuth2Engine.php` separate authorization scopes from token-response scopes (space default, comma for GitHub) |
| [PASS] | Trusted issuer validation | `OAuth2Test::testIssuerValidation`, `testIssuerAbsentFallsBackToStateBinding`, `testIssuerRejectedWhenProviderHasNoIssuer`; `OAuth.php`, `State/OAuth2Transaction.php` and `Provider/Google.php` bind and validate supplied `iss` |
| [PASS] | Callback extensions | `OAuth2Test::testUnknownCallbackParametersIgnored`; recognized-field validation in `OAuth.php` remains strict while unknown OAuth2 extension parameters are ignored |
| [PASS] | Immediate expiry | `OAuth2Test::testExpiryBoundarySemantics`; `Engine/HardenedOAuth2Provider.php`, `Engine/OAuth2Engine.php` and `Token.php` preserve immediate expiry for `expires_in=0` rather than converting it to unknown expiry |
| [PASS] | Modern dependency/autoload boundary | Fresh-dependency `composer install` and verify; `composer.json` runtime dependencies and `lib/` autoload; obsolete src/examples/bootstrap removed as recorded in RELEASING |

Paths in the tables without a directory prefix refer to files under `lib/`; test methods refer to `tests/Modern/` in the source repository. Tests and development tools are excluded from the production archive, so reproduce these checks from the repository, not an installed distribution package.

## Documentation verification boundary

The documentation tests evaluate the actual extracted PHP, removing only the opening PHP tag. Fixtures enter through the public PSR-18 injection point, not replacement OAuth classes. Both GitHub start and callback branches execute; a real PHP session is closed/reopened and regenerated. The Google token is advanced to an expired persisted record to exercise the refresh conditional without sleeping. Flickr request/access credentials and identity are mocked; the custom provider goes through code exchange, mapper, PSR-18 authorized request and PSR-3 events. New/reordered PHP fences require an explicit scenario update.

PHP CLI executes header calls but does not prove browser redirect delivery or cookie/proxy deployment. Tests make no live OAuth requests and do not validate a real app registration, consent screen, provider uptime or account policy. These remain staging/release checks, not unsupported PASS claims.

## Official Google research

On 2026-09-17, fetched:

- [Google web-server OAuth guide](https://developers.google.com/identity/protocols/oauth2/web-server): code flow, exact redirect matching, offline access, refresh and re-consent behavior.
- [Google discovery metadata](https://accounts.google.com/.well-known/openid-configuration): authorization `https://accounts.google.com/o/oauth2/v2/auth`, token `https://oauth2.googleapis.com/token`, UserInfo `https://openidconnect.googleapis.com/v1/userinfo`, S256 support and both client_secret_post/basic methods.

These match `Provider/Google.php`. No Google SDK/framework was added; identity is fetched from UserInfo rather than accepted from an unvalidated ID token.

## Remaining release gates

1. [PENDING] Execute the remote PHP 8.2/8.3/8.4/8.5 matrix and lowest-dependency job, then attach actual workflow evidence. Local PHP 8.5 success does not establish those results.
2. [PENDING] Perform private staging consent/session/callback checks for GitHub, Google and Flickr, including Google refresh/re-consent, and record non-secret outcomes. Mocked tests do not establish live provider behavior.
3. Repeat install/verify/audit and archive inspection at release time. Legacy cleanup and local archive inspection are complete; the inspected archive contained runtime PHP only under `lib/`, plus license, metadata and user documentation. Recheck the final release artifact after any subsequent changes.

The local fresh-dependency install and final locked-install engineering gates are complete as recorded above. Lowest-dependency resolution was also checked using Composer's dry-run mode; actual lowest-version execution remains covered by the pending CI job. No remote CI, live staging, release tag or publication result is claimed. No commits were made.
