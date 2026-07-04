<?php
// PERIODOS ACADEMICOS
if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
    header('Location: index.php?page=login');
    exit;
}

$hoy = date('Y-m-d');

$periodoVencido = $conexion->query("SELECT id, nombre FROM periodo_academico WHERE estado = 'Activo' AND fecha_fin IS NOT NULL AND fecha_fin < CURDATE() LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$stmt = $conexion->query("SELECT * FROM periodo_academico WHERE estado = 'Activo' LIMIT 1");
$periodoActivo = $stmt->fetch(PDO::FETCH_ASSOC);

$periodos = $conexion->query("
    SELECT * FROM periodo_academico
    ORDER BY FIELD(estado, 'Activo', 'Planificado', 'Finalizado'), fecha_inicio DESC
")->fetchAll(PDO::FETCH_ASSOC);

$stats = [];
$detalles = [];
if (!empty($periodos)) {
    $ids = array_column($periodos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtStats = $conexion->prepare("
        SELECT id_periodo,
            COUNT(*) as total_inscripciones,
            SUM(estado = 'Pendiente') as pendientes,
            SUM(ciclo_cerrado = 1) as ciclos_cerrados,
            SUM(valido_coordinador = 1) as ciclos_validados
        FROM solicitud_inscripcion
        WHERE id_periodo IN ($placeholders)
        GROUP BY id_periodo
    ");
    $stmtStats->execute($ids);
    foreach ($stmtStats->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $stats[$s['id_periodo']] = $s;
    }

    $stmtDet = $conexion->prepare("
        SELECT si.id_periodo,
            CONCAT(p.nombre, ' ', p.apellido) AS docente,
            m.nombre_materia, m.semestre, s.id AS seccion_id,
            COUNT(si.id) AS total_estudiantes,
            SUM(si.ciclo_cerrado = 1) AS cerrados,
            SUM(si.valido_coordinador = 1) AS validados
        FROM solicitud_inscripcion si
        JOIN seccion s ON si.id_seccion = s.id
        JOIN materia m ON s.id_materia = m.id
        JOIN usuario u ON s.id_docente = u.id
        JOIN perfil p ON u.id_perfil = p.id
        WHERE si.id_periodo IN ($placeholders)
        GROUP BY si.id_periodo, s.id, m.id
        ORDER BY m.semestre, m.nombre_materia
    ");
    $stmtDet->execute($ids);
    foreach ($stmtDet->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $detalles[$d['id_periodo']][] = $d;
    }
}
?>
<div class="container py-5 mt-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold mb-1" style="color:var(--coord-primary);"><i class="fas fa-calendar-alt me-3"></i>Periodos academicos</h1>
            <p class="text-muted mb-0">Gestione los periodos academicos, reviselos y decida cuando iniciarlos o finalizarlos.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="index.php?page=cronograma" class="btn btn-sm rounded-pill px-3 fw-bold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
                <i class="fas fa-calendar-alt me-2"></i>Cronograma
            </a>
            <a href="index.php?page=approve_registration" class="btn btn-sm rounded-pill px-3 fw-bold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if ($periodoVencido): ?>
    <div class="alert alert-warning alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0" role="alert" style="background:#fffbeb;color:#92400e;border-left:5px solid #f59e0b!important;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Periodo vencido:</strong> «<?= htmlspecialchars($periodoVencido['nombre']) ?>» tiene fecha de fin anterior a hoy. Finalicelo o actualice la fecha.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($periodoActivo): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid <?= $periodoVencido ? '#f59e0b' : '#22c55e' ?> !important;">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#dcfce7;">
                    <i class="fas fa-play-circle fa-xl" style="color:#16a34a;"></i>
                </div>
                <div>
                    <span class="badge bg-success rounded-pill px-3 py-2 mb-1">Periodo Activo</span>
                    <?php if ($periodoVencido): ?>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 mb-1"><i class="fas fa-clock me-1"></i>Vencido</span>
                    <?php endif; ?>
                    <h4 class="fw-bold mb-0"><?= htmlspecialchars($periodoActivo['nombre']) ?></h4>
                    <small class="text-muted">
                        Inicio: <?= date('d/m/Y', strtotime($periodoActivo['fecha_inicio'])) ?>
                        <?php if ($periodoActivo['fecha_fin']): ?> · Fin: <?= date('d/m/Y', strtotime($periodoActivo['fecha_fin'])) ?><?php endif; ?>
                    </small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?action=finalize_periodo&id=<?= $periodoActivo['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                class="btn btn-outline-warning rounded-pill px-4 fw-bold"
                onclick="event.preventDefault(); (async ()=>{ if(await confirmar('¿Finalizar el periodo <?= htmlspecialchars($periodoActivo['nombre']) ?>? Se cerraran todas las inscripciones activas.')) window.location.href=this.href; })()">
                    <i class="fas fa-flag-checkered me-2"></i>Finalizar Periodo
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid #f59e0b !important;">
        <div class="card-body p-4 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#fef3c7;">
                <i class="fas fa-pause-circle fa-xl" style="color:#d97706;"></i>
            </div>
            <div>
                <span class="badge bg-warning rounded-pill px-3 py-2 mb-1">Sin Periodo Activo</span>
                <p class="mb-0 text-muted">No hay ningun periodo academico activo. Cree uno nuevo o inicie un periodo planificado.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header border-0 p-4" style="background:var(--coord-primary);color:#fff;">
            <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Periodo</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="index.php?action=add_periodo" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-muted">Nombre del Periodo</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: 2026-I" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Fecha de Fin (opcional)</label>
                    <input type="date" name="fecha_fin" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100 rounded-pill fw-bold shadow-sm" style="background:var(--coord-primary);color:#fff;">
                        <i class="fas fa-save me-2"></i>Crear
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-muted"></i>Todos los Periodos</h5>
            <span class="badge bg-dark rounded-pill px-3"><?= count($periodos) ?> registrados</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small fw-bold">
                        <th class="ps-4 py-4">PERIODO</th>
                        <th>INICIO</th>
                        <th>FIN</th>
                        <th>ESTADO</th>
                        <th class="text-center">INSCRIPCIONES</th>
                        <th class="text-center">CICLOS</th>
                        <th class="text-end pe-4">ACCION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3 opacity-25"></i>
                                <p class="text-muted fw-bold">No hay periodos academicos registrados. Cree uno nuevo arriba.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periodos as $p):
                            $s = $stats[$p['id']] ?? null;
                            $total_insc = $s ? (int)$s['total_inscripciones'] : 0;
                            $pendientes = $s ? (int)$s['pendientes'] : 0;
                            $cerrados = $s ? (int)$s['ciclos_cerrados'] : 0;
                            $validados = $s ? (int)$s['ciclos_validados'] : 0;
                        ?>
                            <tr class="<?= $p['estado'] === 'Activo' ? 'table-success' : '' ?>">
                                <td class="ps-4 py-4">
                                    <div class="fw-bold h6 mb-0"><?= htmlspecialchars($p['nombre']) ?></div>
                                    <small class="text-muted">Creado: <?= date('d/m/Y', strtotime($p['created_at'])) ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></td>
                                <td><?= $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'])) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php if ($p['estado'] === 'Activo'): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-play me-1"></i>Activo</span>
                                        <?php if ($p['fecha_fin'] && $p['fecha_fin'] < $hoy): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 ms-1"><i class="fas fa-clock me-1"></i>Vencido</span>
                                        <?php endif; ?>
                                    <?php elseif ($p['estado'] === 'Planificado'): ?>
                                        <span class="badge bg-info text-dark rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i>Planificado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-flag-checkered me-1"></i>Finalizado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold"><?= $total_insc ?></span>
                                    <?php if ($pendientes > 0): ?>
                                        <span class="badge bg-warning ms-1"><?= $pendientes ?> pend.</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php $det = $detalles[$p['id']] ?? []; ?>
                                    <?php if (empty($det)): ?>
                                        <span class="text-muted small">—</span>
                                    <?php else:
                                        $total_cerrados = array_sum(array_column($det, 'cerrados'));
                                        $total_validados = array_sum(array_column($det, 'validados'));
                                        $total_est = array_sum(array_column($det, 'total_estudiantes'));
                                        $pct = $total_est > 0 ? round($total_cerrados / $total_est * 100) : 0;
                                        $color = $total_cerrados === $total_est ? 'success' : ($total_cerrados > 0 ? 'warning' : 'danger');
                                    ?>
                                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalCiclos_<?= $p['id'] ?>">
                                            <span class="badge bg-<?= $color ?> rounded-pill px-3 py-2"><?= $pct ?>%</span>
                                        </a>
                                        <small class="d-block text-muted"><?= $total_cerrados ?>/<?= $total_est ?> cerrados</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($p['estado'] === 'Planificado'): ?>
                                        <a href="index.php?action=activate_periodo&id=<?= $p['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3 fw-bold me-1"
                                        onclick="event.preventDefault(); (async ()=>{ if(await confirmar('¿Iniciar el periodo <?= htmlspecialchars($p['nombre']) ?>? Se desactivara cualquier otro periodo activo.')) window.location.href=this.href; })()">
                                            <i class="fas fa-play me-1"></i>Iniciar
                                        </a>
                                        <a href="index.php?action=delete_periodo&id=<?= $p['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold"
                                        onclick="event.preventDefault(); (async ()=>{ if(await confirmar('¿Eliminar el periodo <?= htmlspecialchars($p['nombre']) ?>? Esta accion no se puede deshacer.')) window.location.href=this.href; })()">
                                            <i class="fas fa-trash me-1"></i>
                                        </a>
                                    <?php elseif ($p['estado'] === 'Activo'): ?>
                                        <span class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i>En curso</span>
                                    <?php else: ?>
                                        <span class="text-muted small fw-bold"><i class="fas fa-check me-1"></i>Finalizado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($periodos as $p): $det = $detalles[$p['id']] ?? []; if (empty($det)) continue; ?>
<div class="modal fade" id="modalCiclos_<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4" style="background:var(--coord-primary);color:#fff;">
                <h5 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2"></i>Ciclos — <?= htmlspecialchars($p['nombre']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-muted small fw-bold">
                                <th class="py-3">DOCENTE</th>
                                <th>MATERIA</th>
                                <th class="text-center">SEM.</th>
                                <th class="text-center">ESTUDIANTES</th>
                                <th class="text-center">CERRADOS</th>
                                <th class="text-center">VALIDADOS</th>
                                <th class="text-center">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($det as $d):
                                $completado = $d['cerrados'] == $d['total_estudiantes'];
                                $avance = $d['total_estudiantes'] > 0 ? round($d['cerrados'] / $d['total_estudiantes'] * 100) : 0;
                                $estado_cerrado = $completado ? 'Completado' : ($d['cerrados'] > 0 ? 'En progreso' : 'Sin cerrar');
                                $badge_color = $completado ? 'success' : ($d['cerrados'] > 0 ? 'warning text-dark' : 'danger');
                            ?>
                            <tr>
                                <td><span class="fw-medium small"><?= htmlspecialchars($d['docente']) ?></span></td>
                                <td><span class="fw-bold small"><?= htmlspecialchars($d['nombre_materia']) ?></span></td>
                                <td class="text-center"><?= (int)$d['semestre'] ?></td>
                                <td class="text-center fw-bold"><?= (int)$d['total_estudiantes'] ?></td>
                                <td class="text-center"><span class="badge bg-<?= $completado ? 'success' : 'secondary' ?> rounded-pill px-3"><?= (int)$d['cerrados'] ?></span></td>
                                <td class="text-center"><span class="badge bg-<?= $d['validados'] > 0 ? 'success' : 'secondary' ?> rounded-pill px-3"><?= (int)$d['validados'] ?></span></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badge_color ?> rounded-pill px-3"><?= $estado_cerrado ?> (<?= $avance ?>%)</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>