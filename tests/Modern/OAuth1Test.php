<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\Engine\OAuth1Engine;
use Eva\EvaOAuth\Engine\OAuth1Signature;
use Eva\EvaOAuth\Exception\CallbackException;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
use Eva\EvaOAuth\Identity;
use Eva\EvaOAuth\Provider\OAuth1Provider;
use Eva\EvaOAuth\State\OAuth1Transaction;
use Eva\EvaOAuth\OAuth;
use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use Eva\EvaOAuth\Provider\Flickr;
use Eva\EvaOAuth\State\MemoryStateStore;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\Token;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class OAuth1Test extends TestCase
{
    public function testAuthorizedFormSignaturePreservesPercentEncoding(): void
    {
        $provider = new Flickr('client-id', 'client&secret', 'https://app.example/callback/flickr');
        $http = new MockHttpClient([new Response(200)]);
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $token = new Token($provider->binding(), 'oauth1', 'access%2Ftoken', tokenSecret: 'token&secret');
        $oauth->request('flickr', $token, new Request(
            'POST',
            'https://api.flickr.com/services/rest/?query=%253D&empty=',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body=a%2Bb+%252F',
        ));
        $request = $http->requests[0];
        $header = $this->header($request->getHeaderLine('Authorization'));
        $parameters = $header;
        unset($parameters['oauth_signature']);
        $parameters += ['query' => '%3D', 'empty' => '', 'body' => 'a+b %2F'];
        ksort($parameters, SORT_STRING);
        $normalized = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $base = 'POST&https%3A%2F%2Fapi.flickr.com%2Fservices%2Frest%2F&' . rawurlencode($normalized);
        self::assertSame(
            base64_encode(hash_hmac('sha1', $base, 'client%26secret&token%26secret', true)),
            $header['oauth_signature'],
        );
        self::assertSame('body=a%2Bb+%252F', (string) $request->getBody());
        self::assertSame('access%2Ftoken', $header['oauth_token']);
    }

    public function testCompleteFlickrFlowAndReplay(): void
    {
        $http = new MockHttpClient([
            $this->form('oauth_token=request-token&oauth_token_secret=request-secret&oauth_callback_confirmed=true'),
            $this->form(
                'oauth_token=access-token&oauth_token_secret=access-secret&user_nsid=123%40N01',
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"stat":"ok","user":{"id":"123@N01","username":{"_content":"Ada"}}}',
            ),
        ]);
        $provider = new Flickr('client-id', 'client-secret', 'https://app.example/callback/flickr');
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $before = time();
        self::assertSame(
            'https://www.flickr.com/services/oauth/authorize?perms=read&oauth_token=request-token',
            $oauth->authorize('flickr'),
        );
        $result = $oauth->callback('flickr', ['oauth_token' => 'request-token', 'oauth_verifier' => 'verifier%2F+&']);
        self::assertSame('flickr', $result->provider);
        self::assertSame('123@N01', $result->user->id);
        self::assertSame('Ada', $result->user->name);
        self::assertNull($result->user->email);
        self::assertNull($result->user->avatar);
        self::assertSame('access-token', $result->token->accessToken);
        self::assertSame('access-secret', $result->token->tokenSecret);
        self::assertSame($provider->binding(), $result->token->provider);
        self::assertSame('oauth1', $result->token->protocol);
        self::assertCount(3, $http->requests);
        $nonces = [];
        foreach ($http->requests as $index => $request) {
            $header = $this->header($request->getHeaderLine('Authorization'));
            self::assertSame('client-id', $header['oauth_consumer_key']);
            self::assertSame('HMAC-SHA1', $header['oauth_signature_method']);
            self::assertSame('1.0', $header['oauth_version']);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $header['oauth_nonce']);
            self::assertMatchesRegularExpression('/\A[1-9][0-9]*\z/D', $header['oauth_timestamp']);
            self::assertGreaterThanOrEqual($before, (int) $header['oauth_timestamp']);
            self::assertLessThanOrEqual(time(), (int) $header['oauth_timestamp']);
            $nonces[] = $header['oauth_nonce'];
            $secret = ['', 'request-secret', 'access-secret'][$index];
            self::assertSame($this->referenceSignature($request, 'client-secret', $secret), $header['oauth_signature']);
            self::assertSame($index === 2 ? 'GET' : 'POST', $request->getMethod());
            self::assertSame(
                $index === 2 ? 'application/json' : 'application/x-www-form-urlencoded',
                $request->getHeaderLine('Accept'),
            );
            if ($index === 0) {
                self::assertSame($provider->requestTokenUrl, (string) $request->getUri());
                self::assertSame($provider->redirectUri(), $header['oauth_callback']);
                self::assertArrayNotHasKey('oauth_token', $header);
                self::assertSame('', (string) $request->getBody());
            } elseif ($index === 1) {
                self::assertSame($provider->accessTokenUrl, (string) $request->getUri());
                self::assertSame('request-token', $header['oauth_token']);
                self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
                self::assertSame('oauth_verifier=verifier%252F%2B%26', (string) $request->getBody());
                self::assertArrayNotHasKey('oauth_callback', $header);
                self::assertArrayNotHasKey('oauth_verifier', $header);
            } else {
                self::assertSame($provider->resourceUrl(), (string) $request->getUri());
                self::assertSame('access-token', $header['oauth_token']);
                self::assertSame('', (string) $request->getBody());
            }
        }
        self::assertCount(3, array_unique($nonces));
        $this->expectException(CallbackException::class);
        $oauth->exchange('flickr', ['oauth_token' => 'request-token', 'oauth_verifier' => 'verifier']);
    }

    public function testRfc5849PhotoSignatureVector(): void
    {
        $client = new ClientCredentials();
        $client->setIdentifier('dpf43f3p2l4k3l03');
        $client->setSecret('kd94hf93k423kf44');
        $token = new TokenCredentials();
        $token->setIdentifier('nnch734d00sl2jdk');
        $token->setSecret('pfkkdhi9sl3r4s00');
        $signature = new OAuth1Signature($client);
        $signature->setCredentials($token);
        $parameters = [
            'oauth_consumer_key' => 'dpf43f3p2l4k3l03',
            'oauth_token' => 'nnch734d00sl2jdk',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => '137131202',
            'oauth_nonce' => 'chapoH',
        ];
        self::assertSame(
            'MdpQcU8iPSUjWoN/UDMsK2sui9I=',
            $signature->sign('http://photos.example.net/photos?file=vacation.jpg&size=original', $parameters, 'GET'),
        );
        $temporary = new OAuth1Signature($client);
        self::assertSame('74KNZJeDHnMBp0EMJ9ZHt/XKycU=', $temporary->sign('https://photos.example.net/initiate', [
            'oauth_consumer_key' => 'dpf43f3p2l4k3l03',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => '137131200',
            'oauth_nonce' => 'wIjqoS',
            'oauth_callback' => 'http://printer.example.com/ready',
        ], 'POST'));
    }

    public static function malformedResponses(): iterable
    {
        yield [''];
        yield ['oauth_token=t'];
        yield ['oauth_token=&oauth_token_secret=s'];
        yield ['oauth_token=t&oauth_token_secret='];
        yield ['oauth_token=t&oauth_token_secret=s&oauth_token=other'];
        yield ['oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=false'];
        yield ['oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=1'];
        yield ['oauth_token[]=t&oauth_token_secret=s'];
        yield ['oauth_token=t&oauth_token_secret=s&error=raw-secret'];
        yield ['oauth_token=t&oauth_token_secret=s&oauth_problem=raw-secret'];
        yield ['oauth_token=t%0A&oauth_token_secret=s'];
        yield ['oauth_token=t&oauth_token_secret=s%20'];
        yield ['oauth_token=t%ZZ&oauth_token_secret=s'];
        yield ['oauth_token=t&oauth_token_secret=s&extra=%'];
        yield ['oauth_token=t&oauth_token_secret=s&extra'];
        yield [str_repeat('x', 1048577)];
        yield ['oauth_token=' . str_repeat('x', 8193) . '&oauth_token_secret=s'];
    }

    #[DataProvider('malformedResponses')]
    public function testMalformedExchangeResponsesAreSanitized(string $body): void
    {
        $provider = new Flickr('id', 'client-secret', 'https://app.example/callback/flickr');
        $http = new MockHttpClient([
            $this->form($body),
            $this->form('oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=true'),
            $this->form($body),
        ]);
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        try {
            $oauth->authorize('flickr');
            self::fail('Malformed temporary credentials accepted.');
        } catch (ProviderException $error) {
            self::assertSame('OAuth provider response is invalid.', $error->getMessage());
            self::assertNull($error->getPrevious());
        }
        $oauth->authorize('flickr');
        try {
            $oauth->exchange('flickr', ['oauth_token' => 't', 'oauth_verifier' => 'verifier-secret']);
            self::fail('Malformed token credentials accepted.');
        } catch (ProviderException $error) {
            self::assertSame('OAuth provider response is invalid.', $error->getMessage());
            self::assertNull($error->getPrevious());
            self::assertStringNotContainsString('secret', (string) $error);
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('flickr', ['oauth_token' => 't', 'oauth_verifier' => 'verifier-secret']);
    }

    public static function invalidCallbacks(): iterable
    {
        yield [[]];
        yield [['oauth_verifier' => '']];
        yield [['oauth_verifier' => ['array']]];
        yield [['oauth_verifier' => "bad\nvalue"]];
        yield [['oauth_verifier' => str_repeat('x', 8193)]];
        yield [['oauth_verifier' => 'v', 'error' => 'raw-secret']];
        yield [['oauth_verifier' => 'v', 'oauth_problem' => 'signature_invalid']];
        yield [['oauth_verifier' => 'v', 'state' => 'oauth2-mixup']];
        yield [['denied' => 't']];
    }

    #[DataProvider('invalidCallbacks')]
    public function testMalformedCallbackConsumesTransaction(array $query): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $http = new MockHttpClient([$this->form('oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=true')]);
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $oauth->authorize('flickr');
        try {
            $oauth->exchange('flickr', $query + ['oauth_token' => 't']);
            self::fail('Malformed callback accepted.');
        } catch (CallbackException $error) {
            self::assertNull($error->getPrevious());
            self::assertCount(1, $http->requests);
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('flickr', ['oauth_token' => 't', 'oauth_verifier' => 'v']);
    }

    public function testTokenMismatchBrowserBindingAndRedirectBinding(): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $store = new MemoryStateStore();
        $http = new MockHttpClient([$this->form('oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=true')]);
        $oauth = new OAuth(['flickr' => $provider], $store, $http);
        $oauth->authorize('flickr');
        foreach ([[], ['oauth_token' => []], ['oauth_token' => ''], ['oauth_token' => 'wrong']] as $query) {
            try {
                $oauth->exchange('flickr', $query + ['oauth_verifier' => 'v']);
                self::fail('Mismatched callback accepted.');
            } catch (CallbackException) {
                self::assertCount(1, $http->requests);
            }
        }
        $otherBrowser = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $changed = new OAuth(['flickr' => new Flickr('id', 'changed', $provider->redirectUri())], $store, $http);
        foreach ([$otherBrowser, $changed] as $candidate) {
            try {
                $candidate->exchange('flickr', ['oauth_token' => 't', 'oauth_verifier' => 'v']);
                self::fail('Binding mismatch accepted.');
            } catch (CallbackException) {
                self::assertCount(1, $http->requests);
            }
        }
        $transaction = new OAuth1Transaction($provider->binding(), 'https://wrong.example/callback', 't', 's');
        $store->put('oauth1:t', $transaction);
        $this->expectException(CallbackException::class);
        $oauth->exchange('flickr', ['oauth_token' => 't', 'oauth_verifier' => 'v']);
    }

    public static function unsupportedParameters(): iterable
    {
        yield ['?a=1&a=2', ''];
        yield ['?a=1&%61=2', ''];
        yield ['?a=1', 'a=2'];
        yield ['', 'a=1&a=2'];
        yield ['?a.b=1', ''];
        yield ['?a+b=1', ''];
        yield ['?a%5B%5D=1', ''];
        yield ['?1=one', ''];
        yield ['?=empty', ''];
        yield ['?a=1&&b=2', ''];
        yield ['?oauth_nonce=override', ''];
        yield ['', 'oauth_signature=override'];
        yield ['', 'access_token=secret'];
        yield ['', 'a=%ZZ'];
        yield ['', str_repeat('x', 1048577)];
        yield [
            '',
            implode('&', array_map(static fn (int $i): string => 'a' . $i . '=1', range(0, 100))),
        ];
    }

    #[DataProvider('unsupportedParameters')]
    public function testUnsupportedNormalizationRejectedBeforeSending(string $query, string $body): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $http = new MockHttpClient();
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        try {
            $oauth->request('flickr', new Token($provider->binding(), 'oauth1', 't', tokenSecret: 's'), new Request(
                'POST',
                'https://api.flickr.com/services/rest/' . $query,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                $body,
            ));
            self::fail('Unsupported normalization accepted.');
        } catch (UnsupportedOperationException $error) {
            self::assertNull($error->getPrevious());
            self::assertCount(0, $http->requests);
        }
    }

    public function testResponseStatusContentTypeCallbackConfirmationAndSafeLogs(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$message, $context];
            }
        };
        $responses = [
            $this->form('oauth_token=t&oauth_token_secret=secret'),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"oauth_token":"secret","oauth_token_secret":"secret","oauth_callback_confirmed":true}',
            ),
            new Response(200, [], 'oauth_token=t&oauth_token_secret=secret&oauth_callback_confirmed=true'),
            new Response(401, [], 'raw-secret'),
            new Response(500, [], 'raw-secret'),
            new Response(302, ['Location' => 'https://evil.example/secret'], 'raw-secret'),
            new \RuntimeException('raw-secret'),
        ];
        $http = new MockHttpClient($responses);
        $provider = new Flickr('id', 'client-secret', 'https://app.example/callback');
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http, $logger);
        foreach ($responses as $response) {
            try {
                $oauth->authorize('flickr');
                self::fail('Invalid response accepted.');
            } catch (ProviderException | TransportException $error) {
                $expected = $response instanceof \Throwable ? TransportException::class : ProviderException::class;
                self::assertInstanceOf($expected, $error);
                self::assertNull($error->getPrevious());
                self::assertStringNotContainsString('secret', (string) $error);
            }
        }
        self::assertCount(count($responses), $http->requests);
        $trace = json_encode($logger->records, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('secret', $trace);
        self::assertStringNotContainsString('https', $trace);
        self::assertStringContainsString('oauth.http.response', $trace);
        self::assertStringContainsString('oauth.http.failure', $trace);
    }

    public function testGenericProviderMappingAndBinding(): void
    {
        $mapper = static fn (array $data): Identity => new Identity($data['account'], 'Custom');
        $provider = new OAuth1Provider(
            'id',
            'secret',
            'https://app.example/custom',
            'https://auth.example/request',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            $mapper,
            resourceOrigins: ['https://uploads.example:8443'],
        );
        self::assertSame('custom-id', $provider->identity(['account' => 'custom-id'])->id);
        self::assertSame(['https://api.example', 'https://uploads.example:8443'], $provider->allowedOrigins());
        self::assertSame($provider->binding(), $provider->binding());
        self::assertSame('{"protocol":"oauth1"}', json_encode($provider, JSON_THROW_ON_ERROR));
        $this->expectException(ConfigurationException::class);
        serialize($provider);
    }

    public static function invalidFlickrIdentities(): iterable
    {
        yield [[]];
        yield [['stat' => 'fail', 'message' => 'raw-secret']];
        yield [['stat' => 'ok', 'user' => []]];
        yield [['stat' => 'ok', 'user' => ['id' => [], 'username' => ['_content' => 'Ada']]]];
        yield [['stat' => 'ok', 'user' => ['id' => 'id', 'username' => 'Ada']]];
        yield [['stat' => 'ok', 'user' => ['id' => 'id', 'username' => ['_content' => []]]]];
    }

    #[DataProvider('invalidFlickrIdentities')]
    public function testFlickrIdentityErrorsAreSanitized(array $data): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $this->expectException(ProviderException::class);
        $provider->identity($data);
    }

    public function testNonFormBodiesAndEmptyPathSigning(): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $http = new MockHttpClient([new Response(200), new Response(200)]);
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $token = new Token($provider->binding(), 'oauth1', 'token', tokenSecret: 'token-secret');
        foreach (['application/json', 'multipart/form-data; boundary=boundary'] as $contentType) {
            $body = '{"oauth_nonce":"not-a-protocol-parameter","a":"one"}';
            $request = new Request(
                'PATCH',
                'https://api.flickr.com:443?value=%252F',
                ['Content-Type' => $contentType],
                $body,
            );
            $oauth->request('flickr', $token, $request);
            $sent = $http->requests[array_key_last($http->requests)];
            self::assertSame($body, (string) $sent->getBody());
            self::assertSame($contentType, $sent->getHeaderLine('Content-Type'));
            self::assertSame('PATCH', $sent->getMethod());
            self::assertSame(
                $this->referenceSignature($sent, 'secret', 'token-secret'),
                $this->header($sent->getHeaderLine('Authorization'))['oauth_signature'],
            );
        }
    }

    public function testFormStreamPositionPreservedAndNonSeekableRejected(): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $http = new MockHttpClient([new Response(200)]);
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $token = new Token($provider->binding(), 'oauth1', 'token', tokenSecret: 'token-secret');
        $request = new Request(
            'PUT',
            'https://api.flickr.com/',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            'a=one+two',
        );
        $request->getBody()->seek(3);
        $oauth->request('flickr', $token, $request);
        self::assertSame(3, $request->getBody()->tell());
        self::assertSame('a=one+two', (string) $http->requests[0]->getBody());
        self::assertSame(
            $this->referenceSignature($http->requests[0], 'secret', 'token-secret'),
            $this->header($http->requests[0]->getHeaderLine('Authorization'))['oauth_signature'],
        );
        $body = new \GuzzleHttp\Psr7\NoSeekStream($request->getBody());
        $this->expectException(UnsupportedOperationException::class);
        $oauth->request('flickr', $token, $request->withBody($body));
    }

    public function testAmbiguousHeadersAndTargetsRejected(): void
    {
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $http = new MockHttpClient();
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $token = new Token($provider->binding(), 'oauth1', 'token', tokenSecret: 'token-secret');
        $request = new Request('POST', 'https://api.flickr.com/', [], 'a=1');
        foreach (
            [
            $request->withHeader('Authorization', 'OAuth oauth_nonce="override"'),
            $request->withRequestTarget('/different'),
            $request->withHeader('Content-Type', 'application/x-www-form-urlencoded; charset=iso-8859-1'),
            $request->withHeader('Content-Type', ['application/x-www-form-urlencoded', 'application/json']),
            ] as $candidate
        ) {
            try {
                $oauth->request('flickr', $token, $candidate);
                self::fail('Ambiguous request accepted.');
            } catch (UnsupportedOperationException) {
                self::assertCount(0, $http->requests);
            }
        }
    }

    public function testConcurrentAttemptsKeepTheirOwnSecrets(): void
    {
        $http = new MockHttpClient([
            $this->form('oauth_token=a&oauth_token_secret=secret-a&oauth_callback_confirmed=true'),
            $this->form('oauth_token=b&oauth_token_secret=secret-b&oauth_callback_confirmed=true'),
            $this->form('oauth_token=access-b&oauth_token_secret=access-secret-b'),
            $this->form('oauth_token=access-a&oauth_token_secret=access-secret-a'),
        ]);
        $provider = new Flickr('id', 'secret', 'https://app.example/callback');
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http);
        $oauth->authorize('flickr');
        $oauth->authorize('flickr');
        foreach (['b', 'a'] as $index => $id) {
            $token = $oauth->exchange('flickr', ['oauth_token' => $id, 'oauth_verifier' => 'v']);
            self::assertSame('access-' . $id, $token->accessToken);
            $request = $http->requests[$index + 2];
            self::assertSame(
                $this->referenceSignature($request, 'secret', 'secret-' . $id),
                $this->header($request->getHeaderLine('Authorization'))['oauth_signature'],
            );
        }
    }

    public function testSuccessfulFlowLogsExcludeEveryCredential(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$message, $context];
            }
        };
        $http = new MockHttpClient([
            $this->form('oauth_token=request-token&oauth_token_secret=request-secret&oauth_callback_confirmed=true'),
            $this->form('oauth_token=access-token&oauth_token_secret=access-secret'),
            new Response(200),
        ]);
        $provider = new Flickr('consumer-key', 'consumer-secret', 'https://app.example/callback');
        $oauth = new OAuth(['flickr' => $provider], new MemoryStateStore(), $http, $logger);
        $oauth->authorize('flickr');
        $token = $oauth->exchange('flickr', ['oauth_token' => 'request-token', 'oauth_verifier' => 'verifier-secret']);
        $oauth->request('flickr', $token, new Request('GET', $provider->resourceUrl()));
        self::assertCount(3, $logger->records);
        $trace = json_encode($logger->records, JSON_THROW_ON_ERROR);
        $sensitive = [
            'request-token', 'request-secret', 'access-token', 'access-secret',
            'consumer-key', 'consumer-secret', 'verifier-secret',
        ];
        foreach ($sensitive as $needle) {
            self::assertStringNotContainsString($needle, $trace);
        }
    }

    public function testFlickrPermissionsNamedArgumentAndReadmeCompatibility(): void
    {
        $write = new Flickr('id', 'secret', 'https://app.example/callback', permissions: 'write');
        $delete = new Flickr(
            clientId: 'id',
            clientSecret: 'secret',
            redirectUri: 'https://app.example/c',
            permissions: 'delete',
        );
        self::assertSame(['perms' => 'write'], $write->authorizationParameters);
        self::assertSame(['perms' => 'delete'], $delete->authorizationParameters);
        $http = new MockHttpClient([$this->form('oauth_token=t&oauth_token_secret=s&oauth_callback_confirmed=true')]);
        self::assertSame(
            'https://www.flickr.com/services/oauth/authorize?perms=delete&oauth_token=t',
            (new OAuth1Engine($delete, new Transport($http)))->begin()['url'],
        );
        $this->expectException(ConfigurationException::class);
        new Flickr('id', 'secret', 'https://app.example/callback', permissions: 'admin');
    }

    private function referenceSignature(RequestInterface $request, string $clientSecret, string $tokenSecret): string
    {
        $parameters = [];
        foreach ($this->header($request->getHeaderLine('Authorization')) as $key => $value) {
            if ($key !== 'oauth_signature') {
                $parameters[] = [rawurlencode($key), rawurlencode($value)];
            }
        }
        $sources = [$request->getUri()->getQuery()];
        if (str_starts_with($request->getHeaderLine('Content-Type'), 'application/x-www-form-urlencoded')) {
            $sources[] = (string) $request->getBody();
        }
        foreach ($sources as $source) {
            foreach ($source === '' ? [] : explode('&', $source) as $pair) {
                $parts = explode('=', $pair, 2);
                $parameters[] = [rawurlencode(urldecode($parts[0])), rawurlencode(urldecode($parts[1] ?? ''))];
            }
        }
        usort($parameters, static fn (array $a, array $b): int => strcmp($a[0], $b[0]) ?: strcmp($a[1], $b[1]));
        $normalized = implode('&', array_map(static fn (array $pair): string => implode('=', $pair), $parameters));
        $uri = $request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getAuthority() . ($uri->getPath() ?: '/');
        $base = strtoupper($request->getMethod()) . '&' . rawurlencode($url) . '&' . rawurlencode($normalized);
        $key = rawurlencode($clientSecret) . '&' . rawurlencode($tokenSecret);
        return base64_encode(hash_hmac('sha1', $base, $key, true));
    }

    private function form(string $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/x-www-form-urlencoded'], $body);
    }

    private function header(string $header): array
    {
        self::assertStringStartsWith('OAuth ', $header);
        $values = [];
        foreach (explode(', ', substr($header, 6)) as $part) {
            self::assertMatchesRegularExpression('/\A[a-z_]+="[^"]*"\z/D', $part);
            [$key, $value] = explode('=', $part, 2);
            self::assertArrayNotHasKey($key, $values);
            $values[$key] = rawurldecode(substr($value, 1, -1));
        }
        return $values;
    }
}
