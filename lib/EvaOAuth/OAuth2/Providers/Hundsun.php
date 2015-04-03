<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

class Hundsun extends AbstractProvider
{
    protected $authorizeUrl = 'https://open.hs.net/oauth2/oauth2/authorize';

    protected $accessTokenUrl = 'https://open.hs.net/oauth2/oauth2/token';
}
