<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions\Ciec;

use PhpCfdi\ImageCaptchaResolver\CaptchaImage;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Internal\CaptchaBase64Extractor;
use SatScrapersAuth\Portals\SatPortal;
use SatScrapersAuth\Sessions\AbstractSessionManager;
use SatScrapersAuth\Sessions\SessionManager;
use Throwable;

final class CiecSessionManager extends AbstractSessionManager implements SessionManager
{
    public function __construct(private readonly CiecSessionData $sessionData, SatPortal $portal)
    {
        parent::__construct($portal);
    }

    public static function create(string $rfc, string $ciec, CaptchaResolverInterface $resolver, SatPortal $portal): self
    {
        $sessionData = new CiecSessionData($rfc, $ciec, $resolver);

        return new self($sessionData, $portal);
    }

    /**
     * Retrieve captcha image from CIEC login page
     */
    public function requestCaptchaImage(): CaptchaImage
    {
        try {
            $html = $this->portal->getLoginCiecPage();
        } catch (SatHttpGatewayException $exception) {
            throw CiecLoginException::connectionException('getting captcha image', $this->sessionData, $exception);
        }

        try {
            $captchaBase64Extractor = new CaptchaBase64Extractor();
            $captchaImage = $captchaBase64Extractor->retrieveCaptchaImage($html);
        } catch (Throwable $exception) {
            throw CiecLoginException::noCaptchaImageFound($this->sessionData, $html, $exception);
        }

        return $captchaImage;
    }

    /**
     *
     * @throws CiecLoginException
     */
    public function getCaptchaValue(int $attempt): string
    {
        $captchaImage = $this->requestCaptchaImage();
        try {
            $result = $this->sessionData->getCaptchaResolver()->resolve($captchaImage);
            return $result->getValue();
        } catch (Throwable $exception) {
            if ($attempt < $this->sessionData->getMaxTriesCaptcha()) {
                return $this->getCaptchaValue($attempt + 1);
            }

            if (! $exception instanceof CiecLoginException) {
                $exception = CiecLoginException::captchaWithoutAnswer($this->sessionData, $captchaImage, $exception);
            }

            throw $exception;
        }
    }

    public function login(): void
    {
        $this->loginInternal(1);
    }

    public function loginWithOutCaptcha(): void
    {
        $postData = [
            'Username' => $this->sessionData->getRfc(),
            'Password' => $this->sessionData->getCiec(),
        ];

        try {
            $response = $this->portal->postLoginCiec($postData);
        } catch (SatHttpGatewayException $exception) {
            throw CiecLoginException::connectionException('sending login data', $this->sessionData, $exception);
        }

        if (str_contains($response, 'frmLog')) {
            throw CiecLoginException::incorrectLoginData($this->sessionData, $response, $postData);
        }
    }

    /**
     *
     * @throws CiecLoginException
     */
    private function loginInternal(int $attempt): void
    {
        $captchaValue = $this->getCaptchaValue(1);

        try {
            $this->loginPostLoginData($captchaValue);
        } catch (CiecLoginException $exception) {
            if ($attempt < $this->sessionData->getMaxTriesLogin()) {
                $this->loginInternal($attempt + 1);

                return;
            }

            throw $exception;
        }
    }

    /**
     * @throws CiecLoginException
     */
    public function loginPostLoginData(string $captchaValue): void
    {
        $postData = [
            'Ecom_User_ID' => $this->sessionData->getRfc(),
            'Ecom_Password' => $this->sessionData->getCiec(),
            'option' => 'credential',
            'submit' => 'Enviar',
            'userCaptcha' => $captchaValue,
        ];

        try {
            $response = $this->portal->postLoginCiec($postData);
        } catch (SatHttpGatewayException $exception) {
            throw CiecLoginException::connectionException('sending login data', $this->sessionData, $exception);
        }

        if (str_contains($response, 'Ecom_User_ID')) {
            throw CiecLoginException::incorrectLoginData($this->sessionData, $response, $postData);
        }
    }

    public function getSessionData(): CiecSessionData
    {
        return $this->sessionData;
    }

    public function getRfc(): string
    {
        return $this->sessionData->getRfc();
    }

    protected function createExceptionConnection(string $when, SatHttpGatewayException $exception): LoginException
    {
        return CiecLoginException::connectionException('registering on login page', $this->sessionData, $exception);
    }

    protected function createExceptionNotAuthenticated(string $html): LoginException
    {
        return CiecLoginException::notRegisteredAfterLogin($this->sessionData, $html);
    }
}
