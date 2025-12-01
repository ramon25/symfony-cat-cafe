import { Controller } from '@hotwired/stimulus';
import { formatAiText } from '../utils/textFormatter';

/**
 * AI Bonding Advice Controller
 *
 * Fetches and displays AI-generated bonding tips for cats.
 */
export default class extends Controller {
    static targets = ['loading', 'content', 'advice', 'error', 'button'];
    static values = { url: String };

    connect() {
        // Auto-load bonding advice on page load
        this.loadAdvice();
    }

    async loadAdvice(event) {
        if (event) event.preventDefault();

        // Show loading, hide others
        this.loadingTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');
        this.errorTarget.classList.add('hidden');

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
        }

        try {
            const response = await fetch(this.urlValue);
            const data = await response.json();

            if (data.advice) {
                // Format advice with markdown-like formatting
                const formattedAdvice = this.formatAdvice(data.advice);
                this.adviceTarget.innerHTML = formattedAdvice;

                this.loadingTarget.classList.add('hidden');
                this.contentTarget.classList.remove('hidden');
            } else {
                this.showError();
            }
        } catch (error) {
            console.error('Failed to fetch bonding advice:', error);
            this.showError();
        } finally {
            if (this.hasButtonTarget) {
                this.buttonTarget.disabled = false;
            }
        }
    }

    formatAdvice(text) {
        // Convert numbered lists and apply markdown formatting (bold/italic)
        return text
            .split('\n')
            .map(line => {
                // Check if it's a numbered item
                const numberedMatch = line.match(/^(\d+)[.)\s]+(.+)/);
                if (numberedMatch) {
                    return `<div class="flex items-start gap-3 mb-3">
                        <span class="flex-shrink-0 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-sm font-bold">${numberedMatch[1]}</span>
                        <span>${formatAiText(numberedMatch[2])}</span>
                    </div>`;
                }
                return line ? `<p class="mb-2">${formatAiText(line)}</p>` : '';
            })
            .join('');
    }

    showError() {
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.remove('hidden');
    }
}
