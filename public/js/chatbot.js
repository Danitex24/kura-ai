jQuery(document).ready(function($) {
    'use strict';
    
    /**
     * Kura AI Chatbot Class
     */
    class KuraAIChatbot {
        constructor() {
            this.isOpen = false;
            this.isTyping = false;
            this.messageHistory = [];
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadHistory();
            this.adjustInputHeight();
            this.showChatbot();
        }
        
        showChatbot() {
            // Show the chatbot container
            $('#kura-ai-chatbot').show();
        }
        
        bindEvents() {
            // Toggle chatbot
            $(document).on('click', '#kura-ai-chatbot-toggle', () => {
                this.toggle();
            });
            
            // Close chatbot
            $(document).on('click', '#kura-ai-chatbot-close', () => {
                this.close();
            });
            
            // Reset chat
            $(document).on('click', '#kura-ai-chatbot-reset', () => {
                this.resetChat();
            });
            
            // New chat
            $(document).on('click', '#kura-ai-chatbot-new', () => {
                this.newChat();
            });
            
            // Send message on button click
            $(document).on('click', '#kura-ai-send-button', () => {
                this.sendMessage();
            });
            
            // Send message on Enter key (but not Shift+Enter)
            $(document).on('keydown', '#kura-ai-message-input', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Auto-resize textarea
            $(document).on('input', '#kura-ai-message-input', () => {
                this.adjustInputHeight();
            });
            
            // Close on outside click
            $(document).on('click', (e) => {
                if (this.isOpen && !$(e.target).closest('#kura-ai-chatbot').length) {
                    this.close();
                }
            });
            
            // Handle preset prompt clicks
            $(document).on('click', '.kura-ai-prompt-btn', (e) => {
                const prompt = $(e.target).data('prompt');
                if (prompt) {
                    $('#kura-ai-message-input').val(prompt);
                    this.sendMessage();
                }
            });
            
            // Handle scroll indicator
            $(document).on('scroll', '#kura-ai-chatbot-messages', () => {
                this.updateScrollIndicator();
            });
        }
        
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }
        
        open() {
            this.isOpen = true;
            const $window = $('#kura-ai-chatbot-window');
            $('#kura-ai-chatbot').addClass('kura-ai-chatbot-open');
            
            // Show the window with CSS transitions
            $window.css('display', 'flex');
            setTimeout(() => {
                $window.addClass('show').removeClass('hide');
            }, 10);
            
            // Focus input after animation
            setTimeout(() => {
                $('#kura-ai-message-input').focus();
                this.scrollToBottom();
            }, 200);
        }
        
        close() {
            this.isOpen = false;
            const $window = $('#kura-ai-chatbot-window');
            $('#kura-ai-chatbot').removeClass('kura-ai-chatbot-open');
            
            // Hide the window with CSS transitions
            $window.addClass('hide').removeClass('show');
            
            // Hide completely after animation
            setTimeout(() => {
                $window.css('display', 'none');
            }, 400);
        }
        
        sendMessage() {
            const input = $('#kura-ai-message-input');
            const message = input.val().trim();
            
            if (!message || this.isTyping) {
                return;
            }
            
            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Clear input and reset height
            input.val('');
            this.adjustInputHeight();
            
            // Show typing indicator
            this.showTyping();
            
            // Send AJAX request
            this.sendAjaxMessage(message);
        }
        
        addMessage(content, type) {
            const messagesContainer = $('#kura-ai-chatbot-messages');
            const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            const messageHtml = `
                <div class="kura-ai-message kura-ai-message-${type}">
                    <div class="kura-ai-message-content">
                        <p>${this.escapeHtml(content)}</p>
                    </div>
                    <div class="kura-ai-message-time">${timestamp}</div>
                </div>
            `;
            
            messagesContainer.append(messageHtml);
            
            // Hide preset prompts after first user message
            if (type === 'user') {
                messagesContainer.addClass('has-user-message');
            }
            
            this.scrollToBottom();
            
            // Update scroll indicator
            setTimeout(() => {
                this.updateScrollIndicator();
            }, 350);
            
            // Save to history
            this.messageHistory.push({
                content: content,
                type: type,
                timestamp: Date.now()
            });
            
            this.saveHistory();
        }
        
        showTyping() {
            this.isTyping = true;
            const typingHtml = `
                <div class="kura-ai-message kura-ai-message-bot kura-ai-typing" id="kura-ai-typing-indicator">
                    <div class="kura-ai-message-content">
                        <div class="kura-ai-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            
            $('#kura-ai-chatbot-messages').append(typingHtml);
            this.scrollToBottom();
        }
        
        hideTyping() {
            this.isTyping = false;
            $('#kura-ai-typing-indicator').remove();
        }
        
        sendAjaxMessage(message) {
            $.ajax({
                url: kuraAiChatbot.ajax_url,
                type: 'POST',
                data: {
                    action: 'kura_ai_chat_message',
                    message: message,
                    nonce: kuraAiChatbot.nonce
                },
                success: (response) => {
                    this.hideTyping();
                    
                    if (response.success) {
                        this.addMessage(response.data.response, 'bot');
                    } else {
                        this.addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                        console.error('Chatbot error:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    this.hideTyping();
                    this.addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
                    console.error('AJAX error:', error);
                }
            });
        }
        
        adjustInputHeight() {
            const textarea = $('#kura-ai-message-input')[0];
            if (textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            }
        }
        
        scrollToBottom() {
            const messagesContainer = $('#kura-ai-chatbot-messages');
            const container = messagesContainer[0];
            
            if (container) {
                // Use smooth scrolling with animation
                messagesContainer.animate({
                    scrollTop: container.scrollHeight
                }, 300, 'swing');
            }
        }
        
        updateScrollIndicator() {
            const messagesContainer = $('#kura-ai-chatbot-messages');
            const container = messagesContainer[0];
            
            if (container) {
                const isScrolled = container.scrollTop > 20;
                messagesContainer.toggleClass('has-scroll', isScrolled);
            }
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        saveHistory() {
            // Keep only last 50 messages
            if (this.messageHistory.length > 50) {
                this.messageHistory = this.messageHistory.slice(-50);
            }
            
            localStorage.setItem('kura_ai_chat_history', JSON.stringify(this.messageHistory));
        }
        
        loadHistory() {
            try {
                const saved = localStorage.getItem('kura_ai_chat_history');
                if (saved) {
                    this.messageHistory = JSON.parse(saved);
                    
                    // Load recent messages (last 10)
                    const recentMessages = this.messageHistory.slice(-10);
                    const messagesContainer = $('#kura-ai-chatbot-messages');
                    
                    // Clear existing messages except welcome message
                    messagesContainer.find('.kura-ai-message:not(:first)').remove();
                    
                    let hasUserMessage = false;
                    recentMessages.forEach(msg => {
                        const timestamp = new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        const messageHtml = `
                            <div class="kura-ai-message kura-ai-message-${msg.type}">
                                <div class="kura-ai-message-content">
                                    <p>${this.escapeHtml(msg.content)}</p>
                                </div>
                                <div class="kura-ai-message-time">${timestamp}</div>
                            </div>
                        `;
                        messagesContainer.append(messageHtml);
                        
                        if (msg.type === 'user') {
                            hasUserMessage = true;
                        }
                    });
                    
                    // Hide preset prompts if there are user messages in history
                    if (hasUserMessage) {
                        messagesContainer.addClass('has-user-message');
                    }
                }
            } catch (e) {
                console.error('Error loading chat history:', e);
                localStorage.removeItem('kura_ai_chat_history');
            }
        }
        
        clearHistory() {
            this.messageHistory = [];
            localStorage.removeItem('kura_ai_chat_history');
            
            // Clear messages except welcome message
            const messagesContainer = $('#kura-ai-chatbot-messages');
            messagesContainer.find('.kura-ai-message:not(:first)').remove();
            messagesContainer.removeClass('has-user-message');
            
            // Show preset prompts again
            $('#kura-ai-preset-prompts').show();
        }
        
        resetChat() {
            // Show confirmation dialog
            if (confirm('Are you sure you want to reset the chat? This will clear all messages.')) {
                this.clearHistory();
                
                // Clear input field
                $('#kura-ai-message-input').val('');
                this.adjustInputHeight();
                
                // Scroll to top to show welcome message
                this.scrollToTop();
                
                // Show success feedback
                this.showTemporaryMessage('Chat has been reset', 'success');
            }
        }
        
        newChat() {
            // Clear input field and start fresh
            $('#kura-ai-message-input').val('').focus();
            this.adjustInputHeight();
            
            // Scroll to bottom for new conversation
            this.scrollToBottom();
            
            // Show temporary message
            this.showTemporaryMessage('Ready for a new conversation!', 'info');
        }
        
        scrollToTop() {
            const messagesContainer = $('#kura-ai-chatbot-messages');
            messagesContainer.animate({
                scrollTop: 0
            }, 300, 'swing');
        }
        
        showTemporaryMessage(text, type = 'info') {
            // Create temporary notification
            const notification = $(`
                <div class="kura-ai-temp-notification kura-ai-temp-${type}">
                    ${text}
                </div>
            `);
            
            // Add to header
            $('.kura-ai-chatbot-header').append(notification);
            
            // Animate in
            notification.fadeIn(200);
            
            // Remove after 2 seconds
            setTimeout(() => {
                notification.fadeOut(200, () => {
                    notification.remove();
                });
            }, 2000);
        }
    }
    
    // Initialize chatbot when DOM is ready
    if (typeof kuraAiChatbot !== 'undefined' && kuraAiChatbot.config && (kuraAiChatbot.config.enabled === true || kuraAiChatbot.config.enabled === '1' || kuraAiChatbot.config.enabled === 1)) {
        new KuraAIChatbot();
    }
});