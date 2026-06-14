/**
 * Chat Utility Class
 * Handles fetching, sending, and rendering messages safely (XSS protection).
 */
class ChatUtil {
    constructor(routes) {
        this.routes = routes;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!this.csrfToken) {
            console.error('CSRF Token not found!');
            // Only alert once to avoid spamming
            if (!window.csrfAlertShown) {
                alert('System Error: CSRF Token missing. Please refresh the page.');
                window.csrfAlertShown = true;
            }
        }
    }

    /**
     * Render a single message safely using textContent to prevent XSS.
     * @param {Object} m - Message object
     * @param {Object} currentUser - Current user object (optional, for styling own messages)
     * @returns {HTMLElement}
     */
    createMessageElement(m, isSelf) {
        const div = document.createElement('div');
        div.className = 'chat-message ' + (isSelf ? 'self' : 'other');

        const senderEl = document.createElement('div');
        senderEl.className = 'sender';
        senderEl.textContent = m.user.name || m.user.username;

        const bubbleEl = document.createElement('div');
        bubbleEl.className = 'chat-bubble';
        bubbleEl.textContent = m.message;

        const timeEl = document.createElement('small');
        timeEl.className = 'timestamp';
        // Format date logic: HH:MM
        const date = new Date(m.created_at);
        timeEl.textContent = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Append timestamp inside bubble
        bubbleEl.appendChild(timeEl);

        div.appendChild(senderEl);
        div.appendChild(bubbleEl);

        return div;
    }

    async fetchMessages(sessionId, lastId = 0) {
        if (!sessionId) return [];
        try {
            let url = this.routes.messages.replace('__ID__', sessionId);
            if (lastId) {
                url += (url.includes('?') ? '&' : '?') + 'last_id=' + lastId;
            }
            const res = await fetch(url);
            return await res.json();
        } catch (e) {
            console.error('Fetch error:', e);
            return [];
        }
    }

    async sendMessage(url, payload) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payload)
            });
            if (!res.ok) {
                let errorMsg = 'Failed to send';
                try {
                    const err = await res.json();
                    errorMsg = err.error || errorMsg;
                } catch (jsonErr) {
                    // Response is not JSON (likely 419 or 500 HTML page)
                    errorMsg = `Server Error (${res.status}): ${res.statusText}`;
                    console.error('Non-JSON error response:', res);
                }
                throw new Error(errorMsg);
            }
            return await res.json();
        } catch (e) {
            console.error('Send error:', e);
            throw e;
        }
    }
}

// Global helper for popup initialization
const ChatPopup = {
    init: function (config) {
        // config: { activeUrl, sendUrl, popupId, bodyId, msgsId, inputId, sendBtnId, toggleId, toggleVisId, csrfToken }
        let activeSession = null;
        let lastMessageId = 0;
        let popupVisible = localStorage.getItem(config.storageKey) !== 'false';

        const popup = document.getElementById(config.popupId);
        const body = document.getElementById(config.bodyId);
        const msgsEl = document.getElementById(config.msgsId);
        const input = document.getElementById(config.inputId);
        const sendBtn = document.getElementById(config.sendBtnId);
        const toggleBtn = document.getElementById(config.toggleId);
        const toggleVisBtn = document.getElementById(config.toggleVisId);

        // Styling init
        if (toggleVisBtn) toggleVisBtn.textContent = popupVisible ? '👁' : '🙈';
        if (popup) popup.style.display = 'none'; // Initial hide until poll loads

        const chatUtil = new ChatUtil({});

        // Helper to determine if message is from "me" or "other"
        // If I am Admin: ME is user_id != session.user_id (Assuming session.user_id is always the customer)
        // If I am User: ME is user_id == session.user_id
        function isMsgSelf(m) {
            if (!activeSession) return false;
            const isCustomer = (m.user_id === activeSession.user_id);
            return config.isAdmin ? !isCustomer : isCustomer;
        }

        function appendMessages(list) {
            if (!list || list.length === 0) return;

            list.forEach(m => {
                // Duplicate check just in case
                if (m.id <= lastMessageId) return;

                const isSelf = isMsgSelf(m);
                const el = chatUtil.createMessageElement(m, isSelf);
                msgsEl.appendChild(el);

                if (m.id > lastMessageId) lastMessageId = m.id;
            });
            body.scrollTop = body.scrollHeight;
        }

        async function pollActive() {
            try {
                let url = config.activeUrl;
                if (lastMessageId) {
                    url += (url.includes('?') ? '&' : '?') + 'last_id=' + lastMessageId;
                }

                const res = await fetch(url);
                const data = await res.json();

                if (data.active) {
                    // New session detected? Reset if session ID changed
                    if (activeSession && activeSession.id !== data.session.id) {
                        msgsEl.innerHTML = '';
                        lastMessageId = 0;
                    }

                    activeSession = data.session;
                    appendMessages(data.messages);

                    if (popupVisible && popup) {
                        popup.style.display = 'block';
                    }
                } else if (popup) {
                    popup.style.display = 'none';
                    activeSession = null;
                    lastMessageId = 0;
                }

                // Return data for external use (e.g. notifications)
                return data;
            } catch (e) {
                console.error('Poll error:', e);
            }
        }

        if (sendBtn && input) {
            sendBtn.addEventListener('click', async (e) => {
                e.preventDefault(); // Prevent form submission if inside form
                console.log('Send button clicked');

                if (!activeSession) {
                    console.error('No active session found');
                    return;
                }
                const text = input.value.trim();
                if (!text) return;

                try {
                    console.log('Sending message:', text);
                    await chatUtil.sendMessage(config.sendUrl, {
                        help_session_id: activeSession.id,
                        message: text
                    });
                    input.value = '';
                    pollActive(); // Fetch immediately
                } catch (e) {
                    console.error('Send failed:', e);
                    alert('Error: ' + e.message);
                }
            });
        } else {
            console.warn('Send button or input not found for popup:', config.popupId);
        }

        if (toggleBtn && body) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Toggle chat body clicked');
                body.style.display = body.style.display === 'none' ? 'block' : 'none';
            });
        } else {
            console.warn('Toggle button or body not found:', config.toggleId);
        }

        if (toggleVisBtn) {
            toggleVisBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Toggle visibility clicked');
                popupVisible = !popupVisible;
                localStorage.setItem(config.storageKey, popupVisible);
                if (popup) popup.style.display = popupVisible && activeSession ? 'block' : 'none';
                toggleVisBtn.textContent = popupVisible ? '👁' : '🙈';
            });
        }

        // Return poll function so caller can set interval
        return pollActive;
    }
};
