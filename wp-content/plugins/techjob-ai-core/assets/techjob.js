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
  const profileToggle = document.querySelector('.tj-profile-toggle');
  const profileLinks = document.getElementById('tj-profile-links');
  profileToggle?.addEventListener('click', () => {
    profileLinks.hidden = !profileLinks.hidden;
    profileToggle.setAttribute('aria-expanded', String(!profileLinks.hidden));
  });
  document.querySelectorAll('.tj-confirm').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(en ? 'Confirm this action?' : 'Bạn có chắc muốn thực hiện thao tác này?')) event.preventDefault();
  }));
  document.querySelectorAll('form[method="post"]:not(.tj-confirm)').forEach(form => form.addEventListener('submit', () => {
    if (form.checkValidity()) form.querySelectorAll('[type="submit"]').forEach(button => { button.disabled = true; });
  }));
  document.querySelectorAll('[data-tj-tabs]').forEach(tabSet => {
    const tabs = Array.from(tabSet.querySelectorAll('[role="tab"][data-tj-tab]'));
    const panels = Array.from(tabSet.querySelectorAll('[role="tabpanel"][data-tj-panel]'));
    const activate = (tab, moveFocus = false) => {
      const key = tab.dataset.tjTab;
      tabs.forEach(item => {
        const selected = item === tab;
        item.setAttribute('aria-selected', String(selected));
        item.tabIndex = selected ? 0 : -1;
      });
      panels.forEach(panel => { panel.hidden = panel.dataset.tjPanel !== key; });
      if (moveFocus) tab.focus();
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', event => {
        let next = index;
        if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        activate(tabs[next], true);
      });
    });
  });
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
  let practiceTrigger = null;
  const setChatOpen = (open, trigger = null) => {
    if (!chat) return;
    chat.hidden = !open;
    launch?.setAttribute('aria-expanded', String(open));
    if (open) {
      practiceTrigger = trigger || launch;
      chat.querySelector('select, a, button')?.focus();
      return;
    }
    const returnTarget = [practiceTrigger, ...document.querySelectorAll('[data-tj-practice]'), document.querySelector('.tj-menu-toggle')]
      .find(node => node && node.getClientRects().length && !node.closest('[hidden]'));
    practiceTrigger = null;
    returnTarget?.focus();
  };
  const close = () => setChatOpen(false);
  launch?.addEventListener('click', () => setChatOpen(chat.hidden, launch));
  document.querySelectorAll('[data-tj-practice]').forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    const fromNav = button.matches('.tj-nav-practice');
    if (fromNav) {
      document.querySelector('.nav-links')?.classList.remove('tj-open');
      menu?.setAttribute('aria-expanded', 'false');
    }
    if (chat?.hidden) setChatOpen(true, fromNav ? menu : button);
  }));
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
  const revealItems = Array.from(document.querySelectorAll('.tj-public [data-tj-reveal]'));
  if (revealItems.length && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    try {
      const revealObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.dataset.tjMotionState = 'visible';
          revealObserver.unobserve(entry.target);
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
      revealItems.forEach((element, index) => {
        element.dataset.tjMotionState = 'pending';
        element.style.setProperty('--tj-motion-delay', String((index % 3) * 70) + 'ms');
        revealObserver.observe(element);
      });
      document.documentElement.classList.add('tj-motion-ready');
    } catch {
      revealItems.forEach(element => {
        delete element.dataset.tjMotionState;
        element.style.removeProperty('--tj-motion-delay');
      });
    }
  }
})();
