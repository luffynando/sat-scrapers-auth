<?php

declare(strict_types=1);

namespace SatScrapersAuth\Exceptions;

use Throwable;

final class RuntimeException extends \RuntimeException implements SatException
{
    protected function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function unableToFindCaptchaImage(string $selector): self
    {
        return new self(sprintf("Unable to find image using filter '%s'", $selector));
    }
}
