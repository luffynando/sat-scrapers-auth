<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Internal\HtmlForm;

final class SatRfcAmpCPortal extends AbstractSatPortal implements SatPortal
{
    /** @var string The main page to access CFDI Portal */
    final public const MAIN_PORTAL = 'https://rfcampc.siat.sat.gob.mx/app/seg/SessionBroker?url=/PTSC/IdcSiat/autc/ReimpresionTramite/ConsultaTramite.jsf&parametro=c&idSessionBit=null';

    /** @var string The url to consulta tramite */
    final public const CONSULTA_TRAMITE = 'https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/autc/ReimpresionTramite/ConsultaTramite.jsf';

    /** @var string The url to download CSF */
    final public const DOWNLOAD_CONSTANCIA = 'https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/IdcGeneraConstancia.jsf';

    /** @var string The sso login */
    final public const AUTH_LOGIN_SSO = 'https://login.siat.sat.gob.mx/nidp/saml2/sso';

    /** @var string The post login */
    public const AUTH_LOGIN_POST = 'https://rfcampc.siat.sat.gob.mx/saml2/sp/acs/post';

    /** @var string The page to log out */
    final public const AUTH_LOGOUT = 'https://login.siat.sat.gob.mx/nidp/app/plogout';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://login.siat.sat.gob.mx/nidp/idff/sso?id=fiel&sid=0&option=credential&sid=0';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://login.siat.sat.gob.mx/nidp/idff/sso?id=mat-ptsc-totp&sid=0&option=credential&sid=0';

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
        return is_numeric(strpos($html, 'Reimpresión de Acuses'));
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
    public function postLoginFiel(array $inputs): void
    {
        $httpGateway = $this->getHttpGateway();
        $httpGateway->postFielLoginData(self::AUTH_LOGIN_FIEL, $inputs);

        $html = $this->getPortalMainPage();
        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $html = $this->postSSOLogin($inputs);

        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $this->postLoginPost($inputs);
    }

    /**
     * @param array<string, string> $formData
     *
     * @throws SatHttpGatewayException
     */
    private function postSSOLogin(array $formData): string
    {
        return $this->getHttpGateway()->postGeneral('post to sso login page', self::AUTH_LOGIN_SSO, $formData);
    }

    /**
     * @param array<string, string> $formData
     *
     * @throws SatHttpGatewayException
     */
    private function postLoginPost(array $formData): string
    {
        return $this->getHttpGateway()->postGeneral('post to login post page', self::AUTH_LOGIN_POST, $formData);
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
