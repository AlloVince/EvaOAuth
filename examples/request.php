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

$requestToken = $oauth->getAdapter()->getRequestToken();
$oauth->getStorage()->saveRequestToken($requestToken);
$requestTokenUrl = $oauth->getAdapter()->getRequestTokenUrl();
header("location: $requestTokenUrl");
