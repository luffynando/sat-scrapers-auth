<?php

declare(strict_types=1);

namespace SatScrapersAuth\Internal;

/**
 * Helper class to return the correct headers for different requests: post and ajax
 *
 * @internal
 */
final class Headers
{
    final public const CHROME_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    final public const FIREFOX_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0';

    private readonly string $userAgent;

    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? self::FIREFOX_USER_AGENT;
    }

    /**
     * Return the headers to use on general get
     *
     * @return array<string, string>
     */
    public function get(string $referer = ''): array
    {
        return array_filter([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Languaje' => 'es,en-US;q=0.9,en;q=0.8',
            'Connection' => 'keep-alive',
            'Referer' => $referer,
            'User-Agent' => $this->userAgent,
        ]);
    }

    /**
     * Return the headers to use on general form submit
     *
     * @return array<string, string>
     */
    public function post(string $host, string $referer): array
    {
        return array_merge($this->get($referer), array_filter([
            'Pragma' => 'no-cache',
            'Host' => $host,
            'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
        ]));
    }

    /**
     * Return the headers to use on ajax requests
     *
     * @return array<string, string>
     */
    public function postAjax(string $host, string $referer): array
    {
        return array_merge($this->post($host, $referer), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }
}
