<?php
$titulo = $titulo ?? 'Solicitar Salón de Cumpleaños / Reunión';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #101c33;">Solicitar Zona de Cumpleaños / Reunión</h2>

            <div class="alert alert-info shadow-sm" style="border-radius: 12px; border-left: 5px solid #0dcaf0;">
                <strong><i class="bi bi-info-circle me-1"></i> Información importante:</strong>
                <p class="mb-2 mt-1" style="font-size: 0.95rem;">TOP GOL dispone de una zona donde se pueden realizar cumpleaños y reuniones, equipada con espacio para mesas y una pantalla grande.</p>
                <ul class="mb-0" style="font-size: 0.95rem;">
                    <li>La cantidad exacta de mesas y el aforo están sujetos a disponibilidad y coordinación directa con el encargado.</li>
                    <li>No se permite el ingreso de bebidas alcohólicas externas.</li>
                    <li><strong>Solicitud de espacio:</strong> Estás enviando una solicitud. El precio final y la confirmación serán acordados y gestionados por el administrador.</li>
                </ul>
            </div>

            <form action="<?= url('/cumpleanos/guardar') ?>" method="POST" class="card p-4 shadow-sm mt-4" style="border-radius: 15px; border: none;">
                <?= csrf_field() ?>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">1. Fecha y Horario</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Hora de Inicio <span class="text-danger">*</span></label>
                        <input type="time" name="hora" class="form-control" required step="1800">
                        <small class="text-muted" style="font-size: 0.75rem;">Bloques de 30 min (ej. 15:00, 15:30)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Duración <span class="text-danger">*</span></label>
                        <select name="duracion" class="form-select" required>
                            <option value="1">1 hora</option>
                            <option value="1.5">1 hora y media</option>
                            <option value="2">2 horas</option>
                            <option value="2.5">2 horas y media</option>
                            <option value="3">3 horas</option>
                            <option value="4">4 horas</option>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">2. Detalles del Evento</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Tipo de Celebración <span class="text-danger">*</span></label>
                        <select name="tipo_celebracion" class="form-select" required>
                            <option value="Cumpleaños Infantil">Cumpleaños Infantil</option>
                            <option value="Cumpleaños Adulto">Cumpleaños Adulto</option>
                            <option value="Reunión de Amigos / Grupo">Reunión de Amigos / Grupo</option>
                            <option value="Reunión Institucional / Empresa">Reunión Institucional / Empresa</option>
                            <option value="Otro">Otro tipo de reunión</option>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">3. Datos de Contacto</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="contacto_nombre" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Teléfono (WhatsApp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8fafc; font-weight: 700; color: #334155;">+51</span>
                            <input type="tel" name="contacto_telefono" class="form-control" placeholder="987 654 321" required>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">4. Observaciones o Necesidades Especiales</h5>
                <div class="mb-4">
                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Indícanos si necesitarás uso de la pantalla, mesas adicionales, o algún requerimiento especial..."></textarea>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100" style="border-radius: 10px; font-weight: 800;">
                    <i class="bi bi-send me-2"></i> Enviar Solicitud de Espacio
                </button>
            </form>
        </div>
    </div>
</div>
