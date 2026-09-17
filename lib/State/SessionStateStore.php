<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\State;

use Eva\EvaOAuth\Exception\ConfigurationException;

final class SessionStateStore extends BoundedStateStore
{
    public function __construct(int $ttl = 600, int $capacity = 10, private readonly string $namespace = 'eva_oauth_v2')
    {
        parent::__construct($ttl, $capacity);
        if (!preg_match('/\A[a-zA-Z0-9_]{1,64}\z/D', $namespace)) {
            throw new ConfigurationException();
        }
        $this->entries();
    }

    protected function &entries(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new ConfigurationException();
        }
        if (!isset($_SESSION[$this->namespace])) {
            $_SESSION[$this->namespace] = [];
        }
        if (!is_array($_SESSION[$this->namespace])) {
            throw new ConfigurationException();
        }
        return $_SESSION[$this->namespace];
    }
}
