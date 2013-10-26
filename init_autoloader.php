<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $loader = include __DIR__ . '/vendor/autoload.php';
} else {
    throw new RuntimeException('Unable to find loader. Run `php composer.phar install` first.');
}

$loader->add('EvaOAuth', __DIR__ . '/src');

function p($r)
{
    echo sprintf("<pre>%s</pre>", var_export($r, true));
}

$localConfig = __DIR__ . '/config/config.local.php';
$config = new Zend\Config\Config(include __DIR__ . '/config/config.default.php');
if(file_exists($localConfig)){
    $localConfig = new Zend\Config\Config(include $localConfig);
    $config = $config->merge($localConfig);
}
