<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use LogicException;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use PhpCfdi\ImageCaptchaResolver\Resolvers\AntiCaptchaResolver;
use PhpCfdi\ImageCaptchaResolver\Resolvers\CaptchaLocalResolver;
use PhpCfdi\ImageCaptchaResolver\Resolvers\ConsoleResolver;
use RuntimeException;
use SatScrapersAuth\SatHttpGateway;
use SatScrapersAuth\Sessions\Ciec\CiecSessionData;
use SatScrapersAuth\Sessions\Ciec\CiecSessionManager;
use SatScrapersAuth\Sessions\Fiel\FielSessionData;
use SatScrapersAuth\Sessions\Fiel\FielSessionManager;
use SatScrapersAuth\Sessions\SessionManager;

final class Factory
{
    public function createCaptchaResolver(): CaptchaResolverInterface
    {
        $resolver = $this->env('CAPTCHA_RESOLVER');

        if ($resolver === 'console') {
            return new ConsoleResolver();
        }

        if ($resolver === 'local') {
            return CaptchaLocalResolver::create(
                $this->env('CAPTCHA_LOCAL_URL'),
                (int) $this->env('CAPTCHA_LOCAL_INITIAL_WAIT') ?: CaptchaLocalResolver::DEFAULT_INITIAL_WAIT,
                (int) $this->env('CAPTCHA_LOCAL_TIMEOUT') ?: CaptchaLocalResolver::DEFAULT_TIMEOUT,
                (int) $this->env('CAPTCHA_LOCAL_WAIT') ?: CaptchaLocalResolver::DEFAULT_WAIT,
            );
        }

        if ($resolver === 'anticaptcha') {
            return AntiCaptchaResolver::create(
                $this->env('ANTICAPTCHA_CLIENT_KEY'),
                (int) $this->env('ANTICAPTCHA_INITIAL_WAIT') ?: AntiCaptchaResolver::DEFAULT_INITIAL_WAIT,
                (int) $this->env('ANTICAPTCHA_TIMEOUT') ?: AntiCaptchaResolver::DEFAULT_TIMEOUT,
                (int) $this->env('ANTICAPTCHA_WAIT') ?: AntiCaptchaResolver::DEFAULT_WAIT,
            );
        }

        throw new RuntimeException('Unable to create resolver');
    }

    public function createSessionManager(): SessionManager
    {
        $satAuthMode = $this->env('SAT_AUTH_MODE');
        if ('FIEL' === $satAuthMode) {
            return $this->createFielSessionManager();
        }

        if ('CIEC' === $satAuthMode) {
            return $this->createCiecSessionManager();
        }

        throw new LogicException("Unable to create a session manager using SAT_AUTHMODE='$satAuthMode'");
    }

    public function createFielSessionManager(): FielSessionManager
    {
        return new FielSessionManager($this->createFielSessionData());
    }

    public function createFielSessionData(): FielSessionData
    {
        $fiel = Credential::openFiles(
            $this->path($this->env('SAT_FIEL_CER')),
            $this->path($this->env('SAT_FIEL_KEY')),
            file_get_contents($this->path($this->env('SAT_FIEL_PWD'))) ?: '',
        );
        if (! $fiel->isFiel()) {
            throw new LogicException('The CERTIFICATE is not a FIEL');
        }

        if (! $fiel->certificate()->validOn()) {
            throw new LogicException('The CERTIFICATE is not valid');
        }

        return new FielSessionData($fiel);
    }

    public function createCiecSessionManager(): CiecSessionManager
    {
        return new CiecSessionManager($this->createCiecSessionData());
    }

    public function createCiecSessionData(): CiecSessionData
    {
        $rfc = $this->env('SAT_AUTH_RFC');
        if ('' === $rfc) {
            throw new RuntimeException('The is no environment variable SAT_AUTH_RFC');
        }

        $ciec = $this->env('SAT_AUTH_CIEC');
        if ('' === $ciec) {
            throw new RuntimeException('The is no environment variable SAT_AUTH_CIEC');
        }

        $resolver = $this->createCaptchaResolver();

        return new CiecSessionData($rfc, $ciec, $resolver);
    }

    public function createSatHttpGateway(SessionManager $sessionManager, string $portalName): SatHttpGateway
    {
        $suffix = basename(str_replace(['\\', 'sessionmanager'], ['/', ''], strtolower($sessionManager::class)));
        $rfc = strtolower($sessionManager->getRfc());
        $cookieFile = sprintf('%s/%s/cookie-%s-%s-%s.json', __DIR__, '../../build', $rfc, $suffix, $portalName);
        $cookieJar = new FileCookieJar($cookieFile, true);
        return new SatHttpGateway($this->createGuzzleClient(), $cookieJar);
    }

    public function createGuzzleClient(): Client
    {
        $container = new HttpLogger($this->path($this->env('SAT_HTTPDUMP_FOLDER')));
        $stack = HandlerStack::create();
        $stack->push(Middleware::history($container));
        return new Client([
            'handler' => $stack,
            'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
        ]);
    }

    public function env(string $variable): string
    {
        return (string) ($_SERVER[$variable] ?? '');
    }

    public function path(string $path): string
    {
        // if is not empty and is not an absolute path, prepend project dir
        if ('' !== $path && ! in_array(substr($path, 0, 1), ['/', '\\'], true)) {
            return dirname(__DIR__, 2) . '/' . $path;
        }
        return $path;
    }
}
