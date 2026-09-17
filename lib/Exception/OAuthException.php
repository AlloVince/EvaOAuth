<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Exception;

abstract class OAuthException extends \RuntimeException
{
    final public function __construct()
    {
        parent::__construct(match (static::class) {
            ConfigurationException::class => 'Invalid OAuth configuration.',
            CallbackException::class => 'Invalid or expired OAuth callback.',
            TransportException::class => 'OAuth transport failed.',
            UnsupportedOperationException::class => 'OAuth operation is not supported.',
            default => 'OAuth provider response is invalid.',
        });
    }

    public function __debugInfo(): array
    {
        return ['category' => static::class, 'message' => $this->getMessage()];
    }
}
