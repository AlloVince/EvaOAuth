# EvaOAuth

一个简单、现代的 PHP OAuth 1.0a / OAuth 2.0 客户端。

EvaOAuth 在应用层为 OAuth 1.0a 和 OAuth 2.0 提供统一 API。大多数情况下，你只需要关心：

```text
authorize → callback → token + user
```

它不依赖 Laravel、Symfony 等框架，可以用于任何 PHP 项目。

## 安装

```bash
composer require evaengine/eva-oauth:^2.0
```

要求 PHP 8.2+。

## 快速开始

以 GitHub 登录为例。

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
], httpClient: $httpClient ?? null);
```

`$httpClient` 是可选的 PSR-18 client，不传时使用内置的 HTTP 实现。

开始授权：

```php
header('Location: ' . $oauth->authorize('github'));
exit;
```

处理回调：

```php
$result = $oauth->callback('github', $_GET);

$result->token;
$result->user->id;
$result->user->name;
$result->user->email;
```

这就是 EvaOAuth 最常见的使用方式。

PKCE、state 校验以及 OAuth 协议细节会由 EvaOAuth 内部处理。

## Google

只需要替换 Provider：

```php
use Eva\EvaOAuth\Provider\Google;

$oauth = new OAuth([
    'google' => new Google(
        clientId: $_ENV['GOOGLE_CLIENT_ID'],
        clientSecret: $_ENV['GOOGLE_CLIENT_SECRET'],
        redirectUri: 'https://example.com/oauth/google/callback',
    ),
], httpClient: $httpClient ?? null);
```

只替换了 Provider 和它的凭据，授权仍然返回一个 URL：

```php
$url = $oauth->authorize('google');
```

回调调用与 GitHub 完全相同：

```php
$result = $oauth->callback('google', $_GET);
```

## OAuth 1.0a

OAuth 1.0a 在应用层也使用相同 API。

EvaOAuth 默认提供 Flickr：

```php
use Eva\EvaOAuth\Provider\Flickr;

$oauth = new OAuth([
    'flickr' => new Flickr(
        clientId: $_ENV['FLICKR_KEY'],
        clientSecret: $_ENV['FLICKR_SECRET'],
        redirectUri: 'https://example.com/oauth/flickr/callback',
    ),
], httpClient: $httpClient ?? null);

$url = $oauth->authorize('flickr');
```

回调调用保持一致：

```php
$result = $oauth->callback('flickr', $_GET);
```

OAuth 1.0a 和 OAuth 2.0 在底层仍然是两套不同协议。

EvaOAuth 只统一应用开发时真正需要关心的部分。

## 支持的 Provider

内置：

| Provider | 协议 |
| --- | --- |
| GitHub | OAuth 2.0 |
| Google | OAuth 2.0 |
| Flickr | OAuth 1.0a |

新增一个标准 OAuth Provider，通常只需要配置 endpoint、scope 和用户身份映射。

更多内容见 [进阶用法](docs/ADVANCED_CN.md)。

## Token 与用户信息

授权成功后，`callback()` 返回一个 `AuthorizationResult`：

```php
$result->token;
$result->user;
```

常用用户字段：

```php
$result->user->id;
$result->user->name;
$result->user->email;
$result->user->avatar;
$result->user->emailVerified;
```

不同 Provider 返回的数据并不完全一致，因此 `email`、`avatar` 等字段可能为 `null`。

如果需要持久化 Token：

```php
$data = $result->token->toArray();
```

导出的 Token 数据包含敏感凭据，应安全保存。

## 进阶用法

README 只介绍最常见的登录流程。

以下内容请查看 [进阶用法](docs/ADVANCED_CN.md)：

- Refresh Token
- 调用授权后的 API
- 自定义 OAuth 1.0a / OAuth 2.0 Provider
- 自定义状态存储
- 自定义 PSR-18 HTTP Client
- PSR-3 Logger 与 Debug
- 异常处理
- Token 持久化
- 安全与部署建议

内部设计可以查看 [Architecture](docs/ARCHITECTURE.md)。

## 从 1.x 升级

EvaOAuth 2.0 是一次重写，不兼容 1.x API。

如果之前使用过：

```text
Service
requestAuthorize()
getAccessToken()
getTokenAndUser()
```

等旧接口，请查看 [UPGRADING.md](UPGRADING.md)。

## License

BSD-3-Clause.