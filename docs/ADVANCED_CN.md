# 进阶用法

这份文档介绍 EvaOAuth 在基础登录流程之外的常见用法。

如果你只需要：

```text
authorize → callback → token + user
```

主 [README](../README_CN.md) 已经足够。

---

## Refresh Token

OAuth 2.0 Provider 可能会在 Access Token 之外返回 Refresh Token。

如果当前 Token 包含 Refresh Token：

```php
$newToken = $oauth->refresh('google', $token);
```

返回值是一个新的 `Token`：

```php
$newToken->accessToken;
$newToken->refreshToken;
$newToken->expiresAt;
```

Provider 可能：

- 返回新的 Refresh Token
- 继续使用原来的 Refresh Token

EvaOAuth 会按照 Provider 的实际响应处理。

Refresh 只属于 OAuth 2.0。

OAuth 1.0a 不支持 `refresh()`。

### 保存刷新后的 Token

如果应用需要持久化 Token，应在刷新成功后原子替换旧 Token：

```php
$newToken = $oauth->refresh('google', $token);

$storage->save(
    $userId,
    $newToken->toArray(),
);
```

Token 中包含敏感凭据，应按敏感数据保存。

---

## 调用授权后的 API

EvaOAuth 可以使用已有 Token 对 PSR-7 请求进行授权或签名。

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

OAuth 1.0a 和 OAuth 2.0 使用相同的应用层 API。

内部授权方式仍然不同：

```text
OAuth 2.0 → Bearer Token
OAuth 1.0a → OAuth Signature
```

正常情况下，应用代码不需要关心这种差异。

### Resource Origin

携带凭据的请求只能发送到 Provider 明确允许的 Origin。

这样可以避免 Token 被误发到无关地址。

如果一个 Provider 需要访问多个 API Origin，应在 Provider 中明确配置。

---

## 分开 Token Exchange 与 User 获取

最常见的写法是：

```php
$result = $oauth->callback('github', $_GET);
```

它会依次完成：

```text
验证 Callback
→ 换取 Token
→ 获取用户信息
→ AuthorizationResult
```

如果应用需要更细的失败处理，可以拆开：

```php
$token = $oauth->exchange('github', $_GET);

// 如果需要，可以先持久化 Token。

$user = $oauth->user('github', $token);
```

这种方式适合 Token 获取和用户资料获取需要分别处理的场景。

OAuth Callback 是一次性事务。

事务被消费以后，不应再次使用同一个 Callback。

---

## Token 持久化

`Token` 是不可变对象。

显式导出：

```php
$data = $token->toArray();
```

之后恢复：

```php
use Eva\EvaOAuth\Token;

$token = Token::fromArray($data);
```

导出的内容可能包含：

```text
Access Token
Refresh Token
OAuth1 Token Secret
过期时间
Scopes
Provider Binding
```

因此应该把这些数据当作敏感凭据处理。

通常建议：

- 必要时加密保存 Token
- 保护包含 Token 的备份
- 限制数据库访问权限
- 不把 Token 写入日志
- Refresh 成功后原子替换旧 Token

`Token` 不支持普通 PHP serialization。

---

## User Identity

EvaOAuth 会把不同 Provider 的用户数据映射成统一的 `Identity`：

```php
$user->id;
$user->name;
$user->email;
$user->avatar;
$user->emailVerified;
```

只有 `id` 是必需字段。

其他字段都可能为 `null`。

不要假设所有 Provider 都会返回 Email，也不要因为存在 Email 就认为它已经经过验证。

本地账号匹配应使用：

```text
(provider, identity.id)
```

不要仅根据 Email 自动合并来自不同 Provider 的账号。

---

## 自定义 OAuth 2.0 Provider

大多数标准 OAuth 2.0 Provider 只需要描述：

```text
Authorization Endpoint
Token Endpoint
User Endpoint
Client Credentials
Redirect URI
Scopes
Identity Mapping
```

例如：

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

正常注册：

```php
$oauth = new OAuth([
    'example' => $provider,
]);
```

应用代码不需要改变：

```php
$url = $oauth->authorize('example');

$result = $oauth->callback('example', $_GET);
```

普通 Provider 不应该重新实现 OAuth 流程。

Provider 扩展主要负责描述 Provider 自己的配置和身份映射。

---

## 自定义 OAuth 1.0a Provider

OAuth 1.0a Provider 在应用层同样使用：

```php
$url = $oauth->authorize('example');
$result = $oauth->callback('example', $_GET);
```

一个普通 OAuth 1.0a Provider 通常只需要提供：

```text
Request Token Endpoint
Authorization Endpoint
Access Token Endpoint
User Endpoint
Consumer Key / Secret
Identity Mapping
```

Request Token、签名和 Access Token 流程由 EvaOAuth 内部处理。

应用不应该自己生成 OAuth Signature。

---

## State Storage

EvaOAuth 需要在：

```text
authorize()
```

和：

```text
callback()
```

之间保存短期 OAuth 授权事务。

默认使用 PHP Session。

创建 `OAuth` 之前启动 Session：

```php
session_start();

$oauth = new OAuth([
    // providers
]);
```

默认 State Storage 提供：

- 一次性消费
- 过期控制
- 有界存储
- 浏览器 Session 隔离

### 自定义 StateStore

多 Worker 或多服务器环境可以提供自己的 `StateStore`。

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

注入：

```php
$oauth = new OAuth(
    providers: $providers,
    stateStore: $stateStore,
);
```

自定义分布式 Store 必须保持默认 Store 的安全语义。

尤其是：

```text
consume()
```

必须是原子的一次性读取。

简单的：

```text
get()
→ delete()
```

并不能保证这一点。

---

## 自定义 HTTP Client

EvaOAuth 支持注入 PSR-18 HTTP Client。

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

这个 Client 会用于：

- OAuth 协议请求
- Provider API 请求

### Redirect

不要让携带凭据的 HTTP Client 自动跟随重定向。

EvaOAuth 会拒绝 Redirect Response。

但如果注入的 HTTP Client 已经提前跟随重定向并发送了凭据，EvaOAuth 无法在事后撤销。

---

## Logging 与 Debug

EvaOAuth 支持任意 PSR-3 Logger。

```php
$oauth = new OAuth(
    providers: $providers,
    logger: $logger,
);
```

Debug 日志只记录安全的元数据。

通常包括：

```text
Provider
OAuth 阶段
HTTP Method
HTTP Status
错误类别
请求耗时
```

EvaOAuth 不会把 OAuth 凭据写入普通 Debug 日志。

日志中不应该出现：

```text
client_secret
access_token
refresh_token
OAuth1 token secret
PKCE verifier
Authorization header
原始 Callback 参数
包含凭据的 Request / Response Body
```

目标是：

> 在能够排查第三方 OAuth 问题的同时，不让日志本身变成凭据存储。

---

## 异常处理

EvaOAuth 提供一组简单的异常类型：

```php
use Eva\EvaOAuth\Exception\CallbackException;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
```

常见处理：

```php
try {
    $result = $oauth->callback('github', $_GET);
} catch (CallbackException $e) {
    // Callback 无效、过期或已经使用。
} catch (ProviderException $e) {
    // Provider 返回错误或无效响应。
} catch (TransportException $e) {
    // HTTP 请求失败。
}
```

异常消息会经过安全处理。

不要依赖异常对象获得 Provider 原始错误信息或包含凭据的异常链。

排查集成问题时应使用安全 Debug 日志。

---

## Provider 特殊授权参数

一些 Provider 需要额外的授权参数，例如：

```text
access_type
prompt
login_hint
audience
resource
```

应该通过 Provider 配置这些参数，而不是手工修改 EvaOAuth 生成的 Authorization URL。

这样可以继续让 EvaOAuth 控制：

```text
state
PKCE
其他安全参数
```

---

## 多 Provider

一个 `OAuth` 实例可以同时注册多个 Provider：

```php
$oauth = new OAuth([
    'github' => $github,
    'google' => $google,
    'flickr' => $flickr,
]);
```

然后显式选择：

```php
$oauth->authorize('github');
$oauth->authorize('google');
$oauth->authorize('flickr');
```

建议每个 Provider 使用独立 Callback URI：

```text
/oauth/github/callback
/oauth/google/callback
/oauth/flickr/callback
```

Callback Route 应该直接对应固定 Provider。

不要根据不可信的 Callback Query 参数动态选择 Provider。

---

## Session 建议

使用默认 Session State Storage 时，普通 PHP Session 安全实践仍然适用。

生产环境通常应该考虑：

```text
Secure Cookie
HttpOnly Cookie
SameSite=Lax
Strict Session Mode
TLS
```

OAuth 登录转换成应用自身登录状态之后，可以重新生成 Session ID：

```php
session_regenerate_id(true);
```

EvaOAuth 管理的是 OAuth 授权事务。

应用自己的登录 Session 仍由应用负责。

---

## 安全边界

EvaOAuth 默认处理 OAuth 流程中的这些安全问题：

- 安全随机 State
- OAuth 2.0 PKCE S256
- Callback 一次性事务
- Provider / 配置绑定
- HTTPS Provider Endpoint
- Callback 严格校验
- Token 严格解析
- 携带凭据请求的 Origin 限制
- 安全异常信息
- 凭据安全日志

EvaOAuth 不负责：

- 应用自己的登录 Session
- 本地用户系统
- 自动账号合并
- 权限系统
- OIDC ID Token 校验
- JWT 校验
- OAuth Discovery
- 自动 Token Refresh
- 隐藏的自动重试

这些应该由应用或其他专门模块处理。

---

## 为什么 EvaOAuth 内部使用 League

EvaOAuth 不重复实现已经成熟的 OAuth 协议核心。

OAuth 1.0a 和 OAuth 2.0 的基础协议能力由成熟的 League Library 提供。

EvaOAuth 自己关注的是应用真正需要的部分：

```text
统一 API
Provider Model
State Lifecycle
PKCE
Token
Identity
安全 HTTP 边界
Debug
异常映射
安全默认值
```

OAuth 1.0a 与 OAuth 2.0 在内部仍然保持独立。

只有在能真正简化应用代码的地方，EvaOAuth 才会统一它们。

更多内部设计见 [Architecture](ARCHITECTURE.md)。

---

## 从 EvaOAuth 1.x 升级

EvaOAuth 2.0 是一次破坏性重写。

旧版本中的：

```text
Service
requestAuthorize()
getAccessToken()
getTokenAndUser()
Storage
debug()
```

等接口已经被新的 2.0 模型取代。

完整迁移关系见 [UPGRADING.md](../UPGRADING.md)。