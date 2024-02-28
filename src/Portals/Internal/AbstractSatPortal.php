<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals\Internal;

use SatScrapersAuth\Portals\Contracts\SatPortal;
use SatScrapersAuth\SatHttpGateway;
use SatScrapersAuth\Sessions\SessionManager;

abstract class AbstractSatPortal implements SatPortal
{
    protected SatHttpGateway $satHttpGateway;

    public function __construct(protected readonly SessionManager $sessionManager, ?SatHttpGateway $satHttpGateway = null)
    {
        $this->satHttpGateway = $satHttpGateway ?? $this->createDefaultSatHttpGateway();
        $this->sessionManager->setPortal($this);
    }

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    public function getHttpGateway(): SatHttpGateway
    {
        return $this->satHttpGateway;
    }

    /**
     * Method factory to create a SatHttpGateway
     *
     * @internal
     */
    protected function createDefaultSatHttpGateway(): SatHttpGateway
    {
        return new SatHttpGateway();
    }
}
