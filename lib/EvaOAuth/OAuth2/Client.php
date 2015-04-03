<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use GuzzleHttp\Client as HttpClient;

namespace Eva\EvaOAuth\OAuth2;

class Client
{

    const GRANT_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * NOTICE: implicit type will skip authorize step.
     */
    const GRANT_IMPLICIT = 'implicit';

    const GRANT_PASSWORD = 'password';

    const GRANT_CLIENT_CREDENTIALS = 'client_credentials';

    protected static $httpClient;

    protected static $httpClientDefaultOptions = [];

    protected $options;

    protected $grantType = self::GRANT_AUTHORIZATION_CODE;

    public function getOptions()
    {
        return $this->options;
    }

    public static function setHttpClientDefaultOptions($options)
    {
        self::$httpClientDefaultOptions = $options;
    }

    /**
     * @return HttpClient
     */
    public static function getHttpClient()
    {
        if (self::$httpClient) {
            return self::$httpClient;
        }

        return self::$httpClient = new HttpClient();
    }

    public function setGrantType($grantType)
    {
        /*
        if (false === in_array($grantType, [
                self::GRANT_AUTHORIZATION_CODE,
                self::GRANT_CLIENT_CREDENTIALS,
                self::GRANT_IMPLICIT,
                self::GRANT_PASSWORD
            ])
        ) {

        }
        */
        $this->grantType = $grantType;
        return $this;
    }

    public function getAuthorizeUrl(AuthorizationServerInterface $authServer)
    {
        $options = $this->options;
        $authorizeQuery = [
            'response_type' => 'code',
            'code' => substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10),
            'client_id' => $options['client_id'],
            'client_secret' => $options['client_secret'],
            'redirect_uri' => $options['redirect_uri'],
        ];
        return $authServer->getAuthorizeUrl() . '?' . http_build_query($authorizeQuery);
    }

    public function authorize(AuthorizationServerInterface $authServer)
    {
        header('Location:' . $this->getAuthorizeUrl($authServer));
    }

    public function getAccessToken(ResourceServerInterface $resourceServer)
    {
        $httpClient = self::getHttpClient();
        $httpClient->createRequest(
            $resourceServer->getAccessTokenMethod(),
            $resourceServer->getAccessTokenUrl()
        );
    }

    public function __construct(array $options)
    {
        $this->options = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ], $options);
    }
}
