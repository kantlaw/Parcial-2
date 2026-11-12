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

// Marca en verde los requisitos de contraseña que ya se cumplen.
// Es solo una ayuda visual: la validación real se hace en PHP (validar_clave).
var campoClave = document.getElementById('clave');
var listaRequisitos = document.getElementById('requisitos-clave');

if (campoClave && listaRequisitos) {
  var reglas = {
    largo: function (c) { return c.length >= 12; },
    mayuscula: function (c) { return /[A-ZÁÉÍÓÚÑ]/.test(c); },
    minuscula: function (c) { return /[a-záéíóúñ]/.test(c); },
    numero: function (c) { return /[0-9]/.test(c); },
    simbolo: function (c) { return /[^A-Za-zÁÉÍÓÚÑáéíóúñ0-9\s]/.test(c); },
    espacios: function (c) { return c.length > 0 && !/\s/.test(c); }
  };

  campoClave.addEventListener('input', function () {
    listaRequisitos.querySelectorAll('li').forEach(function (item) {
      var cumple = reglas[item.dataset.regla](campoClave.value);
      item.classList.toggle('cumple', cumple);
    });
  });
}
