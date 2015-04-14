<?php
require_once 'client.php';

echo '<pre>';
$token = $client->getAccessToken(new $providerClass());
var_dump($token);