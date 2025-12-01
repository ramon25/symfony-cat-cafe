import { Controller } from '@hotwired/stimulus';

/**
 * AI Personality Controller
 *
 * Fetches and displays AI-generated cat personality profiles and fun facts.
 */
export default class extends Controller {
    static targets = ['loading', 'content', 'profile', 'facts', 'error', 'button'];
    static values = { url: String };

    async loadPersonality(event) {
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
            const response = await fetch(this.urlValue);
            const data = await response.json();

            if (data.profile) {
                this.profileTarget.textContent = data.profile;

                // Display fun facts if available
                if (data.funFacts && data.funFacts.length > 0 && this.hasFactsTarget) {
                    this.factsTarget.innerHTML = data.funFacts
                        .map(fact => `<li class="flex items-start gap-2"><span class="text-purple-500">*</span><span>${fact}</span></li>`)
                        .join('');
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

    showError() {
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.remove('hidden');
    }
}
