<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\AuthorizationResult;
use Eva\EvaOAuth\OAuth;
use Eva\EvaOAuth\Token;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DocumentationTest extends TestCase
{
    public static function documents(): iterable
    {
        yield 'English' => ['README.md'];
        yield 'Chinese' => ['README_CN.md'];
    }

    #[DataProvider('documents')]
    public function testEveryReadmePhpFenceExecutes(string $document): void
    {
        $root = dirname(__DIR__, 2);
        $markdown = file_get_contents($root . '/' . $document);
        self::assertIsString($markdown);
        preg_match_all('/^```php\h*\R(.*?)^```\h*$/ms', $markdown, $matches);
        $snippets = $matches[1];
        self::assertCount(9, $snippets, 'Every added PHP fence must have an execution scenario.');
        foreach (['GITHUB', 'GOOGLE', 'FLICKR', 'CUSTOM'] as $provider) {
            putenv($provider . '_CLIENT_ID=documentation-client');
            putenv($provider . '_CLIENT_SECRET=documentation-secret');
        }
        $_GET = [];
        $githubHttp = new MockHttpClient([
            $this->json(['access_token' => 'github-access', 'token_type' => 'Bearer']),
            $this->json(['id' => 42, 'name' => 'Ada', 'email' => 'ada@example.test']),
        ]);
        $login = $this->execute($snippets[0], ['httpClient' => $githubHttp]);
        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertSame('1', ini_get('session.use_strict_mode'));
        self::assertSame('1', ini_get('session.use_only_cookies'));
        $cookies = session_get_cookie_params();
        self::assertTrue($cookies['secure']);
        self::assertTrue($cookies['httponly']);
        self::assertSame('Lax', $cookies['samesite']);
        $start = $this->query($login['url']);
        self::assertSame('S256', $start['code_challenge_method']);
        self::assertCount(0, $githubHttp->requests);
        $oldSessionId = session_id();
        session_write_close();
        $_GET = ['state' => $start['state'], 'code' => 'github-code'];
        $callback = $this->execute($snippets[0], ['httpClient' => $githubHttp]);
        self::assertNotSame($oldSessionId, session_id());
        self::assertInstanceOf(AuthorizationResult::class, $callback['result']);
        self::assertSame('github-access', $callback['token']->accessToken);
        self::assertSame([
            'provider' => 'github',
            'id' => '42',
            'name' => 'Ada',
            'email' => 'ada@example.test',
        ], $_SESSION['oauth_identity']);
        self::assertCount(2, $githubHttp->requests);
        self::assertSame([], $githubHttp->queue);

        $googleHttp = new MockHttpClient([
            $this->json([
                'access_token' => 'google-old',
                'token_type' => 'Bearer',
                'refresh_token' => 'google-refresh',
                'expires_in' => 3600,
            ]),
            $this->json(['sub' => 'google-user', 'name' => 'Ada', 'email_verified' => true]),
            $this->json(['access_token' => 'google-new', 'token_type' => 'Bearer', 'expires_in' => 3600]),
        ]);
        $google = $this->execute($snippets[1], ['httpClient' => $googleHttp]);
        $start = $this->query($google['url']);
        self::assertSame('offline', $start['access_type']);
        self::assertSame('openid profile email', $start['scope']);
        $_GET = ['state' => $start['state'], 'code' => 'google-code'];
        $google = $this->execute($snippets[2], $google);
        self::assertSame('google-user', $google['user']->id);
        self::assertTrue($google['user']->emailVerified);
        $record = $google['token']->toArray();
        $record['expiresAt'] = time() - 1;
        $google['token'] = Token::fromArray($record);
        $google = $this->execute($snippets[3], $google);
        self::assertSame('google-new', $google['token']->accessToken);
        self::assertSame('google-refresh', $google['token']->refreshToken);
        self::assertFalse($google['token']->isExpired());
        self::assertEquals($google['token'], $google['restored']);
        self::assertSame($google['token']->toArray(), $google['record']);
        self::assertCount(3, $googleHttp->requests);
        self::assertSame([], $googleHttp->queue);
        self::assertSame('https://oauth2.googleapis.com/token', (string) $googleHttp->requests[2]->getUri());
        parse_str((string) $googleHttp->requests[2]->getBody(), $refresh);
        self::assertSame('refresh_token', $refresh['grant_type']);
        self::assertSame('google-refresh', $refresh['refresh_token']);

        $flickrHttp = new MockHttpClient([
            new Response(
                200,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                'oauth_token=temporary&oauth_token_secret=temporary-secret&oauth_callback_confirmed=true',
            ),
            new Response(
                200,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                'oauth_token=flickr-access&oauth_token_secret=flickr-secret',
            ),
            $this->json(['stat' => 'ok', 'user' => ['id' => '123@N00', 'username' => ['_content' => 'Ada']]]),
        ]);
        $flickr = $this->execute($snippets[4], ['httpClient' => $flickrHttp]);
        $start = $this->query($flickr['url']);
        self::assertSame('temporary', $start['oauth_token']);
        self::assertSame('read', $start['perms']);
        $_GET = ['oauth_token' => $start['oauth_token'], 'oauth_verifier' => 'flickr-verifier'];
        $flickr = $this->execute($snippets[5], $flickr);
        self::assertSame('flickr', $flickr['result']->provider);
        self::assertSame('123@N00', $flickr['user']->id);
        self::assertSame('Ada', $flickr['user']->name);
        self::assertSame('oauth1', $flickr['token']->protocol);
        self::assertSame('flickr-secret', $flickr['token']->tokenSecret);
        self::assertCount(3, $flickrHttp->requests);
        self::assertSame([], $flickrHttp->queue);
        self::assertStringContainsString('oauth_signature=', $flickrHttp->requests[2]->getHeaderLine('Authorization'));

        $customHttp = new MockHttpClient([
            $this->json(['access_token' => 'custom-access', 'token_type' => 'Bearer']),
            $this->json(['account_id' => 'custom-user']),
            $this->json(['account_id' => 'custom-user']),
        ]);
        $custom = $this->execute($snippets[6], ['httpClient' => $customHttp]);
        $start = $this->query($custom['url']);
        self::assertSame('profile', $start['scope']);
        $logger = new class extends AbstractLogger {
            public array $events = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->events[] = [$level, (string) $message, $context];
            }
        };
        $custom['logger'] = $logger;
        $custom = $this->execute($snippets[7], $custom);
        self::assertInstanceOf(OAuth::class, $custom['oauth']);
        $result = $custom['oauth']->callback('custom', ['state' => $start['state'], 'code' => 'custom-code']);
        self::assertSame('custom-user', $result->user->id);
        $custom['token'] = $result->token;
        $custom = $this->execute($snippets[8], $custom);
        self::assertSame(200, $custom['status']);
        self::assertCount(3, $customHttp->requests);
        self::assertSame([], $customHttp->queue);
        self::assertStringStartsWith('Basic ', $customHttp->requests[0]->getHeaderLine('Authorization'));
        self::assertSame('Bearer custom-access', $customHttp->requests[2]->getHeaderLine('Authorization'));
        self::assertCount(3, $logger->events);
        $stages = [];
        foreach ($logger->events as [$level, $event, $metadata]) {
            self::assertSame('debug', $level);
            self::assertSame('oauth.http.response', $event);
            self::assertSame(['provider', 'stage', 'method', 'status', 'duration_ms'], array_keys($metadata));
            self::assertSame('custom', $metadata['provider']);
            self::assertIsFloat($metadata['duration_ms']);
            self::assertSame(200, $metadata['status']);
            $stages[] = $metadata['stage'];
        }
        self::assertSame(['token_exchange', 'identity', 'resource_request'], $stages);
        session_destroy();
    }

    private function execute(string $source, array $variables): array
    {
        $source = preg_replace('/\A<\?php\s*/', '', $source);
        self::assertIsString($source);
        return (static function (string $source, array $variables): array {
            extract($variables, EXTR_SKIP);
            eval($source);
            return get_defined_vars();
        })($source, $variables);
    }

    private function query(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        return $query;
    }

    private function json(array $data): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }
}
