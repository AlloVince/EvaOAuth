<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Eva\EvaOAuth\Service;

$configDefault = include 'config.php';
$configLocal = include 'config.local.php';
$config = array_merge($configDefault, $configLocal);

$provider = @$_GET['provider'];
$service = new Service($provider, [
    'key' => $config[$provider]['key'],
    'secret' => $config[$provider]['secret'],
    'callback' => dirname('http://' . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"]) . '/access.php?provider=' . $provider
]);

