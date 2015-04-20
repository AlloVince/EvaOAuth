<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\AdapterTrait;
use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\OAuth1\Signature\SignatureInterface;
use Eva\EvaOAuth\OAuth1\Token\AccessToken;
use Eva\EvaOAuth\OAuth1\Token\RequestToken;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Consumer
 * @package Eva\EvaOAuth\OAuth1
 */
class Consumer
{
    use AdapterTrait;

    /**
     * @var string
     */
    protected $signatureMethod = SignatureInterface::METHOD_HMAC_SHA1;

    /**
     * @param $signatureMethod
     * @return $this
     */
    public function setSignatureMethod($signatureMethod)
    {
        $this->signatureMethod = $signatureMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    /**
     * @param ServiceProviderInterface $serviceProvider
     * @return Token\RequestTokenInterface
     */
    public function getRequestToken(ServiceProviderInterface $serviceProvider)
    {
        $options = $this->options;

        $httpMethod = ServiceProviderInterface::METHOD_POST;

        $url = $serviceProvider->getRequestTokenUrl();

        $parameters = [
            'oauth_consumer_key' => $options['consumer_key'],
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => (string) time(),
            'oauth_nonce' => Text::generateRandomString(32),
            'oauth_version' => '1.0',
            //NOTE: callback will cause 400 error for twitter
            //'oauth_callback' => $options['callback'],
        ];

        $baseString = Text::buildBaseString($httpMethod, $url, $parameters);
        $signature = (string) new Hmac(
            $options['consumer_secret'],
            $baseString
        );
        $parameters['oauth_signature'] = $signature;

        $httpClient = self::getHttpClient();
        $httpClientOptions = [
            'headers' => [
                'Authorization' => Text::buildHeaderString($parameters)
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

    /**
     * @param ServiceProviderInterface $serviceProvider
     * @param RequestToken $requestToken
     * @return string
     */
    public function getAuthorizeUri(ServiceProviderInterface $serviceProvider, RequestToken $requestToken)
    {
        $authorizeUrl = $serviceProvider->getAuthorizeUrl();
        return $authorizeUrl . '?oauth_token=' . $requestToken->getTokenValue();
    }

    /**
     * @param ServiceProviderInterface $serviceProvider
     */
    public function requestAuthorize(ServiceProviderInterface $serviceProvider)
    {
        $url = $this->getAuthorizeUri($serviceProvider, $this->getRequestToken($serviceProvider));
        header("Location:$url");
    }

    /**
     * @param ServiceProviderInterface $serviceProvider
     * @param array $urlQuery
     * @return AccessToken
     */
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
            'oauth_nonce' => Text::generateRandomString(32),
            'oauth_token' => $tokenValue,
            'oauth_version' => '1.0',
        ];

        $baseString = Text::buildBaseString($httpMethod, $url, $parameters);
        $signature = (string) new Hmac(
            $options['consumer_secret'],
            $baseString
        );
        $parameters['oauth_signature'] = $signature;

        $httpClient = self::getHttpClient();
        $httpClientOptions = [
            'headers' => [
                'Authorization' => Text::buildHeaderString($parameters)
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
            return AccessToken::factory($response, $serviceProvider, $options);
        } catch (RequestException $e) {
            throw new \Eva\EvaOAuth\Exception\RequestException(
                'Get access token failed',
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
