<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

$config = include 'config.php';

foreach ($config as $key => $client) {
    printf("<a href='request.php?provider=%s'>%s</a><br>", $key, $key);
}
