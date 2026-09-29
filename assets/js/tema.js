(() => {
  let theme;
  try { theme = localStorage.getItem('stocksense-tema'); } catch {}
  document.documentElement.dataset.tema = ['claro','escuro'].includes(theme)
    ? theme : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro';
})();
