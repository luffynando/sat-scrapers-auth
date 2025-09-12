<?php

declare(strict_types=1);

namespace SatScrapersAuth\Sessions\Ciec;

use PhpCfdi\ImageCaptchaResolver\CaptchaImageInterface;
use SatScrapersAuth\Exceptions\LoginException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use Throwable;

final class CiecLoginException extends LoginException
{
    private ?\PhpCfdi\ImageCaptchaResolver\CaptchaImageInterface $captchaImage = null;

    /**
     * LoginException CIEC constructor
     *
     * @param array<string, string> $postedData
     * @param Throwable|null $previous
     */
    public function __construct(string $message, private readonly CiecSessionData $sessionData, string $contents, private readonly array $postedData = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $contents, $previous);
    }

    public static function notRegisteredAfterLogin(CiecSessionData $data, string $contents): self
    {
        $message = "It was expected to have the session registered on portal home page with RFC {$data->getRfc()}";

        return new self($message, $data, $contents);
    }

    public static function noCaptchaImageFound(CiecSessionData $data, string $contents, ?Throwable $previous = null): self
    {
        return new self('It was unable to find the captcha image', $data, $contents, [], $previous);
    }

    public static function captchaWithoutAnswer(CiecSessionData $data, CaptchaImageInterface $captchaImage, ?Throwable $previous = null): self
    {
        $exception = new self('Unable to decode captcha', $data, '', [], $previous);
        $exception->captchaImage = $captchaImage;

        return $exception;
    }

    /**
     * @param array<string, string> $postedData
     */
    public static function incorrectLoginData(CiecSessionData $data, string $contents, array $postedData): self
    {
        return new self('Incorrect login data', $data, $contents, $postedData);
    }

    public static function connectionException(string $when, CiecSessionData $data, SatHttpGatewayException $exception): self
    {
        return new self("Connection error when $when", $data, '', [], $exception);
    }

    public function getSessionData(): CiecSessionData
    {
        return $this->sessionData;
    }

    /** @return array<string, string> */
    public function getPostedData(): array
    {
        return $this->postedData;
    }

    public function getCaptchaImage(): ?CaptchaImageInterface
    {
        return $this->captchaImage;
    }
}
