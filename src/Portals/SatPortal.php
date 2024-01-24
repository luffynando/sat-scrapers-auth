<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\SatHttpGateway;

interface SatPortal
{
    /**
     * Check if the current session manager has an active session
     *
     * @throws LoginException
     */
    public function hasLogin(): bool;

    /**
     * Perform log out
     */
    public function logout(): void;

    /**
     * Send POST CIEC login inputs and authenticate
     *
     * @param array<string, string> $inputs
     * @throws SatHttpGatewayException
     */
    public function postLoginCiec(array $inputs): string;

    /**
     * Send POST Fiel login inputs and authenticate
     *
     * @param array<string, string> $inputs
     */
    public function postLoginFiel(array $inputs): void;

    /**
     * Retrieve CIEC login page
     *
     * @throws SatHttpGatewayException
     */
    public function getLoginCiecPage(): string;

    /**
     * Retrieve a login FIEL page
     * @throws SatHttpGatewayException
     */
    public function getLoginFielPage(): string;

    /**
     * Get portal main page
     *
     * @throws SatHttpGatewayException
     */
    public function getPortalMainPage(): string;

    /**
     * Access to Portal Main Page
     */
    public function accessPortalMainPage(): string;

    /**
     * Check if authenticated in give html input
     */
    public function checkIsAuthenticated(string $html): bool;

    public function getHttpGateway(): SatHttpGateway;

    public function setHttpGateway(SatHttpGateway $httpGateway): void;
}
