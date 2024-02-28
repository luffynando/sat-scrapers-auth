<?php

declare(strict_types=1);

use SatScrapersAuth\Portals\SatAcusesPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in Acuses Portal using CIEC', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        try {
            $sessionManager = $factory->createCiecSessionManager();
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $portal = new SatAcusesPortal(
            $sessionManager,
            $factory->createSatHttpGateway($sessionManager, 'acusesportal'),
        );

        if (!$portal->hasLogin()) {
            $sessionManager->loginWithOutCaptcha();
        }

        $portal->getSessionManager()->accessPortalMainPage();
        expect($portal->hasLogin())->toBeTrue();

        $portal->logout();
        expect($portal->hasLogin())->toBeFalse();
    });
});
