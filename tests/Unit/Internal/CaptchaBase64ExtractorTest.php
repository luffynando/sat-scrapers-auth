<?php

declare(strict_types=1);

use SatScrapersAuth\Exceptions\RuntimeException;
use SatScrapersAuth\Internal\CaptchaBase64Extractor;

/** @param \Tests\TestCase $instance */
function getBase64Image($instance): string
{
    return base64_encode($instance->fileContentPath('sample-captcha.png'));
}

/** @var \Tests\TestCase $this */
describe('CaptchaBase64Extractor', function (): void {
    test('retrieve when default element exists', function (): void {
        $base64Image = getBase64Image($this);
        $html = <<< HTML
            <div id="divCaptcha">
                <img src="data:image/jpeg;base64,$base64Image">
            </div>
            HTML;

        $captchaExtractor = new CaptchaBase64Extractor();
        expect($captchaExtractor->retrieveCaptchaImage($html)->asBase64())->toBe($base64Image);
    });

    test('retrieve when default element not exists', function (): void {
        $base64Image = getBase64Image($this);
        $html = <<< HTML
            <div>
                <img src="data:image/jpeg;base64,$base64Image">
            </div>
            HTML;

        $captchaExtractor = new CaptchaBase64Extractor();
        expect(fn(): string => $captchaExtractor->retrieveCaptchaImage($html)->asBase64())
            ->toThrow(RuntimeException::class, "Unable to find image using filter '#divCaptcha > img'");
    });

    test('retrieve by selector when element exists', function (): void {
        $base64Image = getBase64Image($this);
        $html = <<< HTML
            <div id="captcha">
                <img src="data:image/jpeg;base64,$base64Image">
            </div>
            HTML;

        $captchaExtractor = new CaptchaBase64Extractor();
        expect($captchaExtractor->retrieveCaptchaImage($html, '#captcha > img')->asBase64())->toBe($base64Image);
    });
});
