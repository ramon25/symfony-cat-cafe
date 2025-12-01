import { Controller } from '@hotwired/stimulus';

/*
 * Live Stats Controller
 * Polls the server for updated cat stats and updates the DOM in real-time
 */
export default class extends Controller {
    static values = {
        url: String,
        interval: { type: Number, default: 10000 } // Poll every 10 seconds by default
    }

    static targets = [
        'availableCount',
        'adoptedCount',
        'hungryCount',
        'feedAllButton',
        'catCard',
        'lastUpdated'
    ]

    connect() {
        this.startPolling();
        this.updateLastUpdatedTime();
    }

    disconnect() {
        this.stopPolling();
    }

    startPolling() {
        // Initial fetch
        this.fetchStats();

        // Set up interval
        this.pollInterval = setInterval(() => {
            this.fetchStats();
        }, this.intervalValue);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }

    async fetchStats() {
        try {
            const response = await fetch(this.urlValue);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateStats(data);
                this.updateLastUpdatedTime();
            }
        } catch (error) {
            console.error('Failed to fetch cat stats:', error);
        }
    }

    updateStats(data) {
        // Update summary stats
        if (this.hasAvailableCountTarget) {
            this.animateNumber(this.availableCountTarget, data.summary.availableCount);
        }

        if (this.hasAdoptedCountTarget) {
            this.animateNumber(this.adoptedCountTarget, data.summary.adoptedCount);
        }

        if (this.hasHungryCountTarget) {
            this.animateNumber(this.hungryCountTarget, data.summary.hungryCount);
        }

        // Show/hide feed all button based on hungry cats
        if (this.hasFeedAllButtonTarget) {
            if (data.summary.hungryCount > 0) {
                this.feedAllButtonTarget.classList.remove('hidden');
            } else {
                this.feedAllButtonTarget.classList.add('hidden');
            }
        }

        // Update individual cat cards
        data.cats.forEach(cat => {
            this.updateCatCard(cat);
        });
    }

    updateCatCard(cat) {
        const card = this.element.querySelector(`[data-cat-id="${cat.id}"]`);
        if (!card) return;

        // Update mood emoji
        const moodEmoji = card.querySelector('[data-stat="moodEmoji"]');
        if (moodEmoji && moodEmoji.textContent !== cat.moodEmoji) {
            moodEmoji.textContent = cat.moodEmoji;
            this.flashElement(moodEmoji);
        }

        // Update mood text
        const moodText = card.querySelector('[data-stat="mood"]');
        if (moodText && moodText.textContent !== cat.mood) {
            moodText.textContent = cat.mood;
        }

        // Update stat bars
        this.updateStatBar(card, 'hunger', cat.hunger);
        this.updateStatBar(card, 'happiness', cat.happiness);
        this.updateStatBar(card, 'energy', cat.energy);
    }

    updateStatBar(card, statName, value) {
        const valueElement = card.querySelector(`[data-stat="${statName}-value"]`);
        const barElement = card.querySelector(`[data-stat="${statName}-bar"]`);

        if (valueElement) {
            const currentValue = parseInt(valueElement.textContent);
            if (currentValue !== value) {
                valueElement.textContent = `${value}%`;

                // Add visual feedback for changes
                if (value > currentValue) {
                    this.flashElement(valueElement, 'text-red-500');
                } else if (value < currentValue) {
                    this.flashElement(valueElement, 'text-green-500');
                }
            }
        }

        if (barElement) {
            barElement.style.width = `${value}%`;
        }
    }

    animateNumber(element, newValue) {
        const currentValue = parseInt(element.textContent) || 0;
        if (currentValue === newValue) return;

        // Simple animation for number change
        element.textContent = newValue;
        this.flashElement(element);
    }

    flashElement(element, colorClass = null) {
        // Add a brief scale animation to indicate update
        element.classList.add('scale-110');
        if (colorClass) {
            element.classList.add(colorClass);
        }

        setTimeout(() => {
            element.classList.remove('scale-110');
            if (colorClass) {
                element.classList.remove(colorClass);
            }
        }, 300);
    }

    updateLastUpdatedTime() {
        if (this.hasLastUpdatedTarget) {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            this.lastUpdatedTarget.textContent = timeString;
        }
    }

    // Manual refresh action
    refresh() {
        this.fetchStats();
    }
}
