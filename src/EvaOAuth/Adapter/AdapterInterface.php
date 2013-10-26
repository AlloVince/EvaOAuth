<?php

namespace EvaOAuth\Adapter;

/**
 * @category   Zend
 * @package    Zend_Authentication
 * @subpackage Adapter
 */
interface AdapterInterface
{
    public function getConsumer();
    
    public function getRequestTokenUrl();

    public function getAccessToken($queryData, $token, $httpMethod, $request);
}
