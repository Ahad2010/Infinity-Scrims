/**
 * Include at the top of protected pages (after app.js).
 * If the user isn't logged in, redirect to login.html.
 */
(function () {
  const user = Api.user();
  if (!user) {
    window.location.href = 'login.html';
  }
})();
