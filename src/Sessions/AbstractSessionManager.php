<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions;

use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\SatHttpGateway;

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

    public function accessPortalMainPage(): void
    {
        try {
            $htmlMainPage = $this->portal->accessPortalMainPage();
        } catch (SatHttpGatewayException $exception) {
            throw $this->createExceptionConnection('registering on login page', $exception);
        }

        if (! $this->portal->checkIsAuthenticated($htmlMainPage)) {
            throw $this->createExceptionNotAuthenticated($htmlMainPage);
        }
    }

    public function getHttpGateway(): SatHttpGateway
    {
        return $this->portal->getHttpGateway();
    }

    public function setHttpGateway(SatHttpGateway $httpGateway): void
    {
        $this->portal->setHttpGateway($httpGateway);
    }
}
