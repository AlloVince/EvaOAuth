<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\Storage\StorageInterface;
use Eva\EvaOAuth\OAuth2\GrantStrategy\GrantStrategyInterface;

/**
 * OAuth2 Client
 * @package Eva\EvaOAuth\OAuth2
 */
class Client
{

    /**
     * Authorization Code Grant
     * http://tools.ietf.org/html/rfc6749#section-4.1
     */
    const GRANT_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Implicit Grant
     * http://tools.ietf.org/html/rfc6749#section-4.2
     * NOTICE: implicit type will skip authorize step.
     */
    const GRANT_IMPLICIT = 'implicit';

    /**
     * Resource Owner Password Credentials Grant
     * http://tools.ietf.org/html/rfc6749#section-4.3
     */
    const GRANT_PASSWORD = 'password';

    /**
     * Client Credentials Grant
     * http://tools.ietf.org/html/rfc6749#section-4.4
     */
    const GRANT_CLIENT_CREDENTIALS = 'client_credentials';

    /**
     * @var \GuzzleHttp\Client
     */
    protected static $httpClient;

    /**
     * @var array
     */
    protected static $httpClientDefaultOptions = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $grantStrategyName = self::GRANT_AUTHORIZATION_CODE;

    /**
     * @var GrantStrategyInterface
     */
    protected $grantStrategy;

    /**
     * @var array
     */
    protected static $grantStrategyMapping = [];

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public static function setHttpClientDefaultOptions(array $options)
    {
        self::$httpClientDefaultOptions = $options;
    }


    /**
     * @return \GuzzleHttp\Client
     */
    public static function getHttpClient()
    {
        if (self::$httpClient) {
            return self::$httpClient;
        }

        return self::$httpClient = new \GuzzleHttp\Client();
    }

    public static function getStorage()
    {

    }

    public static function setStorage(StorageInterface $storage)
    {

    }

    public function getGrantStrategyName()
    {
        return $this->grantStrategyName;
    }

    public function changeGrantStrategy($grantStrategyName)
    {
        if (false === array_key_exists($grantStrategyName, self::$grantStrategyMapping)) {
            throw new InvalidArgumentException(sprintf("Input grant strategy %s not exist", $grantStrategyName));
        }

        $this->grantStrategyName = $grantStrategyName;
        return $this;
    }

    public static function registerGrantStrategy($strategyName, $strategyClass)
    {
        if (!class_exists($strategyClass) || !in_array('Eva\EvaOAuth\OAuth2\GrantStrategy\GrantStrategyInterface;', class_implements($strategyClass))) {
            throw new InvalidArgumentException('Register grant strategy failed by unrecognized interface');
        }

        self::$grantStrategyMapping[(string) $strategyName] = $strategyClass;
    }

    /**
     * @return array
     */
    public static function getGrantStrategyMapping()
    {
        if (self::$grantStrategyMapping) {
            return self::$grantStrategyMapping;
        }

        return self::$grantStrategyMapping = [
            self::GRANT_AUTHORIZATION_CODE => 'Eva\EvaOAuth\OAuth2\GrantStrategy\AuthorizationCode',
            self::GRANT_IMPLICIT => 'Eva\EvaOAuth\OAuth2\GrantStrategy\Implicit',
            self::GRANT_PASSWORD => 'Eva\EvaOAuth\OAuth2\GrantStrategy\Password',
            self::GRANT_CLIENT_CREDENTIALS => 'Eva\EvaOAuth\OAuth2\GrantStrategy\ClientCredentials',
        ];
    }

    /**
     * @return GrantStrategyInterface
     */
    public function getGrantStrategy()
    {
        if ($this->grantStrategy) {
            return $this->grantStrategy;
        }

        $grantStrategyClass = self::getGrantStrategyMapping()[$this->grantStrategyName];
        return $this->grantStrategy = new $grantStrategyClass(self::getHttpClient(), $this->options);
    }

    /**
     * @param AuthorizationServerInterface $authServer
     * @return string
     */
    public function getAuthorizeUrl(AuthorizationServerInterface $authServer)
    {
        return $this->getGrantStrategy()->getAuthorizeUrl($authServer);
    }

    /**
     * @param AuthorizationServerInterface $authServer
     */
    public function authorize(AuthorizationServerInterface $authServer)
    {
        return $this->getGrantStrategy()->authorize($authServer);
    }

    /**
     * @param ResourceServerInterface $resourceServer
     * @return mixed
     */
    public function getAccessToken(ResourceServerInterface $resourceServer)
    {
        return $this->getGrantStrategy()->getAccessToken($resourceServer);
    }

    public function __construct(array $options)
    {
        $options = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
            'scope' => '',
        ], $options);

        if (!$options['client_id'] || !$options['client_secret'] || !$options['redirect_uri']) {
            throw new InvalidArgumentException(sprintf("Empty client id or secret or redirect uri"));
        }
        $this->options = $options;
    }
}
