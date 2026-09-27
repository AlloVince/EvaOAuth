# EvaOAuth 2.0

Framework-agnostic PHP 8.2–8.5 OAuth client: one login, token and identity API over separate OAuth 2.0 + PKCE and OAuth 1.0a engines. [中文](README_CN.md)

## GitHub login

Register an OAuth app with the exact HTTPS callback below. Set `GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET` in your server environment. Route both the login entry and callback to this code, before any output; replace `app.example` with your host.

```php
<?php
require 'vendor/autoload.php';

use Eva\EvaOAuth\OAuth;
use Eva\EvaOAuth\Provider\GitHub;

if (!session_start([
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
])) {
    throw new \RuntimeException('Unable to start the login session.');
}
$oauth = new OAuth([
    'github' => new GitHub(
        getenv('GITHUB_CLIENT_ID'),
        getenv('GITHUB_CLIENT_SECRET'),
        'https://app.example/callback/github',
    ),
], httpClient: $httpClient ?? null);
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if ($_GET === []) {
    $url = $oauth->authorize('github');
    header('Location: ' . $url, true, 302);
    return;
}
$result = $oauth->callback('github', $_GET);
session_regenerate_id(true);
$token = $result->token;
$_SESSION['oauth_identity'] = [
    'provider' => $result->provider,
    'id' => $result->user->id,
    'name' => $result->user->name,
    'email' => $result->user->email,
];
header('Location: /account', true, 303);
```

This completes the provider login; your application must resolve `(provider, id)` to its local account and enforce authorization. Do not link accounts by email alone. `name`, `email`, `avatar` and `emailVerified` can be null. GitHub's `/user` may omit private email even with `user:email`; this provider does not fetch `/user/emails` automatically. Never send `$token` to the browser.

The optional `$httpClient` is your PSR-18 client; omit it to use the built-in transport. The snippet assumes a fresh request and an active, secure server-side session; check session startup failures in your application bootstrap. Handle callback exceptions using your error handler, not a raw exception page. An empty query starts authorization; dedicate this route to GitHub. When adding providers, bind each distinct callback path to a fixed provider name in your router; the facade cannot inspect the actual incoming route. Never choose the callback provider from query parameters.

## Install and why EvaOAuth

After a 2.x release is published:

```sh
composer require evaengine/eva-oauth:^2.0
```

In a checkout of this repository: `composer install`. Requires PHP 8.2, 8.3, 8.4 or 8.5, JSON and OpenSSL; no Laravel, Symfony, Google SDK or full framework is required. Composer installs Guzzle and the stable League OAuth1/OAuth2 engines.

Using a League engine directly is reasonable for a single protocol. EvaOAuth adds browser-bound, one-use authorization transactions, secure defaults, provider-identity-bound tokens, normalized identity, the same OAuth1/OAuth2 application API, restricted resource origins, fixed exceptions and metadata-only tracing. It does not pretend that OAuth1 token secrets are OAuth2 refresh tokens.

## Providers and common API

| Provider | Protocol | Default behavior |
| --- | --- | --- |
| `Provider\GitHub` | OAuth2 code + S256 PKCE | `read:user user:email`, `/user` identity |
| `Provider\Google` | OAuth2 code + S256 PKCE | `openid profile email`, offline access, UserInfo identity |
| `Provider\Flickr` | OAuth1.0a | Request token, consent, access token, signed identity request |
| `Provider\OAuth2Provider` | OAuth2 code + S256 PKCE | Your HTTPS endpoints, scopes and identity mapper |
| `Provider\OAuth1Provider` | OAuth1.0a | Your HTTPS endpoints and identity mapper |

`authorize(name)` returns a URL, never sends browser headers. `callback(name, query)` returns `AuthorizationResult(provider, token, user)`. `exchange(name, query)` consumes the same callback but returns only a `Token`; do not call both for one callback. `user(name, token)` fetches identity. `request(name, token, psrRequest)` signs a provider-bound request. `refresh(name, token)` returns a new OAuth2 token; OAuth1 refresh is unsupported.

## Google and refresh tokens

Use the same session and redirect/callback handling as above, with a distinct callback URI:

```php
$oauth = new \Eva\EvaOAuth\OAuth([
    'google' => new \Eva\EvaOAuth\Provider\Google(
        getenv('GOOGLE_CLIENT_ID'),
        getenv('GOOGLE_CLIENT_SECRET'),
        'https://app.example/callback/google',
    ),
], httpClient: $httpClient ?? null);
$url = $oauth->authorize('google');
```

After Google's browser redirect, use this on the callback route (not immediately after `authorize`):

```php
$result = $oauth->callback('google', $_GET);
$token = $result->token;
$user = $result->user;
```

Later, with that provider-bound token loaded from protected server-side storage:

```php
if ($token->refreshToken !== null && $token->isExpired()) {
    $token = $oauth->refresh('google', $token);
}
$record = $token->toArray();
$restored = \Eva\EvaOAuth\Token::fromArray($record);
```

`toArray()` deliberately exports credentials: encrypt the record at rest, restrict access, and atomically replace the old record after refresh. JSON/debug views redact tokens and are **not** persistence formats; PHP serialization is rejected. `expiresAt` is a nullable Unix timestamp; an absent expiry is unknown, not proof of indefinite validity. A reported `expires_in=0` means immediately expired and must never be normalized to an absent expiry; such a token cannot be used to fetch identity before a successful refresh. Requests do not refresh automatically. A refresh response without a new refresh token preserves the old one; a replacement is retained. Serialize refresh operations per account to avoid rotation races. Missing/revoked/expired refresh credentials require a new authorization, not an endless retry loop.

Google requests `access_type=offline`; that does not guarantee a refresh token on every login. Google normally issues it on the initial grant; re-consent may be needed. For an explicit re-consent workflow configure a generic OAuth2 provider with the same Google endpoints, a `sub` mapper, `issuer: 'https://accounts.google.com'` and `authorizationParameters: ['access_type' => 'offline', 'prompt' => 'consent']`; do not force consent on every routine login. Keep each login flow's configuration stable while a user is mid-flow: a pending transaction is bound to the full provider configuration. Already issued tokens are bound to provider *identity*, so rotating a client secret or changing scopes, endpoints and options keeps stored refresh tokens working; only a different client id, callback URI, provider class or protocol invalidates them. Google testing-mode grants can expire; consent-screen publication, app verification and granted scopes are application responsibilities.

Endpoints checked against [Google's official web-server guide](https://developers.google.com/identity/protocols/oauth2/web-server) and [discovery metadata](https://accounts.google.com/.well-known/openid-configuration) on 2026-09-17:

- Authorization: `https://accounts.google.com/o/oauth2/v2/auth`
- Token and refresh: `https://oauth2.googleapis.com/token`
- UserInfo: `https://openidconnect.googleapis.com/v1/userinfo`

Identity comes from the authenticated UserInfo response (`sub`), not an unvalidated ID token. EvaOAuth is not an OIDC/JWT validator and does not implement discovery or DPoP.

## Flickr: the same application boundary

With an active session, Flickr uses the same authorize/callback/result pattern, but its engine performs OAuth1 request-token and HMAC signing internally:

```php
$oauth = new \Eva\EvaOAuth\OAuth([
    'flickr' => new \Eva\EvaOAuth\Provider\Flickr(
        getenv('FLICKR_CLIENT_ID'),
        getenv('FLICKR_CLIENT_SECRET'),
        'https://app.example/callback/flickr',
    ),
], httpClient: $httpClient ?? null);
$url = $oauth->authorize('flickr');
```

Redirect the browser to `$url`; on the callback route:

```php
$result = $oauth->callback('flickr', $_GET);
$token = $result->token;
$user = $result->user;
```

Flickr defaults to read permissions; use its optional fourth `permissions` argument for `write` or `delete` only when needed. Persist the OAuth1 `tokenSecret` together with `accessToken` using the protected `toArray()` record. There is no OAuth2-style refresh grant. The OAuth1 adapter rejects ambiguous parameter shapes (duplicate names, query/form collisions, bracketed names or caller-supplied `oauth_*` fields), pre-existing Authorization headers and unsupported form streams instead of signing them incorrectly. See [Flickr's official OAuth documentation](https://www.flickr.com/services/api/auth.oauth.html).

## Add an OAuth2 provider

Endpoints are trusted deployment configuration, never callback input. A mapper must return `Identity` and reject missing or malformed identifiers. This example uses placeholder domains to illustrate your own service:

```php
$custom = new \Eva\EvaOAuth\Provider\OAuth2Provider(
    clientId: getenv('CUSTOM_CLIENT_ID'),
    clientSecret: getenv('CUSTOM_CLIENT_SECRET'),
    redirect: 'https://app.example/callback/custom',
    authorizeUrl: 'https://auth.example/authorize',
    accessTokenUrl: 'https://auth.example/token',
    userUrl: 'https://api.example/me',
    scopes: ['profile'],
    identityMapper: static function (array $data): \Eva\EvaOAuth\Identity {
        if (!isset($data['account_id']) || !is_string($data['account_id'])) {
            throw new \Eva\EvaOAuth\Exception\ProviderException();
        }
        return new \Eva\EvaOAuth\Identity($data['account_id']);
    },
    clientAuthentication: 'client_secret_basic',
);
$oauth = new \Eva\EvaOAuth\OAuth(['custom' => $custom], httpClient: $httpClient ?? null);
$url = $oauth->authorize('custom');
```

Use `callback('custom', $_GET)` after consent. `client_secret_post` is the default alternative. Optional `authorizationParameters`, `scopeSeparator`, `responseScopeSeparator`, `issuer` and `resourceOrigins` cover common variations. `scopeSeparator` controls authorization request scopes; `responseScopeSeparator` controls token-response parsing and defaults to a space, while GitHub uses commas. The optional trusted `issuer` is checked against callback `iss` when supplied: it must match exactly, and a supplied `iss` is rejected when no issuer is configured. Google configures `https://accounts.google.com`. Unknown OAuth2 callback extension parameters are ignored per RFC 6749; recognized fields retain strict type/shape validation and cannot override security checks. Required state/PKCE/grant parameters cannot be overridden. Additional resource origins must be exact HTTPS origins; only add origins you trust to receive credentials. For reusable mapping, extend the readonly provider class and override `identity()`, not the protocol engine. Third-party League providers are not directly interchangeable with EvaOAuth providers.

## PSR-18 HTTP, PSR-3 logging and authorized requests

Your PSR-18 client and PSR-3 logger are injected once; Guzzle is the default, and no logger output is produced by default. With `$custom` from the preceding example:

```php
$httpClient = $httpClient ?? new \GuzzleHttp\Client([
    'allow_redirects' => false,
    'http_errors' => false,
    'timeout' => 15,
    'connect_timeout' => 5,
]);
$logger = $logger ?? new \Psr\Log\NullLogger();
$oauth = new \Eva\EvaOAuth\OAuth(
    ['custom' => $custom],
    httpClient: $httpClient,
    logger: $logger,
);
```

Given a token obtained through that same custom provider:

```php
$response = $oauth->request(
    'custom',
    $token,
    new \GuzzleHttp\Psr7\Request('GET', 'https://api.example/me'),
);
$status = $response->getStatusCode();
```

An injected client must retain TLS verification, apply finite timeouts and **not follow redirects** or log raw traffic. EvaOAuth rejects 3xx responses, but cannot undo credentials leaked by a transport that already followed a redirect. Resource requests are constrained to configured origins and reject query bearer credentials. The default transport uses a 15-second total and 5-second connection timeout; no automatic retries are provided.

### Safe trace: actual fields

The logger receives debug-level events with these exact metadata schemas:

```text
oauth.http.response {provider: "google", stage: "token_exchange", method: "POST", status: 200, duration_ms: 12.5}
oauth.http.failure  {provider: "google", stage: "identity", method: "GET", category: "transport", duration_ms: 15.0}
oauth.failure       {provider: "google", stage: "token_exchange", category: "provider", duration_ms: 120.4}
```

Numbers are illustrative, not a recorded live request. `provider` is your registry name; `stage` is one of `authorize`, `request_token`, `token_exchange`, `token_refresh`, `identity`, `resource_request`, `callback`. `method` is an allowlisted HTTP verb or `OTHER`. HTTP error responses still produce `oauth.http.response` with their status; malformed payloads do not create a separate parse event. `oauth.failure` closes every public call that ends in an `OAuthException`, with `category` one of `callback`, `configuration`, `provider`, `transport`, `unsupported`, so a login failure is attributable to a provider and a stage without any secret. Logger failures do not break the flow. There are no URLs, headers, bodies, query values, provider messages or exception chains in these events. Use event order, status, stage and duration to diagnose transport/provider failures; attach your own non-secret correlation ID outside the OAuth request data. Never enable Guzzle `debug`, dump request objects, or include callback URLs in APM/access logs.

## Exceptions and security responsibilities

All public OAuth failures derive from `Exception\OAuthException`:

| Exception | Application response |
| --- | --- |
| `ConfigurationException` | Fix credentials, provider map, session setup or token binding; do not retry blindly |
| `CallbackException` | Reject malformed, expired, denied, mismatched or replayed callback; offer a new login |
| `ProviderException` | Provider HTTP/data/signing failure or expired token; show a generic failure and investigate safe metadata |
| `TransportException` | Network/transport failure; apply a bounded application retry policy only where safe |
| `UnsupportedOperationException` | Do not attempt the operation for this protocol/token |

Messages are fixed and previous exceptions are removed. Do not expose stack traces, local variables or credentials; application validation and PHP errors also need a safe global handler. A callback is one-shot: its state or request token is consumed before the code is exchanged, so a callback whose identity request failed cannot be retried and asks the user to log in again. When you need a recoverable identity step, exchange and persist first, then fetch identity as often as you like:

```text
$token = $oauth->exchange('github', $_GET);   // Token, state already consumed
// persist $token->toArray() in your protected storage
$user = $oauth->user('github', $token);       // retriable, no second code exchange
```

- Use HTTPS for endpoints, resources and distinct registered callback URIs per configured provider. Derive neither endpoints nor redirect destinations from user input. Account linking needs explicit user confirmation and local CSRF defenses.
- Start PHP sessions before creating the default store; secure/HttpOnly/SameSite=Lax cookies and strict mode require HTTPS. Retain the session lock through transaction access. Regenerate the session ID after successful login and enforce application session expiration/logout.
- Default pending transactions expire after 600 seconds and cap at 10 per browser. `State\SessionStateStore(ttl: ..., capacity: ...)` permits 1–1800 seconds and 1–100 entries. Exceeding capacity evicts oldest attempts. A custom `StateStore` must browser-scope keys and implement atomic one-use `consume`, expiration and bounds across workers. `MemoryStateStore` is for tests, not multi-request production login.
- State, S256 PKCE, CSPRNG values and OAuth1 temporary secrets are handled by the library. Protect the session backend; never share pending state globally between browsers. Keep provider configuration stable across one login/callback pair; `Token::provider` holds a provider-identity hash, so credential rotation does not invalidate stored tokens.
- Encrypt stored token records, protect backups and keys, apply least privilege, delete/revoke grants when appropriate, and manage refresh concurrency. Token/debug redaction does not protect explicit property access, `toArray()`, arbitrary object casts or your own telemetry.
- Prevent callback query strings appearing in web-server/proxy/APM logs. Serve no third-party resources on callbacks; use `no-store`, `no-referrer` and redirect to a clean local URL. Escape profile data before rendering; treat email as optional and do not infer verification.
- Maintain trust in configured provider hosts/DNS and transport proxy settings; the origin policy is not a general network sandbox. Provider registration, scope review, rate limits, consent policies and incident response remain yours.

## Development and migration

```sh
composer install
composer verify
composer validate --strict
```

`verify` runs PHPUnit, PHPStan level 6, PSR-12 PHPCS, strict Composer validation and locked dependency audit (including abandoned packages). Only the `missingType.iterableValue` PHPStan identifier is disabled because this project uses native types without code comments; no baseline or blanket suppression is used. CI declares PHP 8.2–8.5 and a lowest-dependency job. This is not a claim that remote jobs have already run.

All PHP fences in both READMEs are extracted and executed with mocked HTTP by `tests/Modern/DocumentationTest.php`; separate wire-level tests verify protocol details. No demo website or live provider credentials are needed. The obsolete `src/`, example website and old tests have been removed; the 1.x implementation remains available in Git history. Tests and development tooling are available in the source repository, not the production package archive.

See [1.x → 2.x migration](UPGRADING.md), [architecture](docs/ARCHITECTURE.md), [acceptance evidence](docs/ACCEPTANCE.md), [release procedure](docs/RELEASING.md) and [ADR](docs/ADR-001.md). Licensed under [BSD-3-Clause](LICENSE).
