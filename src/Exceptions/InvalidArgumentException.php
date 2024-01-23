<?php

declare(strict_types=1);

namespace SatScrapersAuth\Exceptions;

final class InvalidArgumentException extends \InvalidArgumentException implements SatException
{
    public static function emptyInput(string $name): self
    {
        return new self(sprintf('Invalid argument %s is empty', $name));
    }
}
