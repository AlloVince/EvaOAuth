<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;

/**
 * Class Facebook
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
class Facebook extends AbstractProvider
{
    /**
     * @var string
     */
    protected $authorizeUrl = 'https://www.facebook.com/dialog/oauth';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';

    /**
     * @var string
     */
    protected $accessTokenFormat = ResourceServerInterface::FORMAT_QUERY;
}
