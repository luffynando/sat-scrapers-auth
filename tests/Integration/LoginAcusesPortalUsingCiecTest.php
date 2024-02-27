<?php

declare(strict_types=1);

use SatScrapersAuth\SatAcusesPortal;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in Acuses Portal using CIEC', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        $rfc = $factory->env('SAT_AUTH_RFC');
        $portal = new SatAcusesPortal($rfc);
        try {
            $sessionManager = $factory->createCiecSessionManager($portal);
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $sessionManager->setHttpGateway($factory->createSatHttpGateway($sessionManager, 'acusesportal'));
        if (!$sessionManager->hasLogin()) {
            $sessionManager->loginWithOutCaptcha();
        }

        $sessionManager->accessPortalMainPage();
        expect($sessionManager->hasLogin())->toBeTrue();

        $sessionManager->logout();
        expect($sessionManager->hasLogin())->toBeFalse();
    });
});
