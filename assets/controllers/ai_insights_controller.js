import { Controller } from '@hotwired/stimulus';

/**
 * AI Insights Controller
 *
 * Fetches and displays AI-generated compatibility insights.
 */
export default class extends Controller {
    static targets = ['loading', 'content', 'text', 'error'];
    static values = { url: String };

    connect() {
        this.fetchInsights();
    }

    async fetchInsights() {
        try {
            const response = await fetch(this.urlValue);
            const data = await response.json();

            if (data.insights) {
                this.textTarget.textContent = data.insights;
                this.loadingTarget.classList.add('hidden');
                this.contentTarget.classList.remove('hidden');
            } else {
                this.showError();
            }
        } catch (error) {
            console.error('Failed to fetch AI insights:', error);
            this.showError();
        }
    }

    showError() {
        this.loadingTarget.classList.add('hidden');
        this.errorTarget.classList.remove('hidden');
    }
}
