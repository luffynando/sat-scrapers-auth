<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\LogicException;
use SatScrapersAuth\SatHttpGateway;

abstract class AbstractSatPortal
{
    /** @var SatHttpGateway|null */
    protected $satHttpGateway;

    public function getHttpGateway(): SatHttpGateway
    {
        if ($this->satHttpGateway === null) {
            throw LogicException::generic('Must set http gateway property before use');
        }

        return $this->satHttpGateway;
    }

    public function setHttpGateway(SatHttpGateway $httpGateway): void
    {
        $this->satHttpGateway = $httpGateway;
    }
}
