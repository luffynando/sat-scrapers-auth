<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Internal\HtmlForm;
use SatScrapersAuth\Portals\Contracts\SatPortal;
use SatScrapersAuth\Portals\Internal\AbstractSatPortal;

final class SatCfdiPortal extends AbstractSatPortal implements SatPortal
{
    /** @var string The main page to access CFDI Portal */
    final public const PORTAL_CFDI = 'https://portalcfdi.facturaelectronica.sat.gob.mx/';

    /** @var string The page to search for received */
    final public const PORTAL_CFDI_CONSULTA_RECEPTOR = 'https://portalcfdi.facturaelectronica.sat.gob.mx/ConsultaReceptor.aspx';

    /** @var string The page to search for issued */
    final public const PORTAL_CFDI_CONSULTA_EMISOR = 'https://portalcfdi.facturaelectronica.sat.gob.mx/ConsultaEmisor.aspx';

    /** @var string The page to log out */
    final public const PORTAL_CFDI_LOGOUT = 'https://portalcfdi.facturaelectronica.sat.gob.mx/logout.aspx';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://cfdiau.sat.gob.mx/nidp/app/login?id=SATx509Custom&sid=0&option=credential&sid=0';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://cfdiau.sat.gob.mx/nidp/wsfed/ep?id=SATUPCFDiCon&sid=0&option=credential&sid=0';

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
        return is_numeric(strpos($html, 'RFC Autenticado: ' . $this->getSessionManager()->getRfc()));
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
     */
    public function postLoginFiel(array $inputs): void
    {
        $httpGateway = $this->getHttpGateway();
        $html = $httpGateway->postFielLoginData(self::AUTH_LOGIN_FIEL, $inputs);

        // Submit login credentials to portalcfdi
        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues(); // wa, weesult, wctx
        $httpGateway->postGeneral('post to portal main page', self::PORTAL_CFDI, $inputs);
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
     * Retrieve the main page of portal
     *
     *
     * @throws SatHttpGatewayException
     */
    public function getPortalMainPage(): string
    {
        return $this->getHttpGateway()->get('get portal main page', self::PORTAL_CFDI);
    }

    /**
     * Access to Portal Main Page
     */
    public function accessPortalMainPage(): string
    {
        $htmlMainPage = $this->getPortalMainPage();
        $inputs = (new HtmlForm($htmlMainPage, 'form'))->getFormValues();
        if ($inputs !== []) {
            return $this->getHttpGateway()->postGeneral('post to portal main page', self::PORTAL_CFDI, $inputs);
        }

        return $htmlMainPage;
    }

    public function logout(): void
    {
        $this->getHttpGateway()->getLogout(self::PORTAL_CFDI_LOGOUT, self::PORTAL_CFDI);
    }
}
