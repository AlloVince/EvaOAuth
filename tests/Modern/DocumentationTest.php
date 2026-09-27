<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\AuthorizationResult;
use Eva\EvaOAuth\OAuth;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DocumentationTest extends TestCase
{
    private static array $headers = [];

    private static string $imports = '';

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
        self::assertCount(11, $snippets, 'Every added PHP fence must have an execution scenario.');
        $this->credentials();
        self::$headers = [];
        self::$imports = $this->imports($snippets);
        $_GET = [];

        $githubHttp = new MockHttpClient([
            $this->json(['access_token' => 'github-access', 'token_type' => 'Bearer']),
            $this->json(['id' => 42, 'name' => 'Ada', 'email' => 'ada@example.test']),
        ]);
        $variables = $this->execute($snippets[0], ['httpClient' => $githubHttp]);
        self::assertInstanceOf(OAuth::class, $variables['oauth']);
        self::assertSame(PHP_SESSION_ACTIVE, session_status());

        $this->execute($snippets[1], $variables);
        $redirect = $this->redirect();
        self::assertStringStartsWith('https://github.com/login/oauth/authorize?', $redirect);
        $start = $this->query($redirect);
        self::assertSame('S256', $start['code_challenge_method']);
        self::assertSame('code', $start['response_type']);
        self::assertSame('https://example.com/oauth/github/callback', $start['redirect_uri']);
        self::assertSame('documentation-client', $start['client_id']);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $start['state']);
        self::assertCount(0, $githubHttp->requests);

        $_GET = ['state' => $start['state'], 'code' => 'github-code'];
        $result = $this->authorizationResult($this->execute($snippets[2], $variables));
        self::assertSame('github', $result->provider);
        self::assertSame('oauth2', $result->token->protocol);
        self::assertSame('github-access', $result->token->accessToken);
        self::assertSame('42', $result->user->id);
        self::assertSame('Ada', $result->user->name);
        self::assertSame('ada@example.test', $result->user->email);
        self::assertCount(2, $githubHttp->requests);
        self::assertSame([], $githubHttp->queue);
        self::assertSame('https://github.com/login/oauth/access_token', (string) $githubHttp->requests[0]->getUri());
        self::assertSame('https://api.github.com/user', (string) $githubHttp->requests[1]->getUri());

        $googleHttp = new MockHttpClient([
            $this->json([
                'access_token' => 'google-access',
                'token_type' => 'Bearer',
                'refresh_token' => 'google-refresh',
                'expires_in' => 3600,
            ]),
            $this->json([
                'sub' => 'google-user',
                'name' => 'Ada',
                'email' => 'ada@example.test',
                'email_verified' => true,
            ]),
        ]);
        $variables = $this->execute($snippets[3], ['httpClient' => $googleHttp]);
        self::assertInstanceOf(OAuth::class, $variables['oauth']);
        $variables = $this->execute($snippets[4], $variables);
        self::assertCount(0, $googleHttp->requests);
        $start = $this->query($variables['url']);
        self::assertSame('offline', $start['access_type']);
        self::assertSame('S256', $start['code_challenge_method']);
        self::assertSame('openid profile email', $start['scope']);

        $_GET = ['state' => $start['state'], 'code' => 'google-code'];
        $result = $this->authorizationResult($this->execute($snippets[5], $variables));
        self::assertSame('google', $result->provider);
        self::assertSame('google-user', $result->user->id);
        self::assertTrue($result->user->emailVerified);
        self::assertSame('google-refresh', $result->token->refreshToken);
        self::assertCount(2, $googleHttp->requests);
        self::assertSame([], $googleHttp->queue);
        self::assertSame('https://oauth2.googleapis.com/token', (string) $googleHttp->requests[0]->getUri());
        self::assertSame(
            'https://openidconnect.googleapis.com/v1/userinfo',
            (string) $googleHttp->requests[1]->getUri(),
        );

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
        $variables = $this->execute($snippets[6], ['httpClient' => $flickrHttp]);
        self::assertInstanceOf(OAuth::class, $variables['oauth']);
        self::assertCount(1, $flickrHttp->requests);
        $start = $this->query($variables['url']);
        self::assertSame('temporary', $start['oauth_token']);
        self::assertSame('read', $start['perms']);

        $_GET = ['oauth_token' => $start['oauth_token'], 'oauth_verifier' => 'flickr-verifier'];
        $variables = $this->execute($snippets[7], $variables);
        $result = $this->authorizationResult($variables);
        self::assertSame('flickr', $result->provider);
        self::assertSame('oauth1', $result->token->protocol);
        self::assertSame('flickr-access', $result->token->accessToken);
        self::assertSame('flickr-secret', $result->token->tokenSecret);
        self::assertSame('123@N00', $result->user->id);
        self::assertSame('Ada', $result->user->name);
        self::assertCount(3, $flickrHttp->requests);
        self::assertSame([], $flickrHttp->queue);
        self::assertStringContainsString('oauth_signature=', $flickrHttp->requests[2]->getHeaderLine('Authorization'));

        $this->execute($snippets[8], $variables);
        $variables = $this->execute($snippets[9], $variables);
        self::assertNull($result->user->avatar);
        self::assertNull($result->user->emailVerified);
        $variables = $this->execute($snippets[10], $variables);
        self::assertSame($result->token->toArray(), $variables['data']);
        self::assertSame('flickr-access', $variables['data']['accessToken']);
        self::assertSame([], self::$headers);
        session_destroy();
    }

    public static function recordHeader(string $header): void
    {
        self::$headers[] = $header;
    }

    private function execute(string $source, array $variables = []): array
    {
        return (static function (string $source, array $variables): array {
            extract($variables, EXTR_SKIP);
            eval(self::fence($source));
            return get_defined_vars();
        })($source, $variables);
    }

    private static function fence(string $source): string
    {
        $source = preg_replace('/\A<\?php\s*/', '', $source);
        self::assertIsString($source);
        $source = preg_replace('/\b(?:exit|die)\b\s*;/', 'return;', $source);
        self::assertIsString($source);
        $source = preg_replace('/^use\s+[^;]+;\h*$/m', '', $source);
        self::assertIsString($source);
        return 'namespace Eva\EvaOAuth\Tests; ' . self::$imports . "\n" . $source;
    }

    private function imports(array $snippets): string
    {
        $imports = [];
        foreach ($snippets as $snippet) {
            preg_match_all('/^use\s+[^;]+;$/m', $snippet, $found);
            foreach ($found[0] as $line) {
                $imports[$line] = $line;
            }
        }
        ksort($imports);
        return implode("\n", $imports) . "\n";
    }

    private function credentials(): void
    {
        $credentials = [
            'GITHUB_CLIENT_ID' => 'documentation-client',
            'GITHUB_CLIENT_SECRET' => 'documentation-secret',
            'GOOGLE_CLIENT_ID' => 'documentation-client',
            'GOOGLE_CLIENT_SECRET' => 'documentation-secret',
            'FLICKR_KEY' => 'documentation-client',
            'FLICKR_SECRET' => 'documentation-secret',
        ];
        foreach ($credentials as $name => $value) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }

    private function redirect(): string
    {
        $headers = self::$headers;
        self::$headers = [];
        self::assertCount(1, $headers);
        self::assertStringStartsWith('Location: ', $headers[0]);
        return substr($headers[0], strlen('Location: '));
    }

    private function authorizationResult(array $variables): AuthorizationResult
    {
        self::assertArrayHasKey('result', $variables);
        $result = $variables['result'];
        self::assertInstanceOf(AuthorizationResult::class, $result);
        return $result;
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

function header(string $header, bool $replace = true, int $response_code = 0): void
{
    DocumentationTest::recordHeader($header);
}
