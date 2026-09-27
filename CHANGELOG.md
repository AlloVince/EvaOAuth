# Changelog

All notable changes to EvaOAuth are documented here. This project follows
[Semantic Versioning](https://semver.org/); 1.x to 2.0 is a breaking rewrite, not a compatibility layer.
See [UPGRADING.md](UPGRADING.md) for the 1.x → 2.x API mapping.

## [2.0.0] - 2026-09-27

### Breaking

- **Complete rewrite.** The 1.x `Service` facade, `OAuth1\Consumer`, `OAuth2\Client`, global
  `EventsManager` hooks, `Storage`/Doctrine cache layer, `AuthorizedHttpClient`,
  `debug()` file logger and `StandardUser` getters are **removed**, not deprecated. The Composer
  package name (`evaengine/eva-oauth`) and namespace (`Eva\EvaOAuth`) are unchanged; maintained
  code moved from `src/` to `lib/`, and PHP 8.2 is now the minimum.
- Every provider is an explicit object: `new OAuth(['github' => new Provider\GitHub($id, $secret, $redirect)])`.
  There is no global registry and no `Service::registerProvider()`.
- `requestAuthorize()` is now `OAuth::authorize(string $name): string` and only returns a URL; the
  library never sends headers.
- `getAccessToken()` / `getTokenAndUser()` are now `OAuth::exchange()` / `OAuth::callback()` with an
  explicit callback query argument. `callback()` is one-shot: the state or request token is consumed
  before the code is exchanged, so a failed identity request requires a new login. Use
  `exchange()` then `user()` when identity fetching must be retriable.
- Tokens are one immutable, unified readonly `Token` value with an explicit persistence contract
  (`toArray()` / `fromArray()`); protocol-specific mutable access-token objects are gone. An OAuth1
  token secret is never presented as an OAuth2 refresh token.
- `StandardUser` is now `Identity` with readonly `id`, `name`, `email`, `avatar`, `emailVerified`
  (all but `id` nullable).
- Exceptions are now `Exception\OAuthException` and five fixed subclasses with constant messages and
  no previous-exception chain. 1.x `Exception\*` classes and raw provider messages are removed.
- **Removed providers:** 1.x shipped Douban, Facebook, Hundsun, Tencent and Weibo (OAuth2) plus
  Twitter (OAuth1). None of them exist in 2.0; their code remains in Git history only. Port them
  with `Provider\OAuth2Provider` / `Provider\OAuth1Provider` and current official endpoint
  documentation.
- The example website, `src/`, legacy tests/reports, Travis/Coveralls/Scrutinizer config and
  obsolete build/phpdoc tooling were deleted from the distribution.
- Dependency floor: PHP `~8.2 || ~8.3 || ~8.4 || ~8.5`, `league/oauth2-client ^2.9.1`,
  `league/oauth1-client ^1.11`, `guzzlehttp/guzzle ^7.10`, PSR HTTP/PSR-3 interfaces. No framework,
  Google SDK, Doctrine cache or event-emitter dependency.

### Added

- **OAuth2 Authorization Code with mandatory S256 PKCE** and CSPRNG state (`random_bytes`), plus the
  refresh grant with correct retention/rotation of refresh tokens.
- **OAuth1.0a** three-legged flow (request token → consent → access token) with HMAC-SHA1 signing,
  a CSPRNG nonce and RFC 5849-correct signature normalization.
- **Built-in providers:** `Provider\GitHub` (OAuth2), `Provider\Google` (OAuth2, offline access,
  UserInfo `sub` identity, issuer validation) and `Provider\Flickr` (OAuth1).
- **Generic providers:** `Provider\OAuth2Provider` and `Provider\OAuth1Provider` take endpoints,
  scopes, provider-specific authorization parameters and an identity mapper. Adding a standard
  provider requires no protocol code; see `tests/Modern/Fixture/DemoProvider.php`.
- **Unified application API** for both protocols: `authorize`, `exchange`, `callback`, `refresh`,
  `user` and provider-bound `request`, returning `AuthorizationResult(provider, token, user)`.
- **Unified `Token`/`Identity`** value objects with expiry semantics (`expires_in=0` is immediate
  expiry, never an unknown expiry), redaction in JSON/debug output and rejected PHP serialization.
- **Injected PSR-18 client and PSR-3 logger.** The default transport disables redirects, disables
  HTTP error conversion and applies 15 s total / 5 s connect timeouts.
- **Metadata-only diagnostics:** `oauth.http.response`, `oauth.http.failure` and `oauth.failure`
  events carrying the provider alias, stage, method, status, failure category and duration — no
  URLs, headers, bodies, query values, credentials or exception content.
- **Browser-bound state store:** `State\SessionStateStore` by default (bounded, expiring, atomic
  one-use consume), plus `State\MemoryStateStore` for tests and a `State\StateStore` interface for
  distributed deployments.
- **Token binding by provider identity:** `Token::provider` hashes provider class, protocol, client id
  and callback URI, so client-secret rotation, scope changes and endpoint changes keep stored tokens
  usable; pending transactions remain bound to the full configuration.
- **Origin-restricted authorized requests:** resource requests are limited to configured HTTPS
  origins, reject query bearer credentials and re-sign OAuth1 requests deterministically.
- CI matrix on PHP 8.2–8.5 plus a lowest-dependency job, `composer verify` (PHPUnit, PHPStan level 6,
  PSR-12 PHPCS, strict `composer validate`, locked `composer audit --abandoned=fail`).
- Executable documentation: every PHP fence in both READMEs is extracted and run against mocked HTTP.
- `CHANGELOG.md`, `SECURITY.md`, `docs/ARCHITECTURE.md`, `docs/ADR-001.md`, `docs/ACCEPTANCE.md` and
  `docs/RELEASING.md`.

### Security

- Browser-bound, single-use state (OAuth2) and request tokens (OAuth1) with configurable TTL/capacity.
- Configuration-bound authorization transactions reject a changed provider, secret, scope set,
  endpoint or callback between authorize and callback.
- Strict callback parsing: recognized fields are shape-validated, unknown OAuth2 extension parameters
  are ignored per RFC 6749, OAuth1/OAuth2 field mixing is rejected, and `denied`/`error` callbacks
  are refused.
- Trusted `issuer` validation for a callback `iss` parameter, including path-bearing issuers.
- No implicit/password grants, no caller-supplied state, no disabled PKCE, no automatic retries and
  no automatic refresh.
- Metadata-only logging by default; secrets are marked `#[\SensitiveParameter]` and never appear in
  messages, debug output, JSON or stack traces.

[2.0.0]: https://github.com/AlloVince/EvaOAuth/releases/tag/2.0.0
