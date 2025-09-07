/**
 * Kura AI Chatbot JavaScript
 * Handles chatbot interactions and AJAX communication
 *
 * @package Kura_AI
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Kura AI Chatbot Class
     */
    class KuraAIChatbot {
        constructor() {
            this.isOpen = false;
            this.isTyping = false;
            this.messageQueue = [];
            
            this.init();
        }

        /**
         * Initialize chatbot
         */
        init() {
            this.bindEvents();
            this.loadChatHistory();
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            const $container = $('#kura-ai-chatbot');
            const $toggle = $('#kura-chatbot-toggle');
            const $close = $('#kura-chatbot-close');
            const $form = $('#kura-chatbot-form');
            const $input = $('#kura-chatbot-message');

            // Toggle chatbot
            $toggle.on('click', (e) => {
                e.preventDefault();
                this.toggle();
            });

            // Close chatbot
            $close.on('click', (e) => {
                e.preventDefault();
                this.close();
            });
            
            // Reset chat
            $('#kura-ai-chatbot-reset').on('click', (e) => {
                e.preventDefault();
                this.resetChat();
            });
            
            // New chat
            $('#kura-ai-chatbot-new').on('click', (e) => {
                e.preventDefault();
                this.newChat();
            });

            // Handle form submission
            $form.on('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Handle Enter key
            $input.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize input
            $input.on('input', () => {
                this.adjustInputHeight();
            });

            // Close on outside click
            $(document).on('click', (e) => {
                if (this.isOpen && !$container.is(e.target) && $container.has(e.target).length === 0) {
                    this.close();
                }
            });

            // Handle escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        }

        /**
         * Toggle chatbot visibility
         */
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        /**
         * Open chatbot
         */
        open() {
            this.isOpen = true;
            const $window = $('#kura-chatbot-window');
            $('#kura-ai-chatbot').addClass('open');
            
            // Show the window with CSS transitions
            $window.css('display', 'flex');
            setTimeout(() => {
                $window.addClass('show').removeClass('hide');
            }, 10);
            
            // Focus input after animation
            setTimeout(() => {
                $('#kura-chatbot-message').focus();
            }, 200);
            
            // Scroll to bottom
            this.scrollToBottom();
        }

        /**
         * Close chatbot
         */
        close() {
            this.isOpen = false;
            const $window = $('#kura-chatbot-window');
            $('#kura-ai-chatbot').removeClass('open');
            
            // Hide the window with CSS transitions
            $window.addClass('hide').removeClass('show');
            
            // Hide completely after animation
            setTimeout(() => {
                $window.css('display', 'none');
            }, 400);
        }

        /**
         * Send message
         */
        sendMessage() {
            const $input = $('#kura-chatbot-message');
            const message = $input.val().trim();

            if (!message || this.isTyping) {
                return;
            }

            // Clear input
            $input.val('');
            this.adjustInputHeight();

            // Add user message to chat
            this.addMessage(message, 'user');

            // Show typing indicator
            this.showTyping();

            // Send AJAX request
            this.sendAjaxMessage(message);
        }

        /**
         * Add message to chat
         */
        addMessage(content, type = 'bot', timestamp = null) {
            const $messages = $('#kura-chatbot-messages');
            const time = timestamp ? new Date(timestamp * 1000) : new Date();
            const timeString = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const messageHtml = `
                <div class="kura-chatbot-message kura-chatbot-message-${type}">
                    <div class="kura-chatbot-message-content">
                        ${this.escapeHtml(content)}
                    </div>
                    <div class="kura-chatbot-message-time">
                        ${timeString}
                    </div>
                </div>
            `;

            $messages.append(messageHtml);
            this.scrollToBottom();
            
            // Save to local storage
            this.saveChatHistory();
        }

        /**
         * Show typing indicator
         */
        showTyping() {
            const $typing = $('#kura-chatbot-typing');
            $typing.show();
            this.isTyping = true;
            this.scrollToBottom();
        }

        /**
         * Hide typing indicator
         */
        hideTyping() {
            const $typing = $('#kura-chatbot-typing');
            $typing.hide();
            this.isTyping = false;
        }

        /**
         * Send AJAX message to backend
         */
        sendAjaxMessage(message) {
            $.ajax({
                url: kura_ai_chatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_chatbot_message',
                    message: message,
                    nonce: kura_ai_chatbot.nonce
                },
                timeout: 30000, // 30 seconds timeout
                success: (response) => {
                    this.hideTyping();
                    
                    if (response.success && response.data.response) {
                        this.addMessage(response.data.response, 'bot', response.data.timestamp);
                    } else {
                        this.addMessage(
                            kura_ai_chatbot.config.error_message || 'Sorry, I encountered an error. Please try again.',
                            'bot'
                        );
                    }
                },
                error: (xhr, status, error) => {
                    this.hideTyping();
                    
                    let errorMessage = 'Sorry, I\'m having trouble connecting. Please try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again with a shorter message.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Connection lost. Please check your internet connection.';
                    }
                    
                    this.addMessage(errorMessage, 'bot');
                }
            });
        }

        /**
         * Scroll messages to bottom
         */
        scrollToBottom() {
            const $messages = $('#kura-chatbot-messages');
            const scrollHeight = $messages[0].scrollHeight;
            
            $messages.animate({
                scrollTop: scrollHeight
            }, 300);
        }

        /**
         * Adjust input height
         */
        adjustInputHeight() {
            const $input = $('#kura-chatbot-message');
            $input.css('height', 'auto');
            
            const scrollHeight = $input[0].scrollHeight;
            const maxHeight = 100; // Maximum height in pixels
            
            $input.css('height', Math.min(scrollHeight, maxHeight) + 'px');
        }

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Save chat history to localStorage
         */
        saveChatHistory() {
            try {
                const $messages = $('#kura-chatbot-messages');
                const messages = [];
                
                $messages.find('.kura-chatbot-message').each(function() {
                    const $msg = $(this);
                    const type = $msg.hasClass('kura-chatbot-message-user') ? 'user' : 'bot';
                    const content = $msg.find('.kura-chatbot-message-content').text();
                    const time = $msg.find('.kura-chatbot-message-time').text();
                    
                    messages.push({ type, content, time });
                });
                
                // Keep only last 20 messages
                const recentMessages = messages.slice(-20);
                localStorage.setItem('kura_ai_chat_history', JSON.stringify(recentMessages));
            } catch (e) {
                // Ignore localStorage errors
            }
        }

        /**
         * Load chat history from localStorage
         */
        loadChatHistory() {
            try {
                const history = localStorage.getItem('kura_ai_chat_history');
                if (!history) return;
                
                const messages = JSON.parse(history);
                const $messages = $('#kura-chatbot-messages');
                
                // Clear existing messages except welcome message
                $messages.find('.kura-chatbot-message').not(':first').remove();
                
                // Add historical messages
                messages.forEach(msg => {
                    if (msg.type !== 'bot' || !msg.content.includes(kura_ai_chatbot.config.welcome_message)) {
                        const messageHtml = `
                            <div class="kura-chatbot-message kura-chatbot-message-${msg.type}">
                                <div class="kura-chatbot-message-content">
                                    ${this.escapeHtml(msg.content)}
                                </div>
                                <div class="kura-chatbot-message-time">
                                    ${msg.time}
                                </div>
                            </div>
                        `;
                        $messages.append(messageHtml);
                    }
                });
                
                this.scrollToBottom();
            } catch (e) {
                // Ignore localStorage errors
            }
        }

        /**
         * Clear chat history
         */
        clearHistory() {
            try {
                localStorage.removeItem('kura_ai_chat_history');
                
                const $messages = $('#kura-chatbot-messages');
                $messages.find('.kura-chatbot-message').not(':first').remove();
            } catch (e) {
                // Ignore localStorage errors
            }
        }
        
        /**
         * Reset chat with confirmation
         */
        resetChat() {
            if (confirm('Are you sure you want to reset the chat? This will clear all messages.')) {
                this.clearHistory();
                
                // Clear input field
                $('#kura-chatbot-message').val('');
                this.adjustInputHeight();
                
                // Scroll to top to show welcome message
                this.scrollToTop();
                
                // Show success feedback
                this.showTemporaryMessage('Chat has been reset', 'success');
            }
        }
        
        /**
         * Start new chat
         */
        newChat() {
            // Clear input field and start fresh
            $('#kura-chatbot-message').val('').focus();
            this.adjustInputHeight();
            
            // Scroll to bottom for new conversation
            this.scrollToBottom();
            
            // Show temporary message
            this.showTemporaryMessage('Ready for a new conversation!', 'info');
        }
        
        /**
         * Scroll to top of messages
         */
        scrollToTop() {
            const $messages = $('#kura-chatbot-messages');
            $messages.animate({
                scrollTop: 0
            }, 300);
        }
        
        /**
         * Show temporary notification message
         */
        showTemporaryMessage(text, type = 'info') {
            const $notification = $(`
                <div class="kura-chatbot-temp-notification kura-chatbot-temp-${type}">
                    ${text}
                </div>
            `);
            
            // Add to header
            $('.kura-chatbot-header').append($notification);
            
            // Animate in
            $notification.fadeIn(200);
            
            // Remove after 2 seconds
            setTimeout(() => {
                $notification.fadeOut(200, () => {
                    $notification.remove();
                });
            }, 2000);
        }
    }

    /**
     * Initialize chatbot when DOM is ready
     */
    $(document).ready(function() {
        // Check if chatbot is enabled
        if (typeof kura_ai_chatbot !== 'undefined' && kura_ai_chatbot.config.enabled) {
            new KuraAIChatbot();
        }
    });

})(jQuery);