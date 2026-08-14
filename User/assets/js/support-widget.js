/**
 * Infinity Scrims — AI Support Widget
 * Floating button + panel on every logged-in page. Posts to /support/ask.php.
 * If the backend escalates (no AI key configured, or the AI can't resolve it),
 * shows a WhatsApp button with the question pre-filled — nothing ever dead-ends.
 */
const SupportWidget = (() => {
  const TICKET_TTL_MS = 24 * 60 * 60 * 1000; // 24 hours
  let ticketId = loadSavedTicketId();
  let opened = false;

  function loadSavedTicketId() {
    try {
      const raw = localStorage.getItem('is_support_ticket');
      if (!raw) return null;
      const saved = JSON.parse(raw);
      if (!saved.id || !saved.ts || Date.now() - saved.ts > TICKET_TTL_MS) {
        localStorage.removeItem('is_support_ticket');
        return null;
      }
      return saved.id;
    } catch { return null; }
  }

  function saveTicketId(id) {
    if (!id) return;
    localStorage.setItem('is_support_ticket', JSON.stringify({ id, ts: Date.now() }));
  }

  const ICON_CHAT = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
  const ICON_CLOSE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';

  async function init() {
    const fab = document.getElementById('supportFab');
    const panel = document.getElementById('supportPanel');
    if (!fab || !panel) return;

    fab.addEventListener('click', () => {
      if (panel.classList.contains('open')) close();
      else open();
    });
    document.getElementById('supportCloseBtn').addEventListener('click', () => close());
    document.getElementById('supportSendBtn').addEventListener('click', sendMessage);
    document.getElementById('supportInput').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') sendMessage();
    });
  }

function open() {
    document.getElementById('supportPanel').classList.add('open');
    const fab = document.getElementById('supportFab');
    fab.classList.add('is-open');
    fab.innerHTML = ICON_CLOSE;
    fab.title = 'Close';
    if (!opened) {
      opened = true;
      if (ticketId) {
        loadHistory(ticketId);
      } else {
        addBotMessage("Hi! I'm the Infinity Scrims assistant. Ask me anything about booking slots, payments, teams, or scrims.");
      }
    }
  }

  async function loadHistory(id) {
    try {
      const data = await Api.get('/support/history.php?ticket_id=' + id);
      if (!data.messages || !data.messages.length) {
        addBotMessage("Hi! I'm the Infinity Scrims assistant. Ask me anything about booking slots, payments, teams, or scrims.");
        return;
      }
      data.messages.forEach(m => {
        if (m.sender === 'user') addUserMessage(m.message);
        else addBotMessage(m.message);
      });
    } catch {
      // Couldn't load old ticket (maybe expired server-side) — start fresh
      ticketId = null;
      localStorage.removeItem('is_support_ticket');
      addBotMessage("Hi! I'm the Infinity Scrims assistant. Ask me anything about booking slots, payments, teams, or scrims.");
    }
  }

  function close() {
    document.getElementById('supportPanel').classList.remove('open');
    const fab = document.getElementById('supportFab');
    fab.classList.remove('is-open');
    fab.innerHTML = ICON_CHAT;
    fab.title = 'Need help?';
  }

  function scrollToBottom() {
    const box = document.getElementById('supportMessages');
    box.scrollTop = box.scrollHeight;
  }

  function addUserMessage(text) {
    const box = document.getElementById('supportMessages');
    const row = document.createElement('div');
    row.className = 'support-msg-row user';
    row.innerHTML = `<div class="support-msg-bubble">${escapeHtml(text)}</div>`;
    box.appendChild(row);
    scrollToBottom();
  }

  const BOT_AVATAR = '<svg viewBox="0 0 48 24" width="20" height="10" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4M40 12h6M46 12l-4-3M46 12l-4 3"/><path d="M17 6c-4.5 0-7.5 2.8-7.5 6s3 6 7.5 6c6 0 7-12 14-12s7.5 2.8 7.5 6-3 6-7.5 6c-7 0-8-12-14-12z"/></svg>';

  function addBotMessage(text) {
    const box = document.getElementById('supportMessages');
    const row = document.createElement('div');
    row.className = 'support-msg-row';
    row.innerHTML = `<div class="support-msg-avatar">${BOT_AVATAR}</div><div class="support-msg-bubble">${escapeHtml(text)}</div>`;
    box.appendChild(row);
    scrollToBottom();
  }

  function showTyping() {
    const box = document.getElementById('supportMessages');
    const row = document.createElement('div');
    row.className = 'support-msg-row';
    row.id = 'supportTypingRow';
    row.innerHTML = `<div class="support-msg-avatar">${BOT_AVATAR}</div><div class="support-msg-bubble"><div class="support-typing"><span></span><span></span><span></span></div></div>`;
    box.appendChild(row);
    scrollToBottom();
  }

  function hideTyping() {
    document.getElementById('supportTypingRow')?.remove();
  }

  function showWhatsappButton(link) {
    document.getElementById('supportWhatsappWrap').innerHTML = `
      <a href="${link}" target="_blank" class="support-whatsapp-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2zm5.83 14.13c-.24.68-1.4 1.32-1.94 1.4-.5.08-1.12.11-1.8-.12a16.5 16.5 0 0 1-1.66-.62c-2.92-1.26-4.83-4.2-4.98-4.4-.15-.19-1.19-1.58-1.19-3.02 0-1.43.75-2.14 1.02-2.43.26-.29.58-.36.77-.36h.55c.18 0 .42-.07.65.5.24.57.82 1.98.89 2.13.07.14.12.31.02.5-.09.19-.14.31-.28.47-.14.17-.29.37-.42.5-.14.14-.28.29-.12.57.16.28.71 1.17 1.53 1.9 1.05.94 1.94 1.23 2.22 1.37.28.14.44.12.61-.07.16-.19.7-.81.88-1.09.19-.28.37-.24.62-.14.26.09 1.65.78 1.93.92.28.14.47.21.54.33.07.12.07.68-.17 1.35z"/></svg>
        Chat on WhatsApp
      </a>`;
  }

  async function sendMessage() {
    const input = document.getElementById('supportInput');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    addUserMessage(text);
    document.getElementById('supportWhatsappWrap').innerHTML = '';
    showTyping();

    try {
const data = await Api.post('/support/ask.php', { message: text, ticket_id: ticketId || undefined });
      ticketId = data.ticket_id || ticketId;
      saveTicketId(ticketId);
      hideTyping();
      addBotMessage(data.answer);
      if (data.escalated && data.whatsapp_link) {
        showWhatsappButton(data.whatsapp_link);
      }
    } catch (err) {
      hideTyping();
      addBotMessage("Sorry, I couldn't reach support right now. Please try again in a moment.");
    }
  }

  return { open, close, init };
})();

window.SupportWidget = SupportWidget;
document.addEventListener('DOMContentLoaded', () => {
  loadPartial('#support-widget-mount', 'partials/support-widget.html').then(() => SupportWidget.init());
});