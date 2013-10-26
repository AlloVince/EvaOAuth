<?php
require_once '../init_autoloader.php';

use EvaOAuth\Service as OAuthService;

$oauth = new OAuthService();
$oauth->setOptions(array(
    'callbackUrl' => 'http://localhost/EvaOAuth/examples/access.php',
    'consumerKey' => $config->oauth->oauth2->douban->consumer_key,
    'consumerSecret' => $config->oauth->oauth2->douban->consumer_secret,
));
$oauth->initAdapter('Douban', 'Oauth2');

$requestToken = $oauth->getStorage()->getRequestToken();
$accessToken = $oauth->getAdapter()->getAccessToken($_GET, $requestToken);
$accessTokenArray = $oauth->getAdapter()->accessTokenToArray($accessToken);
$oauth->getStorage()->saveAccessToken($accessTokenArray);
$oauth->getStorage()->clearRequestToken();

p($accessTokenArray);


$oauth->initByAccessToken($accessTokenArray);
$adapter = $oauth->getAdapter();
$client = $adapter->getHttpClient();
$client->setUri('https://api.douban.com/v2/user/~me');
$response = $client->send();
p($response->getBody());


