/**
 * Demo session seed — only runs when no real session exists in localStorage
 * (i.e. no backend login has happened yet). This lets pages open for design
 * review without a backend, using a demo user. As soon as a real login via
 * /auth/login.php succeeds, that real session overrides this demo one.
 */
(function () {
  if (!localStorage.getItem('is_user')) {
    localStorage.setItem('is_user', JSON.stringify({
      id: 1,
      username: 'Ahad Plays',
      email: 'ahadplays@gmail.com',
      role: 'user',
      player_id: '#INF-10023',
    }));
    localStorage.setItem('is_token', 'demo-mode-no-real-token');
  }
})();