<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\Exception\CallbackException;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\OAuthException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Identity;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\OAuth;
use Eva\EvaOAuth\Provider\GitHub;
use Eva\EvaOAuth\Provider\Google;
use Eva\EvaOAuth\Provider\OAuth2Provider;
use Eva\EvaOAuth\State\MemoryStateStore;
use Eva\EvaOAuth\State\SessionStateStore;
use Eva\EvaOAuth\Token;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class OAuth2Test extends TestCase
{
    private function provider(string $secret = 'client-secret'): GitHub
    {
        return new GitHub('client-id', $secret, 'https://app.example/callback/github');
    }

    private function start(OAuth $oauth, string $name = 'github'): array
    {
        parse_str((string) parse_url($oauth->authorize($name), PHP_URL_QUERY), $query);
        return $query;
    }

    private function google(): Google
    {
        return new Google('id', 'secret', 'https://app.example/callback/google');
    }

    private function response(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function testCodePkceIdentityAndReplay(): void
    {
        $http = new MockHttpClient([
            $this->response([
                'access_token' => 'access-secret',
                'token_type' => 'bearer',
                'refresh_token' => 'refresh-secret',
                'expires_in' => 3600,
                'scope' => 'read:user,user:email',
            ]),
            $this->response([
                'id' => 123,
                'name' => 'Ada',
                'email' => 'ada@example.test',
                'avatar_url' => 'https://images.example/avatar',
            ]),
        ]);
        $provider = $this->provider();
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        self::assertSame('code', $start['response_type']);
        self::assertSame('client-id', $start['client_id']);
        self::assertSame($provider->redirectUri(), $start['redirect_uri']);
        self::assertSame('S256', $start['code_challenge_method']);
        self::assertSame('read:user user:email', $start['scope']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $start['state']);
        $before = time();
        $result = $oauth->callback('github', ['state' => $start['state'], 'code' => 'authorization-code']);
        self::assertSame('github', $result->provider);
        self::assertSame('123', $result->user->id);
        self::assertSame('Ada', $result->user->name);
        self::assertSame('ada@example.test', $result->user->email);
        self::assertSame($provider->tokenBinding(), $result->token->provider);
        self::assertSame('refresh-secret', $result->token->refreshToken);
        self::assertGreaterThanOrEqual($before + 3600, $result->token->expiresAt);
        $request = $http->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://github.com/login/oauth/access_token', (string) $request->getUri());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        parse_str((string) $request->getBody(), $body);
        self::assertSame('authorization_code', $body['grant_type']);
        self::assertSame('authorization-code', $body['code']);
        self::assertSame('client-id', $body['client_id']);
        self::assertSame('client-secret', $body['client_secret']);
        self::assertSame($provider->redirectUri(), $body['redirect_uri']);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43,128}$/', $body['code_verifier']);
        self::assertSame(
            $start['code_challenge'],
            rtrim(strtr(base64_encode(hash('sha256', $body['code_verifier'], true)), '+/', '-_'), '=')
        );
        self::assertSame('GET', $http->requests[1]->getMethod());
        self::assertSame('Bearer access-secret', $http->requests[1]->getHeaderLine('Authorization'));
        self::assertSame('https://api.github.com/user', (string) $http->requests[1]->getUri());
        $this->expectException(CallbackException::class);
        $oauth->exchange('github', ['state' => $start['state'], 'code' => 'authorization-code']);
    }

    public function testRefreshRetentionRotationAndNoVerifier(): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient([
            $this->response([
                'access_token' => 'new-access',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            $this->response([
                'access_token' => 'next-access',
                'token_type' => 'Bearer',
                'refresh_token' => 'rotated',
                'scope' => 'read:user',
                'expires_in' => 0,
            ]),
        ]);
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $old = new Token(
            $provider->tokenBinding(),
            'oauth2',
            'old-access',
            'original-refresh',
            time() - 1,
            scopes: ['read:user', 'user:email'],
        );
        $new = $oauth->refresh('github', $old);
        self::assertSame('original-refresh', $new->refreshToken);
        self::assertSame($old->scopes, $new->scopes);
        self::assertFalse($new->isExpired());
        parse_str((string) $http->requests[0]->getBody(), $body);
        self::assertSame('POST', $http->requests[0]->getMethod());
        self::assertSame('refresh_token', $body['grant_type']);
        self::assertSame('original-refresh', $body['refresh_token']);
        self::assertSame('client-secret', $body['client_secret']);
        self::assertArrayNotHasKey('code_verifier', $body);
        $rotated = $oauth->refresh('github', $new);
        self::assertSame('rotated', $rotated->refreshToken);
        self::assertSame(['read:user'], $rotated->scopes);
        self::assertTrue($rotated->isExpired());
    }

    public function testGenericProviderBasicAuthenticationAndMapper(): void
    {
        $provider = new OAuth2Provider(
            'id: space',
            'secret: value',
            'https://app.example/callback/custom',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            ['profile'],
            fn (array $data): Identity => new Identity($data['account']),
            clientAuthentication: 'client_secret_basic',
        );
        $http = new MockHttpClient([
            $this->response(['access_token' => 'custom-access', 'token_type' => 'Bearer']),
            $this->response(['account' => 'custom-id']),
        ]);
        $oauth = new OAuth(['custom' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth, 'custom');
        self::assertSame('id: space', $start['client_id']);
        $result = $oauth->callback('custom', ['state' => $start['state'], 'code' => 'code']);
        self::assertSame('custom-id', $result->user->id);
        self::assertSame(
            'Basic ' . base64_encode('id%3A+space:secret%3A+value'),
            $http->requests[0]->getHeaderLine('Authorization')
        );
        parse_str((string) $http->requests[0]->getBody(), $body);
        self::assertArrayNotHasKey('client_secret', $body);
        self::assertArrayNotHasKey('client_id', $body);
    }

    public function testGoogleMappingAndOfflineAuthorization(): void
    {
        $google = new Google('id', 'secret', 'https://app.example/callback/google');
        $oauth = new OAuth(['google' => $google], new MemoryStateStore(), new MockHttpClient());
        $start = $this->start($oauth, 'google');
        self::assertSame('offline', $start['access_type']);
        self::assertSame('S256', $start['code_challenge_method']);
        self::assertSame('openid profile email', $start['scope']);
        $identity = $google->identity([
            'sub' => '42',
            'email_verified' => true,
            'picture' => 'https://image.example/me',
        ]);
        self::assertSame('42', $identity->id);
        self::assertTrue($identity->emailVerified);
        self::assertSame('https://image.example/me', $identity->avatar);
    }

    public static function invalidTokens(): iterable
    {
        yield [[]];
        yield [['access_token' => 'secret']];
        yield [['access_token' => [], 'token_type' => 'Bearer']];
        yield [['access_token' => "secret\r\nheader", 'token_type' => 'Bearer']];
        yield [['access_token' => 'secret', 'token_type' => 'MAC']];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'expires_in' => -1]];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'expires_in' => 1.5]];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'expires_in' => '1e8']];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'expires_in' => '9999999999999999999']];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'refresh_token' => []]];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'refresh_token' => '']];
        yield [['access_token' => 'secret', 'token_type' => 'Bearer', 'scope' => []]];
        yield [['error' => 'raw-credential-secret']];
    }

    #[DataProvider('invalidTokens')]
    public function testStrictTokenValidation(array $data): void
    {
        $http = new MockHttpClient([$this->response($data)]);
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        try {
            $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
            self::fail('Invalid token accepted.');
        } catch (ProviderException $error) {
            self::assertSame('OAuth provider response is invalid.', $error->getMessage());
            self::assertNull($error->getPrevious());
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
    }

    public function testConcurrentAttemptsHaveIndependentVerifiers(): void
    {
        $http = new MockHttpClient([
            $this->response(['access_token' => 'first', 'token_type' => 'Bearer']),
            $this->response(['access_token' => 'second', 'token_type' => 'Bearer']),
        ]);
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        $a = $this->start($oauth);
        $b = $this->start($oauth);
        self::assertNotSame($a['state'], $b['state']);
        self::assertNotSame($a['code_challenge'], $b['code_challenge']);
        foreach ([$b, $a] as $index => $start) {
            $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
            parse_str((string) $http->requests[$index]->getBody(), $body);
            self::assertSame(
                $start['code_challenge'],
                rtrim(strtr(base64_encode(hash('sha256', $body['code_verifier'], true)), '+/', '-_'), '=')
            );
        }
    }

    public function testBrowserAndConfigurationBinding(): void
    {
        $store = new MemoryStateStore();
        $http = new MockHttpClient();
        $a = new OAuth(['github' => $this->provider()], $store, $http);
        $start = $this->start($a);
        $b = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        try {
            $b->exchange('github', ['state' => $start['state'], 'code' => 'code']);
            self::fail('Cross-browser callback accepted.');
        } catch (CallbackException) {
            self::assertCount(0, $http->requests);
        }
        $changed = new OAuth(['github' => $this->provider('changed-secret')], $store, $http);
        $this->expectException(CallbackException::class);
        $changed->exchange('github', ['state' => $start['state'], 'code' => 'code']);
    }

    public static function invalidCallbacks(): iterable
    {
        yield [['code' => ['array']]];
        yield [['code' => '']];
        yield [['code' => "a\nb"]];
        yield [['error' => 'access_denied']];
        yield [['code' => 'code', 'oauth_token' => 'mixup']];
        yield [['code' => 'code', 'oauth_problem' => 'signature_invalid']];
    }

    #[DataProvider('invalidCallbacks')]
    public function testMalformedCallbackConsumed(array $query): void
    {
        $http = new MockHttpClient();
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        try {
            $oauth->exchange('github', $query + ['state' => $start['state']]);
            self::fail('Malformed callback accepted.');
        } catch (CallbackException) {
            self::assertCount(0, $http->requests);
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
    }

    public static function forbiddenResources(): iterable
    {
        yield ['http://api.github.com/user'];
        yield ['https://evil.example/user'];
        yield ['https://api.github.com.evil.example/user'];
        yield ['https://api.github.com:444/user'];
        yield ['https://user:password@api.github.com/user'];
        yield ['https://api.github.com/user#fragment'];
        yield ['https://api.github.com/user?access_token=secret'];
    }

    public function testUnknownCallbackParametersIgnored(): void
    {
        $http = new MockHttpClient([
            $this->response(['access_token' => 'access-secret', 'token_type' => 'Bearer', 'expires_in' => 3600]),
            $this->response(['id' => 7]),
        ]);
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        $result = $oauth->callback('github', [
            'state' => $start['state'],
            'code' => 'code',
            'expires_in' => 'bogus',
            'scope' => 'https://schema.example/scopes',
            'authuser' => '0',
            'prompt' => 'none',
        ]);
        self::assertSame('7', $result->user->id);
        self::assertSame('access-secret', $result->token->accessToken);
    }

    public function testIssuerValidation(): void
    {
        $provider = $this->google();
        $http = new MockHttpClient([
            $this->response(['access_token' => 'token', 'token_type' => 'Bearer']),
            $this->response(['sub' => '42']),
        ]);
        $oauth = new OAuth(['google' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth, 'google');
        $result = $oauth->exchange('google', [
            'state' => $start['state'],
            'code' => 'code',
            'iss' => 'https://accounts.google.com',
        ]);
        self::assertSame('token', $result->accessToken);
        $start = $this->start($oauth, 'google');
        $before = count($http->requests);
        try {
            $oauth->exchange('google', [
                'state' => $start['state'],
                'code' => 'code',
                'iss' => 'https://evil.example',
            ]);
            self::fail('Cross-issuer callback accepted.');
        } catch (CallbackException) {
            self::assertCount($before, $http->requests);
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('google', [
            'state' => $start['state'],
            'code' => 'code',
            'iss' => 'https://evil.example',
        ]);
    }

    private function issuerProvider(string $issuer): OAuth2Provider
    {
        return new OAuth2Provider(
            'id',
            'secret',
            'https://app.example/callback/tenant',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            issuer: $issuer,
        );
    }

    public function testPathBearingIssuerRequiresExactCallbackMatch(): void
    {
        $issuer = 'https://auth.example/tenant';
        $provider = $this->issuerProvider($issuer);
        self::assertSame($issuer, $provider->issuer);
        $http = new MockHttpClient([
            $this->response(['access_token' => 'tenant-token', 'token_type' => 'Bearer']),
        ]);
        $oauth = new OAuth(['tenant' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth, 'tenant');
        $token = $oauth->exchange('tenant', ['state' => $start['state'], 'code' => 'code', 'iss' => $issuer]);
        self::assertSame('tenant-token', $token->accessToken);
        foreach (['https://auth.example', $issuer . '/', 'https://auth.example/other'] as $different) {
            $start = $this->start($oauth, 'tenant');
            try {
                $oauth->exchange('tenant', ['state' => $start['state'], 'code' => 'code', 'iss' => $different]);
                self::fail('Non-identical issuer accepted.');
            } catch (CallbackException) {
                self::assertCount(1, $http->requests);
            }
        }
        $nested = 'https://auth.example:8443/a/b/';
        self::assertSame($nested, $this->issuerProvider($nested)->issuer);
        self::assertSame('https://accounts.google.com', $this->google()->issuer);
    }

    public static function invalidIssuerUrls(): iterable
    {
        yield ['http://auth.example/tenant'];
        yield ['https://auth.example/tenant?key=value'];
        yield ['https://auth.example/tenant?'];
        yield ['https://auth.example/tenant#fragment'];
        yield ['https://auth.example/tenant#'];
        yield ['https://user@auth.example/tenant'];
        yield ['https://user:password@auth.example/tenant'];
        yield ['https://'];
        yield ["https://auth.example/ten\nant"];
        yield ['https://auth.example/tenant\\other'];
    }

    #[DataProvider('invalidIssuerUrls')]
    public function testIssuerRejectsForbiddenUrlComponents(string $issuer): void
    {
        $this->expectException(ConfigurationException::class);
        $this->issuerProvider($issuer);
    }

    public function testIssuerAbsentFallsBackToStateBinding(): void
    {
        $http = new MockHttpClient([
            $this->response(['access_token' => 'token', 'token_type' => 'Bearer']),
            $this->response(['sub' => '42']),
        ]);
        $oauth = new OAuth(['google' => $this->google()], new MemoryStateStore(), $http);
        $start = $this->start($oauth, 'google');
        $token = $oauth->exchange('google', ['state' => $start['state'], 'code' => 'code']);
        self::assertSame('token', $token->accessToken);
    }

    public function testIssuerRejectedWhenProviderHasNoIssuer(): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient();
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        try {
            $oauth->exchange('github', [
                'state' => $start['state'],
                'code' => 'code',
                'iss' => 'https://accounts.google.com',
            ]);
            self::fail('Callback with iss accepted for provider without issuer.');
        } catch (CallbackException) {
            self::assertCount(0, $http->requests);
        }
        $this->expectException(CallbackException::class);
        $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
    }

    public function testResponseScopeSeparatorOnWire(): void
    {
        $http = new MockHttpClient([
            $this->response([
                'access_token' => 'comma-token',
                'token_type' => 'Bearer',
                'scope' => 'read:user,user:email',
            ]),
            $this->response(['id' => 5]),
        ]);
        $provider = $this->provider();
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        $result = $oauth->callback('github', ['state' => $start['state'], 'code' => 'code']);
        self::assertSame(['read:user', 'user:email'], $result->token->scopes);
        $comma = new OAuth2Provider(
            'id',
            'secret',
            'https://app.example/callback/comma-free',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            ['one', 'two'],
            responseScopeSeparator: ' ',
        );
        self::assertNotSame($provider->binding(), $comma->binding());
    }

    public function testExpiryBoundarySemantics(): void
    {
        $http = new MockHttpClient([
            $this->response(['access_token' => 'token', 'token_type' => 'Bearer', 'expires_in' => 0]),
            $this->response(['id' => 9]),
        ]);
        $oauth = new OAuth(['github' => $provider = $this->provider()], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        $token = $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
        self::assertNotNull($token->expiresAt);
        self::assertTrue($token->isExpired($token->expiresAt));
        self::assertFalse(
            (new Token($provider->tokenBinding(), 'oauth2', 't', expiresAt: $token->expiresAt + 1))
                ->isExpired($token->expiresAt)
        );
        $this->expectException(ProviderException::class);
        $oauth->request('github', $token, new Request('GET', 'https://api.github.com/user'));
    }

    public function testRefreshKeepsEpochExpiredSemantics(): void
    {
        $http = new MockHttpClient([
            $this->response(['access_token' => 'token', 'token_type' => 'Bearer', 'expires_in' => 0]),
        ]);
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        $token = $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
        self::assertTrue($token->isExpired());
        self::assertSame(0, $token->expiresAt);
    }

    public function testTokenValidationAndPersistence(): void
    {
        $provider = $this->provider();
        $token = new Token(
            $provider->tokenBinding(),
            'oauth2',
            'access-token',
            'refresh-token',
            time() + 60,
            scopes: ['a'],
        );
        self::assertSame($token->toArray(), Token::fromArray($token->toArray())->toArray());
        $expired = new Token($provider->tokenBinding(), 'oauth2', 'access-token', expiresAt: 0);
        self::assertTrue($expired->isExpired());
        self::assertSame($expired->toArray(), Token::fromArray($expired->toArray())->toArray());
        $this->expectException(ConfigurationException::class);
        Token::fromArray([
            'provider' => $provider->tokenBinding(),
            'protocol' => 'oauth2',
            'accessToken' => 'x',
            'surprise' => true,
        ]);
    }

    public function testTokenConstructionFailures(): void
    {
        $this->expectException(ConfigurationException::class);
        new Token('binding', 'oauth2', "bad\nline");
    }

    public function testTokenSecretRequiredForOauth1(): void
    {
        $this->expectException(ConfigurationException::class);
        new Token('binding', 'oauth1', 'access-token');
    }

    public function testInvalidIdentityMapperRejected(): void
    {
        $provider = new OAuth2Provider(
            'id',
            'secret',
            'https://app.example/callback/mapper',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            identityMapper: fn (): int => 42,
        );
        $this->expectException(ProviderException::class);
        $provider->identity(['id' => '1']);
    }

    public function testMapperExceptionsAreWrapped(): void
    {
        $provider = new OAuth2Provider(
            'id',
            'secret',
            'https://app.example/callback/mapper',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            identityMapper: function (): never {
                throw new \RuntimeException('raw-data-secret');
            },
        );
        try {
            $provider->identity(['id' => '1']);
            self::fail('Mapper exception not wrapped.');
        } catch (ProviderException $error) {
            self::assertNull($error->getPrevious());
            self::assertStringNotContainsString('raw-data-secret', (string) $error);
        }
    }

    public function testSessionStateStoreRequiresActiveSession(): void
    {
        $this->expectException(ConfigurationException::class);
        new SessionStateStore();
    }

    #[DataProvider('forbiddenResources')]
    public function testResourceOriginPolicy(string $url): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient();
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        try {
            $token = new Token($provider->tokenBinding(), 'oauth2', 'secret');
            $oauth->request('github', $token, new Request('GET', $url));
            self::fail('Forbidden resource accepted.');
        } catch (ProviderException) {
            self::assertCount(0, $http->requests);
        }
    }

    public function testNoRedirectAndSafeTraceAndTransportException(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['event' => $message, 'context' => $context];
            }
        };
        $http = new MockHttpClient([
            new Response(302, ['Location' => 'https://evil.example/access-secret'], 'refresh-secret'),
            new \RuntimeException('raw-client-secret'),
        ]);
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http, $logger);
        foreach ([ProviderException::class, TransportException::class] as $expected) {
            $start = $this->start($oauth);
            try {
                $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code-secret']);
                self::fail('Failure not mapped.');
            } catch (\Throwable $error) {
                self::assertInstanceOf($expected, $error);
                self::assertNull($error->getPrevious());
                self::assertStringNotContainsString('secret', (string) $error);
            }
        }
        self::assertCount(2, $http->requests);
        $trace = json_encode($logger->records, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('secret', $trace);
        self::assertStringNotContainsString('https', $trace);
        self::assertStringContainsString('oauth.http.response', $trace);
        self::assertStringContainsString('oauth.http.failure', $trace);
    }

    public function testWrongTokenBinding(): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient();
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $token = new Token('wrong-binding', 'oauth2', 'secret', 'refresh-secret');
        foreach (['user', 'refresh'] as $operation) {
            try {
                $oauth->{$operation}('github', $token);
                self::fail('Token from another provider accepted by ' . $operation . '().');
            } catch (ConfigurationException) {
                self::assertCount(0, $http->requests);
            }
        }
        $this->expectException(ConfigurationException::class);
        $oauth->request('github', $token, new Request('GET', 'https://api.github.com/user'));
    }

    public static function traceScenarios(): iterable
    {
        yield 'denied callback' => [
            'callback',
            [],
            ['state' => 'REPLACE', 'error' => 'access_denied'],
            [['oauth.failure', 'callback', 'callback', []]],
        ];
        yield 'replayed state' => [
            'callback',
            [],
            ['state' => 'f' . str_repeat('0', 63), 'code' => 'code'],
            [['oauth.failure', 'callback', 'callback', []]],
        ];
        yield 'provider rejects the code' => [
            'exchange',
            [new Response(400, [], 'bad_verification_code')],
            ['state' => 'REPLACE', 'code' => 'code'],
            [
                ['oauth.http.response', 'token_exchange', null, ['method' => 'POST', 'status' => 400]],
                ['oauth.failure', 'token_exchange', 'provider', []],
            ],
        ];
        yield 'identity endpoint fails' => [
            'callback',
            [
                new Response(200, [], '{"access_token":"ok","token_type":"Bearer"}'),
                new Response(503, [], 'unavailable'),
            ],
            ['state' => 'REPLACE', 'code' => 'code'],
            [
                ['oauth.http.response', 'token_exchange', null, ['method' => 'POST', 'status' => 200]],
                ['oauth.http.response', 'identity', null, ['method' => 'GET', 'status' => 503]],
                ['oauth.failure', 'callback', 'provider', []],
            ],
        ];
        yield 'transport failure' => [
            'exchange',
            [new \RuntimeException('raw-client-secret')],
            ['state' => 'REPLACE', 'code' => 'code'],
            [
                ['oauth.http.failure', 'token_exchange', 'transport', ['method' => 'POST']],
                ['oauth.failure', 'token_exchange', 'transport', []],
            ],
        ];
    }

    #[DataProvider('traceScenarios')]
    public function testTraceIdentifiesProviderStageAndCategory(
        string $operation,
        array $queue,
        array $query,
        array $expected,
    ): void {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'event' => (string) $message, 'context' => $context];
            }
        };
        $keys = [
            'oauth.http.response' => ['provider', 'stage', 'method', 'status', 'duration_ms'],
            'oauth.http.failure' => ['provider', 'stage', 'method', 'category', 'duration_ms'],
            'oauth.failure' => ['provider', 'stage', 'category', 'duration_ms'],
        ];
        $http = new MockHttpClient(array_values($queue));
        $oauth = new OAuth(['github' => $this->provider()], new MemoryStateStore(), $http, $logger);
        $query['state'] = $operation === 'exchange' || $query['state'] === 'REPLACE'
            ? $this->start($oauth)['state']
            : $query['state'];
        $this->expectException(OAuthException::class);
        try {
            $operation === 'callback'
                ? $oauth->callback('github', $query)
                : $oauth->exchange('github', $query);
        } catch (OAuthException $error) {
            self::assertCount(count($expected), $logger->records);
            foreach ($expected as $index => [$event, $stage, $category, $extra]) {
                $record = $logger->records[$index];
                self::assertSame('debug', $record['level']);
                self::assertSame($event, $record['event']);
                self::assertSame($keys[$event], array_keys($record['context']));
                self::assertSame('github', $record['context']['provider']);
                self::assertSame($stage, $record['context']['stage']);
                self::assertIsFloat($record['context']['duration_ms']);
                if ($category !== null) {
                    self::assertSame($category, $record['context']['category']);
                }
                foreach ($extra as $key => $value) {
                    self::assertSame($value, $record['context'][$key]);
                }
            }
            $trace = json_encode($logger->records, JSON_THROW_ON_ERROR);
            foreach (['secret', 'https', 'access_token', 'github.com', 'code', 'error'] as $needle) {
                self::assertStringNotContainsString($needle, $trace);
            }
            throw $error;
        }
    }

    public function testTokenBindingSurvivesCredentialRotationButNotProviderIdentityChange(): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient([$this->response(['id' => 'rotated-user'])]);
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $token = new Token($provider->tokenBinding(), 'oauth2', 'persisted-access');

        $rotated = new GitHub('client-id', 'rotated-client-secret', 'https://app.example/callback/github');
        self::assertNotSame($provider->binding(), $rotated->binding());
        self::assertSame($provider->tokenBinding(), $rotated->tokenBinding());
        self::assertSame('rotated-user', (new OAuth(
            ['github' => $rotated],
            new MemoryStateStore(),
            $http,
        ))->user('github', $token)->id);

        $rescoped = new OAuth2Provider(
            'client-id',
            'client-secret',
            'https://app.example/callback/custom',
            'https://auth.example/authorize',
            'https://auth.example/token',
            'https://api.example/me',
            ['profile'],
        );
        $moved = new OAuth2Provider(
            'client-id',
            'rotated-client-secret',
            'https://app.example/callback/custom',
            'https://auth.example/v2/authorize',
            'https://auth.example/v2/token',
            'https://api.example/v2/me',
            ['profile', 'email'],
            authorizationParameters: ['prompt' => 'consent'],
            clientAuthentication: 'client_secret_basic',
            resourceOrigins: ['https://uploads.example'],
        );
        self::assertNotSame($rescoped->binding(), $moved->binding());
        self::assertSame($rescoped->tokenBinding(), $moved->tokenBinding());
        self::assertNotSame($rescoped->tokenBinding(), $provider->tokenBinding());

        foreach (
            [
            new GitHub('other-client-id', 'client-secret', 'https://app.example/callback/github'),
            new GitHub('client-id', 'client-secret', 'https://app.example/callback/other'),
            new Google('client-id', 'client-secret', 'https://app.example/callback/github'),
            ] as $different
        ) {
            $candidate = new OAuth(['github' => $different], new MemoryStateStore(), $http);
            try {
                $candidate->user('github', $token);
                self::fail('Token accepted by a different provider identity.');
            } catch (ConfigurationException) {
                self::assertCount(1, $http->requests);
            }
        }
        self::assertSame('oauth2', $token->protocol);
    }

    public function testIdentityFailureConsumesCallbackAndRecoversWithExchangeThenUser(): void
    {
        $provider = $this->provider();
        $http = new MockHttpClient([
            $this->response(['access_token' => 'first-access', 'token_type' => 'Bearer']),
            new Response(500, [], 'unavailable'),
            $this->response(['access_token' => 'second-access', 'token_type' => 'Bearer']),
            new Response(500, [], 'unavailable'),
        ]);
        $oauth = new OAuth(['github' => $provider], new MemoryStateStore(), $http);
        $start = $this->start($oauth);
        try {
            $oauth->callback('github', ['state' => $start['state'], 'code' => 'code']);
            self::fail('Identity failure not surfaced.');
        } catch (ProviderException $error) {
            self::assertNull($error->getPrevious());
        }
        try {
            $oauth->callback('github', ['state' => $start['state'], 'code' => 'code']);
            self::fail('Consumed callback replayed.');
        } catch (CallbackException) {
            self::assertCount(2, $http->requests);
        }
        $start = $this->start($oauth);
        $token = $oauth->exchange('github', ['state' => $start['state'], 'code' => 'code']);
        self::assertSame('second-access', $token->accessToken);
        try {
            $oauth->user('github', $token);
            self::fail('Identity retry not attempted.');
        } catch (ProviderException) {
            self::assertCount(4, $http->requests);
        }
        $http->queue[] = $this->response(['id' => 'recovered', 'name' => 'Ada']);
        self::assertSame('recovered', $oauth->user('github', $token)->id);
        self::assertCount(5, $http->requests);
    }

    public function testTransportUrlPolicyFailureClassification(): void
    {
        $http = new MockHttpClient();
        $transport = new Transport($http);
        try {
            $transport->send(new Request('GET', 'http://api.github.com/user'));
            self::fail('Insecure transport URL accepted.');
        } catch (ConfigurationException $error) {
            self::assertNull($error->getPrevious());
        }
        try {
            $transport->send(new Request('GET', 'https://user:secret@evil.example/token'));
            self::fail('Userinfo transport URL accepted.');
        } catch (ConfigurationException $error) {
            self::assertNull($error->getPrevious());
        }
        self::assertCount(0, $http->requests);
    }
}
