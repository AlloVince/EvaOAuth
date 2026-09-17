# EvaOAuth 2.0

适用于 PHP 8.2–8.5、无需完整框架的 OAuth Client。在应用边界统一登录、Token 和用户身份，底层分别使用 OAuth2 + PKCE 与 OAuth1.0a 引擎。[English](README.md)

## GitHub 登录

在 GitHub 注册 OAuth App，将回调地址设置为下方完整 HTTPS 地址。用你的域名替换 `app.example`，在服务器环境设置 `GITHUB_CLIENT_ID` 和 `GITHUB_CLIENT_SECRET`。登录入口及回调路由都运行这段代码，且必须在任何输出之前执行：

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

这完成第三方登录；应用需要将 `(provider, id)` 对应到本地账户并执行权限检查。不要仅凭 email 自动关联账户。`name`、`email`、`avatar`、`emailVerified` 都可能为 null；即使申请 `user:email`，GitHub `/user` 也可能不返回私密邮箱，本 Provider 不自动请求 `/user/emails`。不要把 `$token` 发给浏览器。

可选 `$httpClient` 是你注入的 PSR-18 Client，不提供则使用内置传输。代码假定新请求可成功启动安全的服务器端 Session；生产应用应在初始化阶段检查 Session 启动失败。为回调异常配置安全错误处理，不输出异常页面。空查询开始授权，此路由专用于 GitHub。添加 Provider 时，在路由层将每个独立回调路径固定绑定到对应 Provider 名称；Facade 无法检查实际请求路径。绝不能根据 Query 参数选择回调 Provider。

## 安装与定位

发布 2.x 后可执行：

```sh
composer require evaengine/eva-oauth:^2.0
```

当前开发仓库使用 `composer install`。要求 PHP 8.2、8.3、8.4 或 8.5，JSON 和 OpenSSL 扩展。Composer 安装 Guzzle 及稳定版 League OAuth1/OAuth2 引擎；不需要 Laravel、Symfony、Google SDK 或完整框架。

单一协议可以直接使用 League。EvaOAuth 额外提供浏览器绑定的一次性授权事务、安全默认值、Provider 配置绑定的 Token、统一身份、统一 OAuth1/OAuth2 API、资源来源限制、固定异常和仅元数据的 Trace。OAuth1 的 Token Secret 不会被伪装成 OAuth2 Refresh Token。

## Provider 与通用 API

| Provider | 协议 | 默认行为 |
| --- | --- | --- |
| `Provider\GitHub` | OAuth2 code + S256 PKCE | `read:user user:email`，`/user` 身份 |
| `Provider\Google` | OAuth2 code + S256 PKCE | `openid profile email`，offline，UserInfo 身份 |
| `Provider\Flickr` | OAuth1.0a | Request Token、授权、Access Token、签名身份请求 |
| `Provider\OAuth2Provider` | OAuth2 code + S256 PKCE | 自定义 HTTPS endpoint、scope 和身份映射 |
| `Provider\OAuth1Provider` | OAuth1.0a | 自定义 HTTPS endpoint 和身份映射 |

`authorize(name)` 只返回 URL，不发送 Header。`callback(name, query)` 返回 `AuthorizationResult(provider, token, user)`。`exchange(name, query)` 只返回 Token；同一回调不能先调用 exchange 再调用 callback。`user(name, token)` 获取身份；`request(name, token, psrRequest)` 发送 Provider 绑定的授权请求；`refresh(name, token)` 返回新 OAuth2 Token，OAuth1 不支持 refresh。

## Google 与刷新

沿用上述安全 Session 和浏览器重定向处理，使用独立回调地址：

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

浏览器授权完成后，在回调路由执行，而不是紧接 authorize 执行：

```php
$result = $oauth->callback('google', $_GET);
$token = $result->token;
$user = $result->user;
```

之后，从受保护的服务器存储加载同一 Provider 的 Token：

```php
if ($token->refreshToken !== null && $token->isExpired()) {
    $token = $oauth->refresh('google', $token);
}
$record = $token->toArray();
$restored = \Eva\EvaOAuth\Token::fromArray($record);
```

`toArray()` **包含明文凭据**，请加密保存、限制访问，在刷新成功后原子替换记录。JSON/debug 输出脱敏，不能用于持久化；禁止 PHP serialize。`expiresAt` 是可空 Unix 时间戳，缺失表示未知，不代表永久有效。`expires_in=0` 表示立即过期，绝不能转换成缺失的到期时间；此 Token 必须先成功刷新才能获取身份。资源请求不会自动刷新。刷新响应未包含新 refresh token 时保留旧值，包含时使用新值。按账户串行刷新以避免轮换竞争；缺少、撤销或过期的刷新凭据应重新授权，而非无限重试。

Google 默认申请 `access_type=offline`，但不保证每次登录都返回 refresh token，通常首次授权才返回。需要重新同意时，可使用 Google 相同 endpoint、`sub` 映射的通用 OAuth2 Provider，配置 `issuer: 'https://accounts.google.com'` 和 `authorizationParameters: ['access_type' => 'offline', 'prompt' => 'consent']`，不要每次正常登录都强制同意。修改配置会改变 Token 绑定，各流程必须保持配置一致。Google 测试模式授权可能过期；同意屏幕发布、应用验证和 Scope 审核由应用负责。

2026-09-17 已核对 [Google 官方服务器端指南](https://developers.google.com/identity/protocols/oauth2/web-server) 与 [官方 discovery](https://accounts.google.com/.well-known/openid-configuration)：

- 授权：`https://accounts.google.com/o/oauth2/v2/auth`
- Token 交换与刷新：`https://oauth2.googleapis.com/token`
- UserInfo：`https://openidconnect.googleapis.com/v1/userinfo`

身份使用经授权 UserInfo 响应中的 `sub`，不信任未经验证的 ID Token。EvaOAuth 不提供 OIDC/JWT 验证、自动 discovery 或 DPoP。

## Flickr：相同的应用边界

在已启动的 Session 内，同样调用 authorize/callback/result；OAuth1 Request Token 和 HMAC 签名由引擎负责：

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

将浏览器重定向到 `$url`，回调路由执行：

```php
$result = $oauth->callback('flickr', $_GET);
$token = $result->token;
$user = $result->user;
```

Flickr 默认只读权限；只有需要时才使用第四个 `permissions` 参数指定 `write` 或 `delete`。持久化时通过受保护的 `toArray()` 记录同时保存 `accessToken` 和 `tokenSecret`。OAuth1 没有 OAuth2 式 refresh grant。适配器拒绝歧义参数（重名、Query/Form 冲突、括号名称或调用者传入的 `oauth_*` 字段）、已有 Authorization Header 和不支持的 Form Stream，而非错误签名。参见 [Flickr 官方文档](https://www.flickr.com/services/api/auth.oauth.html)。

## 新增 OAuth2 Provider

Endpoint 只能来自可信部署配置，不能取自回调。Mapper 必须返回 Identity，拒绝缺失或非法标识符。以下保留域名只是自建服务示例：

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

授权后使用 `callback('custom', $_GET)`。另一种客户端认证为默认的 `client_secret_post`。可选配置包括 `authorizationParameters`、`scopeSeparator`、`responseScopeSeparator`、`issuer` 和 `resourceOrigins`。`scopeSeparator` 用于授权请求，`responseScopeSeparator` 用于 Token 响应，默认空格，GitHub 使用逗号。可选可信 `issuer` 用于校验回调中的 `iss`：提供时必须精确匹配；未配置 issuer 却收到 iss 时拒绝。Google 配置为 `https://accounts.google.com`。按照 RFC 6749 忽略未知 OAuth2 回调扩展参数，但已识别字段仍严格检查类型及结构，不能覆盖必要的 state/PKCE/grant 参数。额外资源来源必须是完整 HTTPS origin，仅添加可信的凭据接收方。可复用 Provider 继承 readonly Provider 并覆盖 `identity()`，无需复制协议流程。League 第三方 Provider 不能直接代替 EvaOAuth Provider。

## PSR-18、PSR-3 与授权请求

一次注入 PSR-18 Client 和 PSR-3 Logger。默认使用 Guzzle，不输出日志。承接上面的 `$custom`：

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

使用通过同一 custom Provider 获取的 Token：

```php
$response = $oauth->request(
    'custom',
    $token,
    new \GuzzleHttp\Psr7\Request('GET', 'https://api.example/me'),
);
$status = $response->getStatusCode();
```

注入的 Client 必须开启 TLS 验证、设置有限超时、禁止自动重定向及原始流量日志。EvaOAuth 拒绝 3xx，但不能撤回被错误 Client 自动重定向泄露的凭据。资源请求限制于配置的来源，拒绝 Query Bearer Token。默认总超时 15 秒、连接超时 5 秒，不自动重试。

### 安全 Trace 的实际字段

Logger 收到 debug 级事件，其结构如下，数值仅为示意而非真实请求记录：

```text
oauth.http.response {operation: "http", method: "POST", status: 200, duration_ms: 12.5}
oauth.http.failure  {operation: "http", method: "GET", category: "transport", duration_ms: 15.0}
```

`method` 为白名单 HTTP 方法或 `OTHER`；`operation` 当前固定为 `http`，不是 Provider 或 grant 名称。HTTP 错误仍记录带状态码的 response 事件，非法响应体不会另发 parse 事件。Logger 自身失败不会破坏授权。事件不包含 URL、Header、Body、Query、第三方错误文本或异常链。通过事件顺序、状态及耗时排查问题；关联 ID 应由应用生成，不取自 OAuth 凭据。禁止开启 Guzzle debug、dump 请求对象或在 APM/access log 中记录完整回调地址。

## 异常与安全责任

所有公开 OAuth 异常继承 `Exception\OAuthException`，信息固定，移除 previous exception：

| 异常 | 建议处理 |
| --- | --- |
| `ConfigurationException` | 修复配置、Session、凭据或 Token 绑定 |
| `CallbackException` | 拒绝畸形、过期、拒绝授权、绑定不符或重放回调，重新登录 |
| `ProviderException` | HTTP、数据、签名失败或 Token 过期；显示通用错误，检查安全元数据 |
| `TransportException` | 网络错误，仅在安全且有界的操作中考虑应用级重试 |
| `UnsupportedOperationException` | 不对此协议或 Token 使用该操作 |

不要暴露堆栈、局部变量或凭据，应用校验和 PHP 错误也需要安全的全局错误处理。事务在交换前消费，同一回调不可重试；需要独立恢复身份获取时使用 `exchange()` 后再 `user()`。

- Endpoint、资源和回调强制 HTTPS，每个配置的 Provider 使用独立、精确注册的回调。不得从用户输入派生 endpoint 或重定向目标。账户关联还需显式确认和应用 CSRF 防御。
- 构建默认 Store 前启动 Session，启用 strict mode、secure/HttpOnly/SameSite=Lax Cookie。事务访问期间保持 Session 锁，登录后更新 Session ID，应用自行管理会话到期和退出。
- 默认每浏览器最多 10 个待授权事务，600 秒过期。`State\SessionStateStore(ttl: ..., capacity: ...)` 支持 1–1800 秒及 1–100 条，超限淘汰最早事务。自定义 `StateStore` 必须跨进程实现浏览器隔离、原子一次性 consume、过期和容量限制。MemoryStateStore 仅用于测试，不用于生产跨请求登录。
- 库负责 State、S256 PKCE、安全随机数和 OAuth1 临时凭据；你负责保护 Session 后端，不能跨浏览器共享待授权状态。登录、回调和刷新保持 Provider 配置一致，轮换 Client Secret 可能使旧 Token 绑定失效。
- 加密 Token 记录，限制权限，保护备份与密钥，必要时撤销/删除授权，控制刷新并发。脱敏不保护显式属性读取、toArray、对象强制转换或自行添加的遥测。
- Web Server、Proxy 和 APM 不记录回调 Query。回调不加载第三方资源，使用 no-store/no-referrer 并重定向至干净本地地址。展示身份数据前转义，不假定邮箱存在或已经验证。
- 保证配置的域名、DNS 和代理可信；origin 限制不是通用网络沙箱。Provider 注册、Scope 审查、限流、同意政策及事故响应由应用负责。

## 开发与迁移

```sh
composer install
composer verify
composer validate --strict
```

verify 执行 PHPUnit、PHPStan level 6、PSR-12 PHPCS、严格 Composer 校验和锁定依赖安全审计（含 abandoned）。项目使用原生类型且不写代码注释，仅关闭 `missingType.iterableValue`，没有 baseline 或全局忽略。CI 配置 PHP 8.2–8.5 和最低依赖任务；配置存在不等于远程已执行通过。

两个 README 的所有 PHP fenced snippets 由 `tests/Modern/DocumentationTest.php` 提取并使用 Mock HTTP 执行，协议测试另行验证实际 wire request。无需 Demo 网站或真实凭据。旧 src、示例网站和旧测试已删除，1.x 实现保留在 Git 历史中。测试及开发工具位于源码仓库，不包含在生产包归档中。

参见 [迁移指南](UPGRADING.md)、[架构](docs/ARCHITECTURE.md)、[验收证据](docs/ACCEPTANCE.md)、[发布流程](docs/RELEASING.md) 和 [ADR](docs/ADR-001.md)。许可证：[BSD-3-Clause](LICENSE)。
