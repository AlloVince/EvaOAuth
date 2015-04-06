<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

/**
 * Class Douban
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
class Douban extends AbstractProvider
{
    /**
     * @var string
     */
    protected $authorizeUrl = 'https://www.douban.com/service/auth2/auth';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://www.douban.com/service/auth2/token';
}
