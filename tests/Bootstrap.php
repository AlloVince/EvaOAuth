<?php
/** @var Composer\Autoload\ClassLoader $loader */
$loader = require_once __DIR__ . '/../vendor/autoload.php';

$loader->addPsr4('Eva\EvaOAuthTest\\', __DIR__ . '/EvaOAuthTest');
