<?php // TARJETA DE DOCENTE ?>
<?php if (isset($est_data) && $est_data): ?>
    <div class="card border-0 overflow-hidden animate__animated animate__fadeIn" style="box-shadow:0 1px 3px rgba(0,0,0,0.06),0 1px 2px rgba(0,0,0,0.04);border-radius:14px;">
        <div class="p-4" style="background:linear-gradient(135deg,#003366,#005c99);">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:40px;height:40px;background:rgba(255,255,255,0.15);">
                    <i class="fas fa-id-card text-white"></i>
                </div>
                <div class="min-w-0">
                    <h5 class="text-white fw-bold mb-0" style="font-size:1rem;"><?= htmlspecialchars(trim("{$est_data['nombre']} {$est_data['segundo_nombre']} {$est_data['apellido']} {$est_data['segundo_apellido']}")) ?></h5>
                    <small class="text-white opacity-75"><?= htmlspecialchars(($est_data['tipo_documento'] ?? 'V').'-'.$est_data['cedula']) ?> · <?= htmlspecialchars($est_data['correo']) ?></small>
                </div>
            </div>
        </div>
        <div class="p-4" style="background:#fafbfc;">
            <?php if ($est_data['foto_perfil']): ?>
            <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:#f0fdf4;">
                <img src="public/uploads/profiles/<?= htmlspecialchars($est_data['foto_perfil']) ?>"
                    class="rounded-circle border" style="width:48px;height:48px;object-fit:cover;border-color:#86efac !important;">
                <div>
                    <div style="color:#166534;font-weight:600;font-size:.9rem;">Foto registrada</div>
                    <div style="color:#6b7280;font-size:.8rem;">
                        <?= $est_data['fecha_carnetizacion'] ? 'Ultima carnetizacion: '.date('d/m/Y H:i', strtotime($est_data['fecha_carnetizacion'])) : 'N/A' ?>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm dropdown-toggle p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:none;border:none;color:#64748b;font-size:.7rem;">
                            <i class="fas fa-magic me-1"></i>Simular escenario
                        </button>
                        <ul class="dropdown-menu border-0 shadow rounded-3" style="font-size:.8rem;">
                            <li><button onclick="simulate(EST_ID, 'total')" class="dropdown-item py-2"><i class="fas fa-calendar-times text-danger me-2"></i>Vencimiento de validez (13 meses)</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-3 rounded-3 mb-4 d-flex align-items-center gap-2" style="background:#fffbeb;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706;"></i>
                <span style="color:#92400e;font-size:.85rem;"><strong>Sin foto.</strong> Captura la foto con la webcam o subela.</span>
            </div>
            <?php endif; ?>

            <ul class="nav nav-pills nav-justified mb-4 gap-2" style="background:#f1f5f9;padding:4px;border-radius:12px;" id="captureTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="webcam-tab" data-bs-toggle="pill" data-bs-target="#tab-webcam" type="button" role="tab" onclick="stopCamera()" style="border-radius:10px;padding:8px 12px;font-size:.8rem;color:#475569;background:transparent;border:none;">
                        <i class="fas fa-video me-1"></i>Camara
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="upload-tab" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button" role="tab" onclick="stopCamera()" style="border-radius:10px;padding:8px 12px;font-size:.8rem;color:#475569;background:transparent;border:none;">
                        <i class="fas fa-upload me-1"></i>Subir Archivo
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="captureTabsContent">
                <div class="tab-pane fade show active" id="tab-webcam" role="tabpanel">
                    <div class="p-3 rounded-3 mb-3 d-flex align-items-start gap-2" style="background:#f1f5f9;">
                        <i class="fas fa-info-circle" style="color:#2563eb;margin-top:2px;"></i>
                        <span style="color:#475569;font-size:.8rem;">Coloca al docente frente a un <strong>fondo blanco</strong>, centrado dentro del recuadro guia.</span>
                    </div>
                    <div class="position-relative d-flex justify-content-center">
                        <div class="position-relative" style="max-width:300px;width:100%;aspect-ratio:1;">
                            <video id="video" class="rounded-3 bg-dark position-absolute" style="width:100%;height:100%;object-fit:cover;display:none;inset:0;"></video>
                            <canvas id="canvas" class="rounded-3 position-absolute" style="width:100%;height:100%;display:none;inset:0;"></canvas>
                            <div id="guide-overlay" class="position-absolute" style="inset:0;pointer-events:none;display:none;border-radius:10px;overflow:hidden;">
                                <svg width="100%" height="100%" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" style="position:absolute;top:0;left:0;">
                                    <rect width="300" height="300" fill="rgba(0,0,0,0.35)"/>
                                    <ellipse cx="150" cy="135" rx="80" ry="100" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="2.5" stroke-dasharray="8,4"/>
                                    <text x="150" y="270" text-anchor="middle" fill="white" font-size="11" font-family="Arial">Centra el rostro aqui</text>
                                </svg>
                            </div>
                            <div id="cam-placeholder" class="d-flex flex-column align-items-center justify-content-center gap-2 rounded-3 position-absolute" style="width:100%;height:100%;inset:0;background:#f8fafc;border:2px dashed #cbd5e1;">
                                <i class="fas fa-camera fa-3x" style="color:#94a3b8;"></i>
                                <span style="color:#94a3b8;font-size:.8rem;">La camara aparecera aqui</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-upload" role="tabpanel">
                    <div class="p-4 rounded-3 d-flex flex-column align-items-center justify-content-center" id="drop-zone" style="border:2px dashed #cbd5e1;background:#f8fafc;min-height:260px;cursor:pointer;" onclick="document.getElementById('file-input').click()">
                        <div id="upload-ui" class="text-center">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-2" style="color:#94a3b8;"></i>
                            <div style="color:#475569;font-weight:600;font-size:.9rem;">Selecciona la foto</div>
                            <p style="color:#94a3b8;font-size:.8rem;">JPG o PNG · Max. 2MB</p>
                            <button class="btn btn-sm rounded-pill px-3" style="background:#003366;color:#fff;border:none;">
                                <i class="fas fa-file-image me-1"></i>Examinar
                            </button>
                        </div>
                        <img id="preview-upload" class="rounded-3 shadow-sm" style="max-width:100%;max-height:260px;display:none;object-fit:cover;">
                        <input type="file" id="file-input" class="d-none" accept="image/jpeg,image/png" onchange="handleFileUpload(this)">
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                <button id="btn-start" onclick="startCamera()" class="btn btn-sm rounded-pill px-3 fw-semibold" style="background:#003366;color:#fff;border:none;">
                    <i class="fas fa-video me-1"></i>Activar Camara
                </button>
                <button id="btn-capture" onclick="capturePhoto()" class="btn btn-sm rounded-pill px-3 fw-semibold" style="display:none;background:#16a34a;color:#fff;border:none;">
                    <i class="fas fa-camera me-1"></i>Capturar
                </button>
                <button id="btn-retry" onclick="retryCamera()" class="btn btn-sm rounded-pill px-3" style="display:none;background:#e2e8f0;color:#475569;border:none;">
                    <i class="fas fa-redo me-1"></i>Reintentar
                </button>
                <button id="btn-save" onclick="savePhoto()" class="btn btn-sm rounded-pill px-3 fw-semibold" style="display:none;background:#16a34a;color:#fff;border:none;">
                    <i class="fas fa-save me-1"></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>