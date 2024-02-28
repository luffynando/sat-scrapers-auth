<?php

declare(strict_types=1);

use SatScrapersAuth\Portals\SatRfcAmpCPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in RFCAmpC Portal', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        try {
            $sessionManager = $factory->createFielSessionManager();
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $portal = new SatRfcAmpCPortal(
            $sessionManager,
            $factory->createSatHttpGateway($sessionManager, 'rfcampc'),
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
