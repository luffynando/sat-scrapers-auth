<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions;

use SatScrapersAuth\Exceptions\LogicException;
use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Portals\Contracts\SatPortal;

interface SessionManager
{
    /**
     * Check if the current session manager has an active session
     *
     * @throws LoginException
     */
    public function hasLogin(): bool;

    /**
     * Perform log in
     *
     * @throws LoginException
     */
    public function login(): void;

    /**
     * Perform log out
     */
    public function logout(): void;

    /**
     * Access to portal main page once session is created
     *
     * @throws LoginException
     */
    public function accessPortalMainPage(): void;

    /**
     * Get SatPortal Property
     *
     * @throws LogicException when property has not been set
     */
    public function getPortal(): SatPortal;

    /**
     * Set SatPortal property
     */
    public function setPortal(SatPortal $satPortal): void;

    /**
     * Get the RFC associated with the session data
     */
    public function getRfc(): string;
}
