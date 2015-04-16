<?php
require_once 'service.php';

echo '<pre>';
$token = $service->getAccessToken();
var_dump($token);