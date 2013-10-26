EvaOAuth
=========

[EvaOAuth](http://avnpc.com/pages/evaoauth)是一个统一接口设计，兼容OAuth1.0与OAuth2.0规范的php oauth登录模块，目前支持超过20个主流网站的Oauth登录，包括：

- 国外
  1. Facebook Oauth2
  2. Twitter Oauth1
  3. Google Oauth2
  4. Github Oauth2
  5. Msn Live Oauth2
  6. Flickr Oauth1
  7. LinkedIn Oauth1
  8. Yahoo Oauth1
  9. Dropbox Oauth1
  10. Foursquare Oauth2
  11. Disqus Oauth2
- 国内
  1. 豆瓣 Douban Oauth1
  2. 豆瓣 Douban Oauth2
  3. 微博 Weibo Oauth2
  4. 人人网 Renren Oauth2
  5. 腾讯QQ Tencent Oauth2
  6. 开心网 Kaixin Oauth2
  7. 百度 Baidu Oauth2
  8. 360 Qihoo Oauth2
  9. 网易微博 Netease Oauth2
  10. 搜狐微博 Sohu Oauth1
  11. 天涯 Tianya Oauth1

EvaOAuth统一接口规范，上面的任何一个第三方网站，在使用EvaOAuth时的代码与流程都是完全一致的，也可以很简单的扩展并加入新的第三方网站。

最终可以用20行左右代码实现以上所有支持网站的完整OAuth登录授权。


获得代码
-------------

推荐在项目中使用Composer进行一键安装。

请编辑composer.json，加入

    "require": {
        "AlloVince/EvaOAuth": "dev-master"
    },

然后运行

    php composer.phar install

即可。

EvaOAuth要求PHP版本必须高于5.3.3，并主要依赖以下几个ZF2模块：

- [ZendOAuth](https://github.com/zendframework/ZendOAuth)
- Zend\Session 储存Token信息，也可以自己扩展Storage\StorageInterface实现其他存储方式
- Zend\Json

在同目录下会自动创建vendor目录并下载所有的依赖，在你的项目中，只需要包含自动生成的vendor/autoload.php即可。

或者请访问[EvaOAuth的Github项目主页](https://github.com/AlloVince/EvaOAuth)。

###在Windows环境下composer.phar的安装配置

参考之前的[ZF2在Windows下的环境搭建](http://avnpc.com/pages/zend-framework-2-installation-for-windows)，假设我们的php.exe目录在d:\xampp\php，那么首先将php目录加入windows环境变量。

    cd d:\xampp\php
    php -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));"


同目录下编辑文件 composer.bat，内容为

    @ECHO OFF
    SET composerScript=composer.phar
    php "%~dp0%composerScript%" %*

运行

    composer -V
    
检查composer安装是否成功。

进入EvaOAuth目录下运行：

    php D:\xampp\php\composer.phar install


申请应用
-------------

实现Oauth登录必须先在相应的第三方网站上申请应用并获得的consumer key与consumer secret，每个网站可能叫法不太一样，以豆瓣为例：

访问[豆瓣开发者](http://developers.douban.com/)，我的应用->创建新应用。创建完毕后

- 豆瓣应用的API Key对应EvaOAuth的consumer key
- 豆瓣应用的Secret对应EvaOAuth的consumer secret

快速开始
-------------

假设我们将EvaOAuth文件夹命名为Oauth并可以用http://localhost/Oauth/访问，同时已经安装好了所有的依赖。我们以豆瓣的Oauth2.0为例（因为豆瓣没有限制CallbackUrl的域，非常方便测试），用几十行代码构建一个完整的Oauth登录：

###获得Request Token并跳转到第三方进行授权

首先编写一个文件request.php，内容如下：

    require_once './vendor/autoload.php';
    use EvaOAuth\Service as OAuthService;

    $oauth = new OAuthService();
    $oauth->setOptions(array(
        'callbackUrl' => 'http://localhost/EvaOAuth/examples/access.php',
        'consumerKey' => 'XXX',
        'consumerSecret' => 'YYY',
    ));
    $oauth->initAdapter('Douban', 'Oauth2');
    
    $requestToken = $oauth->getAdapter()->getRequestToken();
    $oauth->getStorage()->saveRequestToken($requestToken);
    $requestTokenUrl = $oauth->getAdapter()->getRequestTokenUrl();
    header("location: $requestTokenUrl");

将consumerKey和consumerSecret替换为在豆瓣申请应用的API Key与Secret，然后访问

    http://localhost/EvaOAuth/examples/request.php

不出意外的话会被引导向豆瓣进行授权。

这一步中，我们取得了一个Request Token，然后将其暂存在Session里。然后被跳转往第三方网站进行授权。

虽然Request Token只存在于Oauth1.0规范，但是为了兼容两个规范，即便是Oauth2.0中，EvaOAuth也会构建一个虚拟的Request Token。

授权后会被带往我们指定的链接callbackUrl。


###用Request Token换取Access Token

继续编写另一个文件access.php

    require_once './vendor/autoload.php';
    use EvaOAuth\Service as OAuthService;

    $oauth = new OAuthService();
    $oauth->setOptions(array(
        'callbackUrl' => 'http://localhost/EvaOAuth/examples/access.php',
        'consumerKey' => 'XXX',
        'consumerSecret' => 'YYY',
    ));
    $oauth->initAdapter('Douban', 'Oauth2');

    $requestToken = $oauth->getStorage()->getRequestToken();
    $accessToken = $oauth->getAdapter()->getAccessToken($_GET, $requestToken);
    $accessTokenArray = $oauth->getAdapter()->accessTokenToArray($accessToken);
    $oauth->getStorage()->saveAccessToken($accessTokenArray);
    $oauth->getStorage()->clearRequestToken();

    print_r($accessTokenArray);

在这一步中，从Session中取出上一步获得的Request Token，配合CallbackUrl中携带的参数，最终会换取一个授权的Access Token。上例中我们会看到最终获得的Access Token信息：

    Array (
    [adapterKey] => douban
    [token] => tokenXXXXXXX
    [expireTime] => 2012-12-06 15:20:38
    [refreshToken] => refreshTokenXXXXXX
    [version] => Oauth2
    [remoteUserId] => 1291360
    )

###使用Access Token访问API

取得Access Token后，我们可以根据需求将其存入数据库或以其他方式存放。如果需要携带Access Token访问API也很简单，比如使用上例中的$accessTokenArray：


    $oauth = new OauthService();
    $oauth->setOptions(array(
        'consumerKey' => 'XXX',
        'consumerSecret' => 'YYY',
    ));
    $oauth->initByAccessToken($accessTokenArray);
    $adapter = $oauth->getAdapter();

    $client = $adapter->getHttpClient();
    $client->setUri('https://api.douban.com/v2/user/~me');
    $response = $client->send();
    print_r($response->getBody());


Access Token格式参考
-----------

EvaOAuth最终返回的Access Token格式是统一的，但是由于第三方应用规定的差别，并不是所有的参数都一定存在：

- adapterKey (Required) ： 第三方网站名，全小写 
- token (Required)  ： Access Token
- tokenSecret ： Access Token Secret，仅在Oauth1.0中存在
- version (Required)  ： 值为 Oauth1/Oauth2
- refreshToken ： Refresh Token
- expireTime ： Access Token过期时间，为UTC时间
- remoteUserId (Required) ： 当前用户在第三方网站的User Id
- remoteUserName ： 当前用户在第三方网站的User Name
- remoteExtra : 取得Access Token时的其他附加信息，如果有则为一个Json字符串

Oauth登录判断
-----------

每次用户的Oauth登录，只需要判定adapterKey/version/remoteUserId三个值完全一致时，即可认为是同一用户。

注意事项
----------

很多第三方应用内需要将测试用的域名加入白名单。

Yahoo Oauth必须在App Permissions栏选择并设定至少一项权限，否则会出现oauth_problem=consumer_key_rejected错误

