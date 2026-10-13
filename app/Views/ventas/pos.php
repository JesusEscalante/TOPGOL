<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $productos */
/** @var array<int, array<string, mixed>> $categorias */
/** @var string $idempotencyToken */
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .pos-wrap { display: flex; gap: 20px; align-items: flex-start; }
    .pos-catalog { flex: 1; min-width: 0; }
    .pos-cart-panel { width: 380px; flex-shrink: 0; background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(16,28,51,.08); border: 1px solid #e2e8f0; position: sticky; top: 16px; padding: 20px; }
    .cat-pill { padding: 6px 14px; border-radius: 20px; font-size: .8rem; font-weight: 700; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: pointer; transition: all .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .cat-pill:hover, .cat-pill.active { background: #1a7a3a; color: #fff; border-color: #1a7a3a; }
    .prod-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 14px; transition: all .15s; cursor: pointer; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
    .prod-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16,28,51,.1); border-color: #22c55e; }
    .prod-ico { width: 44px; height: 44px; border-radius: 10px; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 10px; }
    .prod-title { font-size: .92rem; font-weight: 800; color: #101c33; line-height: 1.25; margin-bottom: 4px; }
    .prod-price { font-size: 1.15rem; font-weight: 900; color: #1a7a3a; }
    .prod-stock { font-size: .74rem; font-weight: 700; color: #64748b; }
    .cart-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; }
    .cart-qty-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; cursor: pointer; padding: 0; }
    .cart-qty-btn:hover { background: #f1f5f9; }
    .pos-pay-opt { border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: all .15s; flex: 1; font-weight: 700; font-size: .84rem; }
    .pos-pay-opt:hover { border-color: #cbd5e1; }
    .pos-pay-opt.active { border-color: #1a7a3a; background: #f0fdf4; color: #166534; }
    @media (max-width: 991px) {
        .pos-wrap { flex-direction: column; }
        .pos-cart-panel { width: 100%; position: static; }
    }
</style>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="adm-h1">Venta Rápida en Mostrador (POS)</h1>
        <p class="adm-sub">Terminal de cobro ágil para bebidas, hidratantes, snacks y productos de Top Gol.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/ventas') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:8px 16px;">
            <i class="bi bi-receipt me-1"></i> Historial de ventas
        </a>
        <a href="<?= url('/admin/caja') ?>" class="btn btn-sm btn-outline-success fw-bold" style="border-radius:9px;padding:8px 16px;">
            <i class="bi bi-cash-coin me-1"></i> Caja del día
        </a>
    </div>
</div>

<div class="pos-wrap">
    <!-- Catálogo de Productos -->
    <div class="pos-catalog">
        <!-- Filtros Rápidos -->
        <div class="panel-2 mb-3 py-2 px-3">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <!-- Buscador Instantáneo -->
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="posBuscador" placeholder="Buscar producto...">
                </div>

                <!-- Categorías -->
                <div class="d-flex gap-2 flex-wrap" id="posCatPills">
                    <button type="button" class="cat-pill active" data-cat="all">Todos</button>
                    <?php foreach ($categorias as $cat): ?>
                        <button type="button" class="cat-pill" data-cat="<?= (int)$cat['id'] ?>">
                            <i class="bi <?= htmlspecialchars($cat['icono'] ?: 'bi-box') ?>"></i>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Grid de Productos -->
        <div class="row g-3" id="posGridProductos">
            <?php if (empty($productos)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                    No hay productos con stock disponible en este momento.
                </div>
            <?php else: ?>
                <?php foreach ($productos as $p): ?>
                    <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 item-producto"
                         data-id="<?= (int)$p['id'] ?>"
                         data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                         data-precio="<?= (float)$p['precio'] ?>"
                         data-stock="<?= (int)$p['stock'] ?>"
                         data-cat="<?= (int)($p['categoria_id'] ?? 0) ?>"
                         data-unidad="<?= htmlspecialchars($p['unidad'] ?? 'unidad') ?>">
                        <div class="prod-card" onclick="posAgregarItem(<?= (int)$p['id'] ?>)">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="prod-ico"><i class="bi <?= htmlspecialchars($p['categoria_icono'] ?: 'bi-box') ?>"></i></div>
                                    <span class="badge bg-light text-dark border prod-stock">
                                        <?= (int)$p['stock'] ?> disp.
                                    </span>
                                </div>
                                <div class="prod-title"><?= htmlspecialchars($p['nombre']) ?></div>
                                <small class="text-muted d-block mb-2" style="font-size:.72rem;"><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></small>
                            </div>
                            <div class="d-flex align-items-baseline justify-content-between pt-2 border-top">
                                <div class="prod-price">S/ <?= number_format((float)$p['precio'], 2) ?></div>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0 fw-bold" style="font-size:.75rem;">
                                    + Añadir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel del Carrito / Terminal de Cobro -->
    <div class="pos-cart-panel">
        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
            <h2 class="fs-6 fw-bold mb-0 text-dark">
                <i class="bi bi-cart3 me-2 text-success"></i>Orden Actual
            </h2>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none small" onclick="posVaciarCarrito()">
                <i class="bi bi-trash me-1"></i>Vaciar
            </button>
        </div>

        <!-- Lista de Items en Carrito -->
        <div id="posCartLista" style="max-height:280px; overflow-y:auto;" class="mb-3">
            <div id="posCartVacio" class="text-center py-4 text-muted small">
                <i class="bi bi-basket fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Haz clic en los productos para agregarlos a la orden.
            </div>
        </div>

        <!-- Resumen de Totales -->
        <div class="bg-light p-3 rounded-3 mb-3 border">
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Ítems seleccionados:</span>
                <strong id="posCartCantTotal">0</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="fw-bold text-dark fs-6">TOTAL A COBRAR:</span>
                <span class="fs-4 fw-bold text-success" id="posCartTotal">S/ 0.00</span>
            </div>
        </div>

        <!-- Formulario de Finalización de Venta -->
        <form id="posFormVenta" action="<?= url('/admin/ventas/guardar') ?>" method="POST" onsubmit="return posValidarVenta(event)">
            <?= csrf_field() ?>
            <input type="hidden" name="idempotency_token" value="<?= htmlspecialchars($idempotencyToken) ?>">
            <input type="hidden" name="items_json" id="posInputItemsJson" value="[]">

            <!-- Método de Pago -->
            <label class="form-label small fw-bold text-uppercase text-muted mb-2">Método de Cobro:</label>
            <div class="d-flex gap-2 mb-3">
                <div class="pos-pay-opt active" id="btnPagoEfe" onclick="posSetMetodo('efectivo')">
                    <i class="bi bi-cash-stack d-block fs-5 text-success mb-1"></i>
                    Efectivo
                </div>
                <div class="pos-pay-opt" id="btnPagoPos" onclick="posSetMetodo('niubiz')">
                    <i class="bi bi-credit-card-2-front d-block fs-5 text-primary mb-1"></i>
                    Niubiz POS
                </div>
            </div>
            <input type="hidden" name="metodo_pago" id="posInputMetodo" value="efectivo">

            <!-- Datos Opcionales del Cliente -->
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" name="cliente_nombre" id="posClienteNombre" placeholder="Nombre cliente (opcional / Mostrador)" value="Cliente Mostrador">
            </div>
            <div class="mb-3">
                <input type="text" class="form-control form-control-sm" name="notas" placeholder="Nota opcional (ej: mesa, cancha, etc.)">
            </div>

            <!-- Botón de Cobro con Protección Antidoble Clic -->
            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" id="posBtnCobrar" disabled>
                <i class="bi bi-check2-circle me-1"></i> Confirmar y Cobrar
            </button>
        </form>
    </div>
</div>

<script>
// Estado del carrito POS
var posCarrito = {};
var posProductosData = {};

<?php foreach ($productos as $p): ?>
posProductosData[<?= (int)$p['id'] ?>] = {
    id: <?= (int)$p['id'] ?>,
    nombre: <?= json_encode($p['nombre']) ?>,
    precio: <?= (float)$p['precio'] ?>,
    stock: <?= (int)$p['stock'] ?>,
    cat: <?= (int)($p['categoria_id'] ?? 0) ?>
};
<?php endforeach; ?>

function posAgregarItem(id) {
    var p = posProductosData[id];
    if (!p) return;

    var actualEnCarrito = posCarrito[id] ? posCarrito[id].cantidad : 0;
    if (actualEnCarrito >= p.stock) {
        alert('Stock máximo disponible alcanzado para ' + p.nombre + ' (' + p.stock + ' unidades).');
        return;
    }

    if (!posCarrito[id]) {
        posCarrito[id] = {
            producto_id: id,
            nombre: p.nombre,
            precio: p.precio,
            cantidad: 1,
            stockMax: p.stock
        };
    } else {
        posCarrito[id].cantidad++;
    }

    posRenderizarCarrito();
}

function posModificarCantidad(id, delta) {
    if (!posCarrito[id]) return;

    var nuevaCant = posCarrito[id].cantidad + delta;
    if (nuevaCant <= 0) {
        delete posCarrito[id];
    } else if (nuevaCant > posCarrito[id].stockMax) {
        alert('Stock insuficiente. Máximo disponible: ' + posCarrito[id].stockMax);
    } else {
        posCarrito[id].cantidad = nuevaCant;
    }

    posRenderizarCarrito();
}

function posEliminarItem(id) {
    delete posCarrito[id];
    posRenderizarCarrito();
}

function posVaciarCarrito() {
    posCarrito = {};
    posRenderizarCarrito();
}

function posRenderizarCarrito() {
    var listaEl = document.getElementById('posCartLista');
    var keys = Object.keys(posCarrito);

    if (keys.length === 0) {
        listaEl.innerHTML = '<div id="posCartVacio" class="text-center py-4 text-muted small"><i class="bi bi-basket fs-2 d-block mb-2 text-secondary opacity-50"></i>Haz clic en los productos para agregarlos a la orden.</div>';
        document.getElementById('posCartCantTotal').textContent = '0';
        document.getElementById('posCartTotal').textContent = 'S/ 0.00';
        document.getElementById('posBtnCobrar').disabled = true;
        document.getElementById('posBtnCobrar').innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmar y Cobrar';
        document.getElementById('posInputItemsJson').value = '[]';
        return;
    }

    var html = '';
    var total = 0;
    var totalUnidades = 0;
    var itemsParaJson = [];

    keys.forEach(function (k) {
        var it = posCarrito[k];
        var sub = it.precio * it.cantidad;
        total += sub;
        totalUnidades += it.cantidad;

        itemsParaJson.push({
            producto_id: it.producto_id,
            cantidad: it.cantidad
        });

        html += '<div class="cart-item">' +
            '<div style="min-width:0;flex:1;">' +
                '<strong class="d-block text-truncate text-dark small">' + it.nombre + '</strong>' +
                '<small class="text-muted" style="font-size:.72rem;">S/ ' + it.precio.toFixed(2) + ' c/u</small>' +
            '</div>' +
            '<div class="d-flex align-items-center gap-1">' +
                '<button type="button" class="cart-qty-btn" onclick="posModificarCantidad(' + it.producto_id + ', -1)">-</button>' +
                '<span class="fw-bold px-1 small" style="min-width:20px;text-align:center;">' + it.cantidad + '</span>' +
                '<button type="button" class="cart-qty-btn" onclick="posModificarCantidad(' + it.producto_id + ', 1)">+</button>' +
            '</div>' +
            '<div class="text-end" style="min-width:65px;">' +
                '<strong class="text-dark small d-block">S/ ' + sub.toFixed(2) + '</strong>' +
                '<a href="javascript:void(0)" class="text-danger small" style="font-size:.68rem;" onclick="posEliminarItem(' + it.producto_id + ')">Quitar</a>' +
            '</div>' +
        '</div>';
    });

    listaEl.innerHTML = html;
    document.getElementById('posCartCantTotal').textContent = totalUnidades;
    document.getElementById('posCartTotal').textContent = 'S/ ' + total.toFixed(2);
    document.getElementById('posInputItemsJson').value = JSON.stringify(itemsParaJson);

    var btnCobrar = document.getElementById('posBtnCobrar');
    btnCobrar.disabled = false;
    btnCobrar.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Cobrar S/ ' + total.toFixed(2);
}

function posSetMetodo(m) {
    document.getElementById('posInputMetodo').value = m;
    var btnEfe = document.getElementById('btnPagoEfe');
    var btnPos = document.getElementById('btnPagoPos');

    if (m === 'efectivo') {
        btnEfe.classList.add('active');
        btnPos.classList.remove('active');
    } else {
        btnPos.classList.add('active');
        btnEfe.classList.remove('active');
    }
}

function posValidarVenta(e) {
    var itemsJson = document.getElementById('posInputItemsJson').value;
    var items = JSON.parse(itemsJson || '[]');

    if (items.length === 0) {
        alert('Agrega al menos un producto antes de confirmar la venta.');
        e.preventDefault();
        return false;
    }

    var btn = document.getElementById('posBtnCobrar');
    if (btn.disabled) {
        e.preventDefault();
        return false;
    }

    // Prevención de doble clic
    setTimeout(function() {
        btn.disabled = true;
    }, 10);
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando venta...';
    return true;
}

// Buscador y Filtro de Categorías
document.addEventListener('DOMContentLoaded', function () {
    var buscador = document.getElementById('posBuscador');
    var pills = document.querySelectorAll('#posCatPills .cat-pill');
    var items = document.querySelectorAll('#posGridProductos .item-producto');

    var catActiva = 'all';

    function filtrarGrid() {
        var q = buscador.value.toLowerCase().trim();

        items.forEach(function (el) {
            var nombre = (el.getAttribute('data-nombre') || '').toLowerCase();
            var cat = el.getAttribute('data-cat') || '';

            var matchTexto = q === '' || nombre.indexOf(q) !== -1;
            var matchCat = catActiva === 'all' || cat === catActiva;

            if (matchTexto && matchCat) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    buscador.addEventListener('input', filtrarGrid);

    pills.forEach(function (p) {
        p.addEventListener('click', function () {
            pills.forEach(function (x) { x.classList.remove('active'); });
            this.classList.add('active');
            catActiva = this.getAttribute('data-cat');
            filtrarGrid();
        });
    });
});
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
