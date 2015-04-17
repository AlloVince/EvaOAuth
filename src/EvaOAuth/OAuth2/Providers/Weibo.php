<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;

/**
 * Class Weibo
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
class Weibo extends AbstractProvider
{
    /**
     * @var string
     */
    protected $authorizeUrl = 'https://api.weibo.com/oauth2/authorize';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://api.weibo.com/oauth2/access_token';

    /**
     * @var string
     */
    protected $accessTokenFormat = ResourceServerInterface::FORMAT_JSON;
}
