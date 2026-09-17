<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

use Eva\EvaOAuth\Identity;

interface ProviderInterface
{
    public function protocol(): string;

    public function redirectUri(): string;

    public function binding(): string;

    public function identity(array $data): Identity;

    public function resourceUrl(): string;

    public function allowedOrigins(): array;
}
