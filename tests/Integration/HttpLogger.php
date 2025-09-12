<?php

declare(strict_types=1);

namespace Tests\Integration;

use ArrayAccess;
use ArrayObject;
use DateTimeImmutable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends ArrayObject<int|string, mixed>
 */
final class HttpLogger extends ArrayObject
{
    public function __construct(private readonly string $destinationDir)
    {
        parent::__construct();
    }

    public function append($value): void
    {
        $this->write($value);
        parent::append($value);
    }

    /**
     * @param int|string|null $index
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function offsetSet($index, $entry): void
    {
        if (null === $index) {
            $this->write($entry);
        }
        parent::offsetSet($index, $entry);
    }

    public function write(mixed $entry): void
    {
        if (! is_array($entry) && ! $entry instanceof ArrayAccess) {
            return;
        }
        if ('' === $this->destinationDir) {
            return;
        }
        if (! file_exists($this->destinationDir)) {
            mkdir($this->destinationDir, 0755, true);
        }
        /** @var RequestInterface $request */
        $request = $entry['request'];
        /** @var ResponseInterface|null $response */
        $response = $entry['response'];
        $time = new DateTimeImmutable();
        $file = sprintf(
            '%s/%s.%06d-%s-%s.json',
            $this->destinationDir,
            $time->format('c'),
            $time->format('u'),
            strtolower($request->getMethod()),
            $this->slugify(sprintf('%s%s', $request->getUri()->getHost(), $request->getUri()->getPath())),
        );
        file_put_contents($file, $this->entryToJson($request, $response), FILE_APPEND);
    }

    public function slugify(string $text): string
    {
        // replace anything that is not (any kind of letter from any language or any digit) to dash
        $text = (string) preg_replace('~[^\pL\d]+~u', '-', $text);
        // transliterate to ascii
        $text = (string) iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove anything that is not (dash or word character)
        $text = (string) preg_replace('~[^\w\-]+~', '', $text);
        // replace consecutive dashes to only one
        $text = (string) preg_replace('~-+~', '-', $text);
        // final result with trimmed dash and lowercase
        return strtolower(trim($text, '-'));
    }

    public function entryToJson(RequestInterface $request, ?ResponseInterface $response): string
    {
        $json = json_encode(
            [
                'uri' => sprintf('%s: %s', $request->getMethod(), (string) $request->getUri()),
                'request' => [
                    'headers' => $request->getHeaders(),
                    'body' => $this->bodyToVars((string) $request->getBody()),
                ],
                'response' => ($response instanceof \Psr\Http\Message\ResponseInterface) ? [
                    'code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => (string) $response->getBody(),
                ] : '(no response)',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS,
        ) . PHP_EOL;
        $request->getBody()->rewind();
        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            $response->getBody()->rewind();
        }
        return $json;
    }

    /**
     * @return array<array<string>|string>
     */
    public function bodyToVars(string $body): array
    {
        $variables = [];
        parse_str($body, $variables);

        /** @var array<array<string>|string> $variables */
        return $variables;
    }
}
