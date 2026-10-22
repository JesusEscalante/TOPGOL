<?php
declare(strict_types=1);
$idSeleccionado = (int)($canchaSeleccionada['id'] ?? 0);
$fechaPre = $_GET['fecha'] ?? date('Y-m-d');
$horaPre = $_GET['horario'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPre)) $fechaPre = date('Y-m-d');
$hoyStr = date('Y-m-d');
$esHoy = ($fechaPre === $hoyStr);
// Hora mínima de inicio hoy (Lima): posterior a la hora actual
$minHoyCliente = horaMinimaReserva($hoyStr, false);

$canchaIni = $canchaSeleccionada ?? ($canchas[0] ?? null);
$precioHoraIni = (float)($canchaIni['precio_hora'] ?? 60);
$duracionMinutos = (int)($_GET['duracion'] ?? 60);
if ($duracionMinutos < 30 || $duracionMinutos > 480 || $duracionMinutos % 30 !== 0) { $duracionMinutos = 60; }
$ocupacion = $ocupacion ?? [];
$duracionIni = $duracionMinutos > 0 ? $duracionMinutos / 60 : 1;
$totalIni = $precioHoraIni * $duracionIni;

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$capacidadIni = (int)($canchaIni['capacidad'] ?? 8);
$tipoIni = $tipoLabels[$canchaIni['tipo'] ?? ''] ?? 'Fútbol 5';

$usuarioActual = currentUser();
$nombreActual = $_GET['nombre_cliente'] ?? (string)($usuarioActual['nombre'] ?? '');
$emailActual = (string)($usuarioActual['email'] ?? '');
$telActual = preg_replace('/^\+51\s*/', '', $_GET['celular'] ?? (string)($usuarioActual['telefono'] ?? ''));
?>

<div class="container py-3 pago-page">
    <div class="stepper" aria-label="Progreso de reserva">
        <div class="step active" aria-current="step">
            <span class="dot">1</span>
            <span class="lbl"><span class="lbl-full">1. Selección<br>de cancha</span><span class="lbl-short">Selección</span></span>
        </div>
        <span class="line" aria-hidden="true"></span>
        <div class="step">
            <span class="dot">2</span>
            <span class="lbl"><span class="lbl-full">2. Pago y<br>comprobante</span><span class="lbl-short">Pago</span></span>
        </div>
        <span class="line" aria-hidden="true"></span>
        <div class="step">
            <span class="dot">3</span>
            <span class="lbl"><span class="lbl-full">3. Confirmación</span><span class="lbl-short">Confirmación</span></span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form action="<?= url('/reserva/crear') ?>" method="GET" class="needs-validation" id="formDatos" novalidate>
                <input type="hidden" name="cancha_id" value="<?= $idSeleccionado ?>">

                <div class="pago-card mb-3">
                    <h2 class="pago-h2">Formulario de reserva</h2>
                    <p class="pago-text">Completa tus datos para reservar tu cancha.</p>

                    <!-- Cancha seleccionada -->
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px; display:flex; gap:12px; align-items:center; margin-bottom:16px;">
                        <img src="<?= $canchaIni ? 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=200&q=80&fit=crop' : 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?w=200&q=80&fit=crop' ?>" alt="Cancha" style="width:96px; height:72px; object-fit:cover; border-radius:8px; flex-shrink:0;">
                        <div style="flex:1; min-width:0;">
                            <strong style="font-size:.95rem; display:block;"><?= htmlspecialchars($canchaIni['nombre'] ?? 'Cancha 1') ?></strong>
                            <small style="color:#5b6b82; font-size:.78rem;"><?= htmlspecialchars($tipoIni) ?> - Características por confirmar</small>
                            <div style="display:flex; gap:12px; font-size:.72rem; color:#5b6b82; margin-top:4px;">
                                <span><i class="bi bi-geo-alt-fill me-1"></i>Tacna</span>
                            </div>
                        </div>
                        <span class="pill ok" style="font-size:.68rem; white-space:nowrap;"><i class="bi bi-check-circle-fill me-1"></i>Disponible</span>
                    </div>

                    <h3 class="pago-h2" style="font-size:.95rem; margin-top:12px;">Tus datos</h3>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nombre completo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-person-fill"></i></span>
                                <input type="text" class="form-control" name="nombre_cliente" value="<?= htmlspecialchars($nombreActual) ?>" placeholder="Carlos Mendoza" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Número de celular <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155;">+51</span>
                                <input type="tel" class="form-control" name="celular" value="<?= htmlspecialchars($telActual) ?>" placeholder="9XX XXX XXX" required>
                            </div>
                        </div>
                    </div>

                    <h3 class="pago-h2" style="font-size:.95rem;">Detalles de la reserva</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Fecha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-calendar3"></i></span>
                                <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fechaPre) ?>" min="<?= $hoyStr ?>" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Hora de inicio <span class="text-danger">*</span></label>
                            <div class="opt-grid" id="gridHoras" role="group" aria-label="Hora de inicio"></div>
                            <input type="hidden" name="horario" id="horaSel" value="<?= htmlspecialchars($horaPre) ?>">
                            <small class="text-muted d-block mt-1" style="font-size:.74rem;">Las horas tachadas ya pasaron o están ocupadas.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Duración <span class="text-danger">*</span></label>
                            <div class="opt-grid" id="gridDur" role="group" aria-label="Duración"></div>
                            <div class="opt-grid mt-2 d-none" id="gridDurExtra" role="group" aria-label="Más duración"></div>
                            <input type="hidden" name="duracion" id="duracionSel" value="<?= (int)$duracionMinutos ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Observaciones (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-chat-left-text"></i></span>
                                <input type="text" class="form-control" name="observaciones" value="<?= htmlspecialchars($_GET['observaciones'] ?? '') ?>" placeholder="Ej. Celebración, necesidades especiales, etc.">
                            </div>
                            <small class="text-muted" style="font-size:.68rem; float:right;">0/200</small>
                        </div>
                    </div>

                    <div class="alert alert-warning d-none mt-3 mb-0 py-2" id="avisoHorario" role="alert" style="font-size:.9rem;"></div>
                    <p class="text-muted mt-3 mb-0" style="font-size:.85rem;">
                        <i class="bi bi-clock-history me-1"></i> Puedes empezar cada 30 minutos. Atendemos de lunes a viernes de 9:00 a 23:00 y sábados y domingos de 7:00 a 23:00; tu reserva debe terminar a las 23:00.
                    </p>
                    <div class="acciones-form mt-4">
                        <div class="mini-res d-lg-none" id="miniResumen" aria-live="polite"></div>
                        <div class="d-flex gap-2">
                            <a href="<?= url('/canchas') ?>" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 18px; font-weight:600;"><i class="bi bi-arrow-left me-1"></i> Volver</a>
                            <button type="submit" class="btn ms-auto" id="btnContinuar" style="background:#0d7a33; color:#fff; border-radius:10px; padding:10px 22px; font-weight:700;"><span id="btnContinuarTxt">Continuar al pago</span> <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                        <div class="text-end text-danger fw-semibold mt-2 d-none" id="ayudaContinuar" style="font-size:.88rem;"><i class="bi bi-info-circle me-1"></i> <span id="ayudaContinuarTxt">Para continuar, elige la fecha y la hora de inicio.</span></div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
          <div class="resumen-sticky">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="resumen-h2 mb-0">Resumen de tu reserva</h2>
                <span class="pill fin" style="font-size:.68rem;"><i class="bi bi-shield-lock me-1"></i> Reserva segura</span>
            </div>
            <p class="pago-text">Revisa los detalles antes de continuar.</p>
            <div class="resumen-card">
                <div class="resumen-img">
                    <img src="https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&q=80&fit=crop" alt="Cancha">
                    <span class="resumen-badge" style="left:auto; right:12px; background:#dcfce7; color:#166534;"></span>
                    <span class="resumen-badge"><?= htmlspecialchars($canchaIni['nombre'] ?? 'Cancha 1') ?></span>
                    <div class="resumen-img-txt">
                        <strong id="resCanchaNombre"><?= htmlspecialchars($canchaIni['nombre'] ?? 'Cancha 1') ?></strong>
                        <span id="resCanchaTipo"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</span>
                    </div>
                </div>
                <ul class="resumen-list">
                    <li><span class="rl-ico"><i class="bi bi-geo-alt-fill"></i></span><span><small>Ubicación</small><strong>Tacna</strong></span><span class="ms-auto small text-muted">Tacna</span></li>
                    <li><span class="rl-ico"><i class="bi bi-grid"></i></span><span><small>Tipo</small><strong id="resTipo"><?= htmlspecialchars($tipoIni) ?> - Características por confirmar</strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-calendar3"></i></span><span><small>Fecha</small><strong id="resFecha"><?= htmlspecialchars($fechaPre) ?></strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-clock"></i></span><span><small>Hora de inicio y fin</small><strong id="resHora">--:--</strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-info-circle"></i></span><span><small>Detalles adicionales</small><strong>Instalaciones por confirmar</strong></span></li>
                </ul>
                <div class="resumen-pago">
                    <div class="rp-row"><span>Precio por hora</span><strong id="resPrecioHora">S/ <?= number_format($precioHoraIni,0) ?></strong></div>
                    <div class="rp-row" style="font-weight:800;"><span>Total de la reserva</span><strong id="resTotal" style="color:#0f172a; font-size:1rem;">S/ <?= number_format($totalIni,0) ?></strong></div>
                    <div class="rp-row verde" style="background:#eef7f0; border-radius:8px; padding:8px 10px; margin-top:6px;"><span>Para confirmar tu reserva, realiza un adelanto de:</span><strong id="resAdelanto" style="color:#1a7a3a;">S/ 20</strong></div>
                    <div class="rp-row"><span>Saldo a pagar en cancha</span><strong id="resSaldo">S/ <?= number_format(max(0,$totalIni-20),0) ?></strong></div>
                </div>
                <div style="background:#eef6ff; border-radius:10px; padding:10px 12px; display:flex; gap:8px; margin:12px 14px;">
                    <span style="color:#2563eb; font-size:1.1rem;"><i class="bi bi-info-circle-fill"></i></span>
                    <small style="font-size:.75rem; color:#334155;">Tu reserva se confirmará una vez realizado el pago del adelanto. Revisa el estado en Mis reservas.</small>
                </div>
                <div style="margin:0 14px 12px;"><?php require __DIR__ . '/_tolerancia.php'; ?></div>
            </div>
          </div>
        </div>
    </div>
</div>

<style>
.pago-page{ background:#f6f9fb; border-radius:12px; }
#formDatos .form-control, #formDatos .form-select{ min-height:48px; font-size:1rem; }
#formDatos .input-group-text{ min-width:44px; justify-content:center; }
#btnContinuar:disabled{ opacity:.55; cursor:not-allowed; }
@media (max-width:767px){ #btnContinuar{ flex:1; padding:14px 18px !important; font-size:1rem; } }
.resumen-sticky{ position:sticky; top:12px; }
@media (max-width:991px){ .resumen-sticky{ position:static; } }
.opt-grid{ display:flex; flex-wrap:wrap; gap:8px; }
.opt-btn{ min-width:76px; min-height:46px; padding:8px 12px; border-radius:10px; border:1.5px solid #cbd5e1; background:#fff; color:#0f172a; font-size:1rem; font-weight:700; cursor:pointer; }
.opt-btn:hover:not(:disabled){ border-color:#1a7a3a; color:#1a7a3a; }
.opt-btn.on{ background:#1a7a3a; border-color:#1a7a3a; color:#fff; }
.opt-btn:disabled{ background:#f1f5f9; color:#94a3b8; text-decoration:line-through; cursor:not-allowed; border-color:#e2e8f0; }
.opt-btn.dur{ min-width:92px; }
@media (max-width:575px){ .opt-grid{ display:grid; grid-template-columns:repeat(4,1fr); } .opt-btn{ min-width:0; padding:8px 4px; } #gridDur,#gridDurExtra{ grid-template-columns:repeat(3,1fr); } }
.acciones-form{ position:sticky; bottom:0; background:#fff; padding:10px 0 4px; z-index:5; }
.mini-res{ font-size:.82rem; font-weight:700; color:#0f172a; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:6px 10px; margin-bottom:8px; }
@media (max-width:575px){ .acciones-form{ box-shadow:0 -6px 12px rgba(16,28,51,.08); margin:16px -18px -18px !important; padding:10px 18px 12px; border-radius:0 0 12px 12px; } }
.pago-h2{ font-size:1.05rem; font-weight:800; color:#101c33; margin-bottom:2px; }
.pago-text{ color:#7c8aa0; font-size:.85rem; margin-bottom:12px; }
.pago-card{ background:#fff; border:1px solid #e8eef4; border-radius:12px; padding:18px; box-shadow:0 1px 2px rgba(16,28,51,.04); }
.resumen-h2{ font-size:1.2rem; font-weight:800; color:#101c33; margin-bottom:4px; }
.resumen-card{ background:#fff; border:1px solid #e8eef4; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(16,28,51,.06); }
.resumen-img{ position:relative; height:120px; }
.resumen-img img{ width:100%; height:100%; object-fit:cover; display:block; }
.resumen-badge{ position:absolute; top:12px; left:12px; background:#b8e6c3; color:#14532d; font-size:.72rem; font-weight:700; border-radius:8px; padding:5px 10px; }
.resumen-img-txt{ position:absolute; left:14px; bottom:12px; color:#fff; text-shadow:0 1px 4px rgba(0,0,0,.6); }
.resumen-img-txt strong{ display:block; font-size:1rem; }
.resumen-list{ list-style:none; margin:0; padding:12px 16px 4px; }
.resumen-list li{ display:flex; gap:10px; padding:7px 0; color:#101c33; align-items:center; }
.rl-ico{ color:#101c33; font-size:1.1rem; width:26px; text-align:center; }
.resumen-list small{ display:block; color:#7c8aa0; font-size:.68rem; }
.resumen-list strong{ font-size:.82rem; }
.resumen-pago{ background:#f4f7fb; border-radius:10px; margin:8px 14px; padding:12px 14px; font-size:.85rem; }
.rp-title{ font-weight:800; margin-bottom:6px; color:#101c33; }
.rp-row{ display:flex; justify-content:space-between; padding:4px 0; color:#33415c; }
.rp-row.verde{ color:#1a7a3a; font-weight:700; }
.rp-row.saldo{ background:#e9f7ee; color:#166534; font-weight:800; border-radius:8px; padding:8px 10px; margin-top:6px; }
.stepper{ display:flex; align-items:flex-start; gap:8px; margin:6px 0 18px; }
.step{ display:flex; align-items:center; gap:8px; }
.step .dot{ width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; background:#e5e9ef; color:#8a97a8; flex-shrink:0; }
.step .lbl{ font-size:.75rem; color:#8a97a8; line-height:1.2; }
.step.done .dot{ background:#1a7a3a; color:#fff; }
.step.done .lbl{ color:#101c33; font-weight:600; }
.step.active .dot{ background:#22c55e; color:#fff; box-shadow:0 0 0 5px rgba(34,197,94,.18); }
.step.active .lbl{ color:#101c33; font-weight:600; }
.stepper .line{ flex:1; height:3px; border-radius:3px; background:#e5e9ef; margin-top:16px; min-width:30px; }
.stepper .line.done{ background:#22c55e; }
</style>

<script>
(function(){
    var selCancha=document.querySelector('[name="cancha_id"]');
    var selHora=document.getElementById('horaSel');
    var selDur=document.getElementById('duracionSel');
    var inpFecha=document.querySelector('input[name="fecha"]');
    var inpNombre=document.querySelector('[name="nombre_cliente"]');
    var inpCel=document.querySelector('[name="celular"]');
    var gridHoras=document.getElementById('gridHoras');
    var gridDur=document.getElementById('gridDur');
    var gridExtra=document.getElementById('gridDurExtra');
    var canchasData=<?= json_encode(array_map(function($c) use ($tipoLabels){ return ['id'=>(int)$c['id'], 'nombre'=>$c['nombre'], 'tipo'=>$tipoLabels[$c['tipo']]??$c['tipo'], 'precio'=>(float)$c['precio_hora']]; }, $canchas), JSON_UNESCAPED_UNICODE) ?>;
    // Solo ayuda visual: el servidor vuelve a validar horario, pasado y choques al reservar
    var HOY_STR = <?= json_encode($hoyStr) ?>, MIN_HOY = <?= json_encode($minHoyCliente) ?>;
    var OCUP = <?= json_encode($ocupacion ?: new stdClass()) ?>;
    var CIERRE = 1380, ADELANTO = 20;
    var DURS = [30, 60, 90, 120], EXTRA = [150, 180, 210, 240];

    function fmtFecha(val){
        if(!val) return '--';
        var d=new Date(val+'T12:00:00');
        var dias=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        var meses=['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return dias[d.getDay()]+', '+String(d.getDate()).padStart(2,'0')+' de '+meses[d.getMonth()+1]+' de '+d.getFullYear();
    }
    function getCancha(){
        if(!selCancha) return null;
        var id=selCancha.value;
        var f=canchasData.find(function(c){ return String(c.id)===String(id); });
        return f ? { precio: parseFloat(f.precio), nombre: f.nombre, tipo: f.tipo } : null;
    }
    function aMin(h){ return parseInt(h.slice(0,2),10)*60 + parseInt(h.slice(3,5),10); }
    function aHHMM(m){ return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0'); }
    function abre(fecha){ var d=new Date(fecha+'T12:00:00').getDay(); return (d===0||d===6)?420:540; }
    function ocupadoEn(fecha, ini, fin){
        var b=OCUP[fecha]||[];
        for(var i=0;i<b.length;i++){ if(b[i][0]<fin && b[i][1]>ini) return true; }
        return false;
    }
    function horaDisponible(fecha, m){
        if(!fecha || fecha<HOY_STR) return false;
        if(m<abre(fecha) || m+30>CIERRE) return false;
        if(fecha===HOY_STR && m<aMin(MIN_HOY)) return false;
        return !ocupadoEn(fecha, m, m+30);
    }
    function durDisponible(fecha, m, dur){ return m+dur<=CIERRE && !ocupadoEn(fecha, m, m+dur); }
    // Mismo formato que formatoDuracion() de PHP: 30 min, 1 h, 1 h 30 min
    function durLabel(d){
        if(d<60) return d+' min';
        if(d%60===0) return (d/60)+' h';
        return Math.floor(d/60)+' h '+(d%60)+' min';
    }
    function durTexto(d){ return durLabel(d); }
    function crearBtn(texto, valor, clase, activo, deshabilitado){
        var b=document.createElement('button');
        b.type='button'; b.className='opt-btn '+clase+(activo?' on':'');
        b.textContent=texto; b.setAttribute('data-v', valor);
        b.setAttribute('aria-pressed', activo?'true':'false');
        b.disabled=deshabilitado;
        return b;
    }
    function pintarHoras(){
        var fecha=inpFecha.value;
        gridHoras.innerHTML='';
        if(!fecha){
            gridHoras.innerHTML='<span class="text-muted small">Elige primero la fecha.</span>';
            return;
        }
        var ini=abre(fecha);
        for(var m=ini; m+30<=CIERRE; m+=30){
            var h=aHHMM(m);
            gridHoras.appendChild(crearBtn(h, h, 'hora', selHora.value===h, !horaDisponible(fecha, m)));
        }
    }
    function pintarDur(){
        var fecha=inpFecha.value, hora=selHora.value;
        var m=hora?aMin(hora):null, actual=parseInt(selDur.value,10);
        function ok(d){ return m===null || durDisponible(fecha, m, d); }
        gridDur.innerHTML=''; gridExtra.innerHTML='';
        DURS.forEach(function(d){ gridDur.appendChild(crearBtn(durLabel(d), d, 'dur', actual===d, !ok(d))); });
        var masActivo = EXTRA.indexOf(actual)>=0;
        var mas=crearBtn('Más…', 'mas', 'dur', masActivo, false);
        mas.id='btnDurMas';
        gridDur.appendChild(mas);
        EXTRA.forEach(function(d){ gridExtra.appendChild(crearBtn(durLabel(d), d, 'dur', actual===d, !ok(d))); });
        if(masActivo) gridExtra.classList.remove('d-none');
    }
    function setTexto(id, t){ var e=document.getElementById(id); if(e) e.textContent=t; }

    function actualizar(){
        var fecha=inpFecha.value, hora=selHora.value;
        // si la hora elegida dejó de estar libre (otra fecha, ya pasó), se limpia
        if(hora && !horaDisponible(fecha, aMin(hora))){ selHora.value=''; hora=''; }
        pintarHoras(); pintarDur();

        var data=getCancha();
        var precio=data ? parseFloat(data.precio||60) : <?= (float)($canchaIni['precio_hora'] ?? 60) ?>;
        var durMin=parseInt(selDur.value,10)||0;
        var total=precio*durMin/60;
        var nombre=(inpNombre?inpNombre.value.trim():'x'), cel=(inpCel?inpCel.value.trim():'x');

        var falta='';
        var aviso='';
        if(!fecha){ falta='Elige la fecha'; }
        else if(fecha<HOY_STR){ falta='Elige una fecha desde hoy'; }
        else if(!hora){ falta='Elige una hora'; }
        else if(!durMin){ falta='Elige la duración'; }
        else if(!durDisponible(fecha, aMin(hora), durMin)){
            falta='Elige otra duración';
            aviso = (aMin(hora)+durMin>CIERRE)
                ? 'Con esa hora y duración la reserva terminaría después de las 23:00. Elige una duración menor o una hora más temprano.'
                : 'Esa duración se cruza con otra reserva. Elige una duración menor u otra hora.';
        }
        else if(!nombre){ falta='Escribe tu nombre'; }
        else if(!cel){ falta='Escribe tu celular'; }

        var btn=document.getElementById('btnContinuar');
        btn.disabled = falta!=='';
        setTexto('btnContinuarTxt', falta!=='' ? falta : 'Continuar al pago');
        var ayuda=document.getElementById('ayudaContinuar');
        ayuda.classList.toggle('d-none', falta==='');
        setTexto('ayudaContinuarTxt', falta ? 'Para continuar: '+falta.charAt(0).toLowerCase()+falta.slice(1)+'.' : '');
        var avisoH=document.getElementById('avisoHorario');
        if(avisoH){ avisoH.textContent=aviso; avisoH.classList.toggle('d-none', aviso===''); }

        var tieneHorario = hora && durMin && durDisponible(fecha, aMin(hora), durMin);
        var finStr = tieneHorario ? aHHMM(aMin(hora)+durMin) : '';
        setTexto('resFecha', fmtFecha(fecha));
        setTexto('resHora', tieneHorario ? hora+' - '+finStr+' ('+durTexto(durMin)+')' : '--:--');
        setTexto('resPrecioHora', 'S/ '+precio.toFixed(0));
        setTexto('resTotal', tieneHorario ? 'S/ '+total.toFixed(0) : '--');
        setTexto('resAdelanto', 'S/ '+ADELANTO);
        setTexto('resSaldo', tieneHorario ? 'S/ '+Math.max(0,total-ADELANTO).toFixed(0) : '--');
        if(data){
            if(data.tipo) setTexto('resTipo', data.tipo+' - Características por confirmar');
            if(data.nombre) setTexto('resCanchaNombre', data.nombre);
        }
        setTexto('miniResumen', tieneHorario
            ? (data?data.nombre+' · ':'')+fmtFecha(fecha).replace(/ de \d{4}$/,'')+' · '+hora+'-'+finStr+' · Total S/ '+total.toFixed(0)
            : 'Elige fecha, hora y duración para ver tu total');
    }

    gridHoras.addEventListener('click', function(e){
        var b=e.target.closest('.opt-btn'); if(!b || b.disabled) return;
        selHora.value=b.getAttribute('data-v'); actualizar();
    });
    function clickDur(e){
        var b=e.target.closest('.opt-btn'); if(!b || b.disabled) return;
        var v=b.getAttribute('data-v');
        if(v==='mas'){ gridExtra.classList.toggle('d-none'); return; }
        selDur.value=v; actualizar();
    }
    gridDur.addEventListener('click', clickDur);
    gridExtra.addEventListener('click', clickDur);
    [inpFecha, inpNombre, inpCel].forEach(function(el){ el && el.addEventListener('input', actualizar); el && el.addEventListener('change', actualizar); });
    actualizar();
})();
</script>
