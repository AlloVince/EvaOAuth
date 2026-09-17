# Upgrading EvaOAuth 1.x to 2.x

2.0 is a breaking redesign, not a compatibility shim. The Composer package remains `evaengine/eva-oauth` and namespace remains `Eva\EvaOAuth`, but maintained code is now `lib/`. PHP 8.2–8.5 is required. Do not load the old `src/` autoloader alongside 2.x.

## API mapping

| 1.x concept | 2.x replacement | Migration action |
| --- | --- | --- |
| `Service('Facebook', ['key' => ..., 'secret' => ..., 'callback' => ...])` | `OAuth(['github' => new Provider\GitHub(clientId, clientSecret, redirectUri)])` or a generic provider | Explicit provider objects replace global alias registration and string-based construction |
| `requestAuthorize()` | `OAuth::authorize(name): string` | Send your own Location response; the library does not send headers or terminate execution |
| `getAccessToken()` | `OAuth::exchange(name, query): Token` | Pass the callback query explicitly; state/request-token validation and one-use consumption are mandatory |
| `getTokenAndUser()` | `OAuth::callback(name, query): AuthorizationResult` | Read `$result->token` and `$result->user`; do not exchange the callback twice |
| `OAuth1\Providers\AbstractProvider`, `OAuth2\Providers\AbstractProvider` | `Provider\OAuth1Provider`, `Provider\OAuth2Provider` | Configure endpoints and identity mapping, or extend a readonly provider; do not port protocol code |
| `Service::registerProvider()` | Constructor provider map | Register local names explicitly; callback URIs must differ across configured providers |
| `Storage`, `Service::setStorage()`, Doctrine Cache | Inject `State\StateStore`; default `SessionStateStore` | Browser-scoped, bounded, expiring and atomic-consume transactions replace global filesystem cache |
| `debug(path)` and raw HTTP/event logging | Inject PSR-3 `LoggerInterface` | Remove raw bodies/headers/URLs from all logging layers; use safe fixed metadata events |
| `AuthorizedHttpClient($token)` | `OAuth::request(name, token, RequestInterface)` | Use immutable PSR-7 requests and PSR-18 transport; resource origins are restricted |
| `StandardUser` getters | Readonly `Identity` properties | Use `id`, `name`, `email`, `avatar`, `emailVerified`; all except `id` can be null |
| Mutable protocol-specific access tokens | Readonly unified `Token` | Preserve protocol-specific optional fields and provider binding; replace values after refresh |
| Event emitter and `Before*` hooks | Provider configuration/mapper, PSR-18 injection, PSR-3 logger | Old event hooks are removed; mandatory state/PKCE/signing cannot be overridden |
| Old exception hierarchy | `Exception\OAuthException` and its five subclasses | Replace catch clauses and remove reliance on raw provider messages or previous exceptions |

The complete, executable replacement for the common request-page/callback-page flow is the first example in [README](README.md#github-login), also available in [Chinese](README_CN.md). Use the same provider configuration and browser session on both requests; route the provider by a fixed trusted callback path, never by an untrusted endpoint or provider URL.

## Deployment changes

1. Upgrade PHP and install the 2.x dependency set with Composer. Remove application dependencies used only for the obsolete implementation (Doctrine Cache, old Guzzle APIs, event emitter integrations) after checking other consumers. No full framework is needed.
2. Re-register exact **HTTPS** callbacks. Local HTTP examples from 1.x are not valid 2.x configurations; use local TLS. Give GitHub, Google and Flickr distinct callback URIs.
3. Start a secure server-side PHP session before constructing OAuth, enable strict mode and secure/HttpOnly/SameSite=Lax cookies, and keep session locking through transaction operations. Session startup is no longer implicit. Update reverse-proxy TLS and logging configuration.
4. Replace application login/callback handlers using the README. Catch OAuth exceptions in a safe global handler, then redirect to a clean local URL. Regenerate the application session ID after login. Resolve a local account using provider plus identity ID; never blindly merge by email.
5. Replace global request-token storage. Custom stores must enforce browser separation, TTL, capacity and atomic one-use consumption across workers. `MemoryStateStore` is a test utility, not a persistent production store.
6. Redesign token persistence and refresh locking. Separate pending authorization transactions from durable credentials. Encrypt records, protect backups, review log retention and remove raw callback/request telemetry.

## Persisted credentials

2.x `Token` contains `provider` (a stable configuration binding, **not** the registry alias), `protocol`, `accessToken`, nullable `refreshToken`, nullable Unix `expiresAt`, nullable OAuth1 `tokenSecret` and `scopes`. The configuration binding includes client credentials and endpoints; changing these may invalidate old tokens and pending transactions. Plan reauthorization during credential/configuration rotation rather than silently rewriting bindings.

`Token::toArray()` is the explicit secret-bearing export and `Token::fromArray()` restores that record. Encrypt and authenticate records outside the library. JSON serialization and debug output omit credentials; PHP serialization throws. Do not migrate by serializing old objects, and never run `unserialize()` on untrusted records.

There is no automatic 1.x record importer. Review each old record's provider, app credentials, grant and expiry units. The old combined OAuth1 result could omit the token secret: without it, reauthorization is required. Do not invent missing secrets or treat an OAuth1 secret as a refresh token. Old OAuth2 access tokens without a valid refresh grant also require a new authorization when expired. Reauthorization is the recommended migration path for records whose provenance or configuration cannot be established.

Refresh returns a new Token. Save it atomically: retain the old refresh token when the provider omits one, save a replacement when returned, and serialize concurrent refresh operations. Requests never refresh automatically. GitHub OAuth Apps do not generally supply the refresh behavior demonstrated with Google; capabilities depend on the provider and app type.

## Deliberate removals and limits

- No implicit/password grants, query bearer tokens, caller-selected state or disabled PKCE. OAuth2 Authorization Code always uses S256 PKCE and state. OAuth1 remains a separate temporary/access-credential flow.
- No unrestricted authorized HTTP client, automatic credential-bearing redirects, mutable global provider registry, global request-token cache or raw debug dump.
- Old Facebook, Twitter, Douban, Tencent, Weibo and other legacy provider classes have been removed; their implementations remain in Git history, not the 2.x package. Built-ins are GitHub, Google and Flickr. Port additional providers using current official endpoint documentation and wire-level fixtures.
- Identity is fetched from trusted provider APIs. No OIDC certification, ID-token/JWT validation, discovery, DPoP or automatic retries are claimed.
- Historical `src/`, `examples/`, old tests/bootstrap/reports, temporary files, Travis/Coveralls/Scrutinizer configuration and obsolete build/phpdoc tooling have been removed. The 1.x implementation remains in Git history; do not deploy the old example website.

## Verification

Run `composer install`, `composer verify` and `composer validate --strict` from a clean checkout. The supported suite is `tests/Modern`; the README snippets themselves are extracted and executed by `DocumentationTest`. Your application still needs staging validation of consent, callback routing, session continuity, local account authorization and provider policies. See [acceptance evidence](docs/ACCEPTANCE.md) and [architecture](docs/ARCHITECTURE.md).
