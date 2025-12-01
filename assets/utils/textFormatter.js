/**
 * Text Formatter Utility
 *
 * Provides markdown-style text formatting for AI-generated content.
 * Converts **bold** and *italic* syntax to styled HTML.
 */

/**
 * Escape HTML special characters to prevent XSS.
 * @param {string} text - Raw text to escape
 * @returns {string} - HTML-escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format text with markdown-style bold and italic.
 *
 * Converts:
 * - **text** or __text__ to <strong>text</strong> (bold)
 * - *text* or _text_ to <em>text</em> (italic/cursive)
 *
 * @param {string} text - The text to format
 * @returns {string} - HTML string with formatting applied
 */
export function formatAiText(text) {
    if (!text) return '';

    // First, escape HTML to prevent XSS
    let formatted = escapeHtml(text);

    // Convert **text** or __text__ to <strong>text</strong> (bold)
    // Must be done before single asterisk/underscore to avoid conflicts
    formatted = formatted.replace(
        /\*\*(.+?)\*\*|__(.+?)__/g,
        (match, p1, p2) => `<strong class="font-bold">${p1 || p2}</strong>`
    );

    // Convert *text* or _text_ to <em>text</em> (italic)
    // Use negative lookahead/lookbehind equivalent patterns
    formatted = formatted.replace(
        /(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)|(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/g,
        (match, p1, p2) => `<em class="italic">${p1 || p2}</em>`
    );

    return formatted;
}

/**
 * Set formatted text content on an element using innerHTML.
 * Applies markdown formatting to the text.
 *
 * @param {HTMLElement} element - The element to update
 * @param {string} text - The text to format and set
 */
export function setFormattedText(element, text) {
    if (!element) return;
    element.innerHTML = formatAiText(text);
}

/**
 * Format text and preserve line breaks.
 *
 * @param {string} text - The text to format
 * @returns {string} - HTML string with formatting and line breaks
 */
export function formatAiTextWithBreaks(text) {
    if (!text) return '';
    return formatAiText(text).replace(/\n/g, '<br>');
}
