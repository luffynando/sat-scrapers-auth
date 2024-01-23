<?php

declare(strict_types=1);

namespace SatScrapersAuth\Internal;

use PhpCfdi\ImageCaptchaResolver\CaptchaImage;
use SatScrapersAuth\Exceptions\RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * This is a class to extract the captcha from the login web page.
 *
 * @internal
 */
final class CaptchaBase64Extractor
{
    final public const DEFAULT_SELECTOR = '#divCaptcha > img';

    public function retrieveCaptchaImage(string $htmlSource, string $selector = self::DEFAULT_SELECTOR): CaptchaImage
    {
        $images = (new Crawler($htmlSource))->filter($selector);
        if ($images->count() === 0) {
            throw RuntimeException::unableToFindCaptchaImage($selector);
        }

        $imageSource = (string) $images->attr('src');

        return CaptchaImage::newFromInlineHtml($imageSource);
    }
}
