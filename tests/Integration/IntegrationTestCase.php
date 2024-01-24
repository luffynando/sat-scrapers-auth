<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    /**
     * @var Factory
     * @internal
     */
    protected static $factory;

    public function getFactory(): Factory
    {
        if (null === static::$factory) {
            static::$factory = new Factory();
        }

        return static::$factory;
    }
}
