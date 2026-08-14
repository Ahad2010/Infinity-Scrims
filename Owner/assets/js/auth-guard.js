/**
 * Protected pages ke top pe include karein (app.js ke baad).
 * Agar user logged in nahi to login.html pe bhej deta hai.
 */
(function () {
  const user = Api.user();
  if (!user) {
    window.location.href = 'login.html';
  }
})();
