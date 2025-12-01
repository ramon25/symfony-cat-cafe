import { Controller } from '@hotwired/stimulus';

/**
 * Cat Image Controller
 *
 * Handles AI-generated cat image display and regeneration using Gemini Imagen API.
 */
export default class extends Controller {
    static targets = ['image', 'placeholder', 'loading', 'generateBtn', 'regenerateBtn', 'error', 'prompt', 'generatedAt'];
    static values = {
        imageUrl: String,
        generateUrl: String,
        catName: String,
        catEmoji: String
    };

    connect() {
        // Load existing image if available
        this.loadImage();
    }

    async loadImage() {
        try {
            const response = await fetch(this.imageUrlValue);
            const data = await response.json();

            if (data.success && data.hasImage) {
                this.showImage(data.imageDataUrl, data.prompt, data.generatedAt);
            } else {
                this.showPlaceholder();
            }
        } catch (error) {
            console.error('Failed to load cat image:', error);
            this.showPlaceholder();
        }
    }

    async generate(event) {
        if (event) event.preventDefault();

        this.showLoading();

        try {
            const response = await fetch(this.generateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                this.showImage(data.imageDataUrl, data.prompt, data.generatedAt);
            } else {
                this.showError(data.error || 'Failed to generate image');
            }
        } catch (error) {
            console.error('Failed to generate cat image:', error);
            this.showError('Failed to generate image. Please try again.');
        }
    }

    async regenerate(event) {
        await this.generate(event);
    }

    showImage(dataUrl, prompt, generatedAt) {
        // Hide loading and placeholder
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.add('hidden');
        }
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('hidden');
        }
        if (this.hasGenerateBtnTarget) {
            this.generateBtnTarget.classList.add('hidden');
        }

        // Show image
        if (this.hasImageTarget) {
            this.imageTarget.src = dataUrl;
            this.imageTarget.classList.remove('hidden');
        }

        // Show regenerate button
        if (this.hasRegenerateBtnTarget) {
            this.regenerateBtnTarget.classList.remove('hidden');
            this.regenerateBtnTarget.disabled = false;
            this.regenerateBtnTarget.innerHTML = '<span>🔄</span><span>Regenerate</span>';
        }

        // Show prompt info
        if (this.hasPromptTarget && prompt) {
            this.promptTarget.textContent = prompt;
            this.promptTarget.parentElement.classList.remove('hidden');
        }

        // Show generated time
        if (this.hasGeneratedAtTarget && generatedAt) {
            this.generatedAtTarget.textContent = `Generated: ${generatedAt}`;
            this.generatedAtTarget.classList.remove('hidden');
        }
    }

    showPlaceholder() {
        // Hide loading and image
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
        if (this.hasImageTarget) {
            this.imageTarget.classList.add('hidden');
        }
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('hidden');
        }
        if (this.hasRegenerateBtnTarget) {
            this.regenerateBtnTarget.classList.add('hidden');
        }

        // Show placeholder and generate button
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.remove('hidden');
        }
        if (this.hasGenerateBtnTarget) {
            this.generateBtnTarget.classList.remove('hidden');
            this.generateBtnTarget.disabled = false;
        }
    }

    showLoading() {
        // Hide everything except loading
        if (this.hasImageTarget) {
            this.imageTarget.classList.add('hidden');
        }
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.add('hidden');
        }
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('hidden');
        }
        if (this.hasGenerateBtnTarget) {
            this.generateBtnTarget.disabled = true;
            this.generateBtnTarget.innerHTML = '<span class="animate-spin inline-block">🎨</span><span>Generating...</span>';
        }
        if (this.hasRegenerateBtnTarget) {
            this.regenerateBtnTarget.disabled = true;
            this.regenerateBtnTarget.innerHTML = '<span class="animate-spin inline-block">🎨</span><span>Generating...</span>';
        }

        // Show loading
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('hidden');
        }
    }

    showError(message) {
        // Hide loading
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }

        // Show error
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.classList.remove('hidden');
        }

        // Re-enable buttons
        if (this.hasGenerateBtnTarget) {
            this.generateBtnTarget.disabled = false;
            this.generateBtnTarget.innerHTML = '<span>🎨</span><span>Generate AI Portrait</span>';
            this.generateBtnTarget.classList.remove('hidden');
        }
        if (this.hasRegenerateBtnTarget) {
            this.regenerateBtnTarget.disabled = false;
            this.regenerateBtnTarget.innerHTML = '<span>🔄</span><span>Regenerate</span>';
        }

        // Show placeholder
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.remove('hidden');
        }
    }
}
