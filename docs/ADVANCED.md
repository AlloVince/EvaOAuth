# Advanced Usage

This document covers the parts of EvaOAuth that are useful once you move beyond the basic login flow.

If you only need:

```text
authorize → callback → token + user
```

the main [README](../README.md) is enough.

---

## Refresh Tokens

OAuth 2.0 providers may return a refresh token together with the access token.

If a token contains a refresh token:

```php
$newToken = $oauth->refresh('google', $token);
```

The returned value is a new `Token` instance.

```php
$newToken->accessToken;
$newToken->refreshToken;
$newToken->expiresAt;
```

A provider may return a new refresh token or keep using the existing one. EvaOAuth preserves the correct refresh-token behavior for the provider response.

Refresh is an OAuth 2.0 operation.

Calling `refresh()` for an OAuth 1.0a provider is not supported.

### Persisting refreshed tokens

If tokens are stored in your application, replace the old token with the new one atomically after a successful refresh.

```php
$newToken = $oauth->refresh('google', $token);

$storage->save(
    $userId,
    $newToken->toArray(),
);
```

Token data contains credentials and should be treated as sensitive data.

---

## Calling an Authorized API

EvaOAuth can sign or authorize PSR-7 requests using an existing token.

```php
use GuzzleHttp\Psr7\Request;

$request = new Request(
    'GET',
    'https://api.github.com/user',
);

$response = $oauth->request(
    'github',
    $token,
    $request,
);
```

The same API is used for OAuth 1.0a and OAuth 2.0.

Internally, the authorization mechanism remains protocol-specific:

```text
OAuth 2.0 → Bearer token
OAuth 1.0a → OAuth signature
```

EvaOAuth does not expose that difference to normal application code.

### Resource origins

Authorized requests are restricted to origins allowed by the Provider.

This prevents a token from accidentally being sent to an unrelated host.

A Provider should explicitly declare every API origin that may receive credentials.

---

## Separating Token Exchange and User Loading

The normal shortcut is:

```php
$result = $oauth->callback('github', $_GET);
```

This performs:

```text
callback validation
→ token exchange
→ user request
→ AuthorizationResult
```

For applications that need more control, split the flow:

```php
$token = $oauth->exchange('github', $_GET);

// Persist the token here if needed.

$user = $oauth->user('github', $token);
```

This is useful when token persistence and user-profile loading should have separate failure handling.

OAuth callbacks are one-shot transactions. Once a callback transaction is consumed, the same callback should not be replayed.

---

## Token Persistence

`Token` is immutable.

To explicitly export a token:

```php
$data = $token->toArray();
```

To restore it later:

```php
use Eva\EvaOAuth\Token;

$token = Token::fromArray($data);
```

The exported data may contain:

```text
access token
refresh token
OAuth1 token secret
expiry
scopes
provider binding
```

It should therefore be stored as sensitive application data.

Recommended practices include:

- encrypting token storage where appropriate
- protecting backups containing token data
- limiting database access
- avoiding tokens in application logs
- replacing refreshed tokens atomically

`Token` intentionally does not support normal PHP serialization.

---

## User Identity

EvaOAuth maps Provider-specific user data into a common `Identity` object.

```php
$user->id;
$user->name;
$user->email;
$user->avatar;
$user->emailVerified;
```

Only `id` is required.

All other fields may be `null`.

Do not assume that every Provider returns an email address, or that an email address has been verified.

For local account lookup, use the Provider together with the Provider user ID:

```text
(provider, identity.id)
```

Do not merge accounts from different Providers based only on email address.

---

## Custom OAuth 2.0 Provider

Most standard OAuth 2.0 Providers only need:

```text
authorization endpoint
token endpoint
user endpoint
client credentials
redirect URI
scopes
identity mapping
```

Example:

```php
use Eva\EvaOAuth\Identity;
use Eva\EvaOAuth\Provider\OAuth2Provider;

$provider = new OAuth2Provider(
    clientId: $_ENV['EXAMPLE_CLIENT_ID'],
    clientSecret: $_ENV['EXAMPLE_CLIENT_SECRET'],
    redirect: 'https://example.com/oauth/example/callback',
    authorizeUrl: 'https://accounts.example.com/oauth/authorize',
    accessTokenUrl: 'https://accounts.example.com/oauth/token',
    userUrl: 'https://api.example.com/me',
    scopes: ['profile', 'email'],
    identityMapper: static function (array $data): Identity {
        return new Identity(
            id: (string) $data['id'],
            name: $data['display_name'] ?? null,
            email: $data['email'] ?? null,
            avatar: $data['avatar'] ?? null,
            emailVerified: $data['email_verified'] ?? null,
        );
    },
);
```

Register it normally:

```php
$oauth = new OAuth([
    'example' => $provider,
]);
```

Application code remains unchanged:

```php
$url = $oauth->authorize('example');

$result = $oauth->callback('example', $_GET);
```

A normal Provider should not implement the OAuth flow itself.

Provider extensions should mainly describe Provider-specific configuration and identity mapping.

---

## Custom OAuth 1.0a Provider

OAuth 1.0a Providers use the same application-level flow:

```php
$url = $oauth->authorize('example');
$result = $oauth->callback('example', $_GET);
```

A custom OAuth 1.0a Provider normally defines:

```text
request-token endpoint
authorization endpoint
access-token endpoint
user endpoint
consumer key / secret
identity mapping
```

The OAuth 1.0a request-token and signature flow remains inside EvaOAuth.

Applications should not need to manually construct OAuth signatures.

---

## State Storage

EvaOAuth stores short-lived OAuth authorization transactions between:

```text
authorize()
```

and:

```text
callback()
```

By default it uses PHP session storage.

Start the session before creating `OAuth`:

```php
session_start();

$oauth = new OAuth([
    // providers
]);
```

The default state storage provides:

- one-time consumption
- expiration
- bounded storage
- browser-session isolation

### Custom StateStore

Applications running across multiple workers or servers may provide their own `StateStore`.

```php
use Eva\EvaOAuth\State\StateStore;

final class MyStateStore implements StateStore
{
    public function put(string $key, Transaction $transaction): void
    {
        // ...
    }

    public function consume(string $key): ?Transaction
    {
        // Atomic get-and-delete.
    }
}
```

Then inject it:

```php
$oauth = new OAuth(
    providers: $providers,
    stateStore: $stateStore,
);
```

A custom distributed store must preserve the security behavior of the default implementation.

In particular, `consume()` must behave as an atomic one-time read.

A shared cache with simple independent `get()` and `delete()` operations is not sufficient.

---

## Custom HTTP Client

EvaOAuth accepts a PSR-18 HTTP client.

```php
use GuzzleHttp\Client;

$http = new Client([
    'timeout' => 10,
    'connect_timeout' => 3,
    'allow_redirects' => false,
]);

$oauth = new OAuth(
    providers: $providers,
    httpClient: $http,
);
```

The HTTP client is used for OAuth protocol requests and Provider API requests.

### Redirects

Do not configure the injected HTTP client to automatically follow redirects when credentials may be attached.

EvaOAuth rejects redirect responses, but it cannot undo a credential leak if the injected client already followed a redirect before returning the response.

---

## Logging and Debugging

EvaOAuth supports any PSR-3 logger.

```php
$oauth = new OAuth(
    providers: $providers,
    logger: $logger,
);
```

Debug logging is intentionally metadata-only.

Typical information includes:

```text
provider
OAuth stage
HTTP method
HTTP status
failure category
request duration
```

EvaOAuth does not put raw OAuth credentials into normal debug logs.

Logs should not contain:

```text
client_secret
access_token
refresh_token
OAuth1 token secret
PKCE verifier
Authorization header
raw callback parameters
request or response bodies containing credentials
```

The goal is to make Provider integration problems observable without turning debug logs into a credential store.

---

## Exception Handling

EvaOAuth exposes a small exception hierarchy:

```php
use Eva\EvaOAuth\Exception\CallbackException;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
```

Typical handling:

```php
try {
    $result = $oauth->callback('github', $_GET);
} catch (CallbackException $e) {
    // Invalid, expired or already consumed callback.
} catch (ProviderException $e) {
    // Provider returned an invalid or unsuccessful response.
} catch (TransportException $e) {
    // HTTP transport failed.
}
```

Exception messages are intentionally sanitized.

Do not rely on raw Provider errors or credential-bearing exception chains being exposed through exceptions.

Use safe debug logging when diagnosing integration problems.

---

## Provider-Specific Authorization Parameters

Some Providers require additional authorization parameters.

Examples include:

```text
access_type
prompt
login_hint
audience
resource
```

Configure these on the Provider rather than manually modifying the generated authorization URL.

This keeps security-sensitive parameters such as `state` and PKCE under EvaOAuth's control.

---

## Multiple Providers

Multiple Providers can be registered in one `OAuth` instance:

```php
$oauth = new OAuth([
    'github' => $github,
    'google' => $google,
    'flickr' => $flickr,
]);
```

Then select the Provider explicitly:

```php
$oauth->authorize('github');
$oauth->authorize('google');
$oauth->authorize('flickr');
```

Each Provider should use its own callback URI.

For example:

```text
/oauth/github/callback
/oauth/google/callback
/oauth/flickr/callback
```

Route the callback to the Provider configured for that route.

Do not choose the Provider from an untrusted callback query parameter.

---

## Session Recommendations

When using the default session-based state storage, normal PHP session security practices still apply.

Typical production settings include:

```text
Secure cookies
HttpOnly cookies
SameSite=Lax
strict session mode
TLS
```

After OAuth login has been converted into an authenticated application session, regenerate the session ID:

```php
session_regenerate_id(true);
```

EvaOAuth manages OAuth transactions.

Your application remains responsible for the authenticated application session.

---

## Security Model

EvaOAuth provides safe defaults for the OAuth flow itself, including:

- secure random state
- OAuth 2.0 PKCE with S256
- one-time callback transactions
- Provider/configuration binding
- HTTPS Provider endpoints
- strict callback validation
- strict token parsing
- restricted authenticated request origins
- sanitized exceptions
- credential-safe logging

EvaOAuth does not provide:

- application authentication sessions
- local account management
- automatic account linking
- authorization or permissions
- OIDC ID Token validation
- JWT validation
- OAuth discovery
- automatic token refresh
- hidden retry behavior

Those responsibilities belong to the application or to another dedicated component.

---

## Why EvaOAuth Uses League Internally

EvaOAuth does not reimplement mature OAuth protocol engines.

OAuth 2.0 and OAuth 1.0a protocol work is delegated to established League libraries.

EvaOAuth focuses on the application-facing layer around them:

```text
unified API
Provider model
state lifecycle
PKCE
Token
Identity
safe HTTP boundaries
debugging
error mapping
secure defaults
```

OAuth 1.0a and OAuth 2.0 remain separate internally.

They are unified only where doing so makes application code simpler.

For more details, see [Architecture](ARCHITECTURE.md).

---

## Migrating from EvaOAuth 1.x

EvaOAuth 2.0 is a breaking rewrite.

The old APIs such as:

```text
Service
requestAuthorize()
getAccessToken()
getTokenAndUser()
Storage
debug()
```

have been replaced by the 2.0 model.

See [UPGRADING.md](../UPGRADING.md) for the migration mapping.