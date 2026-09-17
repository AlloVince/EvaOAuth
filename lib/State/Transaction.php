<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

abstract readonly class Transaction implements \JsonSerializable
{
    public function __construct(public string $binding, public string $redirectUri)
    {
    }

    final public function jsonSerialize(): array
    {
        return ['protocol' => $this instanceof OAuth2Transaction ? 'oauth2' : 'oauth1'];
    }

    final public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }
}
