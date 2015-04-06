<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;

/**
 * Class Tencent
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
class Tencent extends AbstractProvider
{
    /**
     * @var string
     */
    protected $authorizeUrl = 'https://graph.qq.com/oauth2.0/authorize';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://graph.qq.com/oauth2.0/token';

    /**
     * @var string
     */
    protected $accessTokenFormat = ResourceServerInterface::FORMAT_QUERY;
}
