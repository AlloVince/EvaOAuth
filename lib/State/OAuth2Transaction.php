<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

final readonly class OAuth2Transaction extends Transaction
{
    public function __construct(
        string $binding,
        string $redirectUri,
        #[\SensitiveParameter] public string $verifier,
        public ?string $issuer = null,
    ) {
        parent::__construct($binding, $redirectUri);
    }
}
