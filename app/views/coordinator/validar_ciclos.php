<?php
// VALIDACION DE CICLOS
if (!isset($_SESSION['rol_usuario']) || $_SESSION['rol_usuario'] !== 'Coordinador') {
    header('Location: index.php?page=login');
    exit;
}

$ciclos = $conexion->query("
    SELECT si.id, si.id_estudiante, si.nota, si.valido_coordinador,
        p.nombre, p.apellido, p.cedula,
        m.nombre_materia, m.codigo_materia, m.semestre,
        sec.nombre_seccion,
        u.correo
    FROM solicitud_inscripcion si
    JOIN seccion sec ON si.id_seccion = sec.id
    JOIN materia m ON sec.id_materia = m.id
    JOIN usuario u ON si.id_estudiante = u.id
    JOIN perfil p ON u.id_perfil = p.id
    WHERE si.ciclo_cerrado = 1 AND si.valido_coordinador IS NULL
    ORDER BY m.semestre, p.apellido
")->fetchAll(PDO::FETCH_ASSOC);

$agrupados = [];
foreach ($ciclos as $c) {
    $key = $c['id_estudiante'];
    if (!isset($agrupados[$key])) {
        $agrupados[$key] = ['estudiante' => $c, 'materias' => []];
    }
    $agrupados[$key]['materias'][] = $c;
}
?>
<div class="container py-5 mt-5">
    <?php
    $stmtPeriodo = $conexion->query("SELECT nombre FROM periodo_academico WHERE estado = 'Activo' LIMIT 1");
    $periodoActivo = $stmtPeriodo->fetchColumn();
    ?>
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold mb-1" style="color:var(--coord-primary);"><i class="fas fa-clipboard-check me-3"></i>Validación de Ciclos</h1>
            <p class="text-muted mb-0">Revise los ciclos cerrados por los docentes y valide el avance académico.</p>
            <?php if ($periodoActivo): ?>
                <span class="badge rounded-pill px-3 py-2 mt-2" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;">
                    <i class="fas fa-calendar-check me-1"></i>Periodo activo: <strong><?= htmlspecialchars($periodoActivo) ?></strong>
                </span>
            <?php endif; ?>
        </div>
        <div class="col-auto">
            <a href="index.php?page=approve_registration" class="btn btn-sm rounded-pill px-3 fw-bold" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if (empty($agrupados)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3 opacity-50"></i>
            <p class="text-muted fw-bold">No hay ciclos pendientes de validación.</p>
        </div>
    <?php else: ?>
        <?php foreach ($agrupados as $id_est => $grupo): 
            $est = $grupo['estudiante'];
            $materias = $grupo['materias'];
            $todas_ok = !empty(array_filter($materias, function($m) { return $m['nota'] >= 10; }));
            $fallo = !empty(array_filter($materias, function($m) { return $m['nota'] < 10 && $m['nota'] > 0; }));
        ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header border-0 p-4 d-flex justify-content-between align-items-center" style="background:var(--coord-primary);color:#fff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width:42px;height:42px;background:rgba(255,255,255,0.2);font-size:.85rem;">
                        <?= mb_strtoupper(mb_substr($est['nombre'],0,1) . mb_substr($est['apellido'],0,1), 'UTF-8') ?>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?></h5>
                        <small class="opacity-75"><?= htmlspecialchars($est['correo']) ?> · <?= htmlspecialchars($est['cedula']) ?></small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-muted small">
                                <th class="ps-4">Materia</th>
                                <th>Código</th>
                                <th>Semestre</th>
                                <th>Nota</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materias as $m): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                <td><?= htmlspecialchars($m['codigo_materia']) ?></td>
                                <td><?= $m['semestre'] !== null ? $m['semestre'] : 'Electiva' ?></td>
                                <td><span class="badge bg-dark rounded-pill px-3"><?= $m['nota'] ?: '--' ?>/20</span></td>
                                <td>
                                    <?php if ($m['nota'] >= 10): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">Aprobado</span>
                                    <?php elseif ($m['nota'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3">Reprobado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Sin nota</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 p-4 d-flex justify-content-end gap-2">
                <a href="index.php?action=validar_ciclo&id=<?= $id_est ?>&resultado=rechazar&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                class="btn btn-outline-danger rounded-pill px-4 fw-bold"
                onclick="event.preventDefault(); (async ()=>{ if(await confirmar('¿Rechazar el ciclo de <?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?>? El estudiante será marcado como Rechazado y deberá repetir.')) window.location.href=this.href; })()">
                    <i class="fas fa-times me-2"></i>Rechazar Ciclo
                </a>
                <a href="index.php?action=validar_ciclo&id=<?= $id_est ?>&resultado=validar&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"
                onclick="event.preventDefault(); (async ()=>{ if(await confirmar('¿Validar el ciclo de <?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?>? El estudiante avanzará al siguiente semestre.')) window.location.href=this.href; })()">
                    <i class="fas fa-check me-2"></i>Validar Ciclo
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
