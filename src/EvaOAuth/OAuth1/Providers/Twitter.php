<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Providers;

class Twitter extends AbstractProvider
{
    protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

    protected $authorizeUrl = 'https://api.twitter.com/oauth/authorize';

    protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';
}
