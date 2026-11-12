// Pide confirmación antes de enviar formularios marcados con data-confirmar
// (por ejemplo, eliminar una categoría o un servicio).
// Va en un archivo externo porque la CSP no permite JavaScript dentro del HTML.
document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
  formulario.addEventListener('submit', function (evento) {
    if (!confirm(formulario.dataset.confirmar)) {
      evento.preventDefault();
    }
  });
});
