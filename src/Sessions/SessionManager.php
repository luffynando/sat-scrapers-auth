<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions;

use SatScrapersAuth\Exceptions\LoginException;

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
     * Get the RFC associated with the session data
     */
    public function getRfc(): string;
}
