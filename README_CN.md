EvaOAuth
=========

[![Latest Stable Version](https://poser.pugx.org/evaengine/eva-oauth/v/stable.svg)](https://packagist.org/packages/evaengine/eva-oauth)
[![License](https://poser.pugx.org/evaengine/eva-oauth/license.svg)](https://packagist.org/packages/evaengine/eva-oauth)
[![Build Status](https://travis-ci.org/AlloVince/EvaOAuth.svg?branch=feature%2Frefactoring)](https://travis-ci.org/AlloVince/EvaOAuth)
[![Coverage Status](https://coveralls.io/repos/AlloVince/EvaOAuth/badge.svg?branch=master)](https://coveralls.io/r/AlloVince/EvaOAuth?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/AlloVince/EvaOAuth/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/AlloVince/EvaOAuth/?branch=master)

EvaOAuth 是一个统一接口设计的PHP OAuth Client库，兼容OAuth1.0与OAuth2.0规范，可以通过10多行代码集成到任意项目中。

## 为什么选择EvaOAuth 

经过若干项目考验， EvaOAuth1.0 根据实际需求进行了一次完全重构，主要的一些特性如下：

- **标准接口**，无论OAuth1.0或OAuth2.0，同一套代码实现不同工作流，并且获取一致的数据格式，包括用户信息和Token。  
- **充分测试**，所有关键代码进行单元测试，同时通过CI保证多版本PHP下的可用性。 
- **容易调试**，开启Debug模式后，Log中会记录OAuth流程中所有的URL、Request、Response，帮助定位问题。
- **开箱即用**，项目已经内置了主流的OAuth网址支持，如微博、QQ、Twitter、Facebook等。
- **方便扩展**，可以通过最少3行代码集成新的OAuth服务，工作流程提供事件机制。 

## 快速开始

EvaOAuth可以通过[Packagist](https://packagist.org/packages/evaengine/eva-oauth)下载，推荐通过Composer安装。

编辑composer.json文件为：

``` json
{
    "require": {
        "evaengine/eva-oauth": "~1.0"
    }
}
```

然后通过Composer进行安装。

``` shell
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

下面通过一个实例演示如何集成豆瓣登录功能。假设已经在[豆瓣开发者](http://developers.douban.com)创建好一个应用。准备一个request.php如下：

``` php
require_once './vendor/autoload.php'; //加载Composer自动生成的autoload
$service = new Eva\EvaOAuth\Service('Douban', [
    'key' => 'You Douban App ID',  //对应豆瓣应用的API Key
    'secret' => 'You Douban App Secret', //对应豆瓣应用的Secret
    'callback' => 'http://localhost/EvaOAuth/example/access.php' //回调地址
]);
$service->requestAuthorize();
```

在浏览器中运行request.php，如果参数正确则会被重定向到豆瓣授权页面，登录授权后会再次重定向回我们设置的`callback`。因此再准备好access.php文件：

``` php
$token = $service->getAccessToken();
```

这样就拿到了豆瓣的Access Token，接下来可以使用Token去访问受保护的资源：

``` php
$httpClient = new Eva\EvaOAuth\AuthorizedHttpClient($token);
$response = $httpClient->get('https://api.douban.com/v2/user/~me');
```
 
这样就完成了OAuth的登录功能。更多细节可以参考代码的[示例](https://github.com/AlloVince/EvaOAuth/tree/master/examples)以及[Wiki](https://github.com/AlloVince/EvaOAuth/wiki)页面。

## OAuth网站支持

EvaOAuth将一个OAuth网站称为一个Provider。目前支持的Provider有：

- OAuth2.0
  - 豆瓣（Douban）
  - Facebook
  - QQ （Tencent）
  - 微博 （Weibo）
- OAuth1.0
  - Twitter
  
新增一个Provider仅需数行代码，下面演示如何集成Foursquare网站：


``` php
namespace YourNamespace;

class Foursquare extends \Eva\EvaOAuth\OAuth2\Providers\AbstractProvider
{
    protected $authorizeUrl = 'https://foursquare.com/oauth2/authorize';
    protected $accessTokenUrl = 'https://foursquare.com/oauth2/access_token';
}
```

然后将Provider注册到EvaOAuth就可以使用了。

``` php
use Eva\EvaOAuth\Service;
Service::registerProvider('foursquare', 'YourNamespace\Foursquare');
$service = new Service('foursquare', [
    'key' => 'Foursquare App ID',
    'secret' => 'Foursquare App Secret',
    'callback' => 'http://somecallback/'
]);
```

## 数据存储

在OAuth1.0的流程中，需要将Request Token保存起来，然后在授权成功后使用Request Token换取Access Token。因此需要数据存储功能。

EvaOAuth的数据存储通过[Doctrine\Cache](https://github.com/doctrine/cache)实现。默认情况下EvaOAuth会将数据保存为本地文件，保存路径为`EvaOAuth/tmp`。

可以在EvaOAuth初始化前任意更改存储方式及存储位置，例如将文件保存位置更改为`/tmp`：

``` php
Service::setStorage(new Doctrine\Common\Cache\FilesystemCache('/tmp'));
```

或者使用Memcache保存：

``` php
$storage = new \Doctrine\Common\Cache\MemcacheCache();
$storage->setMemcache(new \Memcache());
Service::setStorage($storage);
```

## 事件支持

EvaOAuth 定义了若干事件方面更容易的注入逻辑

- `BeforeGetRequestToken`: 获取Request Token前触发。
- `BeforeAuthorize`: 重定向到授权页面前触发。
- `BeforeGetAccessToken`: 获取Access Token前触发。

比如我们希望在获取Access Token前向HTTP请求中加一个自定义Header，可以通过以下方式实现：

``` php
$service->getEmitter()->on('beforeGetAccessToken', function(\Eva\EvaOAuth\Events\BeforeGetAccessToken $event) {
    $event->getRequest()->addHeader('foo', 'bar');
});
```

## 技术实现

EvaOAuth 基于强大的HTTP客户端库[Guzzle](https://github.com/guzzle/guzzle)，并通过OOP方式对OAuth规范进行了完整的描述。

为了避免对规范的诠释上出现误差，底层代码优先选择规范描述中的角色与名词，规范间差异则在上层代码中统一。

因此如果没有同时支持两套规范的需求，可以直接使用OAuth1.0、OAuth2.0分别对应的工作流。

详细用例可以参考Wiki：
 
- [OAuth1.0](https://github.com/AlloVince/EvaOAuth/wiki/OAuth1.0-Specification-Implementation)
- [OAuth2.0](https://github.com/AlloVince/EvaOAuth/wiki/OAuth2.0-Specification-Implementation)

## Debug与Log

开启Debug模式将在Log中记录所有的请求与响应。

``` php
$service->debug('/tmp/access.log');
```

请确保PHP对log文件有写入权限。

## API文档

首先通过`pear install phpdoc/phpDocumentor`安装phpDocumentor，然后在项目根目录下运行`phpdoc`，会在`docs/`下生成API文档。

## 问题反馈及贡献代码

项目代码托管在 https://github.com/AlloVince/EvaOAuth，欢迎Star及Fork贡献代码。

有问题欢迎在[EvaOAuth Issue](https://github.com/AlloVince/EvaOAuth/issues)提出。

