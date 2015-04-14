<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\ClientConsumerTrait;
use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\OAuth1\Signature\SignatureInterface;
use Eva\EvaOAuth\OAuth1\Token\AccessToken;
use Eva\EvaOAuth\OAuth1\Token\RequestToken;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Exception\RequestException;

class Consumer
{
    use ClientConsumerTrait;

    protected $signatureMethod = SignatureInterface::METHOD_HMAC_SHA1;

    public function setSignatureMethod($signatureMethod)
    {
        $this->signatureMethod = $signatureMethod;
        return $this;
    }

    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    public function getRequestToken(ServiceProviderInterface $serviceProvider)
    {
        $options = $this->options;

        $httpMethod = ServiceProviderInterface::METHOD_POST;

        $url = $serviceProvider->getRequestTokenUrl();

        $parameters = [
            'oauth_consumer_key' => $options['consumer_key'],
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => (string) time(),
            'oauth_nonce' => strtolower(Text::getRandomString(32)),
            'oauth_version' => '1.0',
            //'oauth_callback' => $options['callback'],
        ];

        $baseString = Text::getBaseString($httpMethod, $url, $parameters);
        $signature = (string) new Hmac(
            $options['consumer_secret'],
            $baseString
        );
        $parameters['oauth_signature'] = $signature;

        $httpClient = self::getHttpClient();
        $httpClientOptions = [
            'headers' => [
                'Authorization' => Text::getHeaderString($parameters)
            ]
        ];
        $request = $httpClient->createRequest(
            $httpMethod,
            $url,
            $httpClientOptions
        );

        try {
            $response = $httpClient->send($request);
            return RequestToken::factory($response, $serviceProvider);
        } catch (RequestException $e) {
            throw new \Eva\EvaOAuth\Exception\RequestException(
                'Get request token failed',
                $e->getRequest(),
                $e->getResponse()
            );
        }
    }

    public function getAuthorizeUrl(ServiceProviderInterface $serviceProvider, RequestToken $requestToken)
    {
        $authorizeUrl = $serviceProvider->getAuthorizeUrl();
        return $authorizeUrl . '?oauth_token=' . $requestToken->getTokenValue();
    }

    public function requestAuthorize(ServiceProviderInterface $serviceProvider)
    {
        $url = $this->getAuthorizeUrl($serviceProvider, $this->getRequestToken($serviceProvider));
        header("Location:$url");
    }

    public function getAccessToken(ServiceProviderInterface $serviceProvider, array $urlQuery = [])
    {
        $urlQuery = $urlQuery ?: $_GET;
        $tokenValue = empty($urlQuery['oauth_token']) ? '' : $urlQuery['oauth_token'];
        $tokenVerify = empty($urlQuery['oauth_verifier']) ? '' : $urlQuery['oauth_verifier'];

        if (!$tokenValue || !$tokenVerify) {
            throw new InvalidArgumentException(sprintf('No oauth_token or oauth_verifier input'));
        }
        $options = $this->options;

        $httpMethod = $serviceProvider->getAccessTokenMethod();
        $url = $serviceProvider->getAccessTokenUrl();

        $parameters = [
            'oauth_consumer_key' => $options['consumer_key'],
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => (string) time(),
            'oauth_nonce' => strtolower(Text::getRandomString(32)),
            'oauth_token' => $tokenValue,
            'oauth_version' => '1.0',
        ];

        $baseString = Text::getBaseString($httpMethod, $url, $parameters);
        $signature = (string) new Hmac(
            $options['consumer_secret'],
            $baseString
        );
        $parameters['oauth_signature'] = $signature;

        $httpClient = self::getHttpClient();
        $httpClientOptions = [
            'debug' => 0,
            'headers' => [
                'Authorization' => Text::getHeaderString($parameters)
            ],
            'body' => [
                'oauth_verifier' => $tokenVerify
            ]
        ];

        $request = $httpClient->createRequest(
            $httpMethod,
            $url,
            $httpClientOptions
        );

        try {
            $response = $httpClient->send($request);
            return AccessToken::factory($response, $serviceProvider);
        } catch (RequestException $e) {
            throw new \Eva\EvaOAuth\Exception\RequestException(
                'Get request token failed',
                $e->getRequest(),
                $e->getResponse()
            );
        };
    }

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $options = array_merge([
            'consumer_key' => '',
            'consumer_secret' => '',
            'callback' => '',
        ], $options);

        if (!$options['consumer_key'] || !$options['consumer_secret'] || !$options['callback']) {
            throw new InvalidArgumentException(sprintf("Empty consumer key or secret or callback"));
        }
        $this->options = $options;
    }
}