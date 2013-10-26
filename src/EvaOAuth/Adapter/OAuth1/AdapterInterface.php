<?php

namespace EvaOAuth\Adapter\OAuth1;

use ZendOAuth\Token\Access as AccessToken;


/**
 * @category   Zend
 * @package    Zend_Authentication
 * @subpackage Adapter
 */
interface AdapterInterface
{
    public function setOptions(array $params);
    public function getConsumer();
    public function accessTokenToArray(AccessToken $accessToken);
}
