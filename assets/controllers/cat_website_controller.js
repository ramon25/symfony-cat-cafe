import { Controller } from '@hotwired/stimulus';

/**
 * Cat Website Controller
 *
 * Handles regenerating AI-generated cat personal websites.
 */
export default class extends Controller {
    static targets = ['regenerateBtn', 'loading', 'success'];
    static values = { regenerateUrl: String, viewUrl: String };

    async regenerate(event) {
        if (event) event.preventDefault();

        // Show loading state
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('hidden');
        }
        if (this.hasSuccessTarget) {
            this.successTarget.classList.add('hidden');
        }
        if (this.hasRegenerateBtnTarget) {
            this.regenerateBtnTarget.disabled = true;
            this.regenerateBtnTarget.innerHTML = '<span class="animate-spin">🔄</span><span>Generating...</span>';
        }

        try {
            const response = await fetch(this.regenerateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                // Hide loading, show success
                if (this.hasLoadingTarget) {
                    this.loadingTarget.classList.add('hidden');
                }
                if (this.hasSuccessTarget) {
                    this.successTarget.classList.remove('hidden');
                }

                // Optionally open the new website in a new tab
                if (data.websiteUrl) {
                    window.open(data.websiteUrl, '_blank');
                }

                // Reload page after a short delay to show new info
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showError(data.error || 'Failed to regenerate website');
            }
        } catch (error) {
            console.error('Failed to regenerate website:', error);
            this.showError('Failed to regenerate website. Please try again.');
        } finally {
            if (this.hasRegenerateBtnTarget) {
                this.regenerateBtnTarget.disabled = false;
                this.regenerateBtnTarget.innerHTML = '<span>🔄</span><span>New Layout</span>';
            }
        }
    }

    showError(message) {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
        alert(message);
    }
}
