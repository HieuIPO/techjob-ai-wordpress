(() => {
  'use strict';
  const config = JSON.parse(document.getElementById('tj-config')?.textContent || '{}');
  const en = config.lang === 'en';
  const menu = document.querySelector('.tj-menu-toggle');
  menu?.addEventListener('click', () => {
    const open = document.querySelector('.nav-links')?.classList.toggle('tj-open');
    menu.setAttribute('aria-expanded', String(!!open));
  });
  document.querySelector('.tj-sidebar-toggle')?.addEventListener('click', (event) => {
    const hidden = document.body.classList.toggle('tj-sidebar-hidden');
    event.currentTarget.setAttribute('aria-expanded', String(!hidden));
  });
  document.querySelectorAll('.tj-confirm').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(en ? 'Confirm this action?' : 'Bạn có chắc muốn thực hiện thao tác này?')) event.preventDefault();
  }));
  document.querySelectorAll('form[method="post"]:not(.tj-confirm)').forEach(form => form.addEventListener('submit', () => {
    if (form.checkValidity()) form.querySelectorAll('[type="submit"]').forEach(button => { button.disabled = true; });
  }));
  const api = async (path, payload) => {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 40000);
    try {
      const response = await fetch(config.rest + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify(payload), signal: controller.signal });
      const body = await response.json();
      if (!response.ok) throw new Error(body.message || (en ? 'Request failed.' : 'Không thể xử lý yêu cầu.'));
      return body;
    } catch (error) {
      if (error.name === 'AbortError') throw new Error(en ? 'Request timed out. Please retry.' : 'Yêu cầu quá thời gian. Vui lòng thử lại.');
      throw error;
    } finally { clearTimeout(timer); }
  };
  const chat = document.getElementById('tj-chat');
  const launch = document.querySelector('.tj-chat-launch');
  const close = () => { if (chat) chat.hidden = true; launch?.setAttribute('aria-expanded', 'false'); launch?.focus(); };
  launch?.addEventListener('click', () => {
    chat.hidden = !chat.hidden;
    launch.setAttribute('aria-expanded', String(!chat.hidden));
    if (!chat.hidden) chat.querySelector('select, a, button')?.focus();
  });
  document.querySelector('.tj-chat-close')?.addEventListener('click', close);
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && chat && !chat.hidden) close(); });
  let session;
  const errorBox = document.getElementById('tj-ai-error');
  const answerForm = document.getElementById('tj-ai-answer');
  const startForm = document.getElementById('tj-ai-start');
  const messages = document.getElementById('tj-ai-messages');
  const append = (text, user = false) => {
    const node = document.createElement('p');
    node.className = user ? 'tj-message user' : 'tj-message';
    node.textContent = text;
    messages.append(node);
    node.scrollIntoView({ block: 'nearest' });
  };
  startForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = startForm.querySelector('button'); button.disabled = true;
    errorBox.textContent = '';
    try {
      const data = Object.fromEntries(new FormData(startForm));
      session = await api('ai/start', { ...data, language: config.lang });
      messages.replaceChildren(); append(session.message);
      const badge = document.getElementById('tj-ai-mode'); badge.hidden = false;
      badge.textContent = session.mode === 'demo' ? (en ? 'DEMO · fixed practice guide' : 'DEMO · hướng dẫn cố định') : 'Gemini AI';
      answerForm.hidden = false;
    } catch (error) { errorBox.textContent = error.message; }
    finally { button.disabled = false; }
  });
  answerForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const field = answerForm.querySelector('textarea');
    const button = answerForm.querySelector('button'); button.disabled = true;
    errorBox.textContent = '';
    try {
      const reply = await api('ai/answer', { id: session.id, step: session.step, answer: field.value });
      append(field.value, true); field.value = ''; session = reply; append(reply.message);
      answerForm.hidden = reply.done;
    } catch (error) { errorBox.textContent = error.message; }
    finally { button.disabled = false; }
  });
  document.getElementById('tj-ai-test')?.addEventListener('click', async event => {
    event.currentTarget.disabled = true;
    const result = document.getElementById('tj-ai-test-result');
    try { result.textContent = (await api('ai/test', {})).message; }
    catch (error) { result.textContent = error.message; }
    finally { document.getElementById('tj-ai-test').disabled = false; }
  });
})();
