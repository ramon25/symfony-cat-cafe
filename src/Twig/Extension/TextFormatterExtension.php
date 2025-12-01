<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for formatting AI-generated text with markdown-style formatting.
 * Converts **bold** and *italic* syntax to HTML tags.
 */
class TextFormatterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_ai_text', [$this, 'formatAiText'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Format AI-generated text with markdown-style bold and italic.
     *
     * Converts:
     * - **text** or __text__ to <strong>text</strong>
     * - *text* or _text_ to <em>text</em>
     *
     * The text is first escaped to prevent XSS, then formatting is applied.
     */
    public function formatAiText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // First, escape HTML to prevent XSS
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Convert **text** or __text__ to <strong>text</strong> (bold)
        // Must be done before single asterisk/underscore to avoid conflicts
        $formatted = preg_replace(
            '/\*\*(.+?)\*\*|__(.+?)__/',
            '<strong class="font-bold">$1$2</strong>',
            $escaped
        );

        // Convert *text* or _text_ to <em>text</em> (italic)
        // Use negative lookbehind/lookahead to avoid matching inside **
        $formatted = preg_replace(
            '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)|(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/',
            '<em class="italic">$1$2</em>',
            $formatted
        );

        // Preserve line breaks
        $formatted = nl2br($formatted);

        return $formatted;
    }
}
