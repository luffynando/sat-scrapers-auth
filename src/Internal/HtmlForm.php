<?php

declare(strict_types=1);

namespace  SatScrapersAuth\Internal;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Utility class to extract data from an HTML form.
 *
 * @internal
 */
final class HtmlForm
{
    private string $parentElement;

    private \Symfony\Component\DomCrawler\Crawler $crawler;

    /** @var string[] */
    private array $elementNameExcludePatterns;

    /**
     * HtmlForm constructor.
     *
     * @param array<string> $elementNameExcludePatterns
     */
    public function __construct(string $htmlSource, string $parentElement, array $elementNameExcludePatterns = [])
    {
        $this->setHtmlSource($htmlSource);
        $this->setParentElement($parentElement);
        $this->setElementNameExcludePatterns(...$elementNameExcludePatterns);
    }

    public function setHtmlSource(string $htmlSource): void
    {
        $this->crawler = new Crawler($htmlSource);
    }

    public function setParentElement(string $parentElement): void
    {
        $this->parentElement = $parentElement;
    }

    public function setElementNameExcludePatterns(string ...$elementNameExcludePatterns): void
    {
        $this->elementNameExcludePatterns = $elementNameExcludePatterns;
    }

    /**
     * Return all inputs (without submit, reset and button) and selects,
     * following the element name exclusion patterns
     *
     * @return array<string, string>
     */
    public function getFormValues(): array
    {
        return array_merge($this->readInputValues(), $this->readSelectValues());
    }

    /**
     * Retrieve an array with key as input element name and value as value
     * It excludes the inputs which name match with an exclusion pattern
     * This excludes all inputs with types submit, reset and button
     * In the case of input type radio it only includes it when is checked
     *
     * @return array<string, string>
     */
    public function readInputValues(): array
    {
        return $this->readFormElementsValues('input', ['submit', 'reset', 'button']);
    }

    /**
     * Retrieve an array with key as select element name and value as first option selected
     *
     * @return array<string, string>
     */
    public function readSelectValues(): array
    {
        $data = [];
        /** @var DOMElement[] $elements */
        $elements = $this->filterCrawlerElements("{$this->parentElement} select");
        foreach ($elements as $element) {
            $name = $element->getAttribute('name');
            if ($this->elementNameIsExcluded($name)) {
                continue;
            }

            $value = '';
            /** @var DOMElement $option */
            foreach ($element->getElementsByTagName('option') as $option) {
                if ($option->getAttribute('selected') !== '' && $option->getAttribute('selected') !== '0') {
                    $value = $option->getAttribute('value');
                    break;
                }
            }

            $data[$name] = $value;
        }

        return $data;
    }

    /**
     * This method is compatible with elements that have a name and value
     * It excludes the selects which name match with an exclusion pattern
     * If type is defined is excluded if was set as an excluded type
     * If type is radio is included only if checked attribute is true-ish
     *
     * @param array<string> $excludeTypes
     * @return array<string, string>
     */
    public function readFormElementsValues(string $element, array $excludeTypes = []): array
    {
        $excludeTypes = array_map('strtolower', $excludeTypes);
        $data = [];
        /** @var DOMElement[] $elements */
        $elements = $this->filterCrawlerElements("{$this->parentElement} $element");
        foreach ($elements as $element) {
            $name = $element->getAttribute('name');
            if ($this->elementNameIsExcluded($name)) {
                continue;
            }

            $type = strtolower($element->getAttribute('type'));
            if (in_array($type, $excludeTypes, true)) {
                continue;
            }

            if (($type === 'radio' || $type === 'checkbox') && ! $element->getAttribute('checked')) {
                continue;
            }

            $data[$name] = $element->getAttribute('value');
        }

        return $data;
    }

    public function elementNameIsExcluded(string $name): bool
    {
        foreach ($this->elementNameExcludePatterns as $excludePattern) {
            if (preg_match($excludePattern, $name) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method is made to ignore RuntimeException if the CssSelector Component is not available.
     *
     * @return Crawler|DOMElement[]
     */
    private function filterCrawlerElements(string $filter): mixed
    {
        try {
            $elements = $this->crawler->filter($filter);
        } catch (Throwable) {
            $elements = [];
        }

        /** @var Crawler|DOMElement[] $elements */
        return $elements;
    }
}
