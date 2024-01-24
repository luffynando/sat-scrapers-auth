<?php

declare(strict_types=1);

use SatScrapersAuth\SatCfdiPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in CFDI Portal', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        $rfc = $factory->env('SAT_AUTH_RFC');
        $cfdiPortal = new SatCfdiPortal($rfc);
        try {
            $sessionManager = $factory->createFielSessionManager($cfdiPortal);
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $sessionManager->setHttpGateway($factory->createSatHttpGateway($sessionManager, 'portalcfdi'));
        if (!$sessionManager->hasLogin()) {
            $sessionManager->login();
        }

        $sessionManager->accessPortalMainPage();
        expect($sessionManager->hasLogin())->toBeTrue();

        $sessionManager->logout();
        expect($sessionManager->hasLogin())->toBeFalse();
    });
});
