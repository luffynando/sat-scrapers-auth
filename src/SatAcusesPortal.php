<?php

declare(strict_types=1);

namespace SatScrapersAuth;

use Exception;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Portals\AbstractSatPortal;
use SatScrapersAuth\Portals\SatPortal;

final class SatAcusesPortal extends AbstractSatPortal implements SatPortal
{
    /** @var string The main page to access Acuses Declaraciones Portal */
    final public const MAIN_PORTAL = 'https://www.acuse.sat.gob.mx/REIMPRESIONINTERNET/REIMdatos.asp';

    /** @var string The page to log out */
    final public const AUTH_LOGOUT = 'https://www.acuse.sat.gob.mx/REIMPRESIONINTERNET/REIMlogout.asp';

    /** @var string The authorization page to verify pwd using CIEC */
    final public const AUTH_VERIF_PWD = 'https://www.acuse.sat.gob.mx/_mem_bin/verifpwd.asp';

    /** @var string The authorization page to log in using FIEL */
    final public const AUTH_LOGIN_FIEL = 'https://www.acuse.sat.gob.mx/_mem_bin/formsloginFEA.asp?/REIMPRESIONINTERNET/REIMDEFAULT.HTM';

    /** @var string The authorization page to log in using CIEC */
    final public const AUTH_LOGIN_CIEC = 'https://www.acuse.sat.gob.mx/_mem_bin/FormsLogin.asp?/ReimpresionInternet/REIMDefault.htm';

    public function __construct(private readonly string $rfc) {}

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
            file_put_contents('output.html', $html);
        } catch (SatHttpGatewayException) {
            // If http error, consider without session
            return false;
        }

        return true;
    }

    public function checkIsAuthenticated(string $html): bool
    {
        return is_numeric(strpos($html, 'Usuario Autenticado:')) && is_numeric(strpos($html, $this->rfc));
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
        return $this->getHttpGateway()->postCiecLoginData(self::AUTH_VERIF_PWD, [
            ...$inputs,
            'bUsername' => '',
            'URL' => '/REIMPRESIONINTERNET/REIMDEFAULT.HTM',
        ]);
    }

    /**
     * @param array<string, string> $inputs
     *
     * @throws Exception
     */
    public function postLoginFiel(array $inputs): void
    {
        throw new Exception('Not implemented by restrictions on SAT');
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

    public function accessPortalMainPage(): string
    {
        return $this->getPortalMainPage();
    }

    public function logout(): void
    {
        $this->getHttpGateway()->getLogout(self::AUTH_LOGOUT, self::MAIN_PORTAL);
    }
}
