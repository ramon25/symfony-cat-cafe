import { Controller } from '@hotwired/stimulus';
import { formatAiText } from '../utils/textFormatter.js';

/**
 * AI Cat Thought Controller
 *
 * Fetches and displays AI-generated cat thoughts and bonding messages.
 */
export default class extends Controller {
    static targets = ['loading', 'content', 'thought', 'bondingMessage', 'error', 'button', 'emoji'];
    static values = { url: String };

    async loadThought(event) {
        if (event) event.preventDefault();

        // Show loading, hide others
        this.loadingTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');
        this.errorTarget.classList.add('hidden');

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.innerHTML = '<span class="animate-pulse">Reading mind...</span>';
        }

        try {
            const response = await fetch(this.urlValue);
            const data = await response.json();

            if (data.thought) {
                // Apply markdown formatting (bold/italic) for AI-generated thoughts
                this.thoughtTarget.innerHTML = formatAiText(data.thought);

                if (data.bondingMessage && this.hasBondingMessageTarget) {
                    this.bondingMessageTarget.innerHTML = formatAiText(data.bondingMessage);
                }

                if (data.moodEmoji && this.hasEmojiTarget) {
                    this.emojiTarget.textContent = data.moodEmoji;
                }

                this.loadingTarget.classList.add('hidden');
                this.contentTarget.classList.remove('hidden');
            } else {
                this.showError();
            }
        } catch (error) {
            console.error('Failed to fetch cat thought:', error);
            this.showError();
        } finally {
            if (this.hasButtonTarget) {
                this.buttonTarget.disabled = false;
                this.buttonTarget.innerHTML = '<span class="text-lg">🧠</span><span>Read Mind Again</span>';
            }
        }
    }

    showError() {
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.remove('hidden');
    }
}
