<?php // AUDITORIA DEL SISTEMA ?>
<style>
.modal-pdf iframe { width: 100%; height: 100%; border: none; }
.modal-pdf .modal-body { padding: 0; background: #f0f0f0; }
.modal-pdf .modal-content { border-radius: 16px; overflow: hidden; }
</style>

<div class="container py-5 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold" style="color: #003366;">
            <i class="fas fa-clipboard-check me-2"></i> Auditoria y Reportes
        </h1>
        <a href="index.php?page=dashboard" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-4 d-flex flex-column">
                    <div class="display-5 mb-3" style="color: #003366;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Estudiantes</h5>
                    <p class="text-muted small flex-grow-1">Listado completo de estudiantes activos organizados por semestre y seccion.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalPDF" data-url="index.php?action=generate_pdf&tipo=estudiantes&preview=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" data-title="Reporte de Estudiantes">
                            <i class="fas fa-eye me-1"></i> Vista Previa
                        </button>
                        <a href="index.php?action=generate_pdf&tipo=estudiantes&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-primary rounded-pill fw-bold" style="background:#003366;border-color:#003366;">
                            <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-4 d-flex flex-column">
                    <div class="display-5 text-success mb-3">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Docentes</h5>
                    <p class="text-muted small flex-grow-1">Plantilla completa de docentes con materias y secciones asignadas.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalPDF" data-url="index.php?action=generate_pdf&tipo=docentes&preview=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" data-title="Reporte de Docentes">
                            <i class="fas fa-eye me-1"></i> Vista Previa
                        </button>
                        <a href="index.php?action=generate_pdf&tipo=docentes&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-success rounded-pill fw-bold">
                            <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-4 d-flex flex-column">
                    <div class="display-5 text-warning mb-3">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5 class="fw-bold">Bitacora de Actividad</h5>
                    <p class="text-muted small flex-grow-1">Ultimos 50 cambios registrados en el sistema con detalle de accion y usuario.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalPDF" data-url="index.php?action=generate_pdf&tipo=bitacora&preview=1&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" data-title="Bitacora de Actividad">
                            <i class="fas fa-eye me-1"></i> Vista Previa
                        </button>
                        <a href="index.php?action=generate_pdf&tipo=bitacora&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-warning rounded-pill fw-bold text-dark">
                            <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-pdf" id="modalPDF" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title fw-bold" id="modalPDFTitle">
                        <i class="fas fa-file-pdf text-danger me-2"></i> Reporte
                    </h6>
                    <div class="d-flex gap-2">
                        <a id="modalPDFDownload" href="#" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
                            <i class="fas fa-download me-1"></i> Descargar
                        </a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-0" style="max-height: 80vh;height:60vh;">
                    <iframe id="modalPDFIframe" src="" style="width:100%;height:100%;border:none;" title="Previsualizacion PDF"></iframe>
                </div>
                <div class="modal-footer bg-light py-2 justify-content-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Si no visualizas el PDF, <a id="modalPDFDirect" href="#" target="_blank" class="text-decoration-none fw-bold">Abrelo en una nueva pestana</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list-alt me-2 text-secondary"></i> Ultimos registros de actividad
            </h5>
        </div>
        <div class="card-body p-4">
            <?php
            require_once 'app/helpers/AuditHelper.php';
            $registros = AuditHelper::obtenerUltimos($conexion, 50);
            if (empty($registros)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    <p class="mb-0">No hay registros de actividad aun.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small" style="table-layout:fixed;">
                        <colgroup>
                            <col style="min-width:100px;width:130px;">
                            <col style="min-width:80px;width:110px;">
                            <col style="min-width:160px;">
                            <col style="min-width:80px;width:110px;">
                            <col>
                            <col style="min-width:100px;width:130px;">
                        </colgroup>
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha / Hora</th>
                                <th>Rol</th>
                                <th>Usuario</th>
                                <th>Accion</th>
                                <th>Detalle</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody id="bitacoraBody">
                            <?php foreach ($registros as $r): ?>
                                <tr>
                                    <td style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:10px 12px 10px 8px;"><?php echo date('d/m/Y H:i', strtotime($r['fecha_hora'])); ?></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><span class="badge bg-secondary"><?php echo htmlspecialchars($r['rol'] ?? '-'); ?></span></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 18px 10px 6px;" title="<?php echo htmlspecialchars($r['correo'] ?? 'Sistema'); ?>"><?php echo htmlspecialchars($r['correo'] ?? 'Sistema'); ?></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['accion']); ?></span></td>
                                    <td style="white-space:normal;word-break:break-word;padding:10px 6px;"><?php echo htmlspecialchars($r['detalle'] ?? '-'); ?></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:10px 6px;"><code><?php echo htmlspecialchars($r['direccion_ip']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="bitacoraCounter" style="height:1px;"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalPDF');
    modal.addEventListener('show.bs.modal', function(event) {
        var btn = event.relatedTarget;
        var url = btn.getAttribute('data-url');
        var title = btn.getAttribute('data-title');
        document.getElementById('modalPDFTitle').innerHTML = '<i class="fas fa-file-pdf text-danger me-2"></i> ' + title;
        document.getElementById('modalPDFIframe').src = url;
        document.getElementById('modalPDFDownload').href = url.replace('&preview=1', '');
        document.getElementById('modalPDFDirect').href = url.replace('&preview=1', '');
    });
    modal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalPDFIframe').src = '';
    });

    setInterval(function() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?action=bitacora_live&_=' + Date.now(), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var body = document.getElementById('bitacoraBody');
                    if (body) body.innerHTML = JSON.parse(xhr.responseText).html;
                } catch(e) {}
            }
        };
        xhr.send();
    }, 5000);
});
</script>

