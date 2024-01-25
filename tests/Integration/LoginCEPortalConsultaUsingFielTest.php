<?php

declare(strict_types=1);

use SatScrapersAuth\SatCEPortalConsulta;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in CE Portal Consulta', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        $portal = new SatCEPortalConsulta();
        try {
            $sessionManager = $factory->createFielSessionManager($portal);
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $sessionManager->setHttpGateway($factory->createSatHttpGateway($sessionManager, 'ceportalconsulta'));
        if (!$sessionManager->hasLogin()) {
            $sessionManager->login();
        }

        $sessionManager->accessPortalMainPage();
        expect($sessionManager->hasLogin())->toBeTrue();

        $sessionManager->logout();
        expect($sessionManager->hasLogin())->toBeFalse();
    });
});
