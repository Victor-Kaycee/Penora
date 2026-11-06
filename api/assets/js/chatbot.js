(() => {
  'use strict';

  /* ======================================================================
     Config
     ====================================================================== */
  const STORAGE_KEY   = 'nova-chat-history-v1';
  const API_KEY_STORAGE = 'nova-api-key';
  const BOT_NAME = 'Penora AI Assistant';
  const TYPING_DELAY = [500, 1100];   // ms range before bot "types" (rule-based mode only)
  const NUDGE_DELAY = 4000;           // ms before proactive nudge appears

  // Real AI backend — Anthropic Messages API called directly from the browser.
  // Needs the user's own API key (entered via the settings gear icon).
  const AI_MODEL = 'claude-sonnet-5';
  const AI_ENDPOINT = 'https://api.anthropic.com/v1/messages';
  const AI_SYSTEM_PROMPT =
    "You are Penora AI Assistant, a friendly, concise support assistant embedded in a website's chat widget. " +
    "Answer clearly and helpfully. Keep replies conversational and under ~120 words unless the user asks for more detail.";

  const WELCOME_MESSAGE =
    "Hi, I'm Penora AI Assistant 👋 Add your Anthropic API key in settings (gear icon above) and I can answer anything. Without a key, I'll stick to canned demo replies about pricing, hours, and support.";

  const QUICK_REPLIES = ['Pricing', 'Support hours', 'Talk to a human'];

  // Simple keyword → response rule set. First match wins.
  const RULES = [
    { keys: ['hello', 'hi', 'hey', 'yo'], reply: "Hey there! What can I help you with today?" },
    { keys: ['pricing', 'price', 'cost', 'plan'], reply: "We've got three plans: Starter (free), Pro ($12/mo), and Team ($29/mo per seat). Want a feature breakdown?" },
    { keys: ['hours', 'support hours', 'open'], reply: "Our support team is online Monday–Friday, 9am–6pm. Outside those hours, messages are answered first thing the next morning." },
    { keys: ['human', 'agent', 'person', 'talk to'], reply: "Got it — I'll flag this for a teammate. In this demo there's no live handoff, but in production this is where I'd open a ticket for you." },
    { keys: ['bug', 'error', 'broken', 'issue', 'problem'], reply: "Sorry to hear that. Could you tell me a bit more about what happened, and which page you were on?" },
    { keys: ['thanks', 'thank you', 'thx'], reply: "Anytime! Let me know if anything else comes up." },
    { keys: ['bye', 'goodbye', 'see you'], reply: "Take care! I'll be right here if you need anything else." },
    { keys: ['who are you', 'what are you'], reply: "I'm Penora AI Assistant — a front-end demo chatbot built for just rule-based replies." },
  ];
  const FALLBACKS = [
    "I don't have a scripted answer for that yet — try asking about pricing, support hours, or say hello.",
    "Hmm, I'm not sure about that one. I can help with pricing, support hours, or connecting you to a human.",
  ];

  /* ======================================================================
     DOM refs
     ====================================================================== */
  const widget      = document.getElementById('novaWidget');
  const launcher     = document.getElementById('novaLauncher');
  const panel        = document.getElementById('novaPanel');
  const closeBtn     = document.getElementById('novaCloseBtn');
  const clearBtn     = document.getElementById('novaClearBtn');
  const messagesEl   = document.getElementById('novaMessages');
  const form         = document.getElementById('novaForm');
  const input        = document.getElementById('novaInput');
  const sendBtn      = document.getElementById('novaSend');
  const unreadBadge  = document.getElementById('novaUnread');
  const quickReplies = document.getElementById('novaQuickReplies');
  const nudge        = document.getElementById('novaNudge');
  const nudgeClose   = document.getElementById('novaNudgeClose');
  const settingsBtn   = document.getElementById('novaSettingsBtn');
  const settingsPanel = document.getElementById('novaSettings');
  const apiKeyInput   = document.getElementById('novaApiKey');
  const settingsSave  = document.getElementById('novaSettingsSave');
  const settingsClear = document.getElementById('novaSettingsClear');
  const settingsStatus = document.getElementById('novaSettingsStatus');
  const statusLine    = document.querySelector('.nova-status');
  const footer        = document.getElementById('novaFooter');

  let isOpen = false;
  let unread = 0;
  let history = loadHistory();
  let apiKey = localStorage.getItem(API_KEY_STORAGE) || '';

  /* ======================================================================
     Persistence
     ====================================================================== */
  function loadHistory() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  }

  function saveHistory() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
    } catch {
      /* storage unavailable — fail silently, chat still works in-memory */
    }
  }

  /* ======================================================================
     Rendering
     ====================================================================== */
  function formatTime(date) {
    return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  function renderMessage(msg, animate = true) {
    const wrap = document.createElement('div');
    wrap.style.display = 'flex';
    wrap.style.flexDirection = 'column';
    wrap.style.alignItems = msg.from === 'user' ? 'flex-end' : 'flex-start';

    const bubble = document.createElement('div');
    bubble.className = `nova-msg ${msg.from === 'user' ? 'nova-msg-user' : 'nova-msg-bot'}`;
    bubble.textContent = msg.text;
    if (!animate) bubble.style.animation = 'none';

    const time = document.createElement('span');
    time.className = 'nova-msg-time';
    time.textContent = formatTime(new Date(msg.at));

    wrap.appendChild(bubble);
    wrap.appendChild(time);
    messagesEl.appendChild(wrap);
    scrollToBottom();
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function renderQuickReplies(list) {
    quickReplies.innerHTML = '';
    list.forEach(label => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'nova-chip';
      chip.textContent = label;
      chip.addEventListener('click', () => sendMessage(label));
      quickReplies.appendChild(chip);
    });
  }

  function showTypingIndicator() {
    const el = document.createElement('div');
    el.className = 'nova-typing';
    el.id = 'novaTypingIndicator';
    el.innerHTML = '<span></span><span></span><span></span>';
    messagesEl.appendChild(el);
    scrollToBottom();
    return el;
  }

  /* ======================================================================
     Conversation logic
     ====================================================================== */
  function addMessage(from, text) {
    const msg = { from, text, at: Date.now() };
    history.push(msg);
    saveHistory();
    renderMessage(msg);
  }

  function getRuleBasedReply(userText) {
    const lower = userText.toLowerCase();
    for (const rule of RULES) {
      if (rule.keys.some(k => lower.includes(k))) return rule.reply;
    }
    return FALLBACKS[Math.floor(Math.random() * FALLBACKS.length)];
  }

  // Calls the real Anthropic API directly from the browser using the user's
  // own key. This is a client-side demo pattern: the key lives in this
  // browser's localStorage and is sent with every request. Fine for local/
  // personal use — for a production site, proxy this through your own
  // backend instead of shipping the key to the client.
  async function getAIReply(conversationHistory) {
    const messages = conversationHistory
      .filter(m => m.from === 'user' || m.from === 'bot')
      .map(m => ({ role: m.from === 'user' ? 'user' : 'assistant', content: m.text }));

    const res = await fetch(AI_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': apiKey,
        'anthropic-version': '2023-06-01',
        'anthropic-dangerous-direct-browser-access': 'true',
      },
      body: JSON.stringify({
        model: AI_MODEL,
        max_tokens: 600,
        system: AI_SYSTEM_PROMPT,
        messages,
      }),
    });

    if (!res.ok) {
      let detail = '';
      try { detail = (await res.json())?.error?.message || ''; } catch {}
      throw new Error(detail || `Request failed (${res.status})`);
    }

    const data = await res.json();
    const text = (data.content || [])
      .filter(block => block.type === 'text')
      .map(block => block.text)
      .join('\n')
      .trim();
    return text || "I got an empty response — could you try rephrasing that?";
  }

  async function sendMessage(text) {
    const trimmed = text.trim();
    if (!trimmed) return;

    addMessage('user', trimmed);
    input.value = '';
    autoResizeInput();
    quickReplies.innerHTML = '';

    const typingEl = showTypingIndicator();
    sendBtn.disabled = true;

    try {
      let reply;
      if (apiKey) {
        reply = await getAIReply(history);
      } else {
        const delay = TYPING_DELAY[0] + Math.random() * (TYPING_DELAY[1] - TYPING_DELAY[0]);
        await new Promise(r => setTimeout(r, delay));
        reply = getRuleBasedReply(trimmed);
      }
      typingEl.remove();
      addMessage('bot', reply);

      if (!isOpen) {
        unread += 1;
        unreadBadge.textContent = String(unread);
        unreadBadge.hidden = false;
      }
    } catch (err) {
      typingEl.remove();
      addMessage('bot', `Sorry — I couldn't reach the AI backend (${err.message}). Check your API key in settings, or clear it to fall back to demo replies.`);
    } finally {
      sendBtn.disabled = false;
    }
  }

  /* ======================================================================
     Panel open / close
     ====================================================================== */
  function openPanel() {
    isOpen = true;
    widget.classList.add('open');
    launcher.setAttribute('aria-expanded', 'true');
    panel.setAttribute('aria-hidden', 'false');
    unread = 0;
    unreadBadge.hidden = true;
    hideNudge();
    setTimeout(() => input.focus(), 350);
    scrollToBottom();
  }

  function closePanel() {
    isOpen = false;
    widget.classList.remove('open');
    launcher.setAttribute('aria-expanded', 'false');
    panel.setAttribute('aria-hidden', 'true');
  }

  function togglePanel() {
    isOpen ? closePanel() : openPanel();
  }

  /* ======================================================================
     Nudge bubble
     ====================================================================== */
  function showNudge() {
    if (isOpen || sessionStorage.getItem('nova-nudge-dismissed')) return;
    nudge.classList.add('show');
  }
  function hideNudge() {
    nudge.classList.remove('show');
  }

  /* ======================================================================
     Settings drawer / API key
     ====================================================================== */
  function updateModeUI() {
    if (apiKey) {
      statusLine.innerHTML = '<i class="nova-status-dot"></i>Online — AI mode';
      footer.textContent = 'Penora AI Assistant is answering with Claude via your API key.';
    } else {
      statusLine.innerHTML = '<i class="nova-status-dot"></i>Online — replies instantly';
      footer.textContent = 'Penora AI Assistant is a scripted demo assistant, not a live agent.';
    }
  }

  function toggleSettings() {
    const willOpen = !settingsPanel.classList.contains('open');
    settingsPanel.classList.toggle('open', willOpen);
    settingsPanel.setAttribute('aria-hidden', String(!willOpen));
    settingsBtn.setAttribute('aria-expanded', String(willOpen));
    if (willOpen) {
      apiKeyInput.value = apiKey;
      setTimeout(() => apiKeyInput.focus(), 200);
    }
  }

  function saveApiKey() {
    const value = apiKeyInput.value.trim();
    if (!value) {
      settingsStatus.textContent = 'Enter a key first, or use Remove key.';
      settingsStatus.classList.add('error');
      return;
    }
    apiKey = value;
    localStorage.setItem(API_KEY_STORAGE, apiKey);
    settingsStatus.textContent = 'Saved — Penora AI Assistant will now answer with Claude.';
    settingsStatus.classList.remove('error');
    updateModeUI();
  }

  function clearApiKey() {
    apiKey = '';
    apiKeyInput.value = '';
    localStorage.removeItem(API_KEY_STORAGE);
    settingsStatus.textContent = 'Key removed — back to demo replies.';
    settingsStatus.classList.remove('error');
    updateModeUI();
  }

  /* ======================================================================
     Input UX
     ====================================================================== */
  function autoResizeInput() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';
  }

  /* ======================================================================
     Init
     ====================================================================== */
  function init() {
    if (history.length === 0) {
      addMessage('bot', WELCOME_MESSAGE);
      renderQuickReplies(QUICK_REPLIES);
    } else {
      history.forEach(msg => renderMessage(msg, false));
    }

    launcher.addEventListener('click', togglePanel);
    closeBtn.addEventListener('click', closePanel);

    clearBtn.addEventListener('click', () => {
      history = [];
      saveHistory();
      messagesEl.innerHTML = '';
      addMessage('bot', WELCOME_MESSAGE);
      renderQuickReplies(QUICK_REPLIES);
    });

    form.addEventListener('submit', e => {
      e.preventDefault();
      sendMessage(input.value);
    });

    input.addEventListener('input', autoResizeInput);
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(input.value);
      }
    });

    nudgeClose.addEventListener('click', () => {
      hideNudge();
      sessionStorage.setItem('nova-nudge-dismissed', '1');
    });

    settingsBtn.addEventListener('click', toggleSettings);
    settingsSave.addEventListener('click', saveApiKey);
    settingsClear.addEventListener('click', clearApiKey);
    apiKeyInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); saveApiKey(); }
    });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && isOpen) {
        if (settingsPanel.classList.contains('open')) toggleSettings();
        else closePanel();
      }
    });

    updateModeUI();
    setTimeout(showNudge, NUDGE_DELAY);
  }

  init();
})();
