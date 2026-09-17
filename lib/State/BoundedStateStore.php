<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

use Eva\EvaOAuth\Exception\ConfigurationException;

abstract class BoundedStateStore implements StateStore
{
    public function __construct(private readonly int $ttl = 600, private readonly int $capacity = 10)
    {
        if ($ttl < 1 || $ttl > 1800 || $capacity < 1 || $capacity > 100) {
            throw new ConfigurationException();
        }
    }

    abstract protected function &entries(): array;

    final public function put(#[\SensitiveParameter] string $key, #[\SensitiveParameter] Transaction $transaction): void
    {
        $entries = &$this->entries();
        $this->prune($entries);
        $key = hash('sha256', $key);
        if (isset($entries[$key])) {
            throw new ConfigurationException();
        }
        while (count($entries) >= $this->capacity) {
            array_shift($entries);
        }
        $entries[$key] = ['expires' => time() + $this->ttl, 'transaction' => $transaction];
    }

    final public function consume(#[\SensitiveParameter] string $key): ?Transaction
    {
        $entries = &$this->entries();
        $this->prune($entries);
        $key = hash('sha256', $key);
        $entry = $entries[$key] ?? null;
        unset($entries[$key]);
        return $entry['transaction'] ?? null;
    }

    private function prune(array &$entries): void
    {
        foreach ($entries as $key => $entry) {
            $expired = !is_array($entry) || !isset($entry['expires'], $entry['transaction'])
                || $entry['expires'] <= time() || !$entry['transaction'] instanceof Transaction;
            if ($expired) {
                unset($entries[$key]);
            }
        }
    }

    final public function __debugInfo(): array
    {
        return [];
    }
}
