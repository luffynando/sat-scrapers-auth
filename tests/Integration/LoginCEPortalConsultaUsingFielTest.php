<?php

declare(strict_types=1);

use SatScrapersAuth\Portals\SatCEPortalConsulta;

/** @var \Tests\Integration\IntegrationTestCase $this */
describe('Login in CE Portal Consulta', function (): void {
    test('login and logout', function (): void {
        /** @var \Tests\Integration\Factory $factory */
        $factory = $this->getFactory();
        try {
            $sessionManager = $factory->createFielSessionManager();
        } catch (Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }

        $portal = new SatCEPortalConsulta(
            $sessionManager,
            $factory->createSatHttpGateway($sessionManager, 'ceportalconsulta'),
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
