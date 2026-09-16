/**
 * ====================================================================
 * TOP GOL - JavaScript Principal
 * ====================================================================
 * Interacciones en cliente, cálculo dinámico de reservas, validaciones
 * y alertas.
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Auto-cierre de alertas flash después de 5 segundos
    const alertas = document.querySelectorAll('.alert-dismissible');
    alertas.forEach(function (alerta) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alerta);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 2. Lógica de cálculo en el formulario de Reserva
    const selectCancha = document.getElementById('cancha_id');
    const selectDuracion = document.getElementById('duracion_horas');
    const inputHoraInicio = document.getElementById('hora_inicio');
    const inputFecha = document.getElementById('fecha');

    const spanPrecioHora = document.getElementById('resumen_precio_hora');
    const spanHoras = document.getElementById('resumen_duracion');
    const spanHoraFin = document.getElementById('resumen_hora_fin');
    const spanTotal = document.getElementById('resumen_total');
    const badgeDisponibilidad = document.getElementById('badge_disponibilidad');

    function actualizarCalculosReserva() {
        if (!selectCancha || !selectDuracion) return;

        const optionSeleccionada = selectCancha.options[selectCancha.selectedIndex];
        const precioHora = optionSeleccionada ? parseFloat(optionSeleccionada.getAttribute('data-precio') || '0') : 0;
        const duracion = parseInt(selectDuracion.value || '1', 10);
        const total = precioHora * duracion;

        // Actualizar valores en el resumen visual
        if (spanPrecioHora) {
            spanPrecioHora.textContent = 'S/ ' + precioHora.toFixed(2);
        }
        if (spanHoras) {
            spanHoras.textContent = duracion + (duracion === 1 ? ' hora' : ' horas');
        }
        if (spanTotal) {
            spanTotal.textContent = 'S/ ' + total.toFixed(2);
        }

        // Calcular hora estimada de fin
        if (inputHoraInicio && spanHoraFin && inputHoraInicio.value) {
            const partes = inputHoraInicio.value.split(':');
            if (partes.length >= 2) {
                let hora = parseInt(partes[0], 10);
                const minutos = partes[1];
                hora = (hora + duracion) % 24;
                const horaStr = (hora < 10 ? '0' : '') + hora + ':' + minutos;
                spanHoraFin.textContent = horaStr;
            }
        }

        // Si tenemos cancha, fecha y hora inicio, consultar disponibilidad por API
        if (badgeDisponibilidad && selectCancha.value && inputFecha && inputFecha.value && inputHoraInicio && inputHoraInicio.value) {
            const url = `${window.location.origin}/topgol/api/cancha/disponibilidad?cancha_id=${selectCancha.value}&fecha=${inputFecha.value}&hora_inicio=${inputHoraInicio.value}&duracion=${duracion}`;

            badgeDisponibilidad.className = 'badge bg-secondary';
            badgeDisponibilidad.innerHTML = '<i class="bi bi-hourglass-split"></i> Verificando horario...';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.disponible) {
                        badgeDisponibilidad.className = 'badge bg-success';
                        badgeDisponibilidad.innerHTML = '<i class="bi bi-check-circle"></i> Cancha Libre en este horario';
                    } else {
                        badgeDisponibilidad.className = 'badge bg-danger';
                        badgeDisponibilidad.innerHTML = '<i class="bi bi-x-circle"></i> Horario Ocupado por otra reserva';
                    }
                })
                .catch(() => {
                    badgeDisponibilidad.className = 'badge bg-info text-dark';
                    badgeDisponibilidad.innerHTML = '<i class="bi bi-info-circle"></i> Horario disponible a validar';
                });
        }
    }

    if (selectCancha) {
        selectCancha.addEventListener('change', actualizarCalculosReserva);
    }
    if (selectDuracion) {
        selectDuracion.addEventListener('change', actualizarCalculosReserva);
    }
    if (inputHoraInicio) {
        inputHoraInicio.addEventListener('change', actualizarCalculosReserva);
    }
    if (inputFecha) {
        inputFecha.addEventListener('change', actualizarCalculosReserva);
    }

    // Ejecutar cálculo inicial si ya hay datos precargados
    if (selectCancha && selectCancha.value) {
        actualizarCalculosReserva();
    }

    // 3. Confirmación para botones con clase .btn-confirmar-eliminar
    const botonesEliminar = document.querySelectorAll('.btn-confirmar-eliminar');
    botonesEliminar.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const mensaje = this.getAttribute('data-confirm') || '¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.';
            if (!confirm(mensaje)) {
                e.preventDefault();
            }
        });
    });

    // 4. Validación básica en formularios HTML5
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

});