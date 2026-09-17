<?php

declare(strict_types=1);

namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Exception\ProviderException;

final readonly class Identity implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $avatar = null,
        public ?bool $emailVerified = null,
    ) {
        if ($id === '' || strlen($id) > 1024) {
            throw new ProviderException();
        }
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
