<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Internal\HtmlForm;
use SatScrapersAuth\Portals\Contracts\SatPortal;
use SatScrapersAuth\Portals\Internal\AbstractSatPortal;

/**
 * Declaración Provisional o Definitiva de Impuestos Federales
 */
final class SatPstcdAndPisrPortal extends AbstractSatPortal implements SatPortal
{
    /** @var string The main portal */
    final public const MAIN_PORTAL = 'https://pstcdypisr.clouda.sat.gob.mx/';

    final public const CONSULTA_DECLARACION = 'https://pstcdypisr.clouda.sat.gob.mx/Consulta/Consulta?tipoDocumento=1';

    /** @var string The page to log out */
    final public const AUTH_LOGOUT = 'https://pstcdypisr.clouda.sat.gob.mx/Home/LogOut';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://loginda.siat.sat.gob.mx/nidp/app/login?id=fiel&sid=0&option=credential&sid=0';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://loginda.siat.sat.gob.mx/nidp/wsfed/ep?id=ciec&sid=0&option=credential&sid=0';

    public function hasLogin(): bool
    {
        $httpGateway = $this->getHttpGateway();
        // If cookie is empty, then it will not be able to detect a session anyway
        if ($httpGateway->isCookieJarEmpty()) {
            return false;
        }

        try {
            $html = $this->getPortalMainPage();
            if (! $this->checkIsAuthenticated($html)) {
                return false;
            }
        } catch (SatHttpGatewayException) {
            // If http error, consider without session
            return false;
        }

        return true;
    }

    public function checkIsAuthenticated(string $html): bool
    {
        return is_numeric(strpos($html, 'RFC:')) && is_numeric(strpos($html, $this->getSessionManager()->getRfc()));
    }

    public function getLoginFielPage(): string
    {
        $httpGateway = $this->getHttpGateway();
        // Contact homepage, it will try to redirect to access by password
        $this->getPortalMainPage();
        // Previous page will try to redirect to access by password using post
        $httpGateway->postCiecLoginData(self::AUTH_LOGIN_CIEC, []);
        // Change to fiel login page and get challenge
        $html = $httpGateway->getAuthLoginPage(self::AUTH_LOGIN_FIEL, self::AUTH_LOGIN_CIEC);

        return $html;
    }

    public function getLoginCiecPage(): string
    {
        return $this->getHttpGateway()->getAuthLoginPage(self::AUTH_LOGIN_CIEC);
    }

    public function postLoginFiel(array $inputs): void
    {
        $httpGateway = $this->getHttpGateway();
        $html = $httpGateway->postFielLoginData(self::AUTH_LOGIN_FIEL, $inputs);
        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues(); // wa, weesult, wctx
        $httpGateway->postGeneral('post to portal main page', self::MAIN_PORTAL, $inputs);
    }

    public function postLoginCiec(array $inputs): string
    {
        return $this->getHttpGateway()->postCiecLoginData(self::AUTH_LOGIN_CIEC, $inputs);
    }

    public function getPortalMainPage(): string
    {
        return $this->getHttpGateway()->get('get portal main page', self::MAIN_PORTAL);
    }

    public function accessPortalMainPage(): string
    {
        $html = $this->getPortalMainPage();
        $inputs = (new HtmlForm($html, 'form'))->getFormValues();
        if (count($inputs) > 0) {
            $html = $this->getHttpGateway()->postGeneral('post to portal main page', self::MAIN_PORTAL, $inputs);
        }

        return $html;
    }

    public function logout(): void
    {
        $this->getHttpGateway()->getLogout(self::AUTH_LOGOUT, self::MAIN_PORTAL);
    }
}
