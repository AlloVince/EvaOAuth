<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

interface StateStore
{
    public function put(#[\SensitiveParameter] string $key, #[\SensitiveParameter] Transaction $transaction): void;

    public function consume(#[\SensitiveParameter] string $key): ?Transaction;
}
