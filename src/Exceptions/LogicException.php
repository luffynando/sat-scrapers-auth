<?php

declare(strict_types=1);

namespace SatScrapersAuth\Exceptions;

use Throwable;

final class LogicException extends \LogicException implements SatException
{
    public static function generic(string $message, Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}
