<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth2\GrantStrategy;

use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AuthorizationCode implements GrantStrategyInterface
{
    protected $httpClient;

    protected $options;

    public function authorize(AuthorizationServerInterface $authServer)
    {
        header('Location:' . $this->getAuthorizeUrl($authServer));
    }

    public function getAuthorizeUrl(AuthorizationServerInterface $authServer)
    {
        $options = $this->options;
        $authorizeQuery = [
            'response_type' => 'code',
            'client_id' => $options['client_id'],
            'redirect_uri' => $options['redirect_uri'],
            'state' => substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10),
        ];
        if (!$options['scope']) {
            $authorizeQuery['scope'] = $options['scope'];
        }
        return $authServer->getAuthorizeUrl() . '?' . http_build_query($authorizeQuery);
    }

    public function getAccessToken(ResourceServerInterface $resourceServer)
    {
        $code = empty($_GET['code']) ?: $_GET['code'];
        $state = empty($_GET['state']) ?: $_GET['state'];
        $options = $this->options;

        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $options['client_id'],
            'redirect_uri' => $options['redirect_uri'],
        ];
        if ($state) {
            $query['state'] = $state;
        }

        $httpClient = $this->httpClient;

        $method = $resourceServer->getAccessTokenMethod();
        $httpClientOptions = ($method == ResourceServerInterface::METHOD_GET) ?
            ['query' => $parameters] :
            ['debug' => 1, 'body' => $parameters];

        $request = $httpClient->createRequest(
            $method,
            $resourceServer->getAccessTokenUrl(),
            $httpClientOptions
        );

        echo "<pre>";
        try {
            $response = $httpClient->send($request);
            $rawToken = $response->json();
            var_dump($rawToken);
            exit;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                //Log
                echo $e->getResponse();
            }
        }
    }

    public function __construct(Client $httpClient, array $options)
    {
        $this->httpClient = $httpClient;
        $this->options = $options;
    }
}
