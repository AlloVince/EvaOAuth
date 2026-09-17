<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

final readonly class OAuth1Transaction extends Transaction
{
    public function __construct(
        string $binding,
        string $redirectUri,
        #[\SensitiveParameter] public string $token,
        #[\SensitiveParameter] public string $secret,
    ) {
        parent::__construct($binding, $redirectUri);
    }
}
