EvaOAuth
=========

[![Latest Stable Version](https://poser.pugx.org/evaengine/eva-oauth/v/stable.svg)](https://packagist.org/packages/evaengine/eva-oauth)
[![License](https://poser.pugx.org/evaengine/eva-oauth/license.svg)](https://packagist.org/packages/evaengine/eva-oauth)
[![Build Status](https://travis-ci.org/AlloVince/EvaOAuth.svg?branch=feature%2Frefactoring)](https://travis-ci.org/AlloVince/EvaOAuth)
[![Coverage Status](https://coveralls.io/repos/AlloVince/EvaOAuth/badge.svg?branch=master)](https://coveralls.io/r/AlloVince/EvaOAuth?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/AlloVince/EvaOAuth/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/AlloVince/EvaOAuth/?branch=master)

EvaOAuth provides a standard interface for OAuth1.0(a) / OAuth2.0 client authorization, it is easy to integrate with any PHP project by very few lines code. 

## Features

- **Fully tested** 
- **Easy to debug**, enable debug mode will record every request and response, to help you find out problems.
- **Out-of-the-box**, already supported most popular websites including Facebook. Twitter, etc.
- **Scalable**, integrate a new oauth website just need 3 lines code.

## Quick Start

EvaOAuth can be found on [Packagist](https://packagist.org/packages/evaengine/eva-oauth). The recommended way to install this is through composer.

Edit your composer.json and add:

``` json
{
    "require": {
        "evaengine/eva-oauth": "~1.0"
    }
}
```

And install dependencies:

``` shell
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

Let's start a example of Facebook Login, if you have already have a Facebook developer account and created an app, prepare a request.php as below: 

``` php
$service = new Eva\EvaOAuth\Service('Facebook', [
    'key' => 'You Facebook App ID',
    'secret' => 'You Facebook App Secret',
    'callback' => 'http://localhost/EvaOAuth/example/access.php'
]);
$service->requestAuthorize();
```

Run request.php in browser, will be redirected to Facebook authorization page. After user confirm authorization, prepare the access.php in callback:

``` php
$token = $service->getAccessToken();
```

Once access token received, we could use access token to visit any protected resources.

``` php
$httpClient = new Eva\EvaOAuth\AuthorizedHttpClient($token);
$response = $httpClient->get('https://graph.facebook.com/me');
```
 
That's it, more usages please check examples and wiki.

## Providers

EvaOAuth supports most popular OAuth services as below:

- OAuth2.0
  - Douban
  - Facebook
  - Tencent
  - Weibo
- OAuth1.0
  - Twitter
  
Creating a custom provider require only few lines code, for OAuth2 sites:


``` php
namespace YourNamespace;

class Foursquare extends \Eva\EvaOAuth\OAuth2\Providers\AbstractProvider
{
    protected $authorizeUrl = 'https://foursquare.com/oauth2/authorize';
    protected $accessTokenUrl = 'https://foursquare.com/oauth2/access_token';
}
```

Then register to service and create instance:

```
use Eva\EvaOAuth\Service;
Service::registerProvider('foursquare', 'YourNamespace\Foursquare');
$service = new Service('foursquare', [
    'key' => 'Foursquare App ID',
    'secret' => 'Foursquare App Secret',
    'callback' => 'http://somecallback/'
]);
```

## Implementation Specification

EvaOAuth based on amazing http client library [Guzzle](https://github.com/guzzle/guzzle), use fully OOP to describe OAuth specification.

Refer wiki for details:
 
- [OAuth2.0](https://github.com/AlloVince/EvaOAuth/wiki/OAuth2.0-Specification-Implementation)