<?php
declare(strict_types=1);
$idSeleccionado = (int)($canchaSeleccionada['id'] ?? 0);
$fechaPre = $_GET['fecha'] ?? date('Y-m-d');
$horaPre = $_GET['horario'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPre)) $fechaPre = date('Y-m-d');
$hoyStr = date('Y-m-d');
$esHoy = ($fechaPre === $hoyStr);
$horaMinHoy = max(7, (int)date('H') + 1);

$canchaIni = $canchaSeleccionada ?? ($canchas[0] ?? null);
$precioHoraIni = (float)($canchaIni['precio_hora'] ?? 60);
$duracionIni = 1;
$totalIni = $precioHoraIni * $duracionIni;

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$capacidadIni = (int)($canchaIni['capacidad'] ?? 8);
$tipoIni = $tipoLabels[$canchaIni['tipo'] ?? ''] ?? 'Fútbol 5';

$usuarioActual = currentUser();
$nombreActual = (string)($usuarioActual['nombre'] ?? '');
$emailActual = (string)($usuarioActual['email'] ?? '');
$telActual = preg_replace('/^\+51\s*/', '', (string)($usuarioActual['telefono'] ?? ''));
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
                            <small style="color:#5b6b82; font-size:.78rem;"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</small>
                            <div style="display:flex; gap:12px; font-size:.72rem; color:#5b6b82; margin-top:4px;">
                                <span><i class="bi bi-geo-alt-fill me-1"></i>Tacna</span>
                                <span><i class="bi bi-lightbulb-fill me-1"></i>Iluminación LED</span>
                                <span><i class="bi bi-door-open-fill me-1"></i>Camerinos</span>
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
                                <input type="tel" class="form-control" name="celular" value="<?= htmlspecialchars($telActual) ?>" placeholder="987 654 321" required>
                            </div>
                        </div>
                    </div>

                    <h3 class="pago-h2" style="font-size:.95rem;">Detalles de la reserva</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Fecha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-calendar3"></i></span>
                                <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fechaPre) ?>" min="<?= $hoyStr ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Hora <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-clock"></i></span>
                                <select class="form-select" name="horario" id="horaSel" required>
                                    <option value="">--:--</option>
                                    <?php for($h=7;$h<=23;$h++): foreach(['00','30'] as $mm): $hs=sprintf('%02d:%s',$h,$mm); if($esHoy){ $tsSlot=strtotime($hs); $tsMin=strtotime(sprintf('%02d:00',$horaMinHoy)); if($tsSlot < $tsMin) continue; } ?>
                                        <option value="<?= $hs ?>" <?= $horaPre===$hs?'selected':'' ?>><?= date('g:i A', strtotime($hs)) ?></option>
                                    <?php endforeach; endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Duración <span class="text-danger">*</span></label>
                            <select class="form-select" name="duracion" id="duracionSel" required>
                                <?php $opts=[[0.5,'30 minutos'],[1,'1 hora'],[1.5,'1h 30m'],[2,'2 horas'],[2.5,'2h 30m'],[3,'3 horas'],[3.5,'3h 30m']]; foreach($opts as [$v,$l]): ?>
                                    <option value="<?= $v ?>" <?= $v==1?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Observaciones (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;"><i class="bi bi-chat-left-text"></i></span>
                                <input type="text" class="form-control" name="observaciones" placeholder="Ej. Celebración, necesidades especiales, etc.">
                            </div>
                            <small class="text-muted" style="font-size:.68rem; float:right;">0/200</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <a href="<?= url('/canchas') ?>" class="btn btn-outline-secondary" style="border-radius:10px; padding:10px 18px; font-weight:600;"><i class="bi bi-arrow-left me-1"></i> Volver</a>
                        <button type="submit" class="btn ms-auto" style="background:#0d7a33; color:#fff; border-radius:10px; padding:10px 22px; font-weight:700;">Continuar al pago <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="resumen-h2 mb-0">Resumen de tu reserva</h2>
                <span class="pill fin" style="font-size:.68rem;"><i class="bi bi-shield-lock me-1"></i> Reserva segura</span>
            </div>
            <p class="pago-text">Revisa los detalles antes de continuar.</p>
            <div class="resumen-card">
                <div class="resumen-img">
                    <img src="https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&q=80&fit=crop" alt="Cancha">
                    <span class="resumen-badge" style="left:auto; right:12px; background:#dcfce7; color:#166534;">Fútbol 5</span>
                    <span class="resumen-badge">Cancha 1</span>
                    <div class="resumen-img-txt">
                        <strong id="resCanchaNombre"><?= htmlspecialchars($canchaIni['nombre'] ?? 'Cancha 1') ?></strong>
                        <span id="resCanchaTipo"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</span>
                    </div>
                </div>
                <ul class="resumen-list">
                    <li><span class="rl-ico"><i class="bi bi-geo-alt-fill"></i></span><span><small>Ubicación</small><strong>Tacna</strong></span><span class="ms-auto small text-muted">Tacna</span></li>
                    <li><span class="rl-ico"><i class="bi bi-grid"></i></span><span><small>Tipo</small><strong id="resTipo"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-calendar3"></i></span><span><small>Fecha</small><strong id="resFecha"><?= htmlspecialchars($fechaPre) ?></strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-clock"></i></span><span><small>Hora</small><strong id="resHora">19:00 - 20:00 (1 hora)</strong></span></li>
                    <li><span class="rl-ico"><i class="bi bi-people-fill"></i></span><span><small>Jugadores (referencial)</small><strong>Hasta <?= $capacidadIni ?> personas</strong></span></li>
                </ul>
                <div class="resumen-pago">
                    <div class="rp-row"><span>Precio por hora</span><strong id="resPrecioHora">S/ <?= number_format($precioHoraIni,0) ?></strong></div>
                    <div class="rp-row" style="font-weight:800;"><span>Total de la reserva</span><strong id="resTotal" style="color:#0f172a; font-size:1rem;">S/ <?= number_format($totalIni,0) ?></strong></div>
                    <div class="rp-row verde" style="background:#eef7f0; border-radius:8px; padding:8px 10px; margin-top:6px;"><span>Para confirmar tu reserva, realiza un adelanto de:</span><strong id="resAdelanto" style="color:#1a7a3a;">S/ 20</strong></div>
                    <div class="rp-row"><span>Saldo a pagar en cancha</span><strong id="resSaldo">S/ <?= number_format(max(0,$totalIni-20),0) ?></strong></div>
                </div>
                <div style="background:#eef6ff; border-radius:10px; padding:10px 12px; display:flex; gap:8px; margin:12px 14px;">
                    <span style="color:#2563eb; font-size:1.1rem;"><i class="bi bi-info-circle-fill"></i></span>
                    <small style="font-size:.75rem; color:#334155;">Tu reserva se confirmará una vez realizado el pago del adelanto. Recibirás un comprobante por WhatsApp o correo.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pago-page{ background:#f6f9fb; border-radius:12px; }
.pago-h2{ font-size:1.05rem; font-weight:800; color:#101c33; margin-bottom:2px; }
.pago-text{ color:#7c8aa0; font-size:.85rem; margin-bottom:12px; }
.pago-card{ background:#fff; border:1px solid #e8eef4; border-radius:12px; padding:18px; box-shadow:0 1px 2px rgba(16,28,51,.04); }
.resumen-h2{ font-size:1.2rem; font-weight:800; color:#101c33; margin-bottom:4px; }
.resumen-card{ background:#fff; border:1px solid #e8eef4; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(16,28,51,.06); }
.resumen-img{ position:relative; height:190px; }
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
    var canchasData=<?= json_encode(array_map(function($c) use ($tipoLabels){ return ['id'=>(int)$c['id'], 'nombre'=>$c['nombre'], 'tipo'=>$tipoLabels[$c['tipo']]??$c['tipo'], 'precio'=>(float)$c['precio_hora']]; }, $canchas), JSON_UNESCAPED_UNICODE) ?>;
    function fmtFecha(val){
        if(!val) return '--';
        var d=new Date(val+'T12:00:00');
        var dias=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        var meses=['','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return dias[d.getDay()]+', '+String(d.getDate()).padStart(2,'0')+' de '+meses[d.getMonth()+1]+' de '+d.getFullYear();
    }
    function getCancha(){
        if(!selCancha) return null;
        if(selCancha.tagName==='SELECT'){
            var opt=selCancha.options[selCancha.selectedIndex];
            if(!opt || !opt.value) return null;
            return { precio: parseFloat(opt.getAttribute('data-precio')||'60'), nombre: opt.textContent.split(' (')[0], tipo: (opt.textContent.match(/\(([^)]+)\)/)?.[1]||'') };
        } else {
            var id=selCancha.value;
            var f=canchasData.find(function(c){ return String(c.id)===String(id); });
            return f ? { precio: parseFloat(f.precio), nombre: f.nombre, tipo: f.tipo } : null;
        }
    }
    function actualizar(){
        var data=getCancha();
        var precio=data ? parseFloat(data.precio||60) : <?= (float)($canchaIni['precio_hora'] ?? 60) ?>;
        var dur=parseFloat(selDur.value||'1');
        var total=precio*dur;
        var hora=selHora.value||'19:00';
        var fin=new Date('2000-01-01T'+hora+':00');
        fin.setTime(fin.getTime()+Math.round(dur*3600*1000));
        var finStr=String(fin.getHours()).padStart(2,'0')+':'+String(fin.getMinutes()).padStart(2,'0');
        var durLbl=dur==0.5?'30 minutos':(dur==1?'1 hora':dur+' horas');
        document.getElementById('resFecha').textContent=fmtFecha(inpFecha.value);
        document.getElementById('resHora').textContent=hora+' - '+finStr+' ('+durLbl+')';
        document.getElementById('resPrecioHora').textContent='S/ '+precio.toFixed(0);
        document.getElementById('resTotal').textContent='S/ '+total.toFixed(0);
        document.getElementById('resSaldo').textContent='S/ '+Math.max(0,total-20).toFixed(0);
        if(data){
            if(data.tipo) document.getElementById('resTipo').textContent=data.tipo+' - Césped sintético';
            if(data.nombre) document.getElementById('resCanchaNombre').textContent=data.nombre;
        }
    }
    [selCancha, selHora, selDur, inpFecha].forEach(function(el){ el && el.addEventListener('change', actualizar); });
    actualizar();
})();
</script>
