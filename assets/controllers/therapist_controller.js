import { Controller } from '@hotwired/stimulus';

/**
 * AI Cat Therapist Controller
 *
 * Handles interactive chat with AI-powered cat therapist.
 */
export default class extends Controller {
    static targets = ['input', 'submit', 'messages', 'chatArea'];
    static values = { url: String, catName: String, catEmoji: String };

    connect() {
        // Add welcome message when controller connects
        this.addMessage(
            `*${this.catNameValue} settles into a comfy spot and looks at you attentively* Mrrrow~ I sense you might need someone to talk to. What's on your mind, friend?`,
            'cat'
        );
    }

    async sendMessage(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();
        if (!message) return;

        // Add user message to chat
        this.addMessage(message, 'user');

        // Clear input and disable while processing
        this.inputTarget.value = '';
        this.submitTarget.disabled = true;
        this.inputTarget.disabled = true;

        // Show thinking indicator
        const thinkingId = this.addThinking();

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message }),
            });

            const data = await response.json();

            // Remove thinking indicator
            this.removeThinking(thinkingId);

            if (data.success) {
                this.addMessage(data.advice, 'cat');
            } else {
                this.addMessage(
                    `*${this.catNameValue} tilts head* Mew? Something went wrong... ${data.error || 'Please try again!'}`,
                    'cat'
                );
            }
        } catch (error) {
            console.error('Failed to get therapy response:', error);
            this.removeThinking(thinkingId);
            this.addMessage(
                `*${this.catNameValue} yawns* My whiskers are detecting some technical difficulties... Perhaps try again after a little catnap?`,
                'cat'
            );
        } finally {
            this.submitTarget.disabled = false;
            this.inputTarget.disabled = false;
            this.inputTarget.focus();
        }
    }

    addMessage(content, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${type === 'user' ? 'justify-end' : 'justify-start'} mb-3`;

        const bubble = document.createElement('div');

        if (type === 'user') {
            bubble.className = 'bg-orange-100 text-orange-900 rounded-2xl rounded-br-md px-4 py-2 max-w-[80%] shadow-sm';
        } else {
            bubble.className = 'bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-900 rounded-2xl rounded-bl-md px-4 py-2 max-w-[80%] shadow-sm';
            // Add cat emoji before cat messages
            const emojiSpan = document.createElement('span');
            emojiSpan.className = 'text-lg mr-1';
            emojiSpan.textContent = this.catEmojiValue;
            bubble.appendChild(emojiSpan);
        }

        const textSpan = document.createElement('span');
        textSpan.textContent = content;
        bubble.appendChild(textSpan);

        messageDiv.appendChild(bubble);
        this.messagesTarget.appendChild(messageDiv);

        // Scroll to bottom
        this.chatAreaTarget.scrollTop = this.chatAreaTarget.scrollHeight;
    }

    addThinking() {
        const id = `thinking-${Date.now()}`;
        const messageDiv = document.createElement('div');
        messageDiv.id = id;
        messageDiv.className = 'flex justify-start mb-3';

        messageDiv.innerHTML = `
            <div class="bg-gradient-to-r from-purple-100 to-indigo-100 text-purple-900 rounded-2xl rounded-bl-md px-4 py-2 shadow-sm">
                <span class="text-lg mr-1">${this.catEmojiValue}</span>
                <span class="inline-flex items-center">
                    <span class="animate-bounce mx-0.5">.</span>
                    <span class="animate-bounce mx-0.5" style="animation-delay: 0.1s">.</span>
                    <span class="animate-bounce mx-0.5" style="animation-delay: 0.2s">.</span>
                    <span class="ml-2 text-sm text-purple-600 italic">*thinking*</span>
                </span>
            </div>
        `;

        this.messagesTarget.appendChild(messageDiv);
        this.chatAreaTarget.scrollTop = this.chatAreaTarget.scrollHeight;

        return id;
    }

    removeThinking(id) {
        const element = document.getElementById(id);
        if (element) {
            element.remove();
        }
    }

    // Handle Enter key in input
    handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage(event);
        }
    }
}
