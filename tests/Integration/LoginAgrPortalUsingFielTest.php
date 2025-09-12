<?php

declare(strict_types=1);

use SatScrapersAuth\Portals\SatAgrPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in Agr Portal', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        try {
            $sessionManager = $factory->createFielSessionManager();
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $portal = new SatAgrPortal(
            $sessionManager,
            $factory->createSatHttpGateway($sessionManager, 'agr'),
        );

        if (!$portal->hasLogin()) {
            $portal->getSessionManager()->login();
        }

        $portal->getSessionManager()->accessPortalMainPage();
        expect($portal->hasLogin())->toBeTrue();

        $portal->logout();
        expect($portal->hasLogin())->toBeFalse();
    });
});
