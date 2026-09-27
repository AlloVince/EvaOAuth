# EvaOAuth 2.0 architecture

The decision and standards research are in [ADR-001](ADR-001.md). EvaOAuth owns the application boundary and security lifecycle; stable League implementations supply separate protocol engines. This is neither a bespoke unified protocol implementation nor a renamed OAuth2 wrapper.

## Responsibilities and source map

| Question | Implementation |
| --- | --- |
| Where do protocols remain separate? | `lib/Engine/OAuth2Engine.php` uses League code/refresh grants and S256 PKCE; `lib/Engine/OAuth1Engine.php` uses temporary/access credentials and HMAC signing |
| Where are they unified? | `lib/OAuth.php`: authorize, exchange, callback, refresh, user and provider-bound request |
| Where do providers vary? | `lib/Provider/OAuth2Provider.php`, `OAuth1Provider.php`: trusted endpoints, configuration, origin policy and identity mapper; GitHub, Google and Flickr supply concrete defaults |
| How are tokens unified? | `lib/Token.php`: readonly application value with protocol-specific constraints, provider binding, expiry and explicit persistence |
| How is identity unified? | `lib/Identity.php` and `lib/AuthorizationResult.php`: normalized provider identity alongside Token; no local account policy |
| How is HTTP replaced? | Constructor PSR-18 `ClientInterface` injection, `lib/Http/Transport.php`; PSR-7 requests/responses and an internal Guzzle bridge for League |
| How is logging replaced? | Constructor PSR-3 `LoggerInterface` injection; metadata-only debug events from Transport, NullLogger by default |
| How is state stored? | `lib/State/StateStore.php`, `BoundedStateStore.php`, `SessionStateStore.php`; memory store for tests |
| How are external errors mapped? | Protocol/HTTP parsing boundaries throw fixed subclasses in `lib/Exception/`; raw messages and previous exception chains are discarded |

Only `lib/` is Composer-autoloaded. The modern PHPUnit suite is `tests/Modern/`. The historical `src/`, example website and old tests have been removed from the working tree; the 1.x implementation remains in Git history. Runtime dependencies include stable League OAuth1/OAuth2 and Guzzle, not an application framework.

## Authorization lifecycle

### OAuth2

1. `OAuth::authorize(name)` resolves a trusted, preconfigured provider. `OAuth2Engine::begin()` obtains a CSPRNG state and S256 challenge/verifier; returns the provider authorization URL.
2. The facade stores an `OAuth2Transaction` under `oauth2:<state>`, carrying verifier, provider configuration binding and redirect URI. Each authorization attempt is independent.
3. The application sends the browser redirect. On callback, it passes the query to a fixed provider route; no provider endpoint is accepted from input.
4. `exchange()` validates the state shape, atomically consumes the matching browser transaction, verifies binding and redirect, and rejects malformed recognized fields or error callbacks. OAuth2 extension parameters not recognized by the client are ignored per RFC 6749. A supplied `iss` must match the configured trusted `issuer`; supplying it without a configured issuer is rejected. Google configures `https://accounts.google.com`. Distinct callback URI configuration does not inspect the incoming HTTP route: the application must map each callback path to a fixed provider, never select one from the query.
5. The hardened League provider performs the code grant with the saved verifier and configured client authentication. Strict response parsing requires valid bearer credentials, expiry and scope shapes. Authorization `scopeSeparator` is separate from token `responseScopeSeparator` (space by default, comma for GitHub). `expires_in=0` represents immediate expiry, never an absent expiry; identity fetching with that token must fail until refresh succeeds.
6. `callback()` additionally fetches the configured user endpoint and maps `Identity`. The result exposes registry name, Token and Identity.

Calling exchange and callback for the same callback is incorrect. Consumption happens before the network exchange: replay and retry of that callback are rejected, including after provider failure. This is a deliberate design choice, not an accident — consumption must precede the exchange to make state and request tokens single-use, so `callback()` is one-shot by construction. If an application needs to retry identity fetching separately, it can use exchange followed by user, persisting the token securely between those operations. `OAuth2Test::testIdentityFailureConsumesCallbackAndRecoversWithExchangeThenUser` records both halves of that contract.

### OAuth1.0a

The facade exposes the same authorize/callback/result API but does not impose OAuth2 grant concepts. The engine first requests temporary credentials, verifies the confirmation response and builds the consent URL. An `OAuth1Transaction` stores the request token and secret in the browser session under `oauth1:<request-token>`, with configuration and callback binding. Callback consumes the transaction, validates the returned token/verifier, then exchanges temporary credentials for access credentials. Authorized requests use OAuth1 HMAC signatures, not bearer headers. Flickr fetches `flickr.test.login` to map its stable user ID and username; optional email/avatar remain absent.

`OAuth1Server`, `OAuth1Signature` and strict response handling constrain the League OAuth1 integration. Wire-level tests, including signature normalization, are the acceptance boundary for changes to these adapters. The current adapter rejects duplicate/bracketed parameter names, query/form collisions, supplied `oauth_*` fields, pre-existing Authorization headers, custom request targets and unsupported form streams instead of allowing League's array normalization to sign ambiguous requests. This is an intentional supported-request subset, not a claim to accept every legal OAuth1 parameter shape. Form bodies must be pre-encoded `application/x-www-form-urlencoded` with optional `charset=utf-8`, readable and seekable; stream position is preserved. JSON and multipart bodies are not included in the OAuth1 signature base string, as specified by RFC 5849. Credential responses are validated before League's form parsing; extra response fields are not exposed as token credentials. OAuth1 refresh is deliberately unsupported.

### Refresh and persistence

`OAuth::refresh()` verifies token/provider binding then invokes the OAuth2 refresh grant. It returns a new immutable token, preserving an omitted refresh token and replacing a supplied one. Expired access tokens may be refreshed; resource requests with expired tokens are rejected. No automatic refresh/retry policy is hidden inside request().

Two distinct hashes separate provider *identity* from provider *runtime configuration*:

| Hash | Contents | Used by | Effect of a change |
| --- | --- | --- | --- |
| `Provider::binding()` | class, protocol, client id, client secret, callback URI, all endpoints, scopes, options, origin policy, issuer | pending `OAuth2Transaction`/`OAuth1Transaction` | a login in flight is rejected; nothing persisted is affected |
| `Provider::tokenBinding()` | class, protocol, client id, callback URI | `Token::provider`, checked by `refresh()`, `user()` and `request()` | rotating the client secret or editing scopes, endpoints, separators, client authentication, origin policy or issuer keeps existing tokens usable; a different client id, callback URI, provider class or protocol invalidates them |

`AuthorizationResult::provider` is the configured alias. Neither hash is a local user ID. Revoking a client secret at the provider, or narrowing scopes there, remains the provider's decision; the application is responsible for re-authorizing users whose grants are withdrawn. Identity mapper behavior is application code and must be reviewed separately.

`toArray()` exports credentials intentionally; fromArray validates the record. JSON and debug representations omit secrets; serialization throws. Encryption, database authorization, refresh concurrency, revocation and lifecycle management belong to the application. Redaction is not protection against explicit property access or arbitrary object casts.

## Session and concurrency model

The default SessionStateStore requires PHP_SESSION_ACTIVE and uses namespace `eva_oauth_v2`. Pending transactions expire after 600 seconds and are limited to ten entries; configurable limits are 1–1800 seconds and 1–100 entries. Keys are hash-indexed, expired entries pruned and oldest entries evicted when full. A consume removes a transaction even if subsequent validation or network exchange fails.

The PHP session lock supplies atomicity for the default store. Applications must not close the session before transaction access and must configure their session handler's locking correctly. A distributed custom StateStore must implement browser scoping, expiry, capacity and atomic get-and-delete itself; a shared key/value cache without those guarantees is insufficient. MemoryStateStore is only suitable for tests/single-process scenarios.

## Transport, parsing and diagnostics

The PSR-18 transport is shared by both protocols. An internal Guzzle handler adapts League's transport expectations; callers still use PSR-18 and PSR-7 at the boundary. Default Guzzle configuration disables redirects and HTTP exception conversion and sets 15-second total/5-second connection timeouts. Injected clients must honor the same credential safety constraints. A returned 3xx is rejected, but the facade cannot stop a misconfigured client from following it before returning.

`Http/UrlPolicy.php` requires HTTPS, rejects userinfo/fragments, checks exact configured origins including port, and validates resource Host consistency and query credential restrictions. Provider endpoints are trusted configuration, not an SSRF sandbox. `Http/ResponseData.php` bounds and validates response envelopes; protocol adapters enforce their credential fields. Provider HTTP failures, invalid JSON/form data, identity mapper failures and unexpected upstream exceptions are mapped into the fixed exception model.

Every public `OAuth` call carries an allowlisted trace context (`provider` alias plus `stage`: `authorize`, `request_token`, `token_exchange`, `token_refresh`, `identity`, `resource_request`, `callback`), so a failure is attributable without any secret. Transport logs only:

- `oauth.http.response`: `provider`, `stage`, safe `method`, `status`, `duration_ms`.
- `oauth.http.failure`: `provider`, `stage`, safe `method`, `category: transport`, `duration_ms`.
- `oauth.failure`: `provider`, `stage`, `category` (`callback`, `configuration`, `provider`, `transport`, `unsupported`), `duration_ms`; emitted once when a public call ends in an `OAuthException`.

This is an allowlist, not denylist redaction of arbitrary payloads. No URLs, headers, bodies, callback fields, grant identifiers or arbitrary exception messages are recorded; the failure category is derived from the fixed exception class, never from its content. HTTP errors produce response events; later parsing failures are reported by the following `oauth.failure` event rather than a separate parse event. Logger failures are swallowed. Applications add their own non-secret correlation context and must disable raw transport/APM/access logging of sensitive requests.

## Security boundary and non-goals

Library guarantees include one-use state/request-token transactions, S256 PKCE, secure random values, configuration binding of pending transactions, provider-identity binding of issued tokens, strict callback/credential parsing, restricted resource origins and metadata-only diagnostics. Provider registration and scope choices remain trusted operator input.

The application owns TLS/proxy/session deployment, lock semantics, safe callback routing, account linking, CSRF on local actions, session ID regeneration, encrypted token storage, refresh races, consent/revocation policies, output escaping, telemetry policy and incident response. Optional identity fields are not authorization decisions. Google identity comes from UserInfo rather than an unvalidated ID token; GitHub private email is not automatically fetched.

No implicit/password grants, OIDC certification, JWT validation, discovery, DPoP, automatic retry, automatic refresh, framework login/session layer or website is provided. See [README security responsibilities](../README.md#exceptions-and-security-responsibilities).

## Verification strategy

`composer verify` chains modern PHPUnit tests, level-6 PHPStan, unfiltered PSR-12 PHPCS, strict package validation and locked dependency audit. Native array element annotations would require comments; only PHPStan's `missingType.iterableValue` is disabled, with no baseline or broad ignore. CI installs locked dependencies on PHP 8.2–8.5 and resolves a lowest-dependency set on PHP 8.2 before the same verify command.

Protocol tests assert real PSR request methods, endpoints, headers, form fields and signatures against mocked responses, not live services. `tests/Modern/Fixture/DemoProvider.php` is the executable statement of the extension model: a standard OAuth2 provider overrides only `__construct` (endpoints, scopes, provider-specific parameters) and `identity()`, and `ProviderExtensionTest` asserts exactly that before running the shared authorize/callback/refresh/user/request flow with it. DocumentationTest extracts every PHP fence from both READMEs and executes authorization/callback/refresh/request scenarios with mocked HTTP and an actual PHP session; separate PHPUnit processes isolate session headers. Snippets are not rewritten into test-specific copies: only the opening PHP tag is removed for evaluation, and public PSR-18 injection supplies fixtures. Browser navigation and real provider consent are not simulated by PHP execution. [Acceptance](ACCEPTANCE.md) distinguishes source/test evidence from unrun remote or live checks.
