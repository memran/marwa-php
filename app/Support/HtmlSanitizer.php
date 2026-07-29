<?php

declare(strict_types=1);

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSanitizer
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'a',
        'b',
        'blockquote',
        'br',
        'code',
        'div',
        'em',
        'h2',
        'h3',
        'i',
        'li',
        'ol',
        'p',
        'pre',
        's',
        'strong',
        'strike',
        'u',
        'ul',
    ];

    /**
     * Elements whose contents must not survive sanitization.
     *
     * @var list<string>
     */
    private const DROP_WITH_CONTENT = [
        'base',
        'button',
        'embed',
        'form',
        'iframe',
        'input',
        'link',
        'math',
        'meta',
        'object',
        'option',
        'script',
        'select',
        'style',
        'svg',
        'textarea',
    ];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="marwa-rich-text-root">'
            . $html
            . '</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return '';
        }

        $root = $document->getElementById('marwa-rich-text-root');
        if (!$root instanceof DOMElement) {
            return '';
        }

        $this->sanitizeChildren($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    public function toPlainText(?string $html): string
    {
        $text = html_entity_decode(
            strip_tags($this->sanitize($html)),
            ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMComment) {
                $parent->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);
                continue;
            }

            $this->sanitizeChildren($child);

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->unwrap($child);
                continue;
            }

            $this->sanitizeAttributes($child, $tag);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $attributeNames = [];
        foreach ($element->attributes as $attribute) {
            $attributeNames[] = $attribute->name;
        }

        foreach ($attributeNames as $attributeName) {
            if ($tag !== 'a' || !in_array($attributeName, ['href', 'rel', 'target', 'title'], true)) {
                $element->removeAttribute($attributeName);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        $href = trim($element->getAttribute('href'));
        if (!$this->isSafeUrl($href)) {
            $element->removeAttribute('href');
        }

        if ($element->getAttribute('target') !== '_blank') {
            $element->removeAttribute('target');
            $element->removeAttribute('rel');
            return;
        }

        $element->setAttribute('rel', 'noopener noreferrer');
    }

    private function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        if (str_starts_with($url, '#')) {
            return true;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild instanceof DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
