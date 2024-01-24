<?php

declare(strict_types=1);

use SatScrapersAuth\Portals\SatRfcAmpCPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in RFCAmpC Portal', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        $cfdiPortal = new SatRfcAmpCPortal();
        try {
            $sessionManager = $factory->createFielSessionManager($cfdiPortal);
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $sessionManager->setHttpGateway($factory->createSatHttpGateway($sessionManager, 'rfcampc'));
        if (!$sessionManager->hasLogin()) {
            $sessionManager->login();
        }

        $sessionManager->accessPortalMainPage();
        expect($sessionManager->hasLogin())->toBeTrue();

        $sessionManager->logout();
        expect($sessionManager->hasLogin())->toBeFalse();
    });
});
