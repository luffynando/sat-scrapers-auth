<?php

declare(strict_types=1);

use SatScrapersAuth\Internal\MetaRefreshInspector;

describe('MetaRefreshInspector', function (): void {
    test('obtain meta refresh absolute', function (): void {
        $html = <<< HTML
            <html>
                <head>
                    <meta http-equiv="refresh" content="0; url=https://example.com/?foo=bar">
                </head>
            </html>
            HTML;

        $inspector = new MetaRefreshInspector();
        $url = $inspector->obtainUrl($html, '');
        expect($url)->toBe('https://example.com/?foo=bar');
    });

    test('obtain meta refresh relative to path', function (): void {
        $html = <<< HTML
            <html>
                <head>
                    <meta http-equiv="refresh" content="0; url=redirect.php?destination=1">
                </head>
            </html>
            HTML;

        $inspector = new MetaRefreshInspector();
        $url = $inspector->obtainUrl($html, 'https://example.com/foo/bar/');
        expect($url)->toBe('https://example.com/foo/bar/redirect.php?destination=1');
    });

    test('obtain meta refresh relative to server', function (): void {
        $html = <<< HTML
            <html>
                <head>
                    <meta http-equiv="refresh" content="0; url=/redirect.php?destination=1">
                </head>
            </html>
            HTML;

        $inspector = new MetaRefreshInspector();
        $url = $inspector->obtainUrl($html, 'https://example.com/foo/bar/');
        expect($url)->toBe('https://example.com/redirect.php?destination=1');
    });

    test('obtain meta refresh without element', function (): void {
        $html = <<< HTML
            <html>
                <body>
                    Foo Bar
                </body>
            </html>
            HTML;

        $inspector = new MetaRefreshInspector();
        expect($inspector->obtainUrl($html, ''))->toBeEmpty();
    });
});
