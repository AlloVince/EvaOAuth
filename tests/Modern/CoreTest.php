<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Token;
use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public function testTokenRedactionAndExplicitPersistence(): void
    {
        $token = new Token('binding', 'oauth2', 'access-secret', 'refresh-secret');
        self::assertSame($token->toArray(), Token::fromArray($token->toArray())->toArray());
        self::assertStringNotContainsString('secret', json_encode($token, JSON_THROW_ON_ERROR));
        ob_start();
        var_dump($token);
        $dump = ob_get_clean();
        self::assertIsString($dump);
        self::assertStringNotContainsString('secret', $dump);
        $this->expectException(ConfigurationException::class);
        serialize($token);
    }
}
