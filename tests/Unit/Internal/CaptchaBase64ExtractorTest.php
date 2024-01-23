<?php

declare(strict_types=1);

use SatScrapersAuth\Exceptions\RuntimeException;
use SatScrapersAuth\Internal\CaptchaBase64Extractor;

function getBase64Image(Tests\TestCase $testCase): string
{
    return base64_encode($testCase->fileContentPath('sample-captcha.png'));
}

/**
 * @var Tests\TestCase $this
 */
describe('CaptchaBase64Extractor', function () {
    test('retrieve when default element exists', function () {
        $base64Image = getBase64Image($this);
        $html = <<< HTML
            <div id="divCaptcha">
                <img src="data:image/jpeg;base64,$base64Image">
            </div>
            HTML;

        $captchaExtractor = new CaptchaBase64Extractor();
        expect($captchaExtractor->retrieveCaptchaImage($html)->asBase64())->toBe($base64Image);
    });

    test('retrieve when default element not exists', function () {
        $base64Image = getBase64Image($this);
        $html = <<< HTML
            <div>
                <img src="data:image/jpeg;base64,$base64Image">
            </div>
            HTML;

        $captchaExtractor = new CaptchaBase64Extractor();
        expect(fn () => $captchaExtractor->retrieveCaptchaImage($html)->asBase64())
            ->toThrow(RuntimeException::class, "Unable to find image using filter '#divCaptcha > img'");
    });

    test('retrieve by selector when element exists', function () {
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
