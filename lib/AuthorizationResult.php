<?php

declare(strict_types=1);

namespace Eva\EvaOAuth;

final readonly class AuthorizationResult implements \JsonSerializable
{
    public function __construct(public string $provider, public Token $token, public Identity $user)
    {
    }

    public function jsonSerialize(): array
    {
        return ['provider' => $this->provider, 'token' => $this->token, 'user' => $this->user];
    }
}
