# EvaOAuth 2.0 长程翻新任务

翻新 EvaOAuth，目标是发布一个真正可用的 **EvaOAuth 2.0**，而不是单纯升级旧依赖或让旧代码重新运行。

## 最终定位

EvaOAuth 2.0 是一个现代、framework-agnostic 的 PHP OAuth Client。

核心原则延续 EvaOAuth 原来的设计思想：

> OAuth1 / OAuth2 底层尊重各自协议模型，在应用边界提供统一、简单的授权、Token、用户身份和 Provider 使用体验。

不要为了统一 API 污染底层协议模型，也不要为了领域建模把 OAuth 的复杂度重新暴露给使用者。

允许重写实现，不要求保持 1.x 源码兼容，但必须保留 EvaOAuth 有价值的设计思想，并提供明确的 migration path。

## 架构研究

开始修改前，先完整研究：

* 当前 EvaOAuth 代码、测试、历史设计和 1.x API
* OAuth1 / OAuth2 当前标准和安全实践
* 当前 PHP OAuth 生态，特别是成熟实现和 Provider 体系

重点做出一个明确架构决策：

> EvaOAuth 是否还应该自行实现协议核心，还是使用成熟 OAuth 实现作为底层 engine，让 EvaOAuth 专注于统一 API、Provider、Identity、安全默认值和开发体验。

不要为了“自己实现”而重复实现已经成熟的协议代码。

关键架构决策记录到 ADR 或相应文档中。

---

# 最终能力

至少覆盖：

* 现代 PHP 8.x 与严格类型
* OAuth 1.0a
* OAuth 2.0 Authorization Code
* PKCE
* state / CSRF protection
* Refresh Token
* 统一 Token 模型
* 统一 User Identity
* Provider 扩展模型
* PSR HTTP 生态
* PSR Logger
* 安全的 Debug / HTTP Trace
* 清晰的 Exception 模型
* GitHub Actions
* Unit / Integration Tests
* Static Analysis
* Coding Style
* Dependency / Security Check
* GitHub Provider
* Google Provider
* 至少一个 OAuth1 Provider
* README
* Architecture 文档
* 1.x → 2.x Migration Guide
* Release-ready Composer package

不需要开发网站或 Web Demo。

---

# 上层 API

最终 API 必须保持简单。

README 第一屏应能展示完整授权流程，例如：

```php
$oauth = new OAuth([...]);

$url = $oauth->authorize('github');

$result = $oauth->callback('github', $_GET);

$result->token;
$result->user->id;
$result->user->name;
$result->user->email;
```

具体 API 可以不同，但必须满足：

> 一个普通 PHP 开发者阅读 README 5 分钟后，可以理解如何使用 EvaOAuth。

不能因为内部模型设计复杂，导致用户需要理解大量 OAuth 协议细节。

---

# Provider 模型

至少实现：

* GitHub OAuth2
* Google OAuth2
* 一个真实 OAuth1 Provider

普通 Provider 不应重新实现整个 OAuth 流程。

新增一个标准 OAuth2 Provider 应主要由 endpoint、scope、用户信息映射等少量配置或代码完成。

应存在测试 Provider，用自动化测试证明扩展模型确实成立。

避免 Provider 之间出现大量复制代码。

---

# 自动化协议验收

不依赖真实网站，通过 Mock HTTP 完整测试至少三个流程：

### OAuth2 Authorization Code + PKCE

验证：

* Authorization URL
* state
* PKCE code verifier / challenge
* callback state validation
* token request
* HTTP method
* headers
* parameters
* Token 解析

### OAuth2 Refresh Token

验证：

* refresh request
* client authentication
* parameters
* 新 Token
* expiry
* refresh token 保留或替换行为

### OAuth1

完整验证：

```text
Request Token
→ Authorization
→ Access Token
→ Authorized Request
```

至少验证：

* nonce
* timestamp
* signature base string
* signature
* authorization header
* request token
* verifier
* access token

测试不能只检查“请求成功”，必须验证实际 HTTP 请求内容。

---

# 安全基线

关键安全行为必须有自动化测试，至少包括：

* state 校验
* PKCE
* cryptographically secure random
* client_secret 不进入普通日志
* access token / refresh token 日志脱敏
* OAuth1 secret 日志脱敏
* callback 参数异常处理
* redirect / state mismatch
* OAuth1 signature correctness
* HTTP 错误映射
* Provider 返回非法数据
* Exception 不泄露敏感凭据

Debug 能力必须真正有助于排查第三方 OAuth 问题，但默认安全。

---

# 核心设计验收

最终代码必须能清楚回答：

```text
OAuth1 与 OAuth2 的协议模型在哪里分开？

它们在哪里统一？

Provider 的特殊行为在哪里扩展？

Token 如何统一？

User Identity 如何统一？

HTTP Client 如何替换？

Logger 如何替换？

状态存储如何处理？

第三方错误如何映射成 EvaOAuth Exception？
```

这些答案必须直接对应清晰的代码结构。

不能最终做成一个简单换名字的 `league/oauth2-client` wrapper。

EvaOAuth 必须仍然拥有自己的产品价值：

> 用统一且舒服的 API 解决应用开发者实际面对的 OAuth 登录问题。

---

# 工程验收

仓库必须提供统一入口：

```bash
composer verify
```

至少执行：

```text
tests
static analysis
coding style
dependency validation
security / vulnerability check
```

全部通过。

同时：

```bash
composer validate --strict
```

必须通过。

GitHub Actions 必须自动执行相同验证，并覆盖项目声明支持的主要 PHP 8.x 版本。

核心库不得依赖 Symfony、Laravel 等完整 Framework 才能运行。

删除废弃、abandoned 或明显没有必要的历史依赖。

---

# 1.x 迁移

不要求 2.0 保持源码兼容。

必须提供：

```text
UPGRADING.md
```

至少解释这些旧概念在 2.0 中的对应关系：

```text
Service
requestAuthorize()
getAccessToken()
getTokenAndUser()
Provider
Storage
debug()
```

说明：

* 哪些 API 被删除
* 哪些 API 被替代
* 为什么变化
* 最常见的 1.x 使用方式如何迁移到 2.0

不需要为了兼容旧代码破坏 2.0 设计。

---

# README 验收

README 至少应让第一次看到 EvaOAuth 的开发者快速理解：

1. EvaOAuth 是什么
2. 为什么不直接使用底层 OAuth library
3. Installation
4. 最简单 OAuth2 登录
5. 获取 User Identity
6. Refresh Token
7. OAuth1 示例
8. 添加 Provider
9. Debug
10. Security
11. Supported Providers
12. Migration from 1.x

README 中的示例必须由测试或可执行 example 验证，避免文档与代码漂移。

---

# 最终 Acceptance

维护：

```text
docs/ACCEPTANCE.md
```

至少包含：

```text
[PASS] Modern PHP 8.x
[PASS] OAuth2 Authorization Code
[PASS] PKCE
[PASS] State Validation
[PASS] Refresh Token
[PASS] OAuth1
[PASS] GitHub Provider
[PASS] Google Provider
[PASS] OAuth1 Provider
[PASS] Unified Token
[PASS] Unified Identity
[PASS] Extensible Provider Model
[PASS] Safe Debug Logging
[PASS] Exception Model
[PASS] composer verify
[PASS] GitHub Actions
[PASS] Migration Guide
[PASS] README Quick Start
```

每个 PASS 后必须给出对应的：

* Test
* Source
* Example
* Documentation

之一或多个作为证据。

不能仅由 Agent 自己声明完成。

---

# 人工最终验收

最终我只需要做很少的人工验收。

在干净环境执行：

```bash
composer install
composer verify
composer validate --strict
```

全部成功。

然后阅读 README，并重点检查：

1. 第一次使用是否足够简单
2. GitHub OAuth2 流程是否一眼能懂
3. OAuth1 / OAuth2 是否拥有统一体验
4. 新增 Provider 是否简单
5. Debug 是否有价值
6. 整个项目是否像一个 2026 年仍值得安装的 PHP Library

如果这两部分都令人满意，即认为 EvaOAuth 2.0 翻新成功。

---

# 推进方式

这是一个长程任务。

围绕最终目标自行拆阶段推进，而不是只完成某个局部技术升级。

推荐顺序：

```text
考古与架构研究
↓
Characterization Tests
↓
现代 PHP / CI 基础
↓
核心架构
↓
OAuth2
↓
OAuth1
↓
统一 API / Token / Identity
↓
Provider
↓
Security / Debug
↓
Migration
↓
README / Release
```

每个阶段必须形成：

* 可运行
* 可测试
* 可复核
* 可继续迭代

的仓库状态。

不要为了制造阶段性进度加入明显会在下一阶段被推翻的临时代码。

遇到架构问题时优先继续研究、实验和验证，再做决定。

最终目标不是：

> “十年前的 EvaOAuth 又能跑了。”

而是：

> **一个陌生 PHP 开发者在 2026 年看到这个仓库，会认为 EvaOAuth 又是一个值得安装和使用的项目。**
