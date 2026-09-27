<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\OAuth;
use Eva\EvaOAuth\State\MemoryStateStore;
use Eva\EvaOAuth\Tests\Fixture\DemoProvider;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ProviderExtensionTest extends TestCase
{
    private function response(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function testProviderOnlyDeclaresConfigurationAndIdentityMapping(): void
    {
        $overridden = [];
        foreach ((new \ReflectionClass(DemoProvider::class))->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === DemoProvider::class) {
                $overridden[] = $method->getName();
            }
        }
        self::assertSame(['__construct', 'identity'], $overridden);
        self::assertSame('demo', (new DemoProvider('id', 'secret', 'https://app.example/callback/demo'))
            ->authorizationParameters['audience']);
    }

    public function testNewProviderRunsTheSharedFlowWithoutProtocolCode(): void
    {
        $http = new MockHttpClient([
            $this->response([
                'access_token' => 'demo-access',
                'token_type' => 'Bearer',
                'refresh_token' => 'demo-refresh',
                'expires_in' => 3600,
            ]),
            $this->response(['account' => ['id' => 'demo-1', 'handle' => 'ada'], 'email' => 'ada@demo.example']),
            $this->response(['access_token' => 'demo-next', 'token_type' => 'Bearer', 'expires_in' => 3600]),
            $this->response(['account' => ['id' => 'demo-1', 'handle' => 'ada']]),
            $this->response(['account' => ['id' => 'demo-1', 'handle' => 'ada']]),
        ]);
        $oauth = new OAuth(
            ['demo' => new DemoProvider('demo-id', 'demo-secret', 'https://app.example/callback/demo')],
            new MemoryStateStore(),
            $http,
        );
        $url = $oauth->authorize('demo');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $start);
        self::assertStringStartsWith('https://auth.demo.example/oauth2/authorize?', $url);
        self::assertSame('code', $start['response_type']);
        self::assertSame('profile:read offline', $start['scope']);
        self::assertSame('demo', $start['audience']);
        self::assertSame('S256', $start['code_challenge_method']);

        $result = $oauth->callback('demo', ['state' => $start['state'], 'code' => 'demo-code']);
        self::assertSame('demo', $result->provider);
        self::assertSame('demo-1', $result->user->id);
        self::assertSame('ada', $result->user->name);
        self::assertSame('ada@demo.example', $result->user->email);
        self::assertSame('demo-access', $result->token->accessToken);
        self::assertSame(['profile:read', 'offline'], $result->token->scopes);

        $token = $oauth->refresh('demo', $result->token);
        self::assertSame('demo-next', $token->accessToken);
        self::assertSame('demo-refresh', $token->refreshToken);
        self::assertSame('demo-1', $oauth->user('demo', $token)->id);
        $response = $oauth->request('demo', $token, new Request('GET', 'https://api.demo.example/v1/me'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Bearer demo-next', $http->requests[4]->getHeaderLine('Authorization'));
        self::assertSame('https://auth.demo.example/oauth2/token', (string) $http->requests[0]->getUri());
    }
}
