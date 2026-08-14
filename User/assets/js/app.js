/**
 * Infinity Scrims — Core frontend JS
 * Theme toggle, partial loader, api() fetch wrapper, toast, mobile nav.
 * Every page loads this file (app.js everywhere, auth-guard.js on protected pages).
 */

// ---------------------------------------------------------------
// CONFIG — point this at your backend
// ---------------------------------------------------------------
const API_BASE = '/api';

// ---------------------------------------------------------------
// THEME
// ---------------------------------------------------------------
const Theme = {
  init() {
    const saved = localStorage.getItem('is_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
  },
  toggle() {
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('is_theme', next);
    Api.post('/auth/theme.php', { theme: next }).catch(() => {});
    document.querySelectorAll('[data-theme-label]').forEach(el => {
      el.textContent = next === 'dark' ? 'Dark' : 'Light';
    });
  }
};
Theme.init();

// ---------------------------------------------------------------
// SIDEBAR COLLAPSE (desktop icon-rail) — persisted across pages
// ---------------------------------------------------------------
const SidebarState = {
  init() {
    if (localStorage.getItem('is_sidebar_collapsed') === '1') {
      document.documentElement.classList.add('sidebar-collapsed');
    }
  },
  toggle() {
    const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
    localStorage.setItem('is_sidebar_collapsed', collapsed ? '1' : '0');
  }
};
SidebarState.init();

// ---------------------------------------------------------------
// API HELPER
// ---------------------------------------------------------------
const Api = {
  token() { return localStorage.getItem('is_token') || ''; },
  user() { try { return JSON.parse(localStorage.getItem('is_user') || 'null'); } catch { return null; } },
  setSession(data) {
    if (data.token) localStorage.setItem('is_token', data.token);
    if (data.user) localStorage.setItem('is_user', JSON.stringify(data.user));
  },
  clearSession() {
    localStorage.removeItem('is_token');
    localStorage.removeItem('is_user');
  },

  authHeaders(extra = {}) {
    const h = { ...extra };
    const t = Api.token();
    if (t) h['Authorization'] = 'Bearer ' + t;
    return h;
  },

  async get(path) {
    const res = await fetch(API_BASE + path, { headers: Api.authHeaders() });
    return handle(res);
  },

  async post(path, body) {
    const res = await fetch(API_BASE + path, {
      method: 'POST',
      headers: Api.authHeaders({ 'Content-Type': 'application/json' }),
      body: JSON.stringify(body || {}),
    });
    return handle(res);
  },

  async postForm(path, formData) {
    const res = await fetch(API_BASE + path, { method: 'POST', headers: Api.authHeaders(), body: formData });
    return handle(res);
  },
};

async function handle(res) {
  let data;
  try { data = await res.json(); }
  catch { throw new Error('Unexpected response from the server.'); }

  if (data.token || (data.data && data.data.token)) Api.setSession(data.data || data);
  if (data.data && data.data.user) Api.setSession(data.data);

  if (!data.success) {
    const err = new Error(data.message || 'Something went wrong.');
    err.status = res.status;
    err.payload = data;
    throw err;
  }
  return data.data;
}

// ---------------------------------------------------------------
// GLOBAL DROPDOWN DELEGATION
// ---------------------------------------------------------------
document.addEventListener('click', (e) => {
  const trigger = e.target.closest('.dropdown-trigger');
  if (trigger) {
    e.stopPropagation();
    const dd = document.getElementById(trigger.dataset.target);
    document.querySelectorAll('.dropdown.open').forEach(d => { if (d !== dd) d.classList.remove('open'); });
    if (dd) dd.classList.toggle('open');
    return;
  }
  document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
});

// ---------------------------------------------------------------
// GLOBAL HAMBURGER / SIDEBAR DELEGATION
// ---------------------------------------------------------------
function getOrCreateSidebarOverlay() {
  let overlay = document.querySelector('.sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    const app = document.querySelector('.app') || document.body;
    app.appendChild(overlay);
    overlay.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.remove('open');
      overlay.classList.remove('open');
    });
  }
  return overlay;
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('.hamburger')) return;
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;
  const overlay = getOrCreateSidebarOverlay();
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  } else {
    SidebarState.toggle();
  }
});

document.addEventListener('click', (e) => {
  const navLink = e.target.closest('.sidebar-nav a');
  if (navLink) {
    document.querySelector('.sidebar')?.classList.remove('open');
    document.querySelector('.sidebar-overlay')?.classList.remove('open');
  }
});

function toast(message, type = 'info') {
  let stack = document.querySelector('.toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }
  const already = Array.from(stack.children).some(el => el.dataset.msg === message);
  if (already) return;
  while (stack.children.length >= 3) stack.firstChild.remove();
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.textContent = message;
  el.dataset.msg = message;
  stack.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

// ---------------------------------------------------------------
// PARTIALS LOADER (sidebar / header)
// ---------------------------------------------------------------
async function loadPartial(selector, file) {
  const el = document.querySelector(selector);
  if (!el) return;
  try {
    const res = await fetch(file);
    el.innerHTML = await res.text();
    afterPartialLoad(selector);
  } catch (e) {
    console.error('Partial load failed:', file, e);
  }
}

function afterPartialLoad(selector) {
  if (selector === '#sidebar-mount') {
    highlightActiveNav();
  }
  if (selector === '#header-mount') {
    initHeaderInteractions();
  }
}

function highlightActiveNav() {
  const page = document.body.dataset.page;
  if (!page) return;
  document.querySelectorAll(`a[data-nav="${page}"]`).forEach(a => a.classList.add('active'));
}

// ---------------------------------------------------------------
// HEADER interactions (bell drawer, user dropdown, hamburger)
// ---------------------------------------------------------------
function initHeaderInteractions() {
  const user = Api.user();
  if (user) {
    const initials = user.username.trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('');
    document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.username);
    document.querySelectorAll('[data-user-role]').forEach(el => el.textContent = user.role === 'owner' ? 'Owner' : 'Player');
    const avatarSrc = user.avatar_url || (user.avatar ? API_BASE.replace(/\/api$/, '/uploads') + '/' + user.avatar : null);
    document.querySelectorAll('[data-user-initial]').forEach(el => {
      el.innerHTML = avatarSrc
        ? `<img src="${avatarSrc}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
        : initials;
    });
  }

  const logoutBtn = document.querySelector('[data-action="logout"]');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      try { await Api.get('/auth/logout.php'); } catch {}
      Api.clearSession();
      window.location.href = 'login.html';
    });
  }

  loadNotifBadge();
  NotifDrawer.init();
}

async function loadNotifBadge() {
  const paint = (unread) => {
    document.querySelectorAll('[data-notif-count]').forEach(el => {
      if (unread > 0) { el.textContent = unread; el.style.display = 'flex'; }
      else el.style.display = 'none';
    });
  };
  try {
    const data = await Api.get('/notifications.php');
    paint(data.unread);
  } catch {
    paint(0);
  }
}

// ---------------------------------------------------------------
// NOTIFICATIONS DRAWER — no categories/tabs, just one flat list.
// ---------------------------------------------------------------
const NotifDrawer = {
  items: [],
  loaded: false,

  init() {
    const bellBtn = document.getElementById('notifBellBtn');
    const drawer = document.getElementById('notifDrawer');
    const overlay = document.getElementById('notifDrawerOverlay');
    if (!bellBtn || !drawer || !overlay) return;

    bellBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.open();
    });
    document.getElementById('notifDrawerClose')?.addEventListener('click', () => this.close());
    overlay.addEventListener('click', () => this.close());
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.close();
    });

    document.getElementById('notifMarkReadBtn')?.addEventListener('click', () => this.markAllRead());
  },

  open() {
    document.getElementById('notifDrawer')?.classList.add('open');
    document.getElementById('notifDrawerOverlay')?.classList.add('open');
    if (!this.loaded) this.load();
  },

  close() {
    document.getElementById('notifDrawer')?.classList.remove('open');
    document.getElementById('notifDrawerOverlay')?.classList.remove('open');
  },

  async load() {
    const body = document.getElementById('notifDrawerBody');
    if (!body) return;
    body.innerHTML = skeleton(4);
    try {
      const data = await Api.get('/notifications.php');
      this.items = data.notifications || [];
      this.loaded = true;
      this.render();
      const footer = document.getElementById('notifDrawerFooter');
      if (footer) footer.textContent = this.items.length ? "You've reached the end of notifications" : '';
    } catch (err) {
      body.innerHTML = `<div class="empty-state">${escapeHtml(err.message || 'Could not load notifications.')}</div>`;
    }
  },

// "Today", "Yesterday", "2 days ago", or the actual date for older ones.
  dayLabel(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    const now = new Date();
    const startOfDay = (x) => new Date(x.getFullYear(), x.getMonth(), x.getDate());
    const diffDays = Math.round((startOfDay(now) - startOfDay(d)) / 86400000);
    if (diffDays <= 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  },

  render() {
    const body = document.getElementById('notifDrawerBody');
    if (!body) return;
    const list = this.items;
    if (!list.length) { body.innerHTML = `<div class="empty-state">No notifications here.</div>`; return; }

    let lastGroup = null;
    body.innerHTML = list.map(n => {
      const ai = activityIcon(n.type);
      const group = this.dayLabel(n.created_at);
      const groupHeader = group !== lastGroup
        ? `<div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.4px; padding:${lastGroup === null ? '0' : '14px'} 0 8px;">${group}</div>`
        : '';
      lastGroup = group;
      return `${groupHeader}
      <div class="notif-item" data-id="${n.id}">
        <div class="row-icon badge ${ai.cls}" style="width:40px;height:40px;">${ai.icon}</div>
        <div style="flex:1;">
          <div style="display:flex; justify-content:space-between; gap:10px;">
            <b style="font-size:14px;">${escapeHtml(n.title)}</b>
            <span style="font-size:11px; color:var(--text-muted); white-space:nowrap;">${n.ago}</span>
          </div>
${n.body ? `<div style="font-size:12px; color:var(--text-muted); margin:4px 0;">${escapeHtml(n.body)}</div>` : ''}
        </div>
        ${!n.is_read ? '<span class="unread-dot"></span>' : ''}
      </div>`;
    }).join('');
  },

  async markOneRead(id) {
    try { await Api.post('/notifications.php', { id }); } catch {}
  },

  async markAllRead() {
    try {
      await Api.post('/notifications.php', {});
      this.items.forEach(n => n.is_read = 1);
      this.render();
      document.querySelectorAll('[data-notif-count]').forEach(el => el.style.display = 'none');
      toast('All notifications marked as read.', 'success');
    } catch (err) {
      toast(err.message || 'Could not mark as read.', 'error');
    }
  },
};

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000;
  if (diff < 60) return 'just now';
  if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
  if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
  if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
  return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function money(n) { return 'PKR ' + Number(n).toLocaleString(); }

function skeleton(count = 3) {
  return Array.from({ length: count }).map(() => `<div class="skeleton skeleton-card"></div>`).join('');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function wait(ms = 450) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// ---------------------------------------------------------------
// GENERIC PAGE-LOAD SKELETON
// ---------------------------------------------------------------
function initPageSkeleton() {
  const content = document.querySelector('.page-content');
  if (!content) return;
  if (content.dataset.skeleton === 'off') {
    content.classList.add('is-ready');
    return;
  }
  const overlay = document.createElement('div');
  overlay.className = 'page-loading-overlay';
  overlay.innerHTML = `
    <div class="skeleton sk-title"></div>
    <div class="skeleton sk-sub"></div>
    <div class="sk-row">
      <div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>
    </div>
    <div class="sk-blocks">
      <div class="skeleton"></div><div class="skeleton"></div>
    </div>
  `;
  content.prepend(overlay);
  wait(500).then(() => {
    overlay.remove();
    content.classList.add('is-ready');
  });
}

// ---------------------------------------------------------------
// AUTO INIT
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  initPageSkeleton();
  loadPartial('#sidebar-mount', 'partials/sidebar.html');
  loadPartial('#header-mount', 'partials/header.html');
});