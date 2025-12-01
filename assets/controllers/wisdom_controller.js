import { Controller } from '@hotwired/stimulus';
import { formatAiText } from '../utils/textFormatter';

/**
 * Cat Wisdom Fortune Controller
 *
 * Fetches and displays whimsical wisdom from cats.
 */
export default class extends Controller {
    static targets = ['button', 'display', 'prefix', 'wisdom', 'lucky', 'emoji'];
    static values = { url: String };

    async getWisdom(event) {
        event.preventDefault();

        // Disable button and show loading state
        this.buttonTarget.disabled = true;
        this.buttonTarget.innerHTML = '<span class="animate-pulse">Consulting the cat...</span>';

        try {
            const response = await fetch(this.urlValue);
            const data = await response.json();

            if (data.success) {
                // Update the display with the fortune
                // Apply markdown formatting (bold/italic) for AI-generated wisdom
                this.prefixTarget.innerHTML = formatAiText(data.prefix);
                this.wisdomTarget.innerHTML = `"${formatAiText(data.wisdom)}"`;
                this.luckyTarget.innerHTML = `
                    <span class="text-purple-600 dark:text-purple-400">Lucky Item:</span> ${formatAiText(data.luckyItem)}
                    <span class="mx-2">|</span>
                    <span class="text-purple-600 dark:text-purple-400">Lucky Number:</span> ${data.luckyNumber}
                `;
                this.emojiTarget.textContent = data.catEmoji;

                // Show the display with animation
                this.displayTarget.classList.remove('hidden');
                this.displayTarget.classList.add('animate-fade-in');
            }
        } catch (error) {
            console.error('Failed to fetch wisdom:', error);
            this.prefixTarget.textContent = 'The cat seems distracted...';
            this.wisdomTarget.textContent = '"Try again when I\'m in a better mood."';
            this.luckyTarget.textContent = '';
            this.displayTarget.classList.remove('hidden');
        } finally {
            // Re-enable button
            this.buttonTarget.disabled = false;
            this.buttonTarget.innerHTML = '<span class="text-xl">🔮</span><span>Ask for Another Wisdom</span>';
        }
    }
}
