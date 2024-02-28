<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions\Fiel;

use PhpCfdi\Credentials\Credential;
use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Sessions\AbstractSessionManager;
use SatScrapersAuth\Sessions\SessionManager;

final class FielSessionManager extends AbstractSessionManager implements SessionManager
{
    public function __construct(private readonly FielSessionData $sessionData) {}

    public static function create(Credential $credential): self
    {
        return new self(new FielSessionData($credential));
    }

    public function login(): void
    {
        try {
            $html = $this->getPortal()->getLoginFielPage();
            // Resolve and submit challenge, it returns an autosubmit form
            $inputs = $this->resolveChallengeUsingFiel($html);
            // Handle submit inputs
            $this->getPortal()->postLoginFiel($inputs);
        } catch (SatHttpGatewayException $exception) {
            throw FielLoginException::connectionException('try to login using FIEL', $this->sessionData, $exception);
        }
    }

    public function getSessionData(): FielSessionData
    {
        return $this->sessionData;
    }

    public function getRfc(): string
    {
        return $this->sessionData->getRfc();
    }

    /**
     * Helper funtion to get form fields
     *
     * @return array<string, string>
     */
    private function resolveChallengeUsingFiel(string $html): array
    {
        $resolver = ChallengeResolver::createFromHtml($html, $this->sessionData);

        return $resolver->obtainFormFields();
    }

    protected function createExceptionConnection(string $when, SatHttpGatewayException $exception): LoginException
    {
        return FielLoginException::connectionException($when, $this->sessionData, $exception);
    }

    protected function createExceptionNotAuthenticated(string $html): LoginException
    {
        return FielLoginException::notRegisteredAfterLogin($this->sessionData, $html);
    }
}
