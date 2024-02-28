<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions;

use SatScrapersAuth\Exceptions\LogicException;
use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Portals\Contracts\SatPortal;

abstract class AbstractSessionManager implements SessionManager
{
    private ?SatPortal $portal = null;

    abstract protected function createExceptionConnection(string $when, SatHttpGatewayException $exception): LoginException;

    abstract protected function createExceptionNotAuthenticated(string $html): LoginException;

    public function hasLogin(): bool
    {
        return $this->getPortal()->hasLogin();
    }

    public function logout(): void
    {
        $this->getPortal()->logout();
    }

    public function accessPortalMainPage(): void
    {
        try {
            $htmlMainPage = $this->getPortal()->accessPortalMainPage();
        } catch (SatHttpGatewayException $exception) {
            throw $this->createExceptionConnection('registering on login page', $exception);
        }

        if (! $this->getPortal()->checkIsAuthenticated($htmlMainPage)) {
            throw $this->createExceptionNotAuthenticated($htmlMainPage);
        }
    }

    public function getPortal(): SatPortal
    {
        if (!$this->portal instanceof SatPortal) {
            throw LogicException::generic('Must set portal property before use');
        }

        return $this->portal;
    }

    public function setPortal(SatPortal $satPortal): void
    {
        $this->portal = $satPortal;
    }
}
