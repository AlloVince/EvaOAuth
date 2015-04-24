<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth2\GrantStrategy;

use Eva\EvaOAuth\Events\BeforeAuthorize;
use Eva\EvaOAuth\Events\BeforeGetAccessToken;
use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\Client;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use Eva\EvaOAuth\OAuth2\Token\AccessToken;
use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Event\HasEmitterTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;

/**
 * Class AuthorizationCode
 * @package Eva\EvaOAuth\OAuth2\GrantStrategy
 */
class AuthorizationCode implements GrantStrategyInterface
{
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $options;

    use HasEmitterTrait;


    /**
     * @param AuthorizationServerInterface $authServer
     * @param string $url
     */
    public function requestAuthorize(AuthorizationServerInterface $authServer, $url = '')
    {
        $url = $url ?: $this->getAuthorizeUrl($authServer);
        $this->getEmitter()->emit('beforeAuthorize', new BeforeAuthorize($url));
        header("Location:$url");
    }

    /**
     * @param AuthorizationServerInterface $authServer
     * @return string
     */
    public function getAuthorizeUrl(AuthorizationServerInterface $authServer)
    {
        $options = $this->options;
        $authorizeQuery = [
            'response_type' => 'code',
            'client_id' => $options['client_id'],
            'redirect_uri' => $options['redirect_uri'],
            'state' => Text::generateRandomString()
        ];
        if ($options['scope']) {
            $authorizeQuery['scope'] = $options['scope'];
        }
        return $authServer->getAuthorizeUrl() . '?' . http_build_query($authorizeQuery);
    }

    /**
     * @param ResourceServerInterface $resourceServer
     * @param array $urlQuery
     * @return \Eva\EvaOAuth\OAuth2\Token\AccessTokenInterface
     */
    public function getAccessToken(ResourceServerInterface $resourceServer, array $urlQuery = [])
    {
        $urlQuery = $urlQuery ?: $_GET;
        $code = empty($urlQuery['code']) ? '' : $urlQuery['code'];
        $state = empty($urlQuery['state']) ? '' : $urlQuery['state'];
        $options = $this->options;

        if (!$code) {
            throw new InvalidArgumentException("No authorization code found");
        }

        //TODO: Valid state to void attach

        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $options['client_id'],
            'client_secret' => $options['client_secret'],
            'redirect_uri' => $options['redirect_uri'],
        ];
        if ($state) {
            $parameters['state'] = $state;
        }

        $httpClient = $this->httpClient;

        $method = $resourceServer->getAccessTokenMethod();
        $httpClientOptions = ($method == ResourceServerInterface::METHOD_GET) ?
            ['query' => $parameters] :
            ['body' => $parameters];

        $request = $httpClient->createRequest(
            $method,
            $resourceServer->getAccessTokenUrl(),
            $httpClientOptions
        );

        try {
            $this->getEmitter()->emit('beforeGetAccessToken', new BeforeGetAccessToken($request, $resourceServer));
            /** @var Response $response */
            $response = $httpClient->send($request);
            return AccessToken::factory($response, $resourceServer);
        } catch (RequestException $e) {
            throw new \Eva\EvaOAuth\Exception\RequestException(
                'Get access token failed',
                $e->getRequest(),
                $e->getResponse()
            );
        }
    }

    /**
     * @param HttpClient $httpClient
     * @param array $options
     */
    public function __construct(HttpClient $httpClient, array $options)
    {
        $this->httpClient = $httpClient;
        $this->options = $options;
    }
}
