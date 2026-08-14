/**
 * Owner panel ke sab pages yeh include karein (app.js + auth-guard.js ke baad).
 * - Owner role check (agar normal user login ho gaya to bahar nikal deta hai)
 * - Sidebar mein Pending Approvals badge count
 */
(function () {
  const user = Api.user();
  if (user && user.role !== 'owner') {
    toast('This is the Owner Panel — your account is not an owner account.', 'error');
    setTimeout(() => window.location.href = 'login.html', 1200);
  }
})();

async function loadPendingBadge() {
  try {
    const data = await Api.get('/payments/pending.php?status=pending');
    document.querySelectorAll('[data-pending-count]').forEach(el => {
      if (data.stats.pending > 0) { el.textContent = data.stats.pending; el.style.display = 'flex'; }
      else el.style.display = 'none';
    });
  } catch {}
}
document.addEventListener('DOMContentLoaded', () => setTimeout(loadPendingBadge, 400));
