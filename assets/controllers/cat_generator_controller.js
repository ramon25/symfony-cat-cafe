import { Controller } from '@hotwired/stimulus';

/**
 * AI Cat Generator Controller
 *
 * Handles interactive AI-powered cat generation with preview and save functionality.
 */
export default class extends Controller {
    static targets = [
        'prompt',
        'generateBtn',
        'generateBtnText',
        'generateBtnLoading',
        'preview',
        'previewEmoji',
        'previewName',
        'previewBreed',
        'previewAge',
        'previewColor',
        'previewInteraction',
        'previewDescription',
        'saveBtn',
        'saveBtnText',
        'saveBtnLoading',
        'error',
        'errorText'
    ];

    static values = {
        generateUrl: String,
        saveUrl: String
    };

    // Store generated cat data for saving
    generatedCat = null;

    // Interaction labels for display
    interactionLabels = {
        'feed': 'Being Fed',
        'pet': 'Being Petted',
        'play': 'Playing',
        'rest': 'Resting'
    };

    // Color to emoji mapping
    colorEmojis = {
        'Orange': '🧡',
        'Black': '🖤',
        'White': '🤍',
        'Gray': '🩶',
        'Calico': '🎨',
        'Tabby': '🐯',
        'Tuxedo': '🎩',
        'Tortoiseshell': '🐢',
        'Cream': '🍦',
        'Brown': '🤎'
    };

    async generate() {
        const prompt = this.promptTarget.value.trim();

        // Show loading state
        this.setGenerateLoading(true);
        this.hideError();
        this.hidePreview();

        try {
            const response = await fetch(this.generateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ prompt: prompt || null }),
            });

            const data = await response.json();

            if (data.success && data.cat) {
                this.generatedCat = data.cat;
                this.showPreview(data.cat);
            } else {
                this.showError(data.error || 'Failed to generate cat. Please try again!');
            }
        } catch (error) {
            console.error('Failed to generate cat:', error);
            this.showError('Something went wrong! Please try again.');
        } finally {
            this.setGenerateLoading(false);
        }
    }

    async save() {
        if (!this.generatedCat) {
            this.showError('No cat to save! Generate one first.');
            return;
        }

        // Show loading state
        this.setSaveLoading(true);
        this.hideError();

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.generatedCat),
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to the cat's page
                window.location.href = data.redirectUrl;
            } else {
                this.showError(data.error || 'Failed to save cat. Please try again!');
            }
        } catch (error) {
            console.error('Failed to save cat:', error);
            this.showError('Something went wrong! Please try again.');
        } finally {
            this.setSaveLoading(false);
        }
    }

    showPreview(cat) {
        // Update preview elements
        this.previewNameTarget.textContent = cat.name;
        this.previewBreedTarget.textContent = cat.breed;
        this.previewAgeTarget.textContent = `${cat.age} yr${cat.age !== 1 ? 's' : ''}`;
        this.previewColorTarget.textContent = cat.color;
        this.previewInteractionTarget.textContent = this.interactionLabels[cat.preferredInteraction] || cat.preferredInteraction;
        this.previewDescriptionTarget.textContent = cat.description;

        // Set emoji based on color
        const colorEmoji = this.colorEmojis[cat.color] || '🐱';
        this.previewEmojiTarget.textContent = colorEmoji === '🖤' ? '🐈‍⬛' : '🐱';

        // Show the preview section with animation
        this.previewTarget.classList.remove('hidden');
        this.previewTarget.style.opacity = '0';
        this.previewTarget.style.transform = 'translateY(20px)';

        requestAnimationFrame(() => {
            this.previewTarget.style.transition = 'all 0.4s ease-out';
            this.previewTarget.style.opacity = '1';
            this.previewTarget.style.transform = 'translateY(0)';
        });

        // Scroll to preview
        setTimeout(() => {
            this.previewTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    hidePreview() {
        this.previewTarget.classList.add('hidden');
    }

    showError(message) {
        this.errorTextTarget.textContent = message;
        this.errorTarget.classList.remove('hidden');
    }

    hideError() {
        this.errorTarget.classList.add('hidden');
    }

    setGenerateLoading(loading) {
        this.generateBtnTarget.disabled = loading;
        this.generateBtnTextTarget.classList.toggle('hidden', loading);
        this.generateBtnLoadingTarget.classList.toggle('hidden', !loading);
    }

    setSaveLoading(loading) {
        this.saveBtnTarget.disabled = loading;
        this.saveBtnTextTarget.classList.toggle('hidden', loading);
        this.saveBtnLoadingTarget.classList.toggle('hidden', !loading);
    }
}
