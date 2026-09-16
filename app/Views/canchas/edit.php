<?php
declare(strict_types=1);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-topgol-dark text-white p-4 rounded-top-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-gold text-dark mb-1">Módulo Administrativo</span>
                            <h3 class="h4 text-dark fw-bold mb-0">Editar Cancha #<?= $cancha['id'] ?></h3>
                        </div>
                        <a href="<?= url('/canchas') ?>" class="btn btn-outline-light btn-sm text-dark">
                            <i class="bi bi-arrow-left me-1"></i> Volver al Catálogo
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="<?= url('/cancha/actualizar/' . $cancha['id']) ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-8">
                                <label for="nombre" class="form-label fw-semibold">Nombre de la Cancha <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($cancha['nombre']) ?>" required>
                                <div class="invalid-feedback">Por favor ingresa un nombre para la cancha.</div>
                            </div>

                            <!-- Tipo de Cancha -->
                            <div class="col-md-4">
                                <label for="tipo" class="form-label fw-semibold">Tipo / Formato <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="futbol_5" <?= $cancha['tipo'] === 'futbol_5' ? 'selected' : '' ?>>Fútbol 5</option>
                                    <option value="futbol_7" <?= $cancha['tipo'] === 'futbol_7' ? 'selected' : '' ?>>Fútbol 7</option>
                                    <option value="futbol_11" <?= $cancha['tipo'] === 'futbol_11' ? 'selected' : '' ?>>Fútbol 11</option>
                                </select>
                            </div>

                            <!-- Precio por Hora -->
                            <div class="col-md-6">
                                <label for="precio_hora" class="form-label fw-semibold">Precio por Hora (S/) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.50" min="1" class="form-control" id="precio_hora" name="precio_hora" value="<?= htmlspecialchars((string)$cancha['precio_hora']) ?>" required>
                                </div>
                                <div class="invalid-feedback">Ingresa una tarifa válida.</div>
                            </div>

                            <!-- Capacidad -->
                            <div class="col-md-6">
                                <label for="capacidad" class="form-label fw-semibold">Capacidad de Jugadores <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-people"></i></span>
                                    <input type="number" min="2" max="50" class="form-control" id="capacidad" name="capacidad" value="<?= (int)$cancha['capacidad'] ?>" required>
                                </div>
                                <div class="invalid-feedback">Ingresa la capacidad.</div>
                            </div>

                            <!-- Descripción -->
                            <div class="col-12">
                                <label for="descripcion" class="form-label fw-semibold">Descripción / Características</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($cancha['descripcion'] ?? '') ?></textarea>
                            </div>

                            <!-- Estado de la Cancha -->
                            <div class="col-md-6">
                                <label for="estado" class="form-label fw-semibold">Estado Operativo</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="disponible" <?= $cancha['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible para reservas</option>
                                    <option value="mantenimiento" <?= $cancha['estado'] === 'mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                                </select>
                            </div>

                            <!-- Características adicionales (Checkboxes) -->
                            <div class="col-md-6 d-flex align-items-center gap-4 pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="iluminacion" name="iluminacion" value="1" <?= !empty($cancha['iluminacion']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="iluminacion">
                                        <i class="bi bi-lightbulb-fill text-warning me-1"></i> Iluminación Nocturna
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="techada" name="techada" value="1" <?= !empty($cancha['techada']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="techada">
                                        <i class="bi bi-shield-shaded text-success me-1"></i> Cancha Techada
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="<?= url('/canchas') ?>" class="btn btn-light border px-4">Cancelar</a>
                            <button type="submit" class="btn btn-topgol px-4">
                                <i class="bi bi-check-lg me-1"></i> Actualizar Cancha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>