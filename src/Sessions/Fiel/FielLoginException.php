<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions\Fiel;

use SatScrapersAuth\Exceptions\LoginException;
use Throwable;

final class FielLoginException extends LoginException
{
    public function __construct(string $message, string $contents, private readonly FielSessionData $sessionData, ?Throwable $previous = null)
    {
        parent::__construct($message, $contents, $previous);
    }

    public static function connectionException(string $when, FielSessionData $sessionData, ?Throwable $previous = null): self
    {
        return new self("Connection error when $when", '', $sessionData, $previous);
    }

    public static function notRegisteredAfterLogin(FielSessionData $sessionData, string $contents): self
    {
        $message = "It was expected to have the session registered on portal home page with RFC {$sessionData->getRfc()}";

        return new self($message, $contents, $sessionData);
    }

    public function getSessionData(): FielSessionData
    {
        return $this->sessionData;
    }
}
