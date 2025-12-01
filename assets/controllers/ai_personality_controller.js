import { Controller } from '@hotwired/stimulus';
import { formatAiText } from '../utils/textFormatter';

/**
 * AI Personality Controller
 *
 * Fetches and displays AI-generated cat personality profiles and fun facts.
 * Supports database caching and regeneration.
 */
export default class extends Controller {
    static targets = ['loading', 'content', 'profile', 'facts', 'error', 'button', 'cachedBadge', 'generatedAt'];
    static values = { url: String };

    async loadPersonality(event, regenerate = false) {
        if (event) event.preventDefault();

        // Show loading, hide others
        this.loadingTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');
        this.errorTarget.classList.add('hidden');

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.innerHTML = '<span class="animate-pulse">Generating...</span>';
        }

        try {
            const url = regenerate ? `${this.urlValue}?regenerate=1` : this.urlValue;
            const response = await fetch(url);
            const data = await response.json();

            if (data.profile) {
                // Apply markdown formatting (bold/italic) for AI-generated personality profile
                this.profileTarget.innerHTML = formatAiText(data.profile);

                // Display fun facts if available
                if (data.funFacts && data.funFacts.length > 0 && this.hasFactsTarget) {
                    this.factsTarget.innerHTML = data.funFacts
                        .map(fact => `<li class="flex items-start gap-2"><span class="text-purple-500">•</span><span>${formatAiText(fact)}</span></li>`)
                        .join('');
                }

                // Show cached badge if content was cached
                if (this.hasCachedBadgeTarget) {
                    if (data.cached) {
                        this.cachedBadgeTarget.classList.remove('hidden');
                    } else {
                        this.cachedBadgeTarget.classList.add('hidden');
                    }
                }

                // Show generated timestamp
                if (this.hasGeneratedAtTarget && data.generatedAt) {
                    this.generatedAtTarget.textContent = `Generated: ${data.generatedAt}`;
                    this.generatedAtTarget.classList.remove('hidden');
                }

                this.loadingTarget.classList.add('hidden');
                this.contentTarget.classList.remove('hidden');
            } else {
                this.showError();
            }
        } catch (error) {
            console.error('Failed to fetch personality:', error);
            this.showError();
        } finally {
            if (this.hasButtonTarget) {
                this.buttonTarget.disabled = false;
                this.buttonTarget.innerHTML = '<span class="text-lg">🔄</span><span>Regenerate</span>';
            }
        }
    }

    regenerate(event) {
        this.loadPersonality(event, true);
    }

    showError() {
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.remove('hidden');
    }
}
