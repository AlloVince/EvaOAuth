<?php
require_once 'service.php';

echo '<pre>';
$token = $service->getAccessToken();
var_dump($token);
$httpClient = new \Eva\EvaOAuth\AuthorizedHttpClient($token);
$httpClient->debug();
$response = $httpClient->get('https://graph.facebook.com/me');
echo $response;
