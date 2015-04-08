<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\ClientConsumerTrait;
use Guzzle\Http\Message\Request;
use GuzzleHttp\Exception\RequestException;

class Consumer
{
    use ClientConsumerTrait;

    public function getRequestToken(ServiceProviderInterface $serviceProvider)
    {
        $options = $this->options;

        $parameters = [
            'oauth_consumer_key' => $options['consumer_key'],
            'oauth_signature_method' => '',
            'oauth_signature' => '',
            'oauth_timestamp' => time(),
            'oauth_nonce' => '',
            'oauth_version' => '',
            'oauth_callback' => $options['callback'],

        ];

        $httpClient = self::getHttpClient();

        $httpClientOptions = ['debug' => 0, 'body' => $parameters];

        $request = $httpClient->createRequest(
            'POST',
            $serviceProvider->getRequestTokenUrl(),
            $httpClientOptions
        );

        try {
            $response = $httpClient->send($request);
            return Request::factory($response, $serviceProvider);
        } catch (RequestException $e) {
            throw new \Eva\EvaOAuth\Exception\RequestException(
                'Get request token failed',
                $e->getRequest(),
                $e->getResponse()
            );
        }
    }


    public function requestAuthorize(ServiceProviderInterface $serviceProvider)
    {
        $requestToken = $this->getRequestToken($serviceProvider);
    }

    public function getAccessToken(ServiceProviderInterface $serviceProvider)
    {
    }

    /**
     * @param array $options
     */

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