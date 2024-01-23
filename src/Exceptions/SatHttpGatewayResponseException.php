<?php

declare(strict_types=1);

namespace SatScrapersAuth\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * This exception is thrown by SatHttpGateway when a ResponseInterface exists but an error was found
 *
 * @see GuzzleException
 */
final class SatHttpGatewayResponseException extends SatHttpGatewayException implements SatException
{
    /**
     * SatHttpGatewayResponseException constructor.
     *
     * @param array<string, mixed> $requestHeaders
     * @param array<string, mixed> $requestData
     */
    protected function __construct(private readonly ResponseInterface $response, string $message, string $httpMethod, string $url, array $requestHeaders, array $requestData)
    {
        parent::__construct($message, $httpMethod, $url, $requestHeaders, $requestData);
    }

    /**
     * Method factory
     *
     * @param array<string, mixed> $requestHeaders
     * @param array<string, mixed> $requestData
     */
    public static function unexpectedEmptyResponse(string $when, ResponseInterface $response, string $httpMethod, string $url, array $requestHeaders, array $requestData = []): self
    {
        return new self($response, "Unexpected empty content when $when", $httpMethod, $url, $requestHeaders, $requestData);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
