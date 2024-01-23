<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions;

use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;

abstract class AbstractSessionManager implements SessionManager
{
    public function __construct(protected \SatScrapersAuth\Portals\SatPortal $portal) {}

    abstract protected function createExceptionConnection(string $when, SatHttpGatewayException $exception): LoginException;

    abstract protected function createExceptionNotAuthenticated(string $html): LoginException;

    public function hasLogin(): bool
    {
        return $this->portal->hasLogin();
    }

    public function logout(): void
    {
        $this->portal->logout();
    }
}
