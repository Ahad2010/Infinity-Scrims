 

/**
 * Infinity Scrims — Owner Panel core JS
 * Theme toggle, sidebar collapse, partial loader, api() fetch wrapper, toast, mobile nav.
 * Every page loads this file (app.js everywhere, auth-guard.js + owner-common.js on protected pages).
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
    // Best-effort sync with backend, fail silently if unavailable
    Api.post('/auth/theme.php', { theme: next }).catch(() => {});
    document.querySelectorAll('[data-theme-label]').forEach(el => {
      el.textContent = next === 'dark' ? 'Dark' : 'Light';
    });
  }
};
Theme.init(); // run before first paint to avoid a flash of the wrong theme

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
SidebarState.init(); // run before first paint to avoid a flash of the wrong width

// ---------------------------------------------------------------
// API HELPER — JWT Bearer auth (stateless, no cookies). Uses its own
// owner_is_token/owner_is_user localStorage keys — completely separate
// from the User app's is_token/is_user, even on the same origin. This is
// what actually fixes "logging into Owner also logs the User app in".
// ---------------------------------------------------------------
const Api = {
  token() { return localStorage.getItem('owner_is_token') || ''; },
  user() { try { return JSON.parse(localStorage.getItem('owner_is_user') || 'null'); } catch { return null; } },
  setSession(data) {
    if (data.token) localStorage.setItem('owner_is_token', data.token);
    if (data.user) localStorage.setItem('owner_is_user', JSON.stringify(data.user));
  },
  clearSession() {
    localStorage.removeItem('owner_is_token');
    localStorage.removeItem('owner_is_user');
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

  /** For file uploads — build your own FormData, the auth header is added for you */
  async postForm(path, formData) {
    const res = await fetch(API_BASE + path, { method: 'POST', headers: Api.authHeaders(), body: formData });
    return handle(res);
  },
};

// ---------------------------------------------------------------
// ERROR MESSAGE TRANSLATION
// Backend error messages (fail() calls) are written in Roman Urdu for the
// player-facing app. The Owner Panel should read in English throughout, so
// known messages are translated here before ever reaching a toast/inline UI.
// ---------------------------------------------------------------
const ERROR_TRANSLATIONS = {
  'Aap is group ka hissa nahi hain.': "You're not part of this group.",
  'Aap is group mein message nahi bhej sakte.': "You can't send messages in this group.",
  'Aap khud ko remove nahi kar sakte. Team delete karein ya leadership transfer karein.': "You can't remove yourself. Delete the team or transfer leadership instead.",
  'Aap pehle se is team mein hain.': "You're already in this team.",
  'Aapka account band kar diya gaya hai. Support se rabta karein.': 'Your account has been suspended. Please contact support.',
  'Aapka payment proof pehle se review mein hai.': 'Your payment proof is already under review.',
  'Aapke paas is action ki ijazat nahi hai.': "You don't have permission to do this.",
  'Aapki team ka is scrim mein pehle se slot book hai.': 'Your team already has a slot booked in this scrim.',
  'Account nahi mila.': 'Account not found.',
  'Action sirf approve ya reject ho sakta hai.': 'Action must be either approve or reject.',
  'Booking nahi mili.': 'Booking not found.',
  'Cancel nahi ho saki.': 'Could not cancel.',
  'Code ghalat ya expire ho chuka hai.': 'This code is incorrect or has expired.',
  'Current password ghalat hai.': 'Current password is incorrect.',
  'Email valid nahi hai.': 'Please enter a valid email address.',
  'Email/username ya password ghalat hai.': 'Incorrect email/username or password.',
  'Entry nahi mili.': 'Entry not found.',
  'File save nahi ho saki.': 'The file could not be saved.',
  'File upload nahi hui. Dobara koshish karein.': 'File upload failed. Please try again.',
  'Ijazat nahi hai.': 'Permission denied.',
  'Is member ki active booking hai — pehle wo resolve karein.': 'This member has an active booking — resolve that first.',
  'Is scrim ki booking band ho chuki hai.': 'Booking is closed for this scrim.',
  'Join code ghalat hai.': 'Invalid join code.',
  'Kuch change karne ke liye dein.': 'Please provide something to update.',
  'Match date/time valid nahi.': 'Invalid match date/time.',
  'Match ka time guzar chuka hai.': "This match's time has already passed.",
  'Message bohat lamba hai (max 1000).': 'Message is too long (max 1000 characters).',
  'Naya password kam az kam 6 characters ka hona chahiye.': 'New password must be at least 6 characters.',
  'Password change karne ke liye current password zaroori hai.': 'Current password is required to change your password.',
  'Password kam az kam 6 characters ka hona chahiye.': 'Password must be at least 6 characters.',
  'Payment method valid nahi.': 'Invalid payment method.',
  'Payment nahi mila.': 'Payment not found.',
  'Pehle login karein.': 'Please log in first.',
  'Phone number valid nahi hai.': 'Please enter a valid phone number.',
  'Result nahi mila.': 'Result not found.',
  'Scrim id chahiye.': 'Scrim ID is required.',
  'scrim_id chahiye.': 'scrim_id is required.',
  'Scrim nahi mili.': 'Scrim not found.',
  'Session expire ho gaya. Page refresh karein.': 'Your session has expired. Please refresh the page.',
  'Sirf JPG, PNG ya WEBP allowed hai.': 'Only JPG, PNG or WEBP files are allowed.',
  'Sirf team captain hi ye edit kar sakta hai.': 'Only the team captain can edit this.',
  'Sirf team captain member remove kar sakta hai.': 'Only the team captain can remove members.',
  'Sirf team ka captain slot book kar sakta hai.': "Only the team's captain can book a slot.",
  'Slots 2 se 200 ke darmiyan hone chahiye.': 'Slots must be between 2 and 200.',
  'Team ka naam 3-60 characters ka hona chahiye.': 'Team name must be 3-60 characters.',
  'Team nahi mili.': 'Team not found.',
  'Thora ahista! Dobara koshish karein.': "You're going a bit fast — please try again shortly.",
  'Thora ruk kar dobara koshish karein.': 'Please wait a moment and try again.',
  'Ticket nahi mila.': 'Ticket not found.',
  'Update karne ke liye kuch nahi diya gaya.': 'Nothing was provided to update.',
  'Username 3-40 characters ka hona chahiye (letters, numbers, underscore).': 'Username must be 3-40 characters (letters, numbers, underscore).',
  'Yeh aapki booking nahi hai.': 'This is not your booking.',
  'Yeh booking pehle hi cancel hai.': 'This booking is already cancelled.',
  'Yeh booking pehle hi confirm hai.': 'This booking is already confirmed.',
  'Yeh email pehle hi verified hai.': 'This email is already verified.',
  'Yeh email pehle hi verified hai. Login karein.': 'This email is already verified. Please log in.',
  'Yeh email pehle se registered hai.': 'This email is already registered.',
  'Yeh member is team mein nahi hai.': 'This member is not in the team.',
  'Yeh payment pehle hi review ho chuka hai.': 'This payment has already been reviewed.',
  'Yeh result pehle hi publish hai.': 'This result is already published.',
  'Yeh result pehle hi publish ho chuka hai.': 'This result has already been published.',
  'Yeh slot abhi abhi kisi aur ne le liya. Doosra slot chunein.': 'This slot was just taken by someone else. Please choose another.',
  'Yeh slot number maujood nahi.': "This slot number doesn't exist.",
  'Yeh team name pehle se maujood hai.': 'This team name is already taken.',
  'Yeh username pehle se le liya gaya hai.': 'This username is already taken.',
  'Database se connect nahi ho saka. Server settings check karein.': 'Could not connect to the database. Please check server settings.',
  'Server mein masla ho gaya. Dobara koshish karein.': 'Something went wrong on the server. Please try again.',
};

function translateError(message) {
  if (!message) return message;
  if (ERROR_TRANSLATIONS[message]) return ERROR_TRANSLATIONS[message];
  if (message.startsWith('File bohat bari hai.')) return message.includes('Max ') ? 'File is too large. Max ' + message.split('Max ')[1] : 'File is too large.';
  if (message.startsWith('Yeh fields zaroori hain:')) return 'Required fields missing:' + message.split(':')[1];
  if (message.startsWith('Method not allowed.')) return message; // already English

  // Safety net: if this message wasn't in the dictionary above (e.g. a new
  // backend error added after this list was written), don't ever show raw
  // Roman Urdu — detect common Urdu/Hinglish words and fall back to a
  // generic English message instead.
  if (/\b(hai|hain|nahi|ka|ki|ke|kar|karein|kare|karo|ho|gaya|gayi|gaye|zaroori|ghalat|pehle|dobara|maujood)\b/i.test(message)) {
    return 'Something went wrong. Please try again.';
  }
  return message;
}

async function handle(res) {
  let data;
  try { data = await res.json(); }
  catch { throw new Error('Unexpected response from the server.'); }

  if (data.token || (data.data && data.data.token)) Api.setSession(data.data || data);
  if (data.data && data.data.user) Api.setSession(data.data);

  if (!data.success) {
    const err = new Error(translateError(data.message) || 'Something went wrong.');
    err.status = res.status;
    err.payload = data;
    throw err;
  }
  return data.data;
}

// ---------------------------------------------------------------
// TOAST
// ---------------------------------------------------------------
function toast(message, type = 'info') {
  let stack = document.querySelector('.toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.textContent = message;
  stack.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

// ---------------------------------------------------------------
// GLOBAL DROPDOWN DELEGATION — works for any .dropdown-trigger on the
// page, even ones added dynamically after this script first runs.
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
// GLOBAL HAMBURGER / SIDEBAR DELEGATION — queries the sidebar fresh at
// click time instead of caching a reference at header-load time.
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
    // Mobile: slide the sidebar in/out as an overlay drawer
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  } else {
    // Desktop: collapse to an icon-only rail, click again to expand
    SidebarState.toggle();
  }
});

// Close the mobile drawer the moment a nav link inside it is tapped.
document.addEventListener('click', (e) => {
  const navLink = e.target.closest('.sidebar-nav a');
  if (navLink) {
    document.querySelector('.sidebar')?.classList.remove('open');
    document.querySelector('.sidebar-overlay')?.classList.remove('open');
  }
});

// ---------------------------------------------------------------
// PARTIALS LOADER (sidebar / header / bottom-nav)
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
  if (selector === '#sidebar-mount' || selector === '#bottomnav-mount') {
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
// HEADER interactions (bell dropdown, user dropdown, logout)
// ---------------------------------------------------------------
function initHeaderInteractions() {
  const user = Api.user();
  if (user) {
    const initials = user.username.trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('');
    document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.username);
    document.querySelectorAll('[data-user-role]').forEach(el => el.textContent = user.role === 'owner' ? 'Owner' : 'Player');
    document.querySelectorAll('[data-user-initial]').forEach(el => el.textContent = initials);
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
  try {
    const data = await Api.get('/notifications.php');
    document.querySelectorAll('[data-notif-count]').forEach(el => {
      if (data.unread > 0) { el.textContent = data.unread; el.style.display = 'flex'; }
      else el.style.display = 'none';
    });
  } catch {}
}

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

// ---------------------------------------------------------------
// NOTIFICATIONS DRAWER — no categories/tabs, just one flat list.
// ---------------------------------------------------------------
const NotifDrawer = {
  items: [],
  loaded: false,
  inited: false,

  init() {
    if (this.inited) return; // header partial can reload; don't double-bind
    const bellBtn = document.getElementById('notifBellBtn');
    const drawer = document.getElementById('notifDrawer');
    const overlay = document.getElementById('notifDrawerOverlay');
    if (!bellBtn || !drawer || !overlay) return;
    this.inited = true;

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
    this.load(); // always refresh on open
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
      loadNotifBadge();
    } catch (err) {
      body.innerHTML = `<div class="empty-state">${escapeHtml(err.message || 'Could not load notifications.')}</div>`;
    }
  },

  render() {
    const body = document.getElementById('notifDrawerBody');
    if (!body) return;
    const list = this.items;
    body.innerHTML = list.map(n => {
      const ai = (typeof activityIcon === 'function') ? activityIcon(n.type) : { icon: '', cls: '' };
      return `
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
    }).join('') || `<div class="empty-state">No notifications here.</div>`;
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
// PAGE-CONTENT REVEAL — the CSS hides .page-content children until
// this runs (data-skeleton="off" on every owner page reveals them
// immediately; owner pages render their own inline skeleton
// placeholders instead of a generic overlay).
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
  setTimeout(() => {
    overlay.remove();
    content.classList.add('is-ready');
  }, 400);
}

// ---------------------------------------------------------------
// AUTO INIT — every page with these mount points calls this
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  initPageSkeleton();
  loadPartial('#sidebar-mount', 'partials/sidebar.html');
  loadPartial('#header-mount', 'partials/header.html');
  loadPartial('#bottomnav-mount', 'partials/bottom-nav.html');
});