import { Controller } from '@hotwired/stimulus';
import { formatAiText } from '../utils/textFormatter';

/**
 * AI Cat Therapist Controller
 *
 * Handles interactive chat with AI-powered cat therapist.
 * Features: message persistence, chat history, clear functionality.
 */
export default class extends Controller {
    static targets = ['input', 'submit', 'messages', 'chatArea', 'loading', 'clearBtn'];
    static values = {
        url: String,
        historyUrl: String,
        clearUrl: String,
        catName: String,
        catEmoji: String
    };

    connect() {
        // Load chat history when controller connects
        this.loadChatHistory();
    }

    async loadChatHistory() {
        this.showLoading(true);

        try {
            const response = await fetch(this.historyUrlValue);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                // Display saved messages
                data.messages.forEach(msg => {
                    this.addMessage(
                        msg.content,
                        msg.role === 'user' ? 'user' : 'cat',
                        msg.createdAt,
                        false // don't animate
                    );
                });
            } else {
                // No history, show welcome message
                this.addWelcomeMessage();
            }
        } catch (error) {
            console.error('Failed to load chat history:', error);
            // Show welcome message on error
            this.addWelcomeMessage();
        } finally {
            this.showLoading(false);
            // Scroll to bottom after loading
            this.scrollToBottom();
        }
    }

    addWelcomeMessage() {
        this.addMessage(
            `*${this.catNameValue} settles into a comfy spot and looks at you attentively* Mrrrow~ I sense you might need someone to talk to. What's on your mind, friend?`,
            'cat',
            null,
            true
        );
    }

    showLoading(show) {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.toggle('hidden', !show);
        }
    }

    async sendMessage(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();
        if (!message) return;

        // Add user message to chat
        this.addMessage(message, 'user', null, true);

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
                this.addMessage(data.advice, 'cat', null, true);
            } else {
                this.addMessage(
                    `*${this.catNameValue} tilts head* Mew? Something went wrong... ${data.error || 'Please try again!'}`,
                    'cat',
                    null,
                    true
                );
            }
        } catch (error) {
            console.error('Failed to get therapy response:', error);
            this.removeThinking(thinkingId);
            this.addMessage(
                `*${this.catNameValue} yawns* My whiskers are detecting some technical difficulties... Perhaps try again after a little catnap?`,
                'cat',
                null,
                true
            );
        } finally {
            this.submitTarget.disabled = false;
            this.inputTarget.disabled = false;
            this.inputTarget.focus();
        }
    }

    async clearChat() {
        if (!confirm('Are you sure you want to clear the chat history? This cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(this.clearUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                // Clear the messages area
                this.messagesTarget.innerHTML = '';
                // Show fresh welcome message
                this.addWelcomeMessage();
            }
        } catch (error) {
            console.error('Failed to clear chat:', error);
        }
    }

    addMessage(content, type, timestamp = null, animate = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${type === 'user' ? 'justify-end' : 'justify-start'}`;

        if (animate) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(10px)';
        }

        const wrapper = document.createElement('div');
        wrapper.className = `max-w-[85%] ${type === 'user' ? 'items-end' : 'items-start'} flex flex-col`;

        const bubble = document.createElement('div');

        if (type === 'user') {
            bubble.className = 'bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-2xl rounded-br-sm px-4 py-3 shadow-md';
        } else {
            bubble.className = 'bg-white text-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 shadow-md border border-purple-100';

            // Add cat avatar
            const avatarWrapper = document.createElement('div');
            avatarWrapper.className = 'flex items-start gap-2';

            const avatar = document.createElement('div');
            avatar.className = 'w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0 shadow-sm';
            avatar.innerHTML = `<span class="text-sm">${this.catEmojiValue}</span>`;

            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'flex flex-col';

            const textSpan = document.createElement('span');
            textSpan.className = 'text-sm leading-relaxed';
            // Apply markdown formatting (bold/italic) for AI responses
            textSpan.innerHTML = formatAiText(content);
            contentWrapper.appendChild(textSpan);

            avatarWrapper.appendChild(avatar);
            avatarWrapper.appendChild(contentWrapper);
            bubble.appendChild(avatarWrapper);

            // Add timestamp if available
            if (timestamp) {
                const timeSpan = document.createElement('span');
                timeSpan.className = 'text-xs text-gray-400 mt-1 block';
                timeSpan.textContent = this.formatTime(timestamp);
                contentWrapper.appendChild(timeSpan);
            }

            wrapper.appendChild(bubble);
            messageDiv.appendChild(wrapper);
            this.messagesTarget.appendChild(messageDiv);

            if (animate) {
                requestAnimationFrame(() => {
                    messageDiv.style.transition = 'all 0.3s ease-out';
                    messageDiv.style.opacity = '1';
                    messageDiv.style.transform = 'translateY(0)';
                });
            }

            this.scrollToBottom();
            return;
        }

        // User message
        const textSpan = document.createElement('span');
        textSpan.className = 'text-sm leading-relaxed';
        // Apply markdown formatting (bold/italic) for user messages too
        textSpan.innerHTML = formatAiText(content);
        bubble.appendChild(textSpan);

        wrapper.appendChild(bubble);

        // Add timestamp for user messages
        if (timestamp) {
            const timeSpan = document.createElement('span');
            timeSpan.className = 'text-xs text-gray-400 mt-1';
            timeSpan.textContent = this.formatTime(timestamp);
            wrapper.appendChild(timeSpan);
        }

        messageDiv.appendChild(wrapper);
        this.messagesTarget.appendChild(messageDiv);

        if (animate) {
            requestAnimationFrame(() => {
                messageDiv.style.transition = 'all 0.3s ease-out';
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateY(0)';
            });
        }

        this.scrollToBottom();
    }

    formatTime(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp);
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

        const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        if (diffDays === 0) {
            return `Today at ${timeStr}`;
        } else if (diffDays === 1) {
            return `Yesterday at ${timeStr}`;
        } else {
            return `${date.toLocaleDateString([], { month: 'short', day: 'numeric' })} at ${timeStr}`;
        }
    }

    scrollToBottom() {
        requestAnimationFrame(() => {
            this.chatAreaTarget.scrollTop = this.chatAreaTarget.scrollHeight;
        });
    }

    addThinking() {
        const id = `thinking-${Date.now()}`;
        const messageDiv = document.createElement('div');
        messageDiv.id = id;
        messageDiv.className = 'flex justify-start';

        messageDiv.innerHTML = `
            <div class="max-w-[85%] flex items-start gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <span class="text-sm">${this.catEmojiValue}</span>
                </div>
                <div class="bg-white text-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 shadow-md border border-purple-100">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                        <span class="ml-2 text-sm text-purple-500 italic">thinking...</span>
                    </div>
                </div>
            </div>
        `;

        this.messagesTarget.appendChild(messageDiv);
        this.scrollToBottom();

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
