<?php

declare(strict_types=1);

namespace SatScrapersAuth\Portals;

use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Internal\HtmlForm;
use SatScrapersAuth\Portals\Contracts\SatPortal;
use SatScrapersAuth\Portals\Internal\AbstractSatPortal;

final class SatAgrPortal extends AbstractSatPortal implements SatPortal
{
    /** @var string The main page to access AGR Portal */
    final public const MAIN_PORTAL = 'https://agr.siat.sat.gob.mx/app/seg/SessionBroker?url=/PTSC/notificacionElectronica/faces/Pages/principalNotificaciones.jsf&parametro=c&idSessionBit=null';

    /** @var string The url to consulta notificaciones */
    final public const CONSULTA_NOTIFICACIONES = 'https://agr.siat.sat.gob.mx/PTSC/notificacionElectronica/faces/Pages/principalNotificaciones.jsf';

    /** @var string The page before sso */
    final public const AUTH_AFTER_SEND_FIEL = 'https://agr.siat.sat.gob.mx/app/seg/cont/accesoC?url=/PTSC/notificacionElectronica/faces/Pages/principalNotificaciones.jsf&idSessionBit=null';

    /** @var string The sso login */
    final public const AUTH_SSO = 'https://login.siat.sat.gob.mx/nidp/saml2/sso';

    /** @var string The post login */
    public const AUTH_LOGIN_POST = 'https://agr.siat.sat.gob.mx/cloudc/saml2/sp/acs/post';

    /** @var string The page to log out */
    final public const AUTH_LOGOUT = 'https://login.siat.sat.gob.mx/nidp/app/plogout';

    /** @var string The post to log out */
    final public const AGR_LOGOUT_POST = 'https://agr.siat.sat.gob.mx/cloudc/saml2slo/endpoint';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://login.siat.sat.gob.mx/nidp/idff/sso?id=fiel&sid=0&option=credential&sid=0';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://login.siat.sat.gob.mx/nidp/idff/sso?id=ptsc-ciec&sid=0&option=credential&sid=0';

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
        return is_numeric(strpos($html, 'Destinatario: ' . $this->getSessionManager()->getRfc()));
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
        $html = $httpGateway->postFielLoginData(self::AUTH_LOGIN_FIEL, $inputs);

        $html = $this->getAfterFielLoginPage();
        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $html = $this->postSSO($inputs);

        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $this->postLoginPost($inputs);
    }

    /**
     * @param array<string, string> $formData
     *
     * @throws SatHttpGatewayException
     */
    private function postSSO(array $formData): string
    {
        return $this->getHttpGateway()->postGeneral('post to sso page', self::AUTH_SSO, $formData);
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
     * @param array<string, string> $formData
     *
     * @throws SatHttpGatewayException
     */
    private function postLogoutPost(array $formData): string
    {
        return $this->getHttpGateway()->postGeneral('post to logout post page', self::AGR_LOGOUT_POST, $formData);
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
     * Retrieve the page for sso
     *
     * @throws SatHttpGatewayException
     */
    public function getAfterFielLoginPage(): string
    {
        return $this->getHttpGateway()->get('get sso login page', self::AUTH_AFTER_SEND_FIEL);
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
        $html = $this->getHttpGateway()->getLogout(self::AUTH_LOGOUT, self::MAIN_PORTAL);

        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $html = $this->postLogoutPost($inputs);

        $form = new HtmlForm($html, 'form');
        $inputs = $form->getFormValues();
        $this->postSSO($inputs);
    }
}
