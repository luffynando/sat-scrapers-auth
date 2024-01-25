<?php

declare(strict_types=1);

namespace SatScrapersAuth;

use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Internal\HtmlForm;
use SatScrapersAuth\Portals\AbstractSatPortal;
use SatScrapersAuth\Portals\SatPortal;

final class SatCEPortalConsulta extends AbstractSatPortal implements SatPortal
{
    /** @var string The main page to access aplicación contabilidad */
    final public const MAIN_PORTAL = 'https://ceportalconsultaextprod.clouda.sat.gob.mx';

    /** @var string The page to log out */
    final public const AUTH_LOGOUT = 'https://ceportalenvioprod.clouda.sat.gob.mx/Logout.aspx?wa=wsignoutcleanup1.0&wreply=https://login.siat.sat.gob.mx/nidp/wsfed/loreply';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://login.siat.sat.gob.mx/nidp/wsfed/ep?id=ptsc-fiel-ciec&sid=0&option=credential&sid=0';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://login.siat.sat.gob.mx/nidp/wsfed/ep?id=anualescontribmorales&sid=0&option=credential&sid=0';

    public function hasLogin(): bool
    {
        // If cookie is empty, then it will not be able to detect a session anyway
        if ($this->getHttpGateway()->isCookieJarEmpty()) {
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
        return is_numeric(strpos($html, 'Consulta Acuses'));
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

    /**
     * @param array<string, string> $inputs
     *
     * @throws SatHttpGatewayException
     */
    public function postLoginCiec(array $inputs): string
    {
        return $this->getHttpGateway()->postCiecLoginData(self::AUTH_LOGIN_CIEC, $inputs);
    }

    /**
     * @param array<string, string> $inputs
     *
     * @throws SatHttpGatewayException
     */
    public function postLoginFiel(array $inputs): void
    {
        $httpGateway = $this->getHttpGateway();
        $httpGateway->postFielLoginData(self::AUTH_LOGIN_FIEL, $inputs);

        // Submit login credentials to portalcfdi
        $html = $this->getPortalMainPage();
        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues(); // wa, weesult, wctx
        $httpGateway->postGeneral('post to portal main page', self::MAIN_PORTAL, $inputs);
    }

    /**
     * Retrieve the main page of portal
     *
     *
     * @throws SatHttpGatewayException
     */
    public function getPortalMainPage(): string
    {
        return $this->getHttpGateway()->get('get portal main page', self::MAIN_PORTAL);
    }

    /**
     * Access to Portal Main Page
     */
    public function accessPortalMainPage(): string
    {
        return $this->getPortalMainPage();
    }

    public function logout(): void
    {
        $this->getHttpGateway()->getLogout(self::AUTH_LOGOUT, self::MAIN_PORTAL);
    }
}
