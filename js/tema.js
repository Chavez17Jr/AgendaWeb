// Modo claro / oscuro.
// Se carga en el <head> para aplicar el tema guardado ANTES de pintar la página (sin parpadeo).
(function () {
  var guardado = null;
  try { guardado = localStorage.getItem('theme'); } catch (e) {}
  if (guardado === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }

  function esOscuro() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('btn-theme');
    if (!boton) return;

    function actualizarTexto() {
      boton.textContent = esOscuro() ? '☀️ Modo Claro' : '🌙 Modo Oscuro';
    }
    actualizarTexto();

    boton.addEventListener('click', function () {
      if (esOscuro()) {
        document.documentElement.removeAttribute('data-theme');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
      try { localStorage.setItem('theme', esOscuro() ? 'dark' : 'light'); } catch (e) {}
      actualizarTexto();
    });
  });
})();
