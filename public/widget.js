// public/widget.js — served from your SaaS platform
(function () {
  const script = document.currentScript;
  const tenant = script.getAttribute('data-tenant');
  const theme = script.getAttribute('data-theme') || '#000';
  const API = `https://${tenant}.yoursaas.io/api/chatbot`;

  // Inject scoped CSS
  const style = document.createElement('style');
  style.textContent = `
    #saas-chat-btn { background: ${theme}; }
    #saas-chat-header { background: ${theme}; }
  `;
  document.head.appendChild(style);

  // Inject widget HTML
  const div = document.createElement('div');
  div.innerHTML = `
    <div id="saas-chat-widget" style="position:fixed;bottom:24px;right:24px;z-index:9999">
      <button id="saas-chat-btn" onclick="window.__saasChatToggle()" style="
        width:56px;height:56px;border-radius:50%;border:none;cursor:pointer;
        color:#fff;font-size:24px;">💬</button>
      <div id="saas-chat-box" style="display:none;position:absolute;bottom:70px;right:0;
        width:360px;height:500px;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.15);
        background:#fff;display:flex;flex-direction:column;overflow:hidden">
        <div id="saas-chat-header" style="padding:16px;color:#fff;font-weight:600">AI Assistant</div>
        <div id="saas-chat-messages" style="flex:1;overflow-y:auto;padding:16px"></div>
        <div style="padding:12px;border-top:1px solid #eee;display:flex;gap:8px">
          <input id="saas-user-input" placeholder="Type a message..." style="
            flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:8px;outline:none">
          <button onclick="window.__saasChatSend()" style="
            padding:8px 16px;background:${theme};color:#fff;border:none;border-radius:8px;cursor:pointer">
            Send
          </button>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(div);

  let conversationId = null;

  window.__saasChatToggle = async function () {
    const box = document.getElementById('saas-chat-box');
    box.style.display = box.style.display === 'none' ? 'flex' : 'none';
    if (!conversationId) {
      const res = await fetch(`${API}/conversation/start`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page_url: window.location.href })
      });
      const data = await res.json();
      conversationId = data.conversation_id;
      appendMsg('assistant', 'Hi! How can I help you today?');
    }
  };

  window.__saasChatSend = async function () {
    const input = document.getElementById('saas-user-input');
    const msg = input.value.trim();
    if (!msg) return;
    appendMsg('user', msg);
    input.value = '';
    appendMsg('assistant', '...', 'typing-indicator');
    const res = await fetch(`${API}/message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id: conversationId, message: msg })
    });
    const data = await res.json();
    document.getElementById('typing-indicator')?.remove();
    appendMsg('assistant', data.answer);
  };

  function appendMsg(role, text, id = null) {
    const box = document.getElementById('saas-chat-messages');
    const div = document.createElement('div');
    div.style.cssText = `margin:8px 0;padding:10px 14px;border-radius:12px;max-width:80%;
      ${role === 'user' ? `margin-left:auto;background:${theme};color:#fff;` : 'background:#f4f4f5;'}`;
    div.textContent = text;
    if (id) div.id = id;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
  }
})();
