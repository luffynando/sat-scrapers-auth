<?php

declare(strict_types=1);

namespace SatScrapersAuth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SatScrapersAuth\Exceptions\SatHttpGatewayClientException;
use SatScrapersAuth\Exceptions\SatHttpGatewayException;
use SatScrapersAuth\Exceptions\SatHttpGatewayResponseException;
use SatScrapersAuth\Internal\Headers;
use SatScrapersAuth\Internal\MetaRefreshInspector;

final class SatHttpGateway
{
    private readonly \GuzzleHttp\ClientInterface $client;

    private readonly \GuzzleHttp\Cookie\CookieJarInterface $cookieJar;

    private ?string $effectiveUri = null;

    private readonly \SatScrapersAuth\Internal\Headers $headers;

    public function __construct(?ClientInterface $client = null, ?CookieJarInterface $cookieJar = null, ?string $userAgent = null)
    {
        // Create a new client (if not set) with the given cookie (if set)
        $client ??= new Client([RequestOptions::COOKIES => $cookieJar ?? new CookieJar()]);

        // If the cookieJar was set on the client but not in the configuration
        if (!$cookieJar instanceof \GuzzleHttp\Cookie\CookieJarInterface) {
            /**
             * @noinspection PhpDeprecationInspection
             * @var mixed $cookieJar
             */
            $cookieJar = $client->getConfig(RequestOptions::COOKIES);
            if (! $cookieJar instanceof CookieJarInterface) {
                $cookieJar = new CookieJar();
            }
        }

        $this->client = $client;
        $this->cookieJar = $cookieJar;
        $this->headers = new Headers($userAgent);
    }

    /**
     * Obtain Login Page
     *
     *
     * @throws SatHttpGatewayException
     */
    public function getAuthLoginPage(string $url, string $referer = ''): string
    {
        return $this->get('get login page', $url, $referer);
    }

    /**
     * @param array<string, string> $formData
     *
     * @throws SatHttpGatewayException
     */
    public function postGeneral(string $reason, string $url, array $formData): string
    {
        return $this->post($reason, $url, $this->headers->post('', ''), $formData);
    }

    /**
     * Helper to login by CIEC
     *
     * @param array<string, string> $formParams
     *
     * @throws SatHttpGatewayException
     */
    public function postCiecLoginData(string $loginUrl, array $formParams): string
    {
        $headers = $this->headers->post($this->urlHost($loginUrl), $loginUrl);

        return $this->post('post ciec login data', $loginUrl, $headers, $formParams);
    }

    /**
     * Helper to login by FIEL
     *
     * @param array<string, string> $formParams
     *
     * @throws SatHttpGatewayException
     */
    public function postFielLoginData(string $loginUrl, array $formParams): string
    {
        $headers = $this->headers->post($this->urlHost($loginUrl), $loginUrl);

        return $this->post('post fiel login data', $loginUrl, $headers, $formParams);
    }

    public function clearCookieJar(): void
    {
        $this->cookieJar->clear();
    }

    public function isCookieJarEmpty(): bool
    {
        return $this->cookieJar->toArray() === [];
    }

    /**
     * Helper to make a GET request
     *
     * @throws SatHttpGatewayClientException
     * @throws SatHttpGatewayResponseException
     */
    public function get(string $reason, string $url, string $referer = ''): string
    {
        $options = [
            RequestOptions::HEADERS => $this->headers->get($referer),
        ];

        return $this->request('GET', $url, $options, $reason);
    }

    /**
     * Helper to make a POST request
     *
     * @param array<string, mixed> $headers
     * @param array<string, string> $data
     *
     * @throws SatHttpGatewayException
     */
    public function post(string $reason, string $url, array $headers, array $data): string
    {
        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::FORM_PARAMS => $data,
        ];

        return $this->request('POST', $url, $options, $reason);
    }

    /**
     * Helper to make a AJAX post request
     *
     * @param array<string, string> $formParams
     *
     * @throws SatHttpGatewayException
     */
    public function postAjax(string $urlHost, string $urlReferer, array $formParams): string
    {
        $headers = $this->headers->postAjax($this->urlHost($urlHost), $urlReferer);

        return $this->post('post ajax', $urlReferer, $headers, $formParams);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws SatHttpGatewayClientException
     * @throws SatHttpGatewayResponseException
     */
    private function request(string $method, string $uri, array $options, string $reason): string
    {
        $options = [
            RequestOptions::COOKIES => $this->cookieJar,
            RequestOptions::ALLOW_REDIRECTS => ['trackredirects' => true],
        ] + $options;
        $this->effectiveUri = $uri;
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (GuzzleException $exception) {
            if ($exception instanceof RequestException && $exception->getResponse() instanceof \Psr\Http\Message\ResponseInterface) {
                $this->setEffectiveUriFromResponse($exception->getResponse(), $uri);
            } else {
                $this->effectiveUri = $uri;
            }

            /** @var array<string, mixed> $requestHeaders */
            $requestHeaders = $options[RequestOptions::HEADERS];
            /** @var array<string, mixed> $requestData */
            $requestData = $options[RequestOptions::FORM_PARAMS] ?? [];
            throw SatHttpGatewayClientException::clientException(
                $reason,
                $method,
                $uri,
                $requestHeaders,
                $requestData,
                $exception,
            );
        }

        $this->setEffectiveUriFromResponse($response, $uri);
        $contents = (string) $response->getBody();
        if ($contents === '') {
            /** @var array<string, mixed> $requestHeaders */
            $requestHeaders = $options[RequestOptions::HEADERS];
            /** @var array<string, mixed> $requestData */
            $requestData = $options[RequestOptions::FORM_PARAMS] ?? [];
            throw SatHttpGatewayResponseException::unexpectedEmptyResponse(
                $reason,
                $response,
                $method,
                $uri,
                $requestHeaders,
                $requestData,
            );
        }

        return $contents;
    }

    public function getLogout(string $destination, string $referer): string
    {
        $metaRefresh = new MetaRefreshInspector();
        do {
            $html = $this->getLogoutWithoutException($destination, $referer);
            $referer = $this->effectiveUri ?? '';
            $destination = $metaRefresh->obtainUrl($html, $referer);
        } while ('' !== $destination && $destination !== $referer);

        $this->clearCookieJar();

        return $html;
    }

    private function getLogoutWithoutException(string $destination, string $referer): string
    {
        try {
            return $this->get('logout', $destination, $referer);
        } catch (SatHttpGatewayException) {
            return '';
        }
    }

    private function setEffectiveUriFromResponse(ResponseInterface $response, string $previousUri): void
    {
        $history = $response->getHeader('X-Guzzle-Redirect-History');
        $effectiveUri = (string) end($history);
        $this->effectiveUri = $effectiveUri ?: $previousUri;
    }

    private function urlHost(string $url): string
    {
        return (string) parse_url($url, PHP_URL_HOST);
    }
}
