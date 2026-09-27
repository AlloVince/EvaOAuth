# EvaOAuth

A simple OAuth 1.0a / OAuth 2.0 client for PHP.

EvaOAuth gives OAuth 1.0a and OAuth 2.0 the same application-level API, so most integrations only need to care about:

```text
authorize → callback → token + user
```

It is framework-agnostic and works with any PSR-compatible PHP application.

## Installation

```bash
composer require evaengine/eva-oauth:^2.0
```

Requires PHP 8.2+.

## Quick Start

GitHub login takes only a few lines.

```php
use Eva\EvaOAuth\OAuth;
use Eva\EvaOAuth\Provider\GitHub;

session_start();

$oauth = new OAuth([
    'github' => new GitHub(
        clientId: $_ENV['GITHUB_CLIENT_ID'],
        clientSecret: $_ENV['GITHUB_CLIENT_SECRET'],
        redirectUri: 'https://example.com/oauth/github/callback',
    ),
]);
```

Start authorization:

```php
header('Location: ' . $oauth->authorize('github'));
exit;
```

Handle the callback:

```php
$result = $oauth->callback('github', $_GET);

$result->token;
$result->user->id;
$result->user->name;
$result->user->email;
```

That's the normal EvaOAuth flow.

PKCE, state validation and the OAuth protocol details are handled internally.

## Google

Replace the provider:

```php
use Eva\EvaOAuth\Provider\Google;

$oauth = new OAuth([
    'google' => new Google(
        clientId: $_ENV['GOOGLE_CLIENT_ID'],
        clientSecret: $_ENV['GOOGLE_CLIENT_SECRET'],
        redirectUri: 'https://example.com/oauth/google/callback',
    ),
]);
```

The rest of the application code stays the same:

```php
$url = $oauth->authorize('google');
$result = $oauth->callback('google', $_GET);
```

## OAuth 1.0a

OAuth 1.0a uses the same API.

Flickr is included as the built-in OAuth 1.0a provider:

```php
use Eva\EvaOAuth\Provider\Flickr;

$oauth = new OAuth([
    'flickr' => new Flickr(
        clientId: $_ENV['FLICKR_KEY'],
        clientSecret: $_ENV['FLICKR_SECRET'],
        redirectUri: 'https://example.com/oauth/flickr/callback',
    ),
]);

$url = $oauth->authorize('flickr');
$result = $oauth->callback('flickr', $_GET);
```

OAuth 1.0a and OAuth 2.0 remain different protocols internally. EvaOAuth only unifies the parts your application normally needs.

## Supported Providers

Built in:

| Provider | Protocol |
| --- | --- |
| GitHub | OAuth 2.0 |
| Google | OAuth 2.0 |
| Flickr | OAuth 1.0a |

Adding another standard OAuth provider normally only requires its endpoints, scopes and user identity mapping.

See [Advanced Usage](docs/ADVANCED.md).

## Token and User

A successful callback returns an `AuthorizationResult`:

```php
$result->token;
$result->user;
```

Common user fields:

```php
$result->user->id;
$result->user->name;
$result->user->email;
$result->user->avatar;
$result->user->emailVerified;
```

Some providers do not return every field. `email`, `avatar` and other optional fields may be `null`.

Tokens can be explicitly exported for persistence:

```php
$data = $result->token->toArray();
```

Treat exported token data as sensitive credentials.

## Advanced Usage

The README intentionally covers only the common login flow.

See [Advanced Usage](docs/ADVANCED.md) for:

- refresh tokens
- calling authenticated APIs
- custom OAuth 1.0a / OAuth 2.0 providers
- custom state storage
- custom PSR-18 HTTP clients
- PSR-3 logging and debugging
- error handling
- token persistence
- security and deployment details

For the internal design, see [Architecture](docs/ARCHITECTURE.md).

## Migrating from 1.x

EvaOAuth 2.0 is a rewrite and is not source-compatible with 1.x.

If you used `Service`, `requestAuthorize()`, `getAccessToken()` or other 1.x APIs, see [UPGRADING.md](UPGRADING.md).

## License

BSD-3-Clause.