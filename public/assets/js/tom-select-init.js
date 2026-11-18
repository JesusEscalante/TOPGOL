/**
 * ====================================================================
 * TOP GOL - Inicializador Global de Tom Select
 * ====================================================================
 * Aplica Tom Select a todos los elementos <select> del proyecto,
 * sincronizando cambios dinámicos de opciones/valores y modales.
 */
(function () {
    'use strict';

    /**
     * Sincroniza las opciones visibles y el valor actual de un <select> nativo
     * hacia su instancia de Tom Select (útil cuando JS filtra horas u opciones).
     */
    window.refreshTomSelect = function (selectEl) {
        if (!selectEl || !selectEl.tomselect) return;
        var ts = selectEl.tomselect;
        var currentVal = selectEl.value;

        ts.clearOptions();
        Array.from(selectEl.options).forEach(function (opt) {
            if (opt.hidden) return;
            ts.addOption({
                value: opt.value,
                text: opt.textContent,
                disabled: opt.disabled
            });
        });
        ts.refreshOptions(false);
        ts.setValue(currentVal, true);
    };

    /**
     * Inicializa Tom Select en un elemento <select> individual
     */
    window.initSingleTomSelect = function (el) {
        if (
            !el ||
            el.tomselect ||
            el.classList.contains('no-tomselect') ||
            el.classList.contains('swal2-select') ||
            el.closest('.swal2-container') ||
            el.closest('.swal2-popup') ||
            typeof TomSelect === 'undefined'
        ) {
            return;
        }

        var inSearchCard = !!el.closest('.search-card');
        var optionCount = el.options ? el.options.length : 0;
        var enableSearch = !inSearchCard && optionCount >= 7;

        // Recolectar opciones iniciales omitiendo las que estén ocultas (hidden)
        var initialOptions = [];
        Array.from(el.options).forEach(function (opt) {
            if (opt.hidden) return;
            initialOptions.push({
                value: opt.value,
                text: opt.textContent,
                disabled: opt.disabled
            });
        });

        var config = {
            create: false,
            allowEmptyOption: true,
            dropdownParent: 'body',
            controlInput: enableSearch ? '<input type="text" autocomplete="off" size="1" />' : null,
            options: initialOptions,
            items: el.value !== '' ? [el.value] : (el.options[el.selectedIndex] ? [el.options[el.selectedIndex].value] : []),
            render: {
                no_results: function (data, escape) {
                    return '<div class="no-results p-2 text-muted small text-center">No se encontraron resultados para "' + escape(data.input) + '"</div>';
                }
            },
            onDropdownOpen: function (dropdown) {
                // Asegurar que el ancho del dropdown coincida al menos con el control
                var rect = this.control.getBoundingClientRect();
                if (rect.width > 0) {
                    dropdown.style.minWidth = Math.max(rect.width, inSearchCard ? 160 : rect.width) + 'px';
                }
            }
        };

        var ts = new TomSelect(el, config);

        // Copiar clases de tamaño (form-select-sm / s-field-val) al wrapper
        if (el.classList.contains('form-select-sm')) {
            ts.wrapper.classList.add('ts-sm');
        }
        if (inSearchCard) {
            ts.wrapper.classList.add('ts-search-card');
        }
    };

    /**
     * Inicializa Tom Select en todos los <select> presentes en el DOM
     */
    window.initTomSelects = function (root) {
        if (typeof TomSelect === 'undefined') return;
        var container = root || document;
        var selects = container.querySelectorAll('select:not(.no-tomselect):not(.swal2-select)');
        selects.forEach(function (el) {
            window.initSingleTomSelect(el);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initTomSelects(document);

        // Sincronizar automáticamente cualquier <select> dentro de un modal de Bootstrap al abrirse
        document.addEventListener('show.bs.modal', function (e) {
            var modal = e.target;
            if (!modal) return;
            var selects = modal.querySelectorAll('select');
            selects.forEach(function (sel) {
                if (sel.tomselect) {
                    sel.tomselect.setValue(sel.value, true);
                } else {
                    window.initSingleTomSelect(sel);
                }
            });
        });
    });
})();
