<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

final class MemoryStateStore extends BoundedStateStore
{
    private array $transactions = [];

    protected function &entries(): array
    {
        return $this->transactions;
    }
}
